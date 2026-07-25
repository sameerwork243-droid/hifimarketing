from flask import session
import html
from werkzeug.security import generate_password_hash, check_password_hash


def is_logged_in():
    return 'user_id' in session


def is_admin():
    return session.get('user_role') == 'admin'


def sanitize(text):
    if text is None:
        return ''
    return html.escape(str(text))


def verify_password(password, stored):
    return check_password_hash(stored, password)


def hash_password(password):
    return generate_password_hash(password)
