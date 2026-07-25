from flask import render_template, session, redirect, request


def init_admin_portal_routes(app):

    @app.route('/admin/portal/dashboard')
    def admin_portal_dashboard():
        if 'user_id' not in session:
            return redirect('/client-portal')
        role = session.get('user_role', '')
        if role not in ('admin', 'super_admin'):
            return redirect('/client/dashboard')
        from db import get_db
        db = get_db()
        active_tab = request.args.get('tab', 'operations')
        try:
            clients = db.execute("""
                SELECT c.*, u.username, u.email FROM clients c
                JOIN users u ON c.user_id = u.id
            """).fetchall()
            packages = db.execute("SELECT * FROM packages WHERE status = 'active' ORDER BY price ASC").fetchall()
            deliverables = db.execute("""
                SELECT d.*, c.name as client_name FROM deliverables d
                JOIN clients c ON d.client_id = c.id ORDER BY d.due_date ASC
            """).fetchall()
            tickets = db.execute("""
                SELECT t.*, c.name as client_name FROM support_tickets t
                JOIN clients c ON t.client_id = c.id ORDER BY t.created_at DESC
            """).fetchall()
            custom_tasks = db.execute("""
                SELECT ct.*, c.name as client_name FROM custom_tasks ct
                JOIN clients c ON ct.client_id = c.id ORDER BY ct.created_at DESC
            """).fetchall()
            verbal_tasks = db.execute("SELECT * FROM verbal_tasks ORDER BY created_at DESC").fetchall()
            invoices = db.execute("""
                SELECT i.*, c.name as client_name FROM invoices i
                JOIN clients c ON i.client_id = c.id ORDER BY i.created_at DESC
            """).fetchall()
        finally:
            db.close()
        return render_template('admin_portal/dashboard.html',
            username=session.get('username', 'Admin'),
            user_role=role,
            clients=clients,
            packages=packages,
            deliverables=deliverables,
            tickets=tickets,
            custom_tasks=custom_tasks,
            verbal_tasks=verbal_tasks,
            invoices=invoices,
            active_tab=active_tab,
            portal_type='admin')
