from flask import session
import html


def is_logged_in():
    return 'user_id' in session


def is_admin():
    return session.get('user_role') == 'admin'


def sanitize(text):
    if text is None:
        return ''
    return html.escape(str(text))
