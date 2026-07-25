def init_employee_routes(app):
    @app.route('/employee/dashboard')
    def employee_dashboard():
        from flask import session, redirect
        if 'user_id' not in session:
            return redirect('/login')
        return 'Employee Dashboard'
