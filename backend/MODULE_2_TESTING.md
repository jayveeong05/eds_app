# Module 2: User Management APIs - Testing Guide

## What Was Built

1. **get_all_users.php** - List users with search, filter, and pagination
2. **update_user_status.php** - Activate/deactivate users
3. **update_user_role.php** - Promote/demote users (with self-demotion prevention)
4. **delete_user.php** - Soft delete users (with self-deletion prevention)
5. **get_dashboard_stats.php** - Dashboard statistics

## Testing the Endpoints

### 1. Get Dashboard Statistics

**Endpoint:** `POST http://localhost:8000/api/admin/get_dashboard_stats.php`

**Request:**
```json
{
  "idToken": "YOUR_ADMIN_FIREBASE_TOKEN"
}
```

**Expected Response:**
```json
{
  "success": true,
  "stats": {
    "total_users": 5,
    "active_users": 3,
    "inactive_users": 2,
    "total_promotions": 10,
    "recent_registrations": 2,
    "total_admins": 1
  }
}
```

---

### 2. Get All Users

**Endpoint:** `POST http://localhost:8000/api/admin/get_all_users.php`

**Request (basic):**
```json
{
  "idToken": "YOUR_ADMIN_FIREBASE_TOKEN"
}
```

**Request (with filters):**
```json
{
  "idToken": "YOUR_ADMIN_FIREBASE_TOKEN",
  "search": "john",
  "status": "active",
  "role": "user",
  "limit": 10,
  "offset": 0
}
```

**Expected Response:**
```json
{
  "success": true,
  "data": [
    {
      "id": "uuid-string",
      "firebase_uid": "firebase-uid",
      "email": "user@example.com",
      "name": "John Doe",
      "role": "user",
      "status": "active",
      "login_method": "email",
      "profile_image_url": null,
      "created_at": "2025-12-16 10:00:00"
    }
  ],
  "total": 5,
  "limit": 10,
  "offset": 0
}
```

---

### 3. Update User Status

**Endpoint:** `POST http://localhost:8000/api/admin/update_user_status.php`

**Request:**
```json
{
  "idToken": "YOUR_ADMIN_FIREBASE_TOKEN",
  "userId": "user-uuid-here",
  "status": "active"
}
```

**Expected Response:**
```json
{
  "success": true,
  "message": "User status updated successfully"
}
```

**Check activity log:**
```sql
SELECT * FROM admin_activity_log ORDER BY created_at DESC LIMIT 5;
```

---

### 4. Update User Role

**Endpoint:** `POST http://localhost:8000/api/admin/update_user_role.php`

**Request:**
```json
{
  "idToken": "YOUR_ADMIN_FIREBASE_TOKEN",
  "userId": "user-uuid-here",
  "role": "admin"
}
```

**Expected Response:**
```json
{
  "success": true,
  "message": "User role updated successfully"
}
```

**Test self-demotion prevention:**
```json
{
  "idToken": "YOUR_ADMIN_FIREBASE_TOKEN",
  "userId": "YOUR_OWN_USER_ID",
  "role": "user"
}
```

**Expected Error:**
```json
{
  "success": false,
  "message": "Cannot demote yourself from admin role"
}
```

---

### 5. Delete User

**Endpoint:** `POST http://localhost:8000/api/admin/delete_user.php`

**Request:**
```json
{
  "idToken": "YOUR_ADMIN_FIREBASE_TOKEN",
  "userId": "user-uuid-to-delete"
}
```

**Expected Response:**
```json
{
  "success": true,
  "message": "User deleted successfully"
}
```

**Verify in database:**
```sql
SELECT email, status FROM users WHERE id = 'user-uuid-to-delete';
-- Should show status as 'inactive'
```

---

## Quick Test Script (curl)

```bash
# Set your admin token
TOKEN="your-firebase-id-token-here"

# 1. Get stats
curl -X POST http://localhost:8000/api/admin/get_dashboard_stats.php \
  -H "Content-Type: application/json" \
  -d "{\"idToken\":\"$TOKEN\"}"

# 2. Get all users
curl -X POST http://localhost:8000/api/admin/get_all_users.php \
  -H "Content-Type: application/json" \
  -d "{\"idToken\":\"$TOKEN\",\"limit\":5}"

# 3. Search users
curl -X POST http://localhost:8000/api/admin/get_all_users.php \
  -H "Content-Type: application/json" \
  -d "{\"idToken\":\"$TOKEN\",\"search\":\"admin\"}"

# 4. Update user status
curl -X POST http://localhost:8000/api/admin/update_user_status.php \
  -H "Content-Type: application/json" \
  -d "{\"idToken\":\"$TOKEN\",\"userId\":\"user-uuid\",\"status\":\"active\"}"
```

## Verification Checklist

- [ ] Dashboard stats endpoint returns correct counts
- [ ] Get all users returns user list
- [ ] Search by email works
- [ ] Filter by status works (active/inactive)
- [ ] Filter by role works (user/admin)
- [ ] Pagination works (limit/offset)
- [ ] Update user status works
- [ ] Update user role works
- [ ] Cannot demote yourself (returns error)
- [ ] Delete user works (soft delete)
- [ ] Cannot delete yourself (returns error)
- [ ] All actions logged in admin_activity_log table

## Database Verification

```sql
-- Check admin activity log
SELECT 
  aal.action,
  aal.target_type,
  aal.details,
  u.email as admin_email,
  aal.created_at
FROM admin_activity_log aal
JOIN users u ON aal.admin_user_id = u.id
ORDER BY aal.created_at DESC
LIMIT 10;
```

## What's Next

Once Module 2 is verified, we'll move to:
- **Module 3**: Promotions Management APIs (create, update, delete promotions)
