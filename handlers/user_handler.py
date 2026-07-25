def init_user_routes(app):
    @app.route('/dashboard')
    def dashboard():
        from flask import session, redirect
        if 'user_id' not in session:
            return redirect('/login')
        role = session.get('user_role', '')
        if role == 'admin':
            return redirect('/admin/dashboard')
        elif role == 'project_manager':
            return redirect('/pm/dashboard')
        elif 'client' in role:
            return redirect('/client-portal/dashboard')
        return 'User Dashboard'
