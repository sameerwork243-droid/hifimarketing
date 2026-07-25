def configure_app_security(app):
    @app.before_request
    def enforce_https():
        from flask import request
        if request.headers.get('X-Forwarded-Proto') == 'http' and request.host not in ('localhost:8000', '127.0.0.1:8000'):
            import urllib.parse
            url = urllib.parse.urlparse(request.url)
            if url.scheme == 'http':
                return redirect('https://' + url.netloc + url.path + ('?' + url.query if url.query else ''), 301)

    app.config['SESSION_COOKIE_HTTPONLY'] = True
    app.config['SESSION_COOKIE_SAMESITE'] = 'Lax'

    @app.after_request
    def add_security_headers(response):
        response.headers['X-Content-Type-Options'] = 'nosniff'
        response.headers['X-Frame-Options'] = 'DENY'
        response.headers['X-XSS-Protection'] = '1; mode=block'
        return response
