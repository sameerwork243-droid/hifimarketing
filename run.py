import os
import sys
from flask import Flask, session, request, redirect, url_for, send_from_directory, send_file
from db import get_db, close_db, init_app as init_db, DatabaseUnavailableError
from helpers import is_logged_in, is_admin, sanitize
from utils.security import configure_app_security

app = Flask(__name__, static_folder='static')
app.secret_key = os.getenv('SECRET_KEY', 'hifi-marketing-inplace-migration-key')
app.config['DB_ENGINE'] = os.getenv('DB_ENGINE', os.getenv('DATABASE_MODE', 'MSSQL'))
app.config['AUTO_FAILOVER'] = os.getenv('AUTO_FAILOVER', 'true')
app.config['DB_HOST'] = os.getenv('DB_HOST', '127.0.0.1')
app.config['DB_USER'] = os.getenv('DB_USER', 'root')
app.config['DB_PASS'] = os.getenv('DB_PASS', '')
app.config['DB_NAME'] = os.getenv('DB_NAME', 'hifiwebsite-313031aed2')
app.config['MYSQL_HOST'] = os.getenv('MYSQL_HOST', os.getenv('DB_HOST', '127.0.0.1'))
app.config['MYSQL_USERNAME'] = os.getenv('MYSQL_USERNAME', os.getenv('DB_USER', 'root'))
app.config['MYSQL_PASSWORD'] = os.getenv('MYSQL_PASSWORD', os.getenv('DB_PASS', ''))
app.config['MYSQL_DATABASE'] = os.getenv('MYSQL_DATABASE', os.getenv('DB_NAME', 'hifiwebsite-313031aed2'))
app.config['SQLSERVER_HOST'] = os.getenv('SQLSERVER_HOST', 'localhost')
app.config['SQLSERVER_DATABASE'] = os.getenv('SQLSERVER_DATABASE', 'hifiwebsite')
app.config['SQLSERVER_USERNAME'] = os.getenv('SQLSERVER_USERNAME', '')
app.config['SQLSERVER_PASSWORD'] = os.getenv('SQLSERVER_PASSWORD', '')
app.config['SQLSERVER_DRIVER'] = os.getenv('SQLSERVER_DRIVER', 'ODBC Driver 18 for SQL Server')
app.config['SITE_URL'] = os.getenv('SITE_URL', 'http://localhost:8000/')
app.config['UPLOAD_FOLDER'] = os.path.join(os.path.dirname(__file__), 'uploads')
app.config['MAX_CONTENT_LENGTH'] = 16 * 1024 * 1024

init_db(app)

configure_app_security(app)

@app.context_processor
def inject_globals():
    return {
        'site_url': app.config['SITE_URL'],
        'now': __import__('datetime').datetime.now(),
        'is_logged_in': lambda: 'user_id' in session,
        'current_user': lambda: {
            'id': session.get('user_id'),
            'username': session.get('username'),
            'role': session.get('user_role')
        } if 'user_id' in session else None
    }

@app.template_filter('time_ago')
def time_ago_filter(dt):
    if not dt:
        return 'N/A'
    from datetime import datetime
    now = datetime.now()
    if isinstance(dt, str):
        try:
            dt = datetime.strptime(dt, '%Y-%m-%d %H:%M:%S')
        except ValueError:
            try:
                dt = datetime.strptime(dt, '%Y-%m-%d')
            except ValueError:
                return dt
    diff = now - dt
    if diff.days > 365:
        return f'{diff.days // 365}y ago'
    if diff.days > 30:
        return f'{diff.days // 30}mo ago'
    if diff.days > 0:
        return f'{diff.days}d ago'
    if diff.seconds >= 3600:
        return f'{diff.seconds // 3600}h ago'
    if diff.seconds >= 60:
        return f'{diff.seconds // 60}m ago'
    return 'just now'

@app.template_filter('e')
def escape_filter(text):
    return sanitize(text)

@app.errorhandler(DatabaseUnavailableError)
def handle_db_unavailable(error):
    logger = __import__('logging').getLogger(__name__)
    logger.error('Database unavailable: %s', error)
    return _db_error_page(str(error))

@app.errorhandler(500)
def handle_500(error):
    original = getattr(error, 'original_exception', None) or error
    if isinstance(original, DatabaseUnavailableError):
        return _db_error_page(str(original))
    logger = __import__('logging').getLogger(__name__)
    logger.error('Internal server error: %s', original)
    return _db_error_page('An unexpected error occurred. Please try again later.')


def _db_error_page(detail):
    return (
        f'''<!DOCTYPE html>
<html lang="en">
<head><meta charset="UTF-8"><title>Service Unavailable</title>
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<style>
* {{ margin:0; padding:0; box-sizing:border-box; }}
body {{ font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',Roboto,sans-serif;
  background:#f8fafc; color:#1e293b; display:flex; align-items:center; justify-content:center;
  min-height:100vh; }}
.container {{ text-align:center; padding:2rem; max-width:480px; }}
.icon {{ font-size:4rem; margin-bottom:1rem; }}
h1 {{ font-size:1.5rem; margin-bottom:.5rem; color:#0f172a; }}
p {{ color:#64748b; margin-bottom:1.5rem; line-height:1.5; }}
.btn {{ display:inline-block; padding:.75rem 1.5rem; background:#6366f1; color:#fff;
  text-decoration:none; border-radius:.5rem; font-weight:500; }}
.btn:hover {{ background:#4f46e5; }}
.detail {{ margin-top:1rem; font-size:.8rem; color:#94a3b8; word-break:break-all; }}
</style></head>
<body><div class="container">
<div class="icon">&#x1F50C;</div>
<h1>Service Unavailable</h1>
<p>We are unable to connect to the database right now. Please try again in a few moments.</p>
<a class="btn" href="/">Try Again</a>
<div class="detail">{sanitize(detail)}</div>
</div></body></html>''',
        503,
        {'Content-Type': 'text/html; charset=utf-8'},
    )


try:
    from database.sync import validate_schemas
    validate_schemas(app)
except Exception:
    import logging
    logging.getLogger(__name__).exception('Schema validation failed')

print()
print('=' * 60)
print('  DATABASE HEALTH CHECK')
print('=' * 60)
try:
    from database.seed import seed_demo_users
    seed_demo_users(app)
except Exception as seed_err:
    import traceback
    print(f'  [FAIL] Seeding error: {seed_err}')
    traceback.print_exc()
print('=' * 60)
print()

from handlers.public import init_routes
from handlers.user_handler import init_user_routes
from handlers.admin_handler import init_admin_routes
from handlers.admin_portal import init_admin_portal_routes
from handlers.pm_portal import init_pm_portal_routes
from handlers.client_portal import init_client_portal_routes
from handlers.employee_handler import init_employee_routes
from handlers.career_portal import init_career_portal_routes

init_routes(app)
init_user_routes(app)
init_admin_routes(app)
init_admin_portal_routes(app)
init_pm_portal_routes(app)
init_client_portal_routes(app)
init_employee_routes(app)
init_career_portal_routes(app)

@app.route('/<path:filename>')
def serve_static(filename):
    file_path = os.path.join(app.root_path, filename)
    if os.path.exists(file_path) and not filename.endswith('.py'):
        return send_from_directory(app.root_path, filename)
    return app.send_static_file('404.html') if os.path.exists(os.path.join(app.root_path, '404.html')) else ('', 404)

if __name__ == '__main__':
    app.run(host='0.0.0.0', port=8000, debug=True)
