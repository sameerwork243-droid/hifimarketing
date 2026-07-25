import os

MYSQL_CONFIG = {
    'host': os.getenv('MYSQL_HOST', '127.0.0.1'),
    'user': os.getenv('MYSQL_USERNAME', 'root'),
    'password': os.getenv('MYSQL_PASSWORD', ''),
    'database': os.getenv('MYSQL_DATABASE', 'hifiwebsite-313031aed2'),
}

SQLSERVER_CONFIG = {
    'server': os.getenv('SQLSERVER_HOST', 'localhost'),
    'database': os.getenv('SQLSERVER_DATABASE', 'hifiwebsite'),
    'username': os.getenv('SQLSERVER_USERNAME', ''),
    'password': os.getenv('SQLSERVER_PASSWORD', ''),
    'driver': os.getenv('SQLSERVER_DRIVER', 'ODBC Driver 18 for SQL Server'),
}

def resolve_engine():
    engine = os.getenv('DB_ENGINE', os.getenv('DATABASE_MODE', 'MSSQL'))
    return engine.upper()

def get_primary_engine():
    return resolve_engine()

def get_secondary_engine():
    engine = resolve_engine()
    return 'MYSQL' if engine == 'MSSQL' else 'MSSQL'

def get_failover_enabled():
    val = os.getenv('AUTO_FAILOVER', 'true')
    return val.lower() in ('true', '1', 'yes')
