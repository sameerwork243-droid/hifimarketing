import logging

logger = logging.getLogger(__name__)


class DatabaseUnavailableError(Exception):
    pass


_db = None


def init_app(app):
    global _db
    engine = app.config.get('DB_ENGINE', 'MSSQL')
    logger.info('Database engine configured: %s', engine)
    try:
        _try_connect(app)
    except Exception as e:
        logger.warning('Database unavailable at startup: %s', e)


def _try_connect(app):
    engine = app.config.get('DB_ENGINE', 'MSSQL').lower()
    if engine == 'mysql':
        _init_mysql(app)
    elif engine in ('mssql', 'sqlserver'):
        _init_sqlserver(app)
    else:
        _init_mysql(app)


def _init_mysql(app):
    global _db
    import mysql.connector
    conn = mysql.connector.connect(
        host=app.config.get('MYSQL_HOST', '127.0.0.1'),
        user=app.config.get('MYSQL_USERNAME', 'root'),
        password=app.config.get('MYSQL_PASSWORD', ''),
        database=app.config.get('MYSQL_DATABASE', 'hifiwebsite-313031aed2'),
    )
    _db = conn


def _init_sqlserver(app):
    global _db
    import pyodbc
    conn_str = (
        f"DRIVER={{{app.config.get('SQLSERVER_DRIVER', 'ODBC Driver 18 for SQL Server')}}};"
        f"SERVER={app.config.get('SQLSERVER_HOST', 'localhost')};"
        f"DATABASE={app.config.get('SQLSERVER_DATABASE', 'hifiwebsite')};"
        f"UID={app.config.get('SQLSERVER_USERNAME', '')};"
        f"PWD={app.config.get('SQLSERVER_PASSWORD', '')};"
        f"TrustServerCertificate=yes;"
    )
    conn = pyodbc.connect(conn_str)
    _db = conn


class CursorWrapper:
    def __init__(self, cur, engine):
        self._cur = cur
        self._engine = engine

    def execute(self, sql, params=None):
        if self._engine == 'mysql':
            return self._execute_mysql(sql, params)
        return self._execute_sqlserver(sql, params)

    def _execute_mysql(self, sql, params):
        cur = self._cur.cursor(dictionary=True)
        if params:
            cur.execute(sql, params)
        else:
            cur.execute(sql)
        return MySQLCursorWrapper(cur)

    def _execute_sqlserver(self, sql, params):
        cur = self._cur.cursor()
        if params:
            cur.execute(sql, params)
        else:
            cur.execute(sql)
        return SQLCursorWrapper(cur)

    def close(self):
        self._cur.close()


class MySQLCursorWrapper:
    def __init__(self, cur):
        self._cur = cur

    def fetchone(self):
        return self._cur.fetchone()

    def fetchall(self):
        return self._cur.fetchall()

    def close(self):
        self._cur.close()


class SQLCursorWrapper:
    def __init__(self, cur):
        self._cur = cur

    def fetchone(self):
        row = self._cur.fetchone()
        if row:
            columns = [desc[0] for desc in self._cur.description]
            return dict(zip(columns, row))
        return None

    def fetchall(self):
        rows = self._cur.fetchall()
        if not rows:
            return []
        columns = [desc[0] for desc in self._cur.description]
        return [dict(zip(columns, row)) for row in rows]

    def close(self):
        self._cur.close()


def get_db():
    if _db is None:
        raise DatabaseUnavailableError('Database not initialized')
    try:
        _db.ping(reconnect=True, attempts=3, delay=1)
    except Exception:
        raise DatabaseUnavailableError('Database connection lost')
    engine = 'mysql'
    return CursorWrapper(_db, engine), engine


def close_db():
    global _db
    if _db:
        try:
            _db.close()
        except Exception:
            pass
        _db = None
