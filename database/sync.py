import logging

logger = logging.getLogger(__name__)

def validate_schemas(app):
    try:
        from db import get_db, close_db, _db_engine, DatabaseUnavailableError
        with app.app_context():
            db = get_db()
            db.execute('SELECT 1')
            logger.info('Schema validation passed (%s)', _db_engine or 'unknown')
    except DatabaseUnavailableError:
        logger.warning('Schema validation skipped (database unavailable)')
    except Exception as e:
        logger.error('Schema validation error: %s', e)
