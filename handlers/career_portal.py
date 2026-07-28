from flask import render_template, session, redirect, request, flash, send_file
import os
from datetime import datetime


def _admin_required():
    return 'user_id' not in session or session.get('user_role') not in ('admin', 'super_admin')


def _get_common_context(db=None):
    if db is None:
        from db import get_db
        db = get_db()
        own_db = True
    else:
        own_db = False
    try:
        total_jobs = db.execute('SELECT COUNT(*) as c FROM jobs').fetchone()['c']
        pending = db.execute("SELECT COUNT(*) as c FROM applications WHERE status = 'pending'").fetchone()['c']
        unread = db.execute("SELECT COUNT(*) as c FROM messages WHERE status = 'unread'").fetchone()['c']
        notif_count = db.execute(
            'SELECT COUNT(*) as c FROM notifications WHERE user_id = ? AND is_read = 0',
            (session['user_id'],)
        ).fetchone()['c']
        notifs = db.execute(
            'SELECT * FROM notifications WHERE user_id = ? ORDER BY created_at DESC',
            (session['user_id'],)
        ).fetchall()[:5]
    finally:
        if own_db:
            db.close()
    return {
        'total_jobs': total_jobs,
        'pending_applications': pending,
        'unread_messages': unread,
        'notification_count': notif_count,
        'notifications': notifs or [],
    }


def init_career_portal_routes(app):

    @app.route('/career/admin/dashboard')
    def career_admin_dashboard():
        if _admin_required():
            return redirect('/login')
        from db import get_db
        db = get_db()
        ctx = _get_common_context(db)
        try:
            total_apps = db.execute('SELECT COUNT(*) as c FROM applications').fetchone()['c']
            status_rows = db.execute('SELECT status, COUNT(*) as count FROM applications GROUP BY status').fetchall()
            status_counts = {row['status']: row['count'] for row in status_rows}
            recent_apps = db.execute("""
                SELECT a.*, j.title as job_title FROM applications a
                LEFT JOIN jobs j ON a.job_id = j.id
                ORDER BY a.applied_at DESC
            """).fetchall()[:5]
            recent_jobs = db.execute("SELECT * FROM jobs WHERE is_active = 1 ORDER BY posted_date DESC").fetchall()[:5]
        finally:
            db.close()
        return render_template('career_admin/dashboard.html',
            username=session.get('username', 'Admin'),
            user_role=session.get('user_role', 'admin'),
            stats={'total_jobs': ctx['total_jobs'], 'total_applications': total_apps,
                   'pending_applications': ctx['pending_applications'], 'unread_messages': ctx['unread_messages']},
            status_counts=status_counts,
            recent_applications=recent_apps,
            recent_jobs=recent_jobs,
            active_page='dashboard',
            **ctx)

    # ─── JOBS ────────────────────────────────────────────────────────────────

    @app.route('/career/admin/jobs', methods=['GET', 'POST'])
    def career_admin_jobs():
        if _admin_required():
            return redirect('/login')
        from db import get_db
        db = get_db()
        ctx = _get_common_context()
        try:
            action = request.args.get('action') or request.form.get('action')

            # ADD / EDIT via POST
            if request.method == 'POST' and action == 'add':
                title = request.form.get('title', '').strip()
                department = request.form.get('department', '').strip()
                location = request.form.get('location', '').strip()
                jtype = request.form.get('type', 'Full-Time').strip()
                workplace = request.form.get('workplace', 'On-site').strip()
                description = request.form.get('description', '').strip()
                responsibilities = request.form.get('responsibilities', '').strip()
                requirements = request.form.get('requirements', '').strip()
                is_active = 1 if request.form.get('is_active') else 0
                edit_id = request.form.get('edit_id', '').strip()

                if not title:
                    flash('Job title is required.', 'error')
                else:
                    if edit_id:
                        db.execute("""
                            UPDATE jobs SET title=?, department=?, location=?, type=?, workplace=?,
                            description=?, responsibilities=?, requirements=?, is_active=?
                            WHERE id=?
                        """, (title, department, location, jtype, workplace, description, responsibilities, requirements, is_active, edit_id))
                        flash('Job updated successfully.', 'success')
                    else:
                        db.execute("""
                            INSERT INTO jobs (title, department, location, type, workplace, description, responsibilities, requirements, is_active, posted_date)
                            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, GETDATE())
                        """, (title, department, location, jtype, workplace, description, responsibilities, requirements, is_active))
                        flash('Job created successfully.', 'success')
                    db.commit()
                return redirect('/career/admin/jobs')

            # DELETE
            if request.method == 'POST' and action == 'delete':
                jid = request.form.get('id', '')
                if jid:
                    db.execute('DELETE FROM jobs WHERE id = ?', (jid,))
                    db.commit()
                    flash('Job deleted.', 'success')
                return redirect('/career/admin/jobs')

            # TOGGLE active
            if request.method == 'POST' and action == 'toggle':
                jid = request.form.get('id', '')
                if jid:
                    job = db.execute('SELECT is_active FROM jobs WHERE id = ?', (jid,)).fetchone()
                    if job:
                        new_val = 0 if job['is_active'] else 1
                        db.execute('UPDATE jobs SET is_active = ? WHERE id = ?', (new_val, jid))
                        db.commit()
                return redirect('/career/admin/jobs')

            # GET: show edit form if action=edit&id=X
            edit_job = None
            if action == 'edit':
                eid = request.args.get('id', '')
                if eid:
                    edit_job = db.execute('SELECT * FROM jobs WHERE id = ?', (eid,)).fetchone()

            # GET: list all jobs
            search = request.args.get('search', '').strip()
            dept_filter = request.args.get('department', '').strip()
            status_filter = request.args.get('status', '').strip()

            sql = 'SELECT * FROM jobs WHERE 1=1'
            params = []
            if search:
                sql += ' AND (title LIKE ? OR department LIKE ? OR location LIKE ?)'
                like = f'%{search}%'
                params.extend([like, like, like])
            if dept_filter:
                sql += ' AND department = ?'
                params.append(dept_filter)
            if status_filter == 'active':
                sql += ' AND is_active = 1'
            elif status_filter == 'inactive':
                sql += ' AND is_active = 0'
            sql += ' ORDER BY posted_date DESC'

            jobs = db.execute(sql, params).fetchall()
            departments = [r['department'] for r in db.execute('SELECT DISTINCT department FROM jobs WHERE department IS NOT NULL').fetchall()]
        finally:
            db.close()
        return render_template('career_admin/jobs.html',
            jobs=jobs,
            edit_job=edit_job,
            departments=departments,
            search=search,
            dept_filter=dept_filter,
            status_filter=status_filter,
            active_page='jobs',
            **ctx)

    # ─── APPLICATIONS ────────────────────────────────────────────────────────

    @app.route('/career/admin/applications')
    def career_admin_applications():
        if _admin_required():
            return redirect('/login')
        from db import get_db
        db = get_db()
        ctx = _get_common_context()
        try:
            search = request.args.get('search', '').strip()
            status_filter = request.args.get('status', '').strip()
            job_filter = request.args.get('job_id', '').strip()

            sql = """
                SELECT a.*, j.title as job_title, j.department
                FROM applications a LEFT JOIN jobs j ON a.job_id = j.id
                WHERE 1=1
            """
            params = []
            if search:
                sql += ' AND (a.first_name LIKE ? OR a.last_name LIKE ? OR a.email LIKE ?)'
                like = f'%{search}%'
                params.extend([like, like, like])
            if status_filter:
                sql += ' AND a.status = ?'
                params.append(status_filter)
            if job_filter:
                sql += ' AND a.job_id = ?'
                params.append(job_filter)
            sql += ' ORDER BY a.applied_at DESC'

            applications = db.execute(sql, params).fetchall() if params else db.execute(sql).fetchall()
            statuses = [r['status'] for r in db.execute('SELECT DISTINCT status FROM applications').fetchall()]
            jobs_list = db.execute('SELECT id, title FROM jobs ORDER BY title').fetchall()
        finally:
            db.close()
        return render_template('career_admin/applications.html',
            applications=applications,
            statuses=statuses,
            jobs_list=jobs_list,
            search=search,
            status_filter=status_filter,
            job_filter=job_filter,
            active_page='applications',
            **ctx)

    @app.route('/career/admin/applications/<int:app_id>')
    def career_admin_application_detail(app_id):
        if _admin_required():
            return redirect('/login')
        from db import get_db
        db = get_db()
        ctx = _get_common_context()
        try:
            app = db.execute("""
                SELECT a.*, j.title as job_title, j.department, j.location, j.type
                FROM applications a LEFT JOIN jobs j ON a.job_id = j.id
                WHERE a.id = ?
            """, (app_id,)).fetchone()
            if not app:
                flash('Application not found.', 'error')
                return redirect('/career/admin/applications')
        finally:
            db.close()
        return render_template('career_admin/application_detail.html',
            app=app,
            active_page='applications',
            **ctx)

    @app.route('/career/admin/applications/<int:app_id>/status', methods=['POST'])
    def career_admin_update_status(app_id):
        if _admin_required():
            return redirect('/login')
        from db import get_db
        db = get_db()
        try:
            new_status = request.form.get('status', '').strip()
            if new_status:
                db.execute('UPDATE applications SET status = ? WHERE id = ?', (new_status, app_id))
                db.commit()
                flash('Application status updated.', 'success')
        finally:
            db.close()
        return redirect(f'/career/admin/applications/{app_id}')

    @app.route('/career/admin/applications/<int:app_id>/resume')
    def career_admin_download_resume(app_id):
        if _admin_required():
            return redirect('/login')
        from flask import current_app
        from db import get_db
        db = get_db()
        try:
            rec = db.execute('SELECT resume, first_name, last_name FROM applications WHERE id = ?', (app_id,)).fetchone()
            if not rec or not rec.get('resume'):
                flash('No resume found.', 'error')
                return redirect(f'/career/admin/applications/{app_id}')
            resume_path = os.path.join(current_app.root_path, rec['resume'])
            if not os.path.exists(resume_path):
                resume_path = os.path.join(os.getcwd(), 'uploads', 'resumes', os.path.basename(rec['resume']))
            if os.path.exists(resume_path):
                return send_file(resume_path, as_attachment=True, download_name=f"{rec['first_name']}_{rec['last_name']}_resume.pdf")
            flash('Resume file not found on disk.', 'error')
        finally:
            db.close()
        return redirect(f'/career/admin/applications/{app_id}')

    # ─── CANDIDATES ──────────────────────────────────────────────────────────

    @app.route('/career/admin/candidates')
    def career_admin_candidates():
        if _admin_required():
            return redirect('/login')
        from db import get_db
        db = get_db()
        ctx = _get_common_context()
        try:
            search = request.args.get('search', '').strip()
            sql = """
                SELECT DISTINCT u.id, u.username, u.email, u.status as user_status, u.created_at,
                    (SELECT COUNT(*) FROM applications WHERE user_id = u.id) as total_apps,
                    (SELECT TOP 1 j.title FROM applications a2 LEFT JOIN jobs j ON a2.job_id = j.id WHERE a2.user_id = u.id ORDER BY a2.applied_at DESC) as last_applied_job
                FROM users u
                INNER JOIN applications a ON a.user_id = u.id
                WHERE 1=1
            """
            params = []
            if search:
                sql += ' AND (u.username LIKE ? OR u.email LIKE ?)'
                like = f'%{search}%'
                params.extend([like, like])
            sql += ' ORDER BY u.created_at DESC'
            candidates = db.execute(sql, params).fetchall() if params else db.execute(sql).fetchall()
        finally:
            db.close()
        return render_template('career_admin/candidates.html',
            candidates=candidates,
            search=search,
            active_page='candidates',
            **ctx)

    @app.route('/career/admin/candidates/<int:candidate_id>')
    def career_admin_candidate_detail(candidate_id):
        if _admin_required():
            return redirect('/login')
        from db import get_db
        db = get_db()
        ctx = _get_common_context()
        try:
            candidate = db.execute("""
                SELECT u.*,
                    (SELECT COUNT(*) FROM applications WHERE user_id = u.id) as total_apps,
                    (SELECT COUNT(*) FROM applications WHERE user_id = u.id AND status = 'pending') as pending_apps,
                    (SELECT COUNT(*) FROM applications WHERE user_id = u.id AND status = 'shortlisted') as shortlisted_apps
                FROM users u WHERE u.id = ?
            """, (candidate_id,)).fetchone()
            if not candidate:
                flash('Candidate not found.', 'error')
                return redirect('/career/admin/candidates')
            apps = db.execute("""
                SELECT a.*, j.title as job_title, j.department, j.location
                FROM applications a LEFT JOIN jobs j ON a.job_id = j.id
                WHERE a.user_id = ? ORDER BY a.applied_at DESC
            """, (candidate_id,)).fetchall()
        finally:
            db.close()
        return render_template('career_admin/candidate_detail.html',
            candidate=candidate,
            applications=apps,
            active_page='candidates',
            **ctx)

    # ─── MESSAGES ────────────────────────────────────────────────────────────

    @app.route('/career/admin/messages')
    def career_admin_messages():
        if _admin_required():
            return redirect('/login')
        from db import get_db
        db = get_db()
        ctx = _get_common_context()
        try:
            tab = request.args.get('tab', 'inbox')
            search = request.args.get('search', '').strip()
            if tab == 'sent':
                sql = "SELECT * FROM messages WHERE user_id = ?"
                params = [session['user_id']]
            else:
                sql = "SELECT * FROM messages WHERE 1=1"
                params = []
            if search:
                sql += ' AND (name LIKE ? OR email LIKE ? OR subject LIKE ?)'
                like = f'%{search}%'
                params.extend([like, like, like])
            sql += ' ORDER BY created_at DESC'
            messages = db.execute(sql, params).fetchall()
            unread_count = db.execute("SELECT COUNT(*) as c FROM messages WHERE status = 'unread'").fetchone()['c']
        finally:
            db.close()
        return render_template('career_admin/messages.html',
            messages=messages,
            tab=tab,
            search=search,
            unread_count=unread_count,
            active_page='messages',
            **ctx)

    @app.route('/career/admin/messages/<int:msg_id>')
    def career_admin_message_detail(msg_id):
        if _admin_required():
            return redirect('/login')
        from db import get_db
        db = get_db()
        ctx = _get_common_context()
        try:
            msg = db.execute('SELECT * FROM messages WHERE id = ?', (msg_id,)).fetchone()
            if not msg:
                flash('Message not found.', 'error')
                return redirect('/career/admin/messages')
            if msg.get('status') == 'unread':
                db.execute("UPDATE messages SET status = 'read' WHERE id = ?", (msg_id,))
                db.commit()
            replies = db.execute('SELECT * FROM messages WHERE id = ? ORDER BY created_at ASC', (msg_id,)).fetchall()
        finally:
            db.close()
        return render_template('career_admin/message_detail.html',
            msg=msg,
            replies=replies,
            active_page='messages',
            **ctx)

    @app.route('/career/admin/messages/<int:msg_id>/reply', methods=['POST'])
    def career_admin_reply_message(msg_id):
        if _admin_required():
            return redirect('/login')
        from db import get_db
        db = get_db()
        try:
            body = request.form.get('message', '').strip()
            if body:
                original = db.execute('SELECT * FROM messages WHERE id = ?', (msg_id,)).fetchone()
                if original:
                    db.execute(
                        "INSERT INTO messages (user_id, name, email, subject, message, status) VALUES (?, ?, ?, ?, ?, 'unread')",
                        (session['user_id'], session.get('username', 'Admin'), original['email'],
                         f"Re: {original['subject']}", body)
                    )
                    db.commit()
                    flash('Reply sent.', 'success')
        finally:
            db.close()
        return redirect(f'/career/admin/messages/{msg_id}')

    @app.route('/career/admin/messages/<int:msg_id>/delete', methods=['POST'])
    def career_admin_delete_message(msg_id):
        if _admin_required():
            return redirect('/login')
        from db import get_db
        db = get_db()
        try:
            db.execute('DELETE FROM messages WHERE id = ?', (msg_id,))
            db.commit()
            flash('Message deleted.', 'success')
        finally:
            db.close()
        return redirect('/career/admin/messages')

    # ─── INTERVIEWS ──────────────────────────────────────────────────────────

    @app.route('/career/admin/interviews', methods=['GET', 'POST'])
    def career_admin_interviews():
        if _admin_required():
            return redirect('/login')
        from db import get_db
        db = get_db()
        ctx = _get_common_context()
        try:
            if request.method == 'POST':
                action = request.form.get('action', '')
                if action == 'schedule':
                    candidate_name = request.form.get('candidate_name', '').strip()
                    candidate_email = request.form.get('candidate_email', '').strip()
                    job_title = request.form.get('job_title', '').strip()
                    interview_date = request.form.get('interview_date', '').strip()
                    interview_time = request.form.get('interview_time', '').strip()
                    interview_type = request.form.get('interview_type', 'video').strip()
                    location_link = request.form.get('location_link', '').strip()
                    notes = request.form.get('notes', '').strip()
                    if candidate_name and candidate_email:
                        db.execute("""
                            INSERT INTO interviews (candidate_name, candidate_email, job_title, interview_date, interview_time, interview_type, location_link, notes, created_by)
                            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
                        """, (candidate_name, candidate_email, job_title, interview_date or None, interview_time, interview_type, location_link, notes, session['user_id']))
                        db.commit()
                        flash('Interview scheduled.', 'success')
                    else:
                        flash('Candidate name and email are required.', 'error')
                return redirect('/career/admin/interviews')

            # GET
            interviews = db.execute('SELECT * FROM interviews ORDER BY interview_date DESC, interview_time ASC').fetchall()
            applications = db.execute("""
                SELECT a.id, a.first_name, a.last_name, a.email, j.title as job_title
                FROM applications a LEFT JOIN jobs j ON a.job_id = j.id
                ORDER BY a.applied_at DESC
            """).fetchall()
        finally:
            db.close()
        return render_template('career_admin/interviews.html',
            interviews=interviews,
            applications=applications,
            active_page='interviews',
            **ctx)

    @app.route('/career/admin/interviews/<int:interview_id>/cancel', methods=['POST'])
    def career_admin_cancel_interview(interview_id):
        if _admin_required():
            return redirect('/login')
        from db import get_db
        db = get_db()
        try:
            db.execute("UPDATE interviews SET status = 'cancelled' WHERE id = ?", (interview_id,))
            db.commit()
            flash('Interview cancelled.', 'success')
        finally:
            db.close()
        return redirect('/career/admin/interviews')

    # ─── REPORTS ─────────────────────────────────────────────────────────────

    @app.route('/career/admin/reports')
    def career_admin_reports():
        if _admin_required():
            return redirect('/login')
        from db import get_db
        db = get_db()
        ctx = _get_common_context()
        try:
            active_jobs = db.execute('SELECT COUNT(*) as c FROM jobs WHERE is_active = 1').fetchone()['c']
            total_apps = db.execute('SELECT COUNT(*) as c FROM applications').fetchone()['c']
            status_counts = db.execute('SELECT status, COUNT(*) as count FROM applications GROUP BY status').fetchall()
            dept_counts = db.execute("""
                SELECT j.department, COUNT(*) as count FROM applications a
                LEFT JOIN jobs j ON a.job_id = j.id
                GROUP BY j.department
            """).fetchall()
            monthly_apps = db.execute("""
                SELECT FORMAT(applied_at, 'yyyy-MM') as month, COUNT(*) as count
                FROM applications GROUP BY FORMAT(applied_at, 'yyyy-MM') ORDER BY month
            """).fetchall()
            total_messages = db.execute('SELECT COUNT(*) as c FROM messages').fetchone()['c']
            total_users = db.execute('SELECT COUNT(*) as c FROM users').fetchone()['c']
            recent_apps = db.execute("""
                SELECT TOP 5 a.*, j.title as job_title FROM applications a
                LEFT JOIN jobs j ON a.job_id = j.id ORDER BY a.applied_at DESC
            """).fetchall()
        finally:
            db.close()
        return render_template('career_admin/reports.html',
            active_jobs=active_jobs,
            total_apps=total_apps, status_counts=status_counts,
            dept_counts=dept_counts, monthly_apps=monthly_apps,
            total_messages=total_messages,
            total_users=total_users, recent_apps=recent_apps,
            active_page='reports',
            **ctx)

    # ─── NOTIFICATIONS ───────────────────────────────────────────────────────

    @app.route('/career/admin/notifications/read')
    def career_admin_mark_notification_read():
        if _admin_required():
            return redirect('/login')
        nid = request.args.get('id', '')
        from db import get_db
        db = get_db()
        try:
            if nid:
                db.execute('UPDATE notifications SET is_read = 1 WHERE id = ? AND user_id = ?', (nid, session['user_id']))
                db.commit()
        finally:
            db.close()
        return redirect(request.referrer or '/career/admin/dashboard')

    @app.route('/career/admin/notifications/read-all')
    def career_admin_mark_all_read():
        if _admin_required():
            return redirect('/login')
        from db import get_db
        db = get_db()
        try:
            db.execute('UPDATE notifications SET is_read = 1 WHERE user_id = ?', (session['user_id'],))
            db.commit()
        finally:
            db.close()
        return redirect(request.referrer or '/career/admin/dashboard')

    # ─── SETTINGS ────────────────────────────────────────────────────────────

    @app.route('/career/admin/settings', methods=['GET', 'POST'])
    def career_admin_settings():
        if _admin_required():
            return redirect('/login')
        from db import get_db
        db = get_db()
        ctx = _get_common_context()
        try:
            if request.method == 'POST':
                section = request.form.get('section', '')
                if section == 'profile':
                    username = request.form.get('username', '').strip()
                    email = request.form.get('email', '').strip()
                    if username:
                        db.execute('UPDATE users SET username = ?, email = ? WHERE id = ?', (username, email, session['user_id']))
                        session['username'] = username
                        flash('Profile updated.', 'success')
                elif section == 'portal':
                    for col in ('notify_email_apps', 'notify_email_msgs', 'notify_email_reports', 'items_per_page', 'default_status'):
                        val = request.form.get(col, '')
                        db.execute(f'UPDATE users SET {col} = ? WHERE id = ?', (val, session['user_id']))
                    flash('Portal settings saved.', 'success')
                elif section == 'password':
                    current = request.form.get('current_password', '')
                    new_pw = request.form.get('new_password', '')
                    confirm = request.form.get('confirm_password', '')
                    if new_pw and new_pw == confirm and len(new_pw) >= 6:
                        from helpers import verify_password, hash_password
                        user = db.execute('SELECT password FROM users WHERE id = ?', (session['user_id'],)).fetchone()
                        if user and verify_password(current, user['password']):
                            db.execute('UPDATE users SET password = ? WHERE id = ?', (hash_password(new_pw), session['user_id']))
                            db.commit()
                            flash('Password changed.', 'success')
                        else:
                            flash('Current password is incorrect.', 'error')
                    else:
                        flash('Invalid new password or passwords do not match.', 'error')
                db.commit()
                return redirect('/career/admin/settings')

            user_data = db.execute('SELECT * FROM users WHERE id = ?', (session['user_id'],)).fetchone()
            portal_settings = db.execute('SELECT * FROM settings WHERE id = 1').fetchone()
        finally:
            db.close()
        return render_template('career_admin/settings.html',
            user_data=user_data,
            portal_settings=portal_settings,
            active_page='settings',
            **ctx)

    # ─── USER DASHBOARD ─────────────────────────────────────────────────────

    @app.route('/career/user/dashboard')
    def career_user_dashboard():
        if 'user_id' not in session:
            return redirect('/login')
        user_id = session.get('user_id')
        from db import get_db
        db = get_db()
        try:
            apps = db.execute(
                'SELECT a.*, j.title as job_title, j.location, j.type, j.department FROM applications a LEFT JOIN jobs j ON a.job_id = j.id WHERE a.user_id = ? ORDER BY a.applied_at DESC',
                (user_id,)
            ).fetchall()
            stats_row = db.execute("""
                SELECT COUNT(*) as total,
                    SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending,
                    SUM(CASE WHEN status = 'reviewed' THEN 1 ELSE 0 END) as reviewed,
                    SUM(CASE WHEN status = 'shortlisted' THEN 1 ELSE 0 END) as shortlisted,
                    SUM(CASE WHEN status = 'rejected' THEN 1 ELSE 0 END) as rejected
                FROM applications WHERE user_id = ?
            """, (user_id,)).fetchone()
            recommended = db.execute("SELECT * FROM jobs WHERE is_active = 1 ORDER BY posted_date DESC").fetchall()[:5]
        finally:
            db.close()
        return render_template('career_user/dashboard.html',
            username=session.get('username', 'User'),
            user_role=session.get('user_role', 'user'),
            stats=stats_row or {},
            applications=apps,
            recommended_jobs=recommended)

    @app.route('/career/user/jobs')
    def career_user_jobs():
        if 'user_id' not in session:
            return redirect('/login')
        user_id = session.get('user_id')
        from flask import request
        from db import get_db
        db = get_db()
        search = request.args.get('search', '').strip()
        dept_filter = request.args.get('department', '').strip()
        try:
            q = "SELECT * FROM jobs WHERE is_active = 1"
            params = []
            if search:
                q += " AND (title LIKE ? OR department LIKE ? OR location LIKE ?)"
                pattern = f'%{search}%'
                params.extend([pattern, pattern, pattern])
            if dept_filter:
                q += " AND department = ?"
                params.append(dept_filter)
            q += " ORDER BY posted_date DESC"
            jobs = db.execute(q, params).fetchall()
            departments = db.execute(
                "SELECT DISTINCT department FROM jobs WHERE is_active = 1 ORDER BY department"
            ).fetchall()
        finally:
            db.close()
        return render_template('career_user/jobs.html',
            username=session.get('username', 'User'),
            user_role=session.get('user_role', 'user'),
            jobs=jobs,
            search=search,
            dept_filter=dept_filter,
            departments=departments)

    @app.route('/career/user/profile')
    def career_user_profile():
        if 'user_id' not in session:
            return redirect('/login')
        user_id = session.get('user_id')
        from db import get_db
        db = get_db()
        try:
            user = db.execute('SELECT * FROM users WHERE id = ?', (user_id,)).fetchone()
            applicant = db.execute('SELECT * FROM applicants WHERE user_id = ?', (user_id,)).fetchone()
        finally:
            db.close()
        return render_template('career_user/profile.html',
            username=session.get('username', 'User'),
            user_role=session.get('user_role', 'user'),
            user=user,
            applicant=applicant or {})

    @app.route('/career/user/settings')
    def career_user_settings():
        if 'user_id' not in session:
            return redirect('/login')
        return render_template('career_user/settings.html',
            username=session.get('username', 'User'),
            user_role=session.get('user_role', 'user'))

    @app.route('/career/job/<int:job_id>')
    def career_user_job_detail(job_id):
        if 'user_id' not in session:
            return redirect('/login')
        user_id = session.get('user_id')
        from db import get_db
        db = get_db()
        try:
            job = db.execute('SELECT * FROM jobs WHERE id = ? AND is_active = 1', (job_id,)).fetchone()
            if not job:
                return redirect('/career/user/jobs')
            has_applied = db.execute(
                'SELECT id FROM applications WHERE user_id = ? AND job_id = ?', (user_id, job_id)
            ).fetchone() is not None
        finally:
            db.close()
        return render_template('career_user/job-detail.html',
            username=session.get('username', 'User'),
            user_role=session.get('user_role', 'user'),
            job=job,
            has_applied=has_applied)
