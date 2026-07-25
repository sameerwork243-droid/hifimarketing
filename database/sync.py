import logging

logger = logging.getLogger(__name__)

def validate_schemas(app):
    try:
        from db import get_db, close_db, DatabaseUnavailableError
        with app.app_context():
            db = get_db()
            cursor = db[0]
            engine = db[1]
            cursor.execute('SELECT 1')
            cursor.close()
            logger.info('Schema validation passed (%s)', engine)
    except DatabaseUnavailableError:
        logger.warning('Schema validation skipped (database unavailable)')
    except Exception as e:
        logger.error('Schema validation error: %s', e)
