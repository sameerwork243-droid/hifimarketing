def init_career_portal_routes(app):
    @app.route('/career-portal')
    def career_portal():
        from flask import session, redirect
        if 'user_id' not in session:
            return redirect('/login')
        return 'Career Portal'
