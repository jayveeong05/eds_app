# Module 3: Promotions Management APIs - Testing Guide

## What Was Built

1. **get_all_promotions.php** - Admin view of all promotions with creator info
2. **update_promotion.php** - Update promotion description and/or image
3. **delete_promotion.php** - Delete promotion permanently
4. **add_promotion.php** - Enhanced to support admin authentication and user_id

## Testing the Endpoints

### 1. Get All Promotions (Admin View)

**Endpoint:** `POST http://localhost:8000/api/admin/get_all_promotions.php`

**Request:**
```json
{
  "idToken": "YOUR_ADMIN_FIREBASE_TOKEN"
}
```

**Request (with pagination):**
```json
{
  "idToken": "YOUR_ADMIN_FIREBASE_TOKEN",
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
      "id": "promo-uuid",
      "image_url": "https://s3-url/image.jpg",
      "description": "Summer sale promotion",
      "created_at": "2025-12-16 10:00:00",
      "user": {
        "id": "user-uuid",
        "email": "admin@example.com",
        "name": "Admin User",
        "profile_image_url": null
      }
    }
  ],
  "total": 15,
  "limit": 10,
  "offset": 0
}
```

---

### 2. Create Promotion (Admin)

**Endpoint:** `POST http://localhost:8000/api/add_promotion.php`

**Request (admin creates promotion):**
```json
{
  "idToken": "YOUR_ADMIN_FIREBASE_TOKEN",
  "image_url": "https://s3.amazonaws.com/bucket/promo.jpg",
  "description": "New year sale - 50% off all items!",
  "user_id": "optional-user-uuid"
}
```

**Request (without auth - backward compatible):**
```json
{
  "image_url": "https://s3.amazonaws.com/bucket/promo.jpg",
  "description": "New promotion"
}
```

**Expected Response:**
```json
{
  "success": true,
  "message": "Promotion created successfully"
}
```

---

### 3. Update Promotion

**Endpoint:** `POST http://localhost:8000/api/admin/update_promotion.php`

**Request (update description only):**
```json
{
  "idToken": "YOUR_ADMIN_FIREBASE_TOKEN",
  "promotionId": "promo-uuid-here",
  "description": "Updated description text"
}
```

**Request (update both description and image):**
```json
{
  "idToken": "YOUR_ADMIN_FIREBASE_TOKEN",
  "promotionId": "promo-uuid-here",
  "description": "New description",
  "image_url": "https://new-image-url.com/image.jpg"
}
```

**Expected Response:**
```json
{
  "success": true,
  "message": "Promotion updated successfully"
}
```

**Verify in database:**
```sql
SELECT id, description, image_url FROM promotions WHERE id = 'promo-uuid';
```

---

### 4. Delete Promotion

**Endpoint:** `POST http://localhost:8000/api/admin/delete_promotion.php`

**Request:**
```json
{
  "idToken": "YOUR_ADMIN_FIREBASE_TOKEN",
  "promotionId": "promo-uuid-to-delete"
}
```

**Expected Response:**
```json
{
  "success": true,
  "message": "Promotion deleted successfully"
}
```

**Verify in database:**
```sql
SELECT * FROM promotions WHERE id = 'promo-uuid-to-delete';
-- Should return 0 rows
```

---

## Quick Test Script (curl)

```bash
# Set your admin token
TOKEN="your-firebase-id-token-here"

# 1. Get all promotions
curl -X POST http://localhost:8000/api/admin/get_all_promotions.php \
  -H "Content-Type: application/json" \
  -d "{\"idToken\":\"$TOKEN\"}"

# 2. Create promotion
curl -X POST http://localhost:8000/api/add_promotion.php \
  -H "Content-Type: application/json" \
  -d "{\"idToken\":\"$TOKEN\",\"image_url\":\"https://example.com/promo.jpg\",\"description\":\"Test promotion\"}"

# 3. Update promotion (replace PROMO_ID)
curl -X POST http://localhost:8000/api/admin/update_promotion.php \
  -H "Content-Type: application/json" \
  -d "{\"idToken\":\"$TOKEN\",\"promotionId\":\"PROMO_ID\",\"description\":\"Updated description\"}"

# 4. Delete promotion (replace PROMO_ID)
curl -X POST http://localhost:8000/api/admin/delete_promotion.php \
  -H "Content-Type: application/json" \
  -d "{\"idToken\":\"$TOKEN\",\"promotionId\":\"PROMO_ID\"}"
```

## Verification Checklist

- [ ] Get all promotions returns list with user info
- [ ] Pagination works (limit/offset)
- [ ] Create promotion works (with admin token)
- [ ] Create promotion works (backward compatible without token)
- [ ] Update promotion description works
- [ ] Update promotion image_url works
- [ ] Update both fields at once works
- [ ] Delete promotion works
- [ ] All actions logged in admin_activity_log table

## Database Verification

```sql
-- Check recent promotions
SELECT p.id, p.description, u.email as creator
FROM promotions p
LEFT JOIN users u ON p.user_id = u.id
ORDER BY p.created_at DESC
LIMIT 10;

-- Check admin activity for promotions
SELECT 
  aal.action,
  aal.target_id,
  aal.details,
  u.email as admin_email,
  aal.created_at
FROM admin_activity_log aal
JOIN users u ON aal.admin_user_id = u.id
WHERE aal.target_type = 'promotion'
ORDER BY aal.created_at DESC
LIMIT 10;
```

## What's Next

Module 3 complete! All backend APIs are now finished:
- ✅ Module 1: Admin authentication & middleware
- ✅ Module 2: User management APIs
- ✅ Module 3: Promotions management APIs

**Next:** Module 4 - Admin Web Interface (UI/UX)
- Login page
- Dashboard with statistics
- Users management page
- Promotions management page
