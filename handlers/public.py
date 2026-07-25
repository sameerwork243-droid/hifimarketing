from flask import Blueprint, render_template, request, redirect, url_for, session, flash

public_bp = Blueprint('public', __name__)

JOBS_DATA = {
    'web-developer': {
        'title': 'Web Developer', 'type': 'Full-Time', 'location': 'Lahore, Pakistan',
        'description': 'We are looking for a talented Web Developer to join our growing team. You will be responsible for building and maintaining client websites and web applications.',
        'requirements': ['Proven experience as a Web Developer', 'Strong HTML, CSS, and JavaScript skills', 'Experience with PHP or Python', 'Database management experience', 'Excellent problem-solving skills']
    },
    'seo-specialist': {
        'title': 'SEO Specialist', 'type': 'Full-Time', 'location': 'Lahore, Pakistan',
        'description': 'We are seeking an experienced SEO Specialist to drive our clients organic growth and improve search rankings.',
        'requirements': ['Expertise in SEO tools (Ahrefs, SEMrush, Moz)', 'Google Analytics and Search Console experience', 'Content strategy skills', 'Link building experience', 'Strong communication skills']
    },
    'graphic-designer': {
        'title': 'Graphic Designer', 'type': 'Part-Time', 'location': 'Remote',
        'description': 'We are looking for a creative Graphic Designer to produce stunning visual content for our clients across various platforms.',
        'requirements': ['Adobe Creative Suite proficiency', 'Social media design experience', 'Brand identity understanding', 'Strong portfolio', 'Time management skills']
    },
    'content-writer': {
        'title': 'Content Writer', 'type': 'Contract', 'location': 'Remote',
        'description': 'We are looking for a skilled Content Writer to create engaging content for websites, blogs, and social media.',
        'requirements': ['Excellent writing and grammar skills', 'SEO content knowledge', 'Research abilities', 'Marketing understanding', 'Editing and proofreading']
    },
    'social-media-manager': {
        'title': 'Social Media Manager', 'type': 'Full-Time', 'location': 'Lahore, Pakistan',
        'description': 'We are looking for a dynamic Social Media Manager to manage client accounts and drive engagement across platforms.',
        'requirements': ['Social media platform expertise', 'Content creation skills', 'Analytics and reporting', 'Community management', 'Campaign management']
    }
}


@public_bp.route('/')
def home():
    return render_template('index.html')


@public_bp.route('/services')
def services():
    return render_template('services.html')


@public_bp.route('/about')
def about():
    return render_template('about.html')


@public_bp.route('/team')
def team():
    return render_template('team.html')


@public_bp.route('/pricing')
def pricing():
    return render_template('pricing.html')


@public_bp.route('/contact', methods=['GET', 'POST'])
def contact():
    if request.method == 'POST':
        try:
            from db import get_db
            db = get_db()
            db.execute(
                'INSERT INTO contact_messages (name, email, phone, message) VALUES (?, ?, ?, ?)',
                (request.form.get('name'), request.form.get('email'),
                 request.form.get('phone'), request.form.get('message'))
            )
            db.commit()
            flash('Thank you! We will get back to you soon.', 'success')
        except Exception:
            flash('Something went wrong. Please try again.', 'error')
        return redirect(url_for('public.contact'))
    return render_template('contact.html')


@public_bp.route('/jobs')
def careers():
    job_id = request.args.get('job', '')
    if job_id in JOBS_DATA:
        job = JOBS_DATA[job_id]
        return render_template('job-detail.html', job=job, job_id=job_id)
    return render_template('careers.html', jobs=JOBS_DATA)


@public_bp.route('/apply', methods=['GET', 'POST'])
def job_apply():
    if request.method == 'GET':
        job_param = request.args.get('job', '')
        if job_param:
            return redirect(url_for('public.careers', job=job_param))
        return redirect(url_for('public.careers'))

    job_param = request.args.get('job', '')
    name = request.form.get('name', '').strip()
    email = request.form.get('email', '').strip()
    phone = request.form.get('phone', '').strip()
    cover = request.form.get('cover', '').strip()
    job_id = request.form.get('job', job_param)
    if not all([name, email, phone, cover]):
        flash('All fields are required.', 'error')
    else:
        resume_path = ''
        if 'resume' in request.files:
            f = request.files['resume']
            if f.filename:
                import uuid, os
                ext = f.filename.rsplit('.', 1)[-1] if '.' in f.filename else ''
                fname = f'resume_{uuid.uuid4().hex}.{ext}' if ext else f'resume_{uuid.uuid4().hex}.pdf'
                from flask import current_app
                folder = os.path.join(current_app.config.get('UPLOAD_FOLDER', 'uploads'), 'resumes')
                os.makedirs(folder, exist_ok=True)
                f.save(os.path.join(folder, fname))
                resume_path = os.path.join('uploads', 'resumes', fname)
        flash('Application submitted successfully! We will review your application and get back to you.', 'success')
    return redirect(url_for('public.careers', job=job_id))


@public_bp.route('/login', methods=['GET', 'POST'])
def login():
    if request.method == 'POST':
        from db import get_db
        email = request.form.get('email', '')
        password = request.form.get('password', '')
        db = get_db()
        try:
            user = db.execute(
                'SELECT id, username, email, password, role FROM users WHERE email = ? AND password = ?',
                (email, password)
            ).fetchone()
            if user:
                session['user_id'] = user['id']
                session['username'] = user['username']
                session['user_role'] = user['role']
                session.permanent = True
                nxt = session.pop('redirect_after_login', '/dashboard')
                return redirect(nxt)
            flash('Invalid credentials')
        finally:
            db.close()
        return redirect('/login')
    return render_template('login.html')


@public_bp.route('/logout')
def logout():
    session.clear()
    return redirect('/')


@public_bp.route('/register')
def register():
    return redirect('/login')


@public_bp.route('/resetpass')
def resetpass():
    return redirect('/login')


@public_bp.route('/client-portal')
def client_portal():
    if 'user_id' not in session:
        return redirect(url_for('public.login'))
    return redirect('/dashboard')


@public_bp.route('/admin-portal')
def admin_portal():
    if 'user_id' not in session:
        return redirect(url_for('public.login'))
    return redirect('/admin/dashboard')


def init_routes(app):
    app.register_blueprint(public_bp)
