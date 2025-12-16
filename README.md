# EDS App - E-Document Solutions

A mobile application for managing e-documents, promotions, and invoices with Firebase authentication and AWS S3 integration.

---

## 🎨 Brand Colors

- **Primary:** Royal Blue `#3F51B5`
- **Accent:** Red `#E53935`
- **Card Background:** Light Blue Tint `#F5F7FF`
- **Screen Background:** Light Blue-Gray `#F0F3FF`
- **Profile Avatar:** Light EDS Blue `#E8EAF6`

---

## ✨ Features

### 🔐 Authentication
- **Email/Password** authentication with Firebase
- **Google Sign-In** integration
- **Apple Sign-In** integration
- **Login method tracking** - Displays authentication provider
- **QR Code activation** - New users display QR for admin approval
- **Account status management** - Active/Inactive states

### 👤 Profile Management
- **Profile picture upload** - S3 integration via backend proxy
- **Camera & gallery support** - Choose photo source
- **Change password** - Email users only (conditional display)
- **Firebase password update** - Direct reauthentication
- **Login method display** - Shows email/google/apple with icons
- **Profile information editing** - Name and email management

### 📱 Dashboard
- **Latest promotions feed** - Displays 3 most recent promotions
- **Polished card design** - Light blue tint with enhanced elevation
- **Vertical scrollable cards** - Image, user, timestamp, description
- **Pull-to-refresh** - Swipe down to reload
- **Relative timestamps** - "5m ago", "2h ago", "3d ago"
- **User avatars** - Profile pictures or initials
- **Branded AppBar** - EDS royal blue, left-aligned title
- **Static cards** - Read-only display

### 🖥️ Admin Panel
- **Web-based management** - Full CRUD for users and content
- **Dashboard stats** - Real-time metrics (active users, promotions)
- **QR Code Scanner** - Instantly activate users via webcam
- **Promotion Management** - Upload images (S3) and manage campaigns
- **User Management** - Activate/deactivate, role assignment, soft delete
- **Secure Access** - Firebase Auth + Session management

### 🎯 Promotions
- **Two-tone card header** - EDS royal blue with white text
- **Circular navigation** - Swipe through promotions with branded arrows
- **Optimized card layout** - 75% width with proper spacing
- **Fullscreen image viewer** - Tap to zoom
- **Pinch-to-zoom** - InteractiveViewer support
- **User information** - Creator profile and timestamp
- **Description display** - Multi-line text with overflow
- **Branded theme** - EDS blue AppBar, icons, and background
- **Light blue background** - Cohesive color scheme

### 📄 Invoices
- **Invoice listing** (screen exists, ready for enhancement)
- **PDF support** (backend ready)

### 🧭 Navigation
- **Bottom navigation bar** - 4 tabs (Home, Promotions, Invoices, Profile)
- **EDS branded selection** - Royal blue for active tab
- **Tab state management** - Persistent across sessions
- **Material Design 3** - Modern UI components
- **Consistent AppBars** - All screens use EDS royal blue with left-aligned titles

---

## 🛠 Tech Stack

### Frontend (Flutter)
- **Flutter SDK** - Cross-platform mobile framework
- **Firebase Auth** - Authentication provider
- **Firebase Core** - Firebase integration
- **Google Sign-In** - OAuth authentication
- **Sign in with Apple** - iOS authentication
- **HTTP** - API communication
- **Shared Preferences** - Local storage
- **QR Flutter** - QR code generation
- **Image Picker** - Photo selection

### Backend (PHP)
- **PHP 8+** - Server-side logic
- **MySQL/MariaDB** - Database
- **Firebase Admin SDK** - Token verification
- **AWS SDK** - S3 file uploads
- **PDO** - Database abstraction

### Infrastructure
- **AWS S3** - File storage (profile pictures, invoices, promotions)
  - **Security**: "Block Public Access" enabled
  - **Access**: Backend proxy with signed requests
  - **Performance**: Streaming response implementation
- **Firebase** - Authentication services
- **Local PHP server** - Development backend

---

## 📁 Project Structure

```
eds_app/
├── lib/
│   ├── screens/
│   │   ├── dashboard_screen.dart       # Home with latest promotions
│   │   ├── login_screen.dart           # Auth screen with brand colors
│   │   ├── inactive_screen.dart        # QR code for activation
│   │   ├── main_navigation.dart        # Bottom nav wrapper
│   │   ├── promotions_screen.dart      # Promotion carousel
│   │   ├── invoices_screen.dart        # Invoice listing
│   │   └── profile_screen.dart         # User profile & settings
│   ├── services/
│   │   ├── auth_service.dart           # Authentication logic
│   │   └── upload_service.dart         # S3 upload via proxy
│   └── main.dart                       # App entry point
├── backend/
│   ├── api/
│   │   ├── verify_token.php            # Firebase token verification
│   │   ├── get_promotions.php          # Fetch promotions (?limit=3)
│   │   ├── get_profile.php             # User profile data
│   │   ├── update_profile.php          # Update user info
│   │   ├── upload.php                  # S3 proxy upload
│   │   ├── get_image.php               # S3 proxy download
│   │   └── check_activation.php        # User status check
│   ├── config/
│   │   ├── database.php                # DB connection
│   │   └── s3_config.php               # AWS S3 credentials
│   └── lib/
│       └── JWTVerifier.php             # Firebase token decoder
└── assets/
    └── images/
        └── company_logo.png            # EDS brand logo
```

---

## 🚀 Setup Instructions

### Prerequisites
- Flutter SDK 3.0+
- PHP 8.0+
- MySQL/MariaDB
- Firebase project
- AWS account (for S3)

### 1. Clone Repository
```bash
git clone <repository-url>
cd eds_app
```

### 2. Flutter Setup
```bash
# Install dependencies
flutter pub get

# Run app (Android emulator)
flutter run
```

### 3. Firebase Configuration
1. Create Firebase project at [console.firebase.google.com](https://console.firebase.google.com)
2. Download `google-services.json` (Android) and `GoogleService-Info.plist` (iOS)
3. Place files in respective platform folders
4. Enable Authentication providers (Email, Google, Apple)

### 4. Database Setup
```sql
-- Create database
CREATE DATABASE eds_db;

-- Users table
CREATE TABLE users (
  id INT AUTO_INCREMENT PRIMARY KEY,
  firebase_uid VARCHAR(255) UNIQUE NOT NULL,
  email VARCHAR(255) NOT NULL,
  name VARCHAR(255),
  profile_image_url TEXT,
  login_method VARCHAR(20) DEFAULT 'email',
  status ENUM('active', 'inactive') DEFAULT 'inactive',
  role ENUM('user', 'admin') DEFAULT 'user',
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Promotions table
CREATE TABLE promotions (
  id INT AUTO_INCREMENT PRIMARY KEY,
  user_id INT,
  image_url TEXT NOT NULL,
  description TEXT,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (user_id) REFERENCES users(id)
);

-- Invoices table
CREATE TABLE invoices (
  id INT AUTO_INCREMENT PRIMARY KEY,
  user_id INT,
  invoice_number VARCHAR(50) NOT NULL,
  amount DECIMAL(10,2),
  pdf_url TEXT,
  status ENUM('paid', 'pending', 'overdue') DEFAULT 'pending',
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (user_id) REFERENCES users(id)
);
```

### 5. Backend Configuration

#### Database Config (`backend/config/database.php`)
```php
<?php
class Database {
    private $host = "localhost";
    private $db_name = "eds_db";
    private $username = "root";
    private $password = "your_password";
    // ...
}
```

#### S3 Config (`backend/config/s3_config.php`)
```php
<?php
return [
    'region' => 'your-region',
    'bucket' => 'your-bucket-name',
    'credentials' => [
        'key' => 'your-access-key',
        'secret' => 'your-secret-key',
    ],
];
```

### 6. Start Backend Server
```bash
cd backend
php -S 0.0.0.0:8000
```

### 7. Update API Endpoints
In Flutter files, update `http://10.0.2.2:8000` to your server:
- Local development: `http://10.0.2.2:8000` (Android emulator)
- Production: `https://your-domain.com`

---

## 🔑 API Endpoints

| Endpoint | Method | Description | Auth |
|----------|--------|-------------|------|
| `/api/verify_token.php` | POST | Verify Firebase token, create/fetch user | Token |
| `/api/get_promotions.php?limit=3` | GET | Fetch promotions (supports limit) | None |
| `/api/get_profile.php` | POST | Get user profile data | Token |
| `/api/update_profile.php` | POST | Update user name/picture | Token |
| `/api/upload.php` | POST | Upload file to S3 (proxy) | Token |
| `/api/get_image.php?key=path` | GET | Download file from S3 (proxy) | None |
| `/api/check_activation.php` | POST | Check user activation status | Token |

**Authentication:** Include `idToken` in request body for protected endpoints.

---

## 🎨 Design Features

### Login Screen
- **Gradient background** - EDS blue and red hints
- **App branding** - Logo icon with circular blue background
- **Branded buttons** - Royal blue for login, outlined for register
- **Social login** - Google and Apple buttons
- **Error handling** - Red alert box for errors

### Dashboard
- **Branded AppBar** - EDS royal blue with white text, left-aligned
- **Welcome header** - Greeting with subtext
- **Latest promotions** - 3 polished cards with images
- **Card styling** - Light blue tint (#F5F7FF), elevation 3
- **Rounded corners** - 12px border radius
- **User avatars** - Profile pictures or initials
- **Pull-to-refresh** - Intuitive gesture
- **Empty state** - "No promotions available" message

### Promotions Screen
- **Branded AppBar** - EDS royal blue, left-aligned title
- **Light background** - Subtle blue-gray (#F0F3FF)
- **Two-tone card header** - Royal blue background with white text
- **Enhanced card design** - Light blue tint, elevation 8
- **Navigation arrows** - EDS royal blue icons in circular buttons
- **User information** - Avatar with semi-transparent background
- **Description section** - EDS blue icon with clean typography
- **Fullscreen viewer** - Black background with zoom controls
- **Page indicator** - Current position overlay

### Profile Screen
- **Branded AppBar** - EDS royal blue with white text, left-aligned
- **Avatar styling** - Light EDS blue background (#E8EAF6)
- **Camera button** - EDS royal blue circular overlay
- **Login method badge** - Email/Google/Apple with EDS blue icon
- **Conditional password** - Only for email users
- **Collapsible form** - Tap button to expand
- **EDS blue buttons** - All action buttons use brand color
- **Edit mode** - Toggle to edit profile info
- **Upload indicator** - Loading state for S3 uploads

### Navigation
- **Bottom nav styling** - EDS royal blue for selected tab
- **Consistent branding** - All icons and labels match theme
- **Fixed type** - All tabs always visible

---

## 🔒 Security

- **Firebase token verification** - All protected endpoints
- **JWTVerifier class** - Custom Firebase token decoder
- **Login method tracking** - Prevents unauthorized password changes
- **S3 proxy** - Backend handles AWS credentials
- **Password reauthentication** - Required before password change
- **Status-based access** - Inactive users see QR screen

---

## 📱 Supported Platforms

- ✅ **Android** - Fully tested
- ✅ **iOS** - Ready (Apple Sign-In configured)
- ⏳ **Web** - Needs testing/adjustments

---

## ✅ Recent Updates (December 2025)

### UI Polish & Branding
- ✅ **Consistent EDS branding** - All screens use royal blue (#3F51B5)
- ✅ **Card styling** - Light blue tint across dashboard and promotions
- ✅ **AppBar consistency** - Left-aligned titles, white text on blue
- ✅ **Two-tone design** - Promotions header with blue/white contrast
- ✅ **Enhanced elevation** - Better visual hierarchy
- ✅ **Branded icons** - All interactive elements use EDS colors
- ✅ **Bottom nav styling** - EDS blue for selected state
- ✅ **Profile components** - Complete EDS blue integration

---

## 🚧 Future Enhancements

### High Priority
- [x] **Admin panel** - Web interface for user/content management (Completed v1.2.0)
- [ ] **Invoice screen** - Complete invoice listing with filters
- [ ] **Push notifications** - Account approval, new promotions
- [ ] **Search functionality** - Search promotions and invoices

### Medium Priority
- [ ] **Enhanced loading states** - Skeleton loaders with shimmer effect
- [ ] **Card animations** - Fade-in effects and micro-interactions
- [ ] **Dark mode** - Theme switcher
- [ ] **Offline support** - Cache data locally
- [ ] **Analytics** - Track user engagement
- [ ] **Multi-language** - i18n support

### Low Priority
- [ ] **Page transitions** - Smooth screen animations
- [ ] **Empty state illustrations** - Branded graphics
- [ ] **Export invoices** - PDF download
- [ ] **Favorites** - Bookmark promotions

---


## 🔧 Troubleshooting

### "Connection Closed" Error (Images)
If you encounter `HttpException: Connection closed while receiving data` when loading images:
- **Cause**: PHP built-in server buffering issues with large files.
- **Fix**: The backend now uses **Streaming** (`SimpleS3::getObjectStream`) to pipe data directly.
- **Action**: Ensure you are using the latest backend code and restart the PHP server.

### Android Network Issues
If the app cannot connect to the local backend (`10.0.2.2`):
- **Manifest**: Ensure `AndroidManifest.xml` has:
  ```xml
  <uses-permission android:name="android.permission.INTERNET" />
  <application ... android:usesCleartextTraffic="true">
  ```
- **Emulator**: Ensure the emulator has internet access.

---

## 📝 Development Notes

### Running the App
```bash
# Development mode
flutter run

# Release mode (Android)
flutter build apk --release

# Release mode (iOS)
flutter build ios --release
```

### Hot Reload
- Press `r` in terminal for hot reload
- Press `R` for hot restart

### Clean Build
```bash
flutter clean
flutter pub get
flutter run
```

---

## 📄 License

Proprietary - E-Document Solutions (EDS)

---

**Last Updated:** December 16, 2025  
**Version:** 1.2.0 - Admin Panel & S3 Proxy Update
