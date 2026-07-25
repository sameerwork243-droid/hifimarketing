from flask import render_template, session, redirect


def init_client_portal_routes(app):

    @app.route('/client/dashboard')
    def client_portal_dashboard():
        if 'user_id' not in session:
            return redirect('/client-portal')
        from db import get_db
        db = get_db()
        user_id = session.get('user_id')
        try:
            user = db.execute('SELECT id, username, email FROM users WHERE id = ?', (user_id,)).fetchone()
            client = db.execute('SELECT * FROM clients WHERE user_id = ?', (user_id,)).fetchone()
            client_id = client['id'] if client else 0
            if not client and user:
                db.execute('INSERT INTO clients (user_id, name) VALUES (?, ?)', (user_id, user['username']))
                db.commit()
                client = db.execute('SELECT * FROM clients WHERE user_id = ?', (user_id,)).fetchone()
                client_id = client['id'] if client else 0
            packages = []
            active_package = None
            if client_id:
                assigned = db.execute('SELECT package_id FROM client_packages WHERE client_id = ?', (client_id,)).fetchall()
                pkg_ids = [r['package_id'] for r in assigned]
                if pkg_ids:
                    placeholders = ','.join('?' * len(pkg_ids))
                    packages = db.execute(
                        f"SELECT * FROM packages WHERE id IN ({placeholders}) AND status = 'active' ORDER BY price ASC",
                        pkg_ids
                    ).fetchall()
                if client.get('active_package_id'):
                    active_pkg_id = client['active_package_id']
                    active_pkg = db.execute('SELECT * FROM packages WHERE id = ?', (active_pkg_id,)).fetchone()
                    active_package = active_pkg or (packages[0] if packages else None)
                if not packages:
                    packages = db.execute("SELECT * FROM packages WHERE status = 'active' ORDER BY price ASC").fetchall()
            invoices = db.execute('SELECT * FROM invoices WHERE client_id = ? ORDER BY created_at DESC', (client_id,)).fetchall() if client_id else []
            tickets = db.execute('SELECT * FROM support_tickets WHERE client_id = ? ORDER BY created_at DESC', (client_id,)).fetchall() if client_id else []
        finally:
            db.close()
        return render_template('client_portal/dashboard.html',
            username=session.get('username', 'Client'),
            user_role=session.get('user_role', 'client'),
            packages=packages,
            active_package=active_package,
            invoices=invoices,
            tickets=tickets,
            active_tab='dashboard')
