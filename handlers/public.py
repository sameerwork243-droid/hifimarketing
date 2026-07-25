import logging
from flask import Blueprint, render_template, request, redirect, url_for, session, flash

logger = logging.getLogger(__name__)

public_bp = Blueprint('public', __name__)

JOBS_DATA = {
    'web-developer': {
        'title': 'Web Developer', 'type': 'Full-Time', 'location': 'Lahore, Pakistan', 'department': 'Development',
        'description': 'We are looking for a talented Web Developer to join our growing team. You will be responsible for building and maintaining client websites and web applications.',
        'requirements': ['Proven experience as a Web Developer', 'Strong HTML, CSS, and JavaScript skills', 'Experience with PHP or Python', 'Database management experience', 'Excellent problem-solving skills']
    },
    'seo-specialist': {
        'title': 'SEO Specialist', 'type': 'Full-Time', 'location': 'Lahore, Pakistan', 'department': 'Marketing',
        'description': 'We are seeking an experienced SEO Specialist to drive our clients organic growth and improve search rankings.',
        'requirements': ['Expertise in SEO tools (Ahrefs, SEMrush, Moz)', 'Google Analytics and Search Console experience', 'Content strategy skills', 'Link building experience', 'Strong communication skills']
    },
    'graphic-designer': {
        'title': 'Graphic Designer', 'type': 'Part-Time', 'location': 'Remote', 'department': 'Design',
        'description': 'We are looking for a creative Graphic Designer to produce stunning visual content for our clients across various platforms.',
        'requirements': ['Adobe Creative Suite proficiency', 'Social media design experience', 'Brand identity understanding', 'Strong portfolio', 'Time management skills']
    },
    'content-writer': {
        'title': 'Content Writer', 'type': 'Contract', 'location': 'Remote', 'department': 'Content',
        'description': 'We are looking for a skilled Content Writer to create engaging content for websites, blogs, and social media.',
        'requirements': ['Excellent writing and grammar skills', 'SEO content knowledge', 'Research abilities', 'Marketing understanding', 'Editing and proofreading']
    },
    'social-media-manager': {
        'title': 'Social Media Manager', 'type': 'Full-Time', 'location': 'Lahore, Pakistan', 'department': 'Marketing',
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
            name = request.form.get('name', '')
            email = request.form.get('email', '')
            subject = request.form.get('subject', '')
            message = request.form.get('message', '')
            user_id = session.get('user_id', 0)
            db.execute(
                'INSERT INTO contact_messages (user_id, name, email, subject, message, status) VALUES (?, ?, ?, ?, ?, ?)',
                (user_id, name, email, subject, message, 'unread')
            )
            db.commit()
            flash('Thank you for your message! We will get back to you soon.', 'success')
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
    if 'user_id' in session:
        role = session.get('user_role', '')
        if role in ('admin', 'super_admin'):
            return redirect('/career/admin/dashboard')
        return redirect('/career/user/dashboard')

    redirect_url = request.args.get('redirect', '')
    if request.method == 'POST':
        from db import get_db
        email = request.form.get('email', '')
        password = request.form.get('password', '')
        logger.info('Login attempt: email=%s', email)
        db = get_db()
        try:
            from helpers import sanitize, verify_password
            safe_email = sanitize(email)
            user = db.execute(
                'SELECT id, username, email, password, role FROM users WHERE email = ? OR username = ?',
                (safe_email, safe_email)
            ).fetchone()
            if user:
                logger.info('User found: id=%s email=%s role=%s', user['id'], user['email'], user['role'])
                pw_ok = verify_password(password, user['password'])
                logger.info('Password verify result: %s', pw_ok)
                if pw_ok:
                    role = user.get('role', '')
                    logger.info('Role check: role=%s allowed=%s', role, role in ('admin', 'user', 'super_admin'))
                    if role not in ('admin', 'user', 'super_admin'):
                        flash('This login is for Career accounts only. Use Client Portal for other accounts.', 'error')
                        return redirect(url_for('public.login'))
                    session['user_id'] = user['id']
                    session['username'] = user['username']
                    session['user_role'] = role
                    session.permanent = True
                    nxt = redirect_url if redirect_url else (
                        '/career/admin/dashboard' if role in ('admin', 'super_admin') else '/career/user/dashboard'
                    )
                    logger.info('Login success: redirect=%s', nxt)
                    return redirect(nxt)
            else:
                logger.info('User not found for email/username: %s', safe_email)
            flash('Invalid email or password.', 'error')
        finally:
            db.close()
        return redirect(url_for('public.login', redirect=redirect_url) if redirect_url else url_for('public.login'))
    return render_template('login.html', redirect=redirect_url)


@public_bp.route('/register', methods=['GET', 'POST'])
def register():
    if 'user_id' in session:
        return redirect('/career/user/dashboard')

    if request.method == 'POST':
        from db import get_db
        username = request.form.get('username', '').strip()
        email = request.form.get('email', '').strip()
        password = request.form.get('password', '')
        confirm = request.form.get('confirm_password', '')

        if not all([username, email, password, confirm]):
            flash('Please fill in all fields.', 'error')
        elif len(username) < 3:
            flash('Username must be at least 3 characters.', 'error')
        elif len(password) < 6:
            flash('Password must be at least 6 characters.', 'error')
        elif password != confirm:
            flash('Passwords do not match.', 'error')
        else:
            db = get_db()
            try:
                existing = db.execute(
                    'SELECT id FROM users WHERE email = ? OR username = ?',
                    (email, username)
                ).fetchone()
                if existing:
                    flash('Email or username already exists.', 'error')
                else:
                    from helpers import hash_password
                    hashed = hash_password(password)
                    db.execute(
                        'INSERT INTO users (username, email, password, role) VALUES (?, ?, ?, ?)',
                        (username, email, hashed, 'user')
                    )
                    db.commit()
                    user = db.execute(
                        'SELECT id, username, role FROM users WHERE email = ?',
                        (email,)
                    ).fetchone()
                    if user:
                        session['user_id'] = user['id']
                        session['username'] = user['username']
                        session['user_role'] = 'user'
                        session.permanent = True
                        flash('Account created successfully! Welcome.', 'success')
                        return redirect('/career/user/dashboard')
            finally:
                db.close()
        return redirect(url_for('public.register'))
    return render_template('register.html')


@public_bp.route('/resetpass')
def resetpass():
    return render_template('login.html')


@public_bp.route('/logout')
def logout():
    session.clear()
    return redirect('/')


@public_bp.route('/client-portal', methods=['GET', 'POST'])
def client_portal():
    redirect_url = request.args.get('redirect', '')
    if 'user_id' in session and request.method == 'GET':
        role = session.get('user_role', '')
        if role in ('super_admin', 'admin'):
            return redirect('/admin/portal/dashboard')
        elif role == 'pm':
            return redirect('/pm/dashboard')
        return redirect('/client/dashboard')

    if request.method == 'POST':
        from db import get_db
        email = request.form.get('email', '')
        password = request.form.get('password', '')
        logger.info('Client portal login attempt: email=%s', email)
        db = get_db()
        try:
            from helpers import sanitize, verify_password
            safe_email = sanitize(email)
            user = db.execute(
                'SELECT id, username, email, password, role FROM users WHERE email = ?',
                (safe_email,)
            ).fetchone()
            if user:
                logger.info('User found: id=%s email=%s role=%s', user['id'], user['email'], user['role'])
                pw_ok = verify_password(password, user['password'])
                logger.info('Password verify result: %s', pw_ok)
                if pw_ok:
                    role = user.get('role', 'client')
                    logger.info('Role check: role=%s allowed=%s', role, role in ('super_admin', 'admin', 'pm', 'client'))
                    if role not in ('super_admin', 'admin', 'pm', 'client'):
                        flash('This login is for Client, PM, and Admin accounts only. Use Career Login for career accounts.', 'error')
                        return redirect(url_for('public.client_portal'))
                    session['user_id'] = user['id']
                    session['username'] = user['username']
                    session['user_role'] = role
                    session.permanent = True
                    route_map = {
                        'super_admin': '/admin/portal/dashboard',
                        'admin': '/admin/portal/dashboard',
                        'pm': '/pm/dashboard',
                        'client': '/client/dashboard',
                    }
                    nxt = redirect_url if redirect_url else route_map.get(role, '/client/dashboard')
                    logger.info('Login success: role=%s redirect=%s', role, nxt)
                    return redirect(nxt)
            else:
                logger.info('User not found for email: %s', safe_email)
            flash('Invalid email or password.', 'error')
        finally:
            db.close()
        return redirect(url_for('public.client_portal'))
    return render_template('client_portal_login.html', redirect=redirect_url)


@public_bp.route('/admin-portal')
def admin_portal():
    if 'user_id' not in session:
        return redirect(url_for('public.client_portal'))
    return redirect('/admin/portal/dashboard')


def init_routes(app):
    app.register_blueprint(public_bp)
