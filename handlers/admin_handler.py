def init_admin_routes(app):
    @app.route('/admin/dashboard')
    def admin_dashboard():
        from flask import session, redirect
        if 'user_id' not in session:
            return redirect('/login')
        role = session.get('user_role', '')
        if role in ('admin', 'super_admin'):
            return redirect('/career/admin/dashboard')
        return redirect('/login')
