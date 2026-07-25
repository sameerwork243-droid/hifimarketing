import logging

logger = logging.getLogger(__name__)

def seed_demo_users():
    try:
        from db import get_db, DatabaseUnavailableError
        engine_info = __import__('flask').current_app.config.get('DB_ENGINE', 'unknown')
        print(f'  Detected engine: {engine_info}')
        try:
            db = get_db()
            cursor = db[0]
            engine = db[1]
            cursor.execute("SELECT COUNT(*) as cnt FROM users")
            count = cursor.fetchone()['cnt']
            if count > 0:
                print('  ~ Users table has data, skipping seed')
                cursor.close()
                return
        except Exception:
            pass
        demo_users = [
            ('admin@hifi.com', 'Admin', 'admin123', 'admin'),
            ('pm@hifi.com', 'Project Manager', 'pm123', 'project_manager'),
            ('client@hifi.com', 'Client User', 'client123', 'client'),
            ('careers@hifimarketing.co', 'Careers Admin', 'careers321', 'careers_admin'),
        ]
        created = 0
        for email, username, password, role in demo_users:
            try:
                cursor.execute("SELECT id FROM users WHERE email = ?", (email,))
                if cursor.fetchone():
                    print(f'  ~ {email} already exists, skipping')
                    continue
                cursor.execute("INSERT INTO users (username, email, password, role) VALUES (?, ?, ?, ?)", (username, email, password, role))
                cursor._cur.commit()
                print(f'  + {email} created as {role}')
                created += 1
            except Exception as ue:
                print(f'  ! {email}: {ue}')
        if created == 0:
            print('  ~ all demo users already exist')
    except DatabaseUnavailableError:
        print('  [SKIP] Database unavailable for seeding')
    except Exception as e:
        print(f'  [FAIL] Seeding error: {e}')
