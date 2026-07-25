def init_user_routes(app):
    @app.route('/dashboard')
    def dashboard():
        from flask import session, redirect
        if 'user_id' not in session:
            return redirect('/login')
        role = session.get('user_role', '')
        if role in ('admin', 'super_admin'):
            return redirect('/career/admin/dashboard')
        return redirect('/career/user/dashboard')
