from flask import Blueprint, request, redirect, url_for, session, flash

def init_auth_routes(app):
    @app.route('/login', methods=['GET', 'POST'])
    def login():
        if request.method == 'POST':
            from db import get_db
            email = request.form.get('email', '')
            password = request.form.get('password', '')
            db = get_db()
            try:
                user = db.execute(
                    'SELECT id, username, email, password, role FROM users WHERE email = ? AND password = ?',
                    (email, password)
                ).fetchone()
                if user:
                    session['user_id'] = user['id']
                    session['username'] = user['username']
                    session['user_role'] = user['role']
                    session.permanent = True
                    nxt = session.pop('redirect_after_login', '/dashboard')
                    return redirect(nxt)
                flash('Invalid credentials')
            finally:
                db.close()
            return redirect('/login')
        return '''<!DOCTYPE html>
<html lang="en">
<head><meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1.0"><title>Login - HIFI Marketing</title>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700;800;900&display=swap" rel="stylesheet" />
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" />
<style>*{margin:0;padding:0;box-sizing:border-box}body{font-family:'Inter',sans-serif;background:#f8fafc;min-height:100vh;display:flex;align-items:center;justify-content:center}.login-wrap{max-width:420px;width:100%;margin:2rem;background:#fff;border-radius:24px;padding:3rem 2.5rem;box-shadow:0 8px 40px rgba(0,0,0,.06);border:1px solid #e9edf2}.login-wrap .logo{font-size:24px;font-weight:900;color:#1a1c26;text-align:center;margin-bottom:4px}.login-wrap .logo span{color:#4a5cf5}.login-wrap .sub{text-align:center;color:#4a5260;font-size:14px;margin-bottom:28px}.login-wrap form label{display:block;font-weight:600;font-size:13px;color:#1a1c26;margin-bottom:4px}.login-wrap form input{width:100%;padding:12px 16px;border:1px solid #e9edf2;border-radius:12px;font-size:14px;outline:none;transition:.2s;margin-bottom:16px}.login-wrap form input:focus{border-color:#4a5cf5;box-shadow:0 0 0 3px rgba(74,92,245,.1)}.login-wrap .btn-primary{width:100%;background:#4a5cf5;color:#fff;padding:14px;border-radius:40px;font-weight:700;font-size:15px;border:none;cursor:pointer;transition:.2s;margin-top:6px}.login-wrap .btn-primary:hover{background:#3a4be0}.login-wrap .links{text-align:center;margin-top:20px;font-size:13px;color:#4a5260}.login-wrap .links a{color:#4a5cf5;font-weight:600}.login-wrap .flash{background:#fee2e2;color:#b91c1c;padding:10px 14px;border-radius:10px;margin-bottom:16px;font-size:13px;text-align:center}
</style></head>
<body><div class="login-wrap">
<div class="logo">HIFI <span>Marketing</span></div>
<div class="sub">Sign in to your account</div>
<form method="POST">
<label>Email Address</label><input type="email" name="email" required placeholder="you@example.com" />
<label>Password</label><input type="password" name="password" required placeholder="&bull;&bull;&bull;&bull;&bull;&bull;&bull;&bull;" />
<button type="submit" class="btn-primary">Sign In</button>
</form>
<div class="links"><a href="/register">Create an account</a> &middot; <a href="/resetpass">Forgot password?</a></div>
<div class="links"><a href="/">&larr; Back to Home</a></div>
</div></body></html>'''

    @app.route('/logout')
    def logout():
        session.clear()
        return redirect('/')

    @app.route('/register')
    def register():
        return redirect('/login')

    @app.route('/resetpass')
    def resetpass():
        return redirect('/login')
