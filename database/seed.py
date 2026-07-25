import logging

logger = logging.getLogger(__name__)

def seed_demo_users(app):
    try:
        from db import get_db, DatabaseUnavailableError
        from helpers import hash_password
        from werkzeug.security import check_password_hash
        with app.app_context():
            print(f'  Detected engine: {app.config.get("DB_ENGINE", "unknown")}')
            db = get_db()
            demo_users = [
                ('admin@hifi.com', 'Admin', 'admin123', 'super_admin'),
                ('pm@hifi.com', 'Project Manager', 'pm123', 'pm'),
                ('client@hifi.com', 'Client User', 'client123', 'client'),
                ('careers@hifimarketing.co', 'Careers Admin', 'careers321', 'super_admin'),
                ('career-admin@hifi.com', 'Career Admin', 'career123', 'admin'),
                ('career-user@hifi.com', 'Career User', 'user123', 'user'),
            ]
            created = 0
            updated = 0
            for email, username, password, role in demo_users:
                try:
                    existing = db.execute("SELECT id, password FROM users WHERE email = ?", (email,)).fetchone()
                    hashed = hash_password(password)
                    if existing:
                        if not check_password_hash(existing['password'], password):
                            db.execute(
                                "UPDATE users SET password = ? WHERE id = ?",
                                (hashed, existing['id'])
                            )
                            db.commit()
                            print(f'  ~ {email} password updated to werkzeug format')
                            updated += 1
                        else:
                            print(f'  ~ {email} already has valid password')
                    else:
                        db.execute(
                            "INSERT INTO users (username, email, password, role, user_role) VALUES (?, ?, ?, ?, ?)",
                            (username, email, hashed, role, role)
                        )
                        db.commit()
                        print(f'  + {email} created as {role}')
                        created += 1
                except Exception as ue:
                    print(f'  ! {email}: {ue}')
            if created == 0 and updated == 0:
                print('  ~ all demo users already up to date')
            else:
                print(f'  Summary: {created} created, {updated} updated')
    except DatabaseUnavailableError:
        print('  [SKIP] Database unavailable for seeding')
    except Exception as e:
        print(f'  [FAIL] Seeding error: {e}')
