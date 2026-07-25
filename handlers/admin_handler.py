def init_admin_routes(app):
    @app.route('/admin/dashboard')
    def admin_dashboard():
        from flask import session, redirect
        if 'user_id' not in session or session.get('user_role') not in ('admin', 'super_admin'):
            return redirect('/login')
        return 'Admin Dashboard'
