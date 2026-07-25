import logging

logger = logging.getLogger(__name__)


class DatabaseUnavailableError(Exception):
    pass


_db = None
_db_engine = None
_db_config = {}


def init_app(app):
    global _db, _db_engine, _db_config
    _db_engine = app.config.get('DB_ENGINE', 'MSSQL').lower()
    _db_config = {
        'engine': _db_engine,
        'mysql_host': app.config.get('MYSQL_HOST', '127.0.0.1'),
        'mysql_user': app.config.get('MYSQL_USERNAME', 'root'),
        'mysql_pass': app.config.get('MYSQL_PASSWORD', ''),
        'mysql_db': app.config.get('MYSQL_DATABASE', 'hifiwebsite-313031aed2'),
        'sql_host': app.config.get('SQLSERVER_HOST', 'localhost'),
        'sql_db': app.config.get('SQLSERVER_DATABASE', 'hifiwebsite'),
        'sql_user': app.config.get('SQLSERVER_USERNAME', ''),
        'sql_pass': app.config.get('SQLSERVER_PASSWORD', ''),
        'sql_driver': app.config.get('SQLSERVER_DRIVER', 'ODBC Driver 18 for SQL Server'),
    }
    logger.info('Database engine configured: %s', _db_engine)
    try:
        _try_connect()
    except Exception as e:
        logger.warning('Database unavailable at startup: %s', e)


def _try_connect():
    global _db_engine
    if _db_engine == 'mysql':
        _init_mysql()
    elif _db_engine in ('mssql', 'sqlserver'):
        _init_sqlserver()
    else:
        _init_mysql()


def _reconnect():
    logger.info('Attempting database reconnection...')
    try:
        _try_connect()
        return True
    except Exception as e:
        logger.error('Reconnection failed: %s', e)
        return False


def _init_mysql():
    global _db, _db_engine
    _db_engine = 'mysql'
    import mysql.connector
    conn = mysql.connector.connect(
        host=_db_config.get('mysql_host', '127.0.0.1'),
        user=_db_config.get('mysql_user', 'root'),
        password=_db_config.get('mysql_pass', ''),
        database=_db_config.get('mysql_db', 'hifiwebsite-313031aed2'),
    )
    _db = conn


def _init_sqlserver():
    global _db, _db_engine
    _db_engine = 'mssql'
    import pyodbc
    uid = _db_config.get('sql_user', '')
    pwd = _db_config.get('sql_pass', '')
    if uid and pwd:
        conn_str = (
            f"DRIVER={{{_db_config.get('sql_driver', 'ODBC Driver 18 for SQL Server')}}};"
            f"SERVER={_db_config.get('sql_host', 'localhost')};"
            f"DATABASE={_db_config.get('sql_db', 'hifiwebsite')};"
            f"UID={uid};PWD={pwd};"
            f"TrustServerCertificate=yes;"
        )
    else:
        conn_str = (
            f"DRIVER={{{_db_config.get('sql_driver', 'ODBC Driver 18 for SQL Server')}}};"
            f"SERVER={_db_config.get('sql_host', 'localhost')};"
            f"DATABASE={_db_config.get('sql_db', 'hifiwebsite')};"
            f"Trusted_Connection=yes;"
            f"TrustServerCertificate=yes;"
        )
    conn = pyodbc.connect(conn_str)
    _db = conn


class CursorWrapper:
    def __init__(self, conn, engine):
        self._conn = conn
        self._engine = engine

    def execute(self, sql, params=None):
        if self._engine == 'mysql':
            return self._execute_mysql(sql, params)
        return self._execute_sqlserver(sql, params)

    def _execute_mysql(self, sql, params):
        cur = self._conn.cursor(dictionary=True)
        if params:
            cur.execute(sql, params)
        else:
            cur.execute(sql)
        return MySQLCursorWrapper(cur)

    def _execute_sqlserver(self, sql, params):
        cur = self._conn.cursor()
        if params:
            cur.execute(sql, params)
        else:
            cur.execute(sql)
        return SQLCursorWrapper(cur)

    def commit(self):
        self._conn.commit()

    def close(self):
        pass


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
    global _db, _db_engine
    if _db is None:
        raise DatabaseUnavailableError('Database not initialized')
    try:
        if _db_engine == 'mysql':
            _db.ping(reconnect=True, attempts=3, delay=1)
        else:
            cur = _db.cursor()
            cur.execute('SELECT 1')
            cur.close()
    except Exception:
        logger.warning('Health check failed, attempting reconnection')
        try:
            try:
                _db.close()
            except Exception:
                pass
            _db = None
            if _reconnect():
                logger.info('Reconnection successful')
                return CursorWrapper(_db, _db_engine or 'mysql')
        except Exception:
            pass
        raise DatabaseUnavailableError('Database connection lost')
    return CursorWrapper(_db, _db_engine or 'mysql')


def close_db():
    global _db, _db_engine
    if _db:
        try:
            _db.close()
        except Exception:
            pass
        _db = None
    _db_engine = None
