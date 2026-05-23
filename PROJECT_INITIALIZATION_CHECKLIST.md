# Online Hostel Management System - Project Initialization Checklist

## Pre-Development Setup

### 1. Version Control & Repository
- [ ] Create GitHub repository (private/public)
- [ ] Clone repository to local machine
- [ ] Set up .gitignore file
- [ ] Create initial README.md
- [ ] Set up branch protection rules (if team project)

### 2. Technology & Tools Installation
- [ ] Install Node.js (v16+ recommended)
- [ ] Install npm or yarn
- [ ] Install MySQL Server (if not using WAMP)
- [ ] Install Visual Studio Code (if not already installed)
- [ ] Install Git
- [ ] Install Postman for API testing
- [ ] Install MySQL Workbench or phpMyAdmin

### 3. Project Folder Structure Setup
```
Create the following structure in c:\wamp64\www\online\:

online-hostel-management/
├── backend/
├── frontend/
├── database/
├── docs/
├── .gitignore
├── README.md
└── DEVELOPMENT_PLAN.md
```

- [ ] Create all folders as per structure

### 4. PHP & Web Environment Setup

#### Step 1: Verify WAMP/LAMP Installation
- [ ] Apache is running
- [ ] MySQL server is running
- [ ] PHP 7.4+ is installed
- [ ] Verify: Open `http://localhost/` in browser (should see WAMP/LAMP home page)

#### Step 2: Configure Apache for URL Rewriting
1. Enable mod_rewrite module:
   - Go to `C:\wamp64\bin\apache\apache2.4.x\conf`
   - Open `httpd.conf`
   - Uncomment: `LoadModule rewrite_module modules/mod_rewrite.so`
   - Restart Apache

2. Create `.htaccess` file in project root (`public/`):
```apache
<IfModule mod_rewrite.c>
    RewriteEngine On
    RewriteBase /online-hostel-management/public/
    RewriteCond %{REQUEST_FILENAME} !-f
    RewriteCond %{REQUEST_FILENAME} !-d
    RewriteRule ^(.*)$ index.php?page=$1 [QSA,L]
</IfModule>
```

#### Step 3: Set up Project Directories
- [ ] Copy project files to `C:\wamp64\www\online-hostel-management\`
- [ ] Create all folders as per project structure
- [ ] Create `uploads/` folder with 755 permissions
- [ ] Create `logs/` folder with 755 permissions
- [ ] Create `cache/` folder with 755 permissions

#### Step 4: Create config/config.php
```php
<?php
// Database Configuration
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASSWORD', '');
define('DB_NAME', 'hostel_management');

// App Configuration
define('APP_NAME', 'Pearls of Wisdom Hostel Management System');
define('APP_URL', 'http://localhost/online-hostel-management/public/');
define('APP_ENV', 'development');

// Security
define('CSRF_TOKEN_LENGTH', 32);
define('SESSION_TIMEOUT', 3600); // 1 hour

// Payment Configuration (Pesapal/Flutterwave)
define('PAYMENT_API_KEY', 'your_api_key_here');
define('PAYMENT_SECRET_KEY', 'your_secret_key_here');
define('PAYMENT_MODE', 'sandbox'); // sandbox or live

// Email Configuration
define('MAIL_HOST', 'smtp.gmail.com');
define('MAIL_PORT', 587);
define('MAIL_USERNAME', 'your_email@gmail.com');
define('MAIL_PASSWORD', 'your_app_password');
define('MAIL_FROM', 'noreply@pearlsofwisdom.com');

// Paths
define('BASE_PATH', dirname(__DIR__));
define('APP_PATH', BASE_PATH . '/app');
define('PUBLIC_PATH', BASE_PATH . '/public');
define('UPLOADS_PATH', PUBLIC_PATH . '/uploads');
define('LOGS_PATH', BASE_PATH . '/logs');
?>
```

- [ ] Copy to `config/.env.example` (for version control)
- [ ] Fill in actual values in `config/config.php` (don't commit to Git)

### 5. Frontend/PHP Template Setup

#### Step 1: Create Layout Templates
Create `views/layouts/header.php`:
```php
<?php
// Header template
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $page_title ?? 'Pearls of Wisdom Hostel'; ?></title>
    <link href="assets/css/bootstrap.min.css" rel="stylesheet">
    <link href="assets/css/style.css" rel="stylesheet">
    <link href="assets/css/responsive.css" rel="stylesheet">
</head>
<body>
```

Create `views/layouts/footer.php`:
```php
    <footer class="bg-dark text-white mt-5 pt-4">
        <div class="container">
            <p>&copy; 2026 Pearls of Wisdom Hostel. All rights reserved.</p>
        </div>
    </footer>
    <script src="assets/js/jquery.min.js"></script>
    <script src="assets/js/bootstrap.min.js"></script>
    <script src="assets/js/main.js"></script>
</body>
</html>
```

#### Step 2: Create Database Helper
Create `app/helpers/DatabaseHelper.php`:
```php
<?php
class DatabaseHelper {
    private $conn;
    
    public function __construct() {
        $this->connect();
    }
    
    private function connect() {
        $this->conn = new mysqli(DB_HOST, DB_USER, DB_PASSWORD, DB_NAME);
        if ($this->conn->connect_error) {
            die("Database connection failed: " . $this->conn->connect_error);
        }
    }
    
    public function query($sql) {
        return $this->conn->query($sql);
    }
    
    public function prepare($sql) {
        return $this->conn->prepare($sql);
    }
    
    public function getConnection() {
        return $this->conn;
    }
}
?>
```

- [ ] Complete PHP template setup
- [ ] Create all helper files

### 6. Database Setup

#### Step 1: Access MySQL
Using phpMyAdmin (built into WAMP):
1. Go to `http://localhost/phpmyadmin`
2. Login (default: username=root, password=empty)

Or using Command Line:
```bash
mysql -u root -p
```

#### Step 2: Create Database
```sql
CREATE DATABASE IF NOT EXISTS hostel_management CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE hostel_management;
```

#### Step 3: Run database/schema.sql
- [ ] Include all table definitions from DEVELOPMENT_PLAN
- [ ] Add proper indexes on frequently queried columns
- [ ] Add foreign key constraints

Execute in phpMyAdmin or command line:
```bash
mysql -u root hostel_management < database/schema.sql
```

#### Step 4: Verify Tables Created
```sql
SHOW TABLES;
```

Expected tables:
- users
- rooms
- room_types
- facilities
- bookings
- payments
- maintenance_requests
- notifications
- receipts

- [ ] Verify all tables created successfully
- [ ] Check table structures with `DESCRIBE table_name;`
- [ ] Verify foreign keys are set up correctly

### 7. Project Documentation

- [ ] Create docs/API_DOCUMENTATION.md (placeholder)
- [ ] Create docs/DATABASE_SCHEMA.md
- [ ] Create docs/SETUP_GUIDE.md
- [ ] Create docs/USER_MANUAL.md
- [ ] Create docs/ADMIN_GUIDE.md
- [ ] Create docs/DEPLOYMENT_GUIDE.md

### 8. Environment Configuration

#### PHP Configuration
- [ ] Copy config/.env.example to config/config.php
- [ ] Fill in database credentials
- [ ] Set APP_URL to your local/production URL
- [ ] Configure payment API credentials
- [ ] Configure email settings

#### Browser & Server
- [ ] Test Apache is serving PHP files correctly
- [ ] Test database connection with test script
- [ ] Verify uploads folder has write permissions
- [ ] Verify logs folder has write permissions

---

## Development Phase Checklist

## Development Phase Checklist

### Phase 1: Foundation & Setup (Week 1-2)

**Backend/PHP Structure:**
- [ ] Set up project folder structure as per DEVELOPMENT_PLAN
- [ ] Create config/config.php with database connection
- [ ] Create app/helpers/DatabaseHelper.php for DB connections
- [ ] Create app/helpers/functions.php with utility functions
- [ ] Set up error handling and logging system
- [ ] Create constants file (app/helpers/constants.php)
- [ ] Set up .htaccess for URL rewriting (if needed)
- [ ] Create simple index.php entry point

**Frontend/HTML/CSS/JS:**
- [ ] Create views/layouts/header.php template
- [ ] Create views/layouts/footer.php template
- [ ] Create views/layouts/navbar.php
- [ ] Set up assets/css/style.css (base styles)
- [ ] Set up assets/css/responsive.css (mobile styles)
- [ ] Set up assets/js/main.js (global JavaScript)
- [ ] Add Bootstrap CSS/JS to assets

**Database Tasks:**
- [ ] Review database schema design
- [ ] Create database in MySQL
- [ ] Create all tables
- [ ] Add indexes on frequently queried columns
- [ ] Create database backup script

**Project Setup:**
- [ ] Initialize Git repository
- [ ] Create .gitignore file
- [ ] Create README.md
- [ ] Set up project documentation structure

---

### Phase 2: Authentication & User Management (Week 3-4)

**PHP Backend Tasks:**
- [ ] Create app/models/User.php model class
- [ ] Implement user registration logic with password hashing (password_hash)
- [ ] Implement user login logic with session management
- [ ] Create app/services/AuthService.php
- [ ] Implement email verification (basic)
- [ ] Implement password reset functionality
- [ ] Create app/middleware/AuthMiddleware.php for checking login
- [ ] Implement CSRF token generation and validation
- [ ] Create session management functions

**Frontend/HTML Pages:**
- [ ] Create public/auth/login.php page
- [ ] Create public/auth/register.php page
- [ ] Create public/auth/forgot-password.php page
- [ ] Create public/auth/reset-password.php page
- [ ] Add form validation JavaScript (assets/js/form-validation.js)
- [ ] Add style for auth pages

**JavaScript Tasks:**
- [ ] Create form validation script
- [ ] Implement password strength meter
- [ ] Add loading states to forms
- [ ] Add error message display

**PHP Routes to Complete:**
- [ ] POST auth/register.php ✓
- [ ] POST auth/login.php ✓
- [ ] GET auth/logout.php ✓
- [ ] POST auth/forgot-password.php ✓
- [ ] GET auth/verify-email.php ✓

---

### Phase 3: Room Management System (Week 5-6)

**PHP Backend Tasks:**
- [ ] Create app/models/Room.php model
- [ ] Create app/models/RoomType.php model
- [ ] Create app/models/Facility.php model
- [ ] Create app/controllers/RoomController.php
- [ ] Implement room CRUD operations (Create, Read, Update, Delete)
- [ ] Implement room search with filters
- [ ] Implement availability checking logic
- [ ] Add image upload handling for room photos
- [ ] Create app/services/RoomService.php

**Frontend/HTML Pages:**
- [ ] Create public/rooms.php (browse rooms page)
- [ ] Create public/room-details.php (single room details)
- [ ] Create public/admin/rooms.php (admin management page)
- [ ] Create forms for add/edit rooms
- [ ] Add room filtering JavaScript (filters by type, price, etc.)
- [ ] Add image gallery display

**JavaScript Tasks:**
- [ ] Create assets/js/rooms-filter.js for search/filter
- [ ] Add image lazy loading
- [ ] Add room photo carousel
- [ ] Add AJAX calls for filtering (optional)

**Routes to Complete:**
- [ ] GET rooms.php ✓
- [ ] GET room-details.php?id=X ✓
- [ ] GET admin/rooms.php ✓
- [ ] POST admin/add-room.php ✓
- [ ] POST admin/edit-room.php ✓
- [ ] POST admin/delete-room.php ✓

---

### Phase 4: Booking Engine (Week 7-8)

**PHP Backend Tasks:**
- [ ] Create app/models/Booking.php model
- [ ] Create app/controllers/BookingController.php
- [ ] Implement booking creation with validation
- [ ] Implement booking code generation (unique alphanumeric)
- [ ] Implement QR code generation (library: phpqrcode or external API)
- [ ] Implement check-in/check-out functionality
- [ ] Prevent double bookings (database constraints + PHP logic)
- [ ] Implement booking status management
- [ ] Create app/services/BookingService.php

**Frontend/HTML Pages:**
- [ ] Create public/booking.php?room_id=X (booking form)
- [ ] Create public/booking-confirmation.php (after booking)
- [ ] Create public/dashboard.php (student dashboard)
- [ ] Create public/bookings.php (view all bookings)
- [ ] Create public/admin/bookings.php (admin view)
- [ ] Add booking form with step-by-step wizard (optional)

**JavaScript Tasks:**
- [ ] Create assets/js/booking.js for booking workflow
- [ ] Add form validation for booking
- [ ] Add QR code display
- [ ] Add booking confirmation animation

**Routes to Complete:**
- [ ] GET booking.php?room_id=X ✓
- [ ] POST process-booking.php ✓
- [ ] GET booking-confirmation.php ✓
- [ ] GET bookings.php ✓
- [ ] GET receipt.php?booking_id=X ✓

---

### Phase 5: Payment Processing (Week 9-10)

**PHP Backend Tasks:**
- [ ] Create app/models/Payment.php model
- [ ] Create app/services/PaymentService.php
- [ ] Integrate payment gateway (Pesapal/Flutterwave)
- [ ] Implement payment initiation
- [ ] Implement payment verification endpoint
- [ ] Create receipt generation logic with unique code
- [ ] Implement payment status tracking
- [ ] Handle payment callbacks from gateway
- [ ] Implement e-receipt download (PDF generation or HTML)

**Frontend/HTML Pages:**
- [ ] Create public/payment/initiate.php (payment page)
- [ ] Create public/payment/verify.php (payment verification)
- [ ] Create public/payment/receipt.php (display e-receipt)
- [ ] Create public/admin/payments.php (admin payment view)
- [ ] Add payment confirmation page

**JavaScript Tasks:**
- [ ] Create assets/js/payment.js
- [ ] Add payment form handling
- [ ] Add receipt download functionality
- [ ] Add payment status polling (optional)

**Libraries to Integrate:**
- [ ] Pesapal PHP SDK or Flutterwave PHP SDK
- [ ] TCPDF or similar for PDF receipt generation
- [ ] QRCode library for QR code in receipt

**Routes to Complete:**
- [ ] POST payment/initiate.php ✓
- [ ] GET payment/verify.php ✓
- [ ] GET payment/receipt.php ✓
- [ ] GET admin/payments.php ✓

---

### Phase 6: Notifications & Communication (Week 11-12)

**PHP Backend Tasks:**
- [ ] Create app/models/Notification.php model
- [ ] Set up PHPMailer or Swift Mailer for emails
- [ ] Set up SMS service (if available locally)
- [ ] Create app/services/EmailService.php
- [ ] Create app/services/NotificationService.php
- [ ] Implement email triggers (booking confirmation, payment)
- [ ] Implement SMS triggers (payment confirmation)
- [ ] Create notification preferences storage

**Frontend/HTML Pages:**
- [ ] Create public/notifications.php (notifications page)
- [ ] Create public/profile.php (notification preferences)
- [ ] Add notification display in navbar
- [ ] Create admin notification management page

**PHP Routes:**
- [ ] GET notifications.php ✓
- [ ] POST notifications/mark-read.php ✓
- [ ] POST profile/update-preferences.php ✓

---

### Phase 7: Maintenance Requests (Week 13)

**PHP Backend Tasks:**
- [ ] Create app/models/MaintenanceRequest.php
- [ ] Create app/controllers/MaintenanceController.php
- [ ] Implement request creation
- [ ] Implement status update logic
- [ ] Notify admin of new requests

**Frontend/HTML Pages:**
- [ ] Create public/maintenance.php (submit request)
- [ ] Create public/admin/maintenance.php (admin view)
- [ ] Add form for maintenance requests
- [ ] Add status tracking display

**Routes:**
- [ ] POST maintenance/submit.php ✓
- [ ] GET admin/maintenance.php ✓
- [ ] POST admin/maintenance/update.php ✓

---

### Phase 8: Reporting & Analytics (Week 14)

**PHP Backend Tasks:**
- [ ] Create app/services/ReportService.php
- [ ] Implement occupancy statistics queries
- [ ] Implement revenue calculations
- [ ] Implement student statistics
- [ ] Create PDF/CSV export functionality
- [ ] Implement chart data generation (JSON for charting library)

**Frontend/HTML Pages:**
- [ ] Create public/admin/dashboard.php (main dashboard)
- [ ] Create public/admin/reports.php (detailed reports)
- [ ] Add charts using Chart.js or similar
- [ ] Add export buttons (PDF/CSV)

**JavaScript Tasks:**
- [ ] Add Chart.js or similar library for graphs
- [ ] Implement chart rendering
- [ ] Add date range filters for reports

---

### Phase 9: Frontend - Student Portal (Week 15-18)

**Pages to Create:**
- [ ] public/index.php (Home page with featured rooms)
- [ ] public/rooms.php (Browse/search rooms)
- [ ] public/room-details.php (Room information)
- [ ] public/booking.php (Booking process)
- [ ] public/payment/initiate.php (Payment page)
- [ ] public/booking-confirmation.php (Confirmation)
- [ ] public/dashboard.php (Student dashboard)
- [ ] public/bookings.php (Booking history)
- [ ] public/profile.php (Profile management)
- [ ] public/maintenance.php (Submit requests)
- [ ] public/notifications.php (View notifications)

**Features:**
- [ ] Responsive design (mobile-first)
- [ ] Search and filtering
- [ ] Image gallery for rooms
- [ ] Booking wizard (step-by-step)
- [ ] Form validation
- [ ] Loading states
- [ ] Error handling
- [ ] Success messages

**Styling:**
- [ ] Complete assets/css/style.css
- [ ] Complete assets/css/responsive.css
- [ ] Test on mobile, tablet, desktop

---

### Phase 10: Frontend - Admin Dashboard (Week 19-21)

**Pages to Create:**
- [ ] public/admin/index.php (Dashboard overview)
- [ ] public/admin/rooms.php (Room management)
- [ ] public/admin/bookings.php (Booking management)
- [ ] public/admin/students.php (Student management)
- [ ] public/admin/payments.php (Payment tracking)
- [ ] public/admin/maintenance.php (Maintenance requests)
- [ ] public/admin/reports.php (Reports & analytics)
- [ ] public/admin/notifications.php (Send notifications)
- [ ] public/admin/settings.php (System settings)

**Features:**
- [ ] Dashboard with key statistics
- [ ] Data tables with sorting/pagination
- [ ] Charts and graphs
- [ ] Search and filtering
- [ ] Bulk operations (if needed)
- [ ] Edit/delete functionality
- [ ] Export to CSV/PDF
- [ ] Responsive admin layout

**Admin Styling:**
- [ ] Create assets/css/admin.css
- [ ] Create assets/js/admin.js (table operations, modals)
- [ ] Add admin-specific components

---

### Phase 11: Testing (Week 22-23)

**Backend Testing:**
- [ ] Install PHPUnit via Composer
- [ ] Write unit tests for Model classes
- [ ] Write tests for Service classes
- [ ] Write integration tests for main workflows
- [ ] Test authentication logic
- [ ] Test payment flow
- [ ] Test data validation
- [ ] Test CSRF protection
- [ ] Test SQL injection prevention

**Frontend Testing:**
- [ ] Manual testing of all pages
- [ ] Test form submissions
- [ ] Test validation messages
- [ ] Test error handling
- [ ] Browser compatibility testing (Chrome, Firefox, Safari, Edge)
- [ ] Mobile responsiveness testing
- [ ] Test on different screen sizes

**Testing Commands:**
```bash
# If using Composer
composer require --dev phpunit/phpunit

# Run tests
php vendor/bin/phpunit tests/
```

**Checklist:**
- [ ] All auth workflows tested
- [ ] All CRUD operations tested
- [ ] Booking workflow tested end-to-end
- [ ] Payment flow tested (sandbox mode)
- [ ] No SQL injection vulnerabilities
- [ ] No XSS vulnerabilities
- [ ] Forms validate correctly
- [ ] Error messages display properly

---

### Phase 12: Deployment & Documentation (Week 24)

**Deployment Tasks:**
- [ ] Set up production server (if needed)
- [ ] Configure production database
- [ ] Update config/config.php for production
- [ ] Set up SSL certificate (HTTPS)
- [ ] Configure Apache for production
- [ ] Set proper file permissions
- [ ] Create database backups
- [ ] Test all functionality on production
- [ ] Set up error logging
- [ ] Set up monitoring/alerts (optional)

**Documentation Tasks:**
- [ ] Complete API/Routes documentation
- [ ] Create USER_MANUAL.md (student guide)
- [ ] Create ADMIN_GUIDE.md (admin guide)
- [ ] Create DATABASE_SCHEMA.md with all tables
- [ ] Create SETUP_GUIDE.md for installation
- [ ] Create TROUBLESHOOTING.md
- [ ] Create DEPLOYMENT_GUIDE.md
- [ ] Add code comments in PHP files
- [ ] Create video tutorials (optional)

**Final Checklist:**
- [ ] All features tested
- [ ] Documentation complete
- [ ] Performance optimized
- [ ] Security hardened
- [ ] Backups configured
- [ ] Error logging setup
- [ ] Ready for live deployment

---


## Weekly Progress Template

### Week [X] Summary
**Completed:**
- [ ] Task 1
- [ ] Task 2

**In Progress:**
- [ ] Task 3

**Blockers:**
- Issue 1
- Issue 2

**Next Week Plans:**
- Task 4
- Task 5

---

## Important Links & Resources

### PHP Backend
- [PHP Official Documentation](https://www.php.net/manual/en/)
- [MySQLi Documentation](https://www.php.net/manual/en/book.mysqli.php)
- [Password Hashing](https://www.php.net/manual/en/function.password-hash.php)
- [PHPMailer](https://github.com/PHPMailer/PHPMailer)
- [PHPUnit Testing](https://phpunit.de/)

### Frontend
- [HTML5 MDN Reference](https://developer.mozilla.org/en-US/docs/Web/HTML)
- [CSS3 MDN Reference](https://developer.mozilla.org/en-US/docs/Web/CSS)
- [JavaScript MDN Reference](https://developer.mozilla.org/en-US/docs/Web/JavaScript)
- [Bootstrap 5 Documentation](https://getbootstrap.com/docs/5.0/)
- [Chart.js](https://www.chartjs.org/)

### Libraries & Tools
- [Postman](https://www.postman.com/) - API testing
- [phpMyAdmin](https://www.phpmyadmin.net/) - Database management
- [Visual Studio Code](https://code.visualstudio.com/) - Code editor
- [Git Documentation](https://git-scm.com/doc) - Version control
- [Composer](https://getcomposer.org/) - PHP dependency manager
- [QR Code Generator](https://davidshimjs.github.io/qrcodejs/)
- [TCPDF](https://tcpdf.org/) - PDF generation

### Payment Integration
- [Pesapal API Documentation](https://developer.pesapal.com/)
- [Flutterwave API Documentation](https://developer.flutterwave.com/)

### Security & Best Practices
- [OWASP PHP Security](https://owasp.org/www-community/attacks/)
- [SQL Injection Prevention](https://owasp.org/www-community/attacks/SQL_Injection)
- [XSS Prevention](https://owasp.org/www-community/attacks/xss/)
- [CSRF Protection](https://owasp.org/www-community/attacks/csrf)

---

## Contact & Support

**Project Team:**
- Wasswa Atibu: wasswaatibu@gmail.com | 0765536881
- Karim Abdul: karimngiragezi@gmail.com | 0706158953

**Instructor:**
- Mr. Baranga Peter

---

**Last Updated**: April 22, 2026  
**Technology Stack**: PHP 7.4+, MySQL, HTML5, CSS3, JavaScript  
**Version**: 2.0 (PHP Edition)

---

**Last Updated**: April 22, 2026  
**Version**: 1.0

