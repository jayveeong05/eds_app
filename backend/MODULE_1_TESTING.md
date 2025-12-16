# Module 1: Backend Foundation - Testing Guide

## What Was Built

1. **AdminMiddleware.php** - Authentication and authorization middleware
2. **admin_schema.sql** - Database updates for admin panel
3. **test_auth.php** - Test endpoint to verify admin authentication

## Database Setup

Run the following SQL script to set up the database:

```bash
# Connect to your PostgreSQL database and run:
psql -U your_username -d eds_db -f backend/sql/admin_schema.sql
```

Or manually run the SQL in your PostgreSQL client.

## Create Your First Admin User

After running the schema, promote a user to admin:

```sql
-- Replace with your actual email
UPDATE users 
SET role = 'admin', status = 'active' 
WHERE email = 'your-email@example.com';
```

## Testing the Module

### Test 1: Database Setup
```sql
-- Verify admin_activity_log table exists
SELECT * FROM admin_activity_log LIMIT 1;

-- Verify indexes were created
SELECT indexname FROM pg_indexes WHERE tablename = 'users';

-- Check your admin user
SELECT id, email, role, status FROM users WHERE role = 'admin';
```

### Test 2: Admin Authentication Endpoint

**Using curl:**
```bash
curl -X POST http://localhost:8000/api/admin/test_auth.php \
  -H "Content-Type: application/json" \
  -d '{"idToken": "YOUR_FIREBASE_TOKEN"}'
```

**Expected Success Response:**
```json
{
  "success": true,
  "message": "Admin authentication successful",
  "admin": {
    "id": "uuid-here",
    "email": "admin@example.com",
    "name": "Admin Name",
    "role": "admin"
  }
}
```

**Expected Error (non-admin):**
```json
{
  "success": false,
  "message": "Admin access required"
}
```

### Test 3: Get Firebase Token from Flutter App

Run your Flutter app and log in with the admin user, then check the console/debug output for the idToken, or add this to get it:

```dart
// In any authenticated screen
FirebaseAuth.instance.currentUser?.getIdToken().then((token) {
  print('ID Token: $token');
});
```

## Verification Checklist

- [ ] Database migrations ran successfully
- [ ] admin_activity_log table exists
- [ ] Indexes created on users table
- [ ] At least one user has role='admin' and status='active'
- [ ] test_auth.php returns success for admin user
- [ ] test_auth.php returns 403 for non-admin user
- [ ] AdminMiddleware.php loads without errors

## What's Next

Once Module 1 is verified, we'll move to:
- **Module 2**: User Management APIs (get all users, update status, update role, delete user)

