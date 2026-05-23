# PHP Online Hostel Management System - Quick Start Guide

## Technology Stack
- **Backend**: PHP 7.4+ (procedural with MVC pattern)
- **Database**: MySQL 5.7+
- **Frontend**: HTML5, CSS3, JavaScript (ES6+)
- **Server**: Apache with WAMP/LAMP
- **Framework**: None (custom MVC structure)

---

## Project Setup (5 minutes)

### 1. Start WAMP/LAMP
```bash
# Windows - Start WAMP
# Click WAMP icon → Start all services

# Linux - Start LAMP
sudo service apache2 start
sudo service mysql start
```

### 2. Navigate to Project
```bash
cd C:\wamp64\www\online-hostel-management
```

### 3. Create Database
```bash
# Option 1: Using phpMyAdmin
# Go to http://localhost/phpmyadmin
# Create new database: hostel_management

# Option 2: Using command line
mysql -u root -p < database/schema.sql
```

### 4. Configure Application
```bash
# Copy example config
cp config/config.php.example config/config.php

# Edit config/config.php with your database credentials
nano config/config.php
```

### 5. Test Installation
```
Open http://localhost/online-hostel-management/public/
You should see the home page
```

---

## File Structure Explained

```
public/                    ← Browser accesses files here
├── index.php             ← Home page
├── rooms.php             ← Browse rooms
├── auth/
│   ├── login.php         ← Login page
│   └── register.php      ← Registration page
└── assets/
    ├── css/              ← Stylesheets
    ├── js/               ← JavaScript files
    └── images/           ← Images

app/
├── models/               ← Database classes
│   └── User.php
├── controllers/          ← Business logic
│   └── AuthController.php
├── services/             ← Reusable services
│   └── AuthService.php
├── middleware/           ← Request filters
│   └── AuthMiddleware.php
└── helpers/              ← Utility functions
    └── functions.php

config/
└── config.php            ← Database & app config

database/
└── schema.sql            ← Database tables

views/
├── layouts/              ← Templates
│   ├── header.php
│   └── footer.php
└── components/           ← Reusable HTML
    └── room-card.php
```

---

## Key Concepts

### 1. Session Management (Authentication)

```php
// Start session at top of PHP file
<?php
session_start();

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: /public/auth/login.php');
    exit;
}

// Get logged-in user
$user_id = $_SESSION['user_id'];
$user_role = $_SESSION['user_role']; // 'student' or 'admin'
```

### 2. Database Connection

```php
<?php
require_once 'config/config.php';

$conn = new mysqli(DB_HOST, DB_USER, DB_PASSWORD, DB_NAME);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Use prepared statements to prevent SQL injection
$stmt = $conn->prepare("SELECT * FROM users WHERE email = ?");
$stmt->bind_param("s", $email);
$stmt->execute();
$result = $stmt->get_result();
```

### 3. Form Handling

```php
<?php
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $name = htmlspecialchars($_POST['name']);
    $email = filter_var($_POST['email'], FILTER_SANITIZE_EMAIL);
    
    // Validate input
    if (empty($name) || empty($email)) {
        $error = "All fields required";
    } else {
        // Process form
        // Insert to database
    }
}
?>

<form method="POST" action="">
    <input type="text" name="name" required>
    <input type="email" name="email" required>
    <button type="submit">Submit</button>
</form>
```

### 4. CSRF Protection

```php
<?php
// Generate CSRF token
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Include in form
echo '<input type="hidden" name="csrf_token" value="' . $_SESSION['csrf_token'] . '">';

// Verify on submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (!hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
        die('CSRF token validation failed');
    }
}
?>
```

### 5. Password Security

```php
<?php
// Hash password (on registration)
$hashed = password_hash($password, PASSWORD_BCRYPT);

// Verify password (on login)
if (password_verify($password, $hashed)) {
    // Correct password
} else {
    // Wrong password
}
?>
```

### 6. File Upload (Room Photos)

```php
<?php
if ($_FILES['photo']) {
    $file = $_FILES['photo'];
    $upload_dir = 'uploads/rooms/';
    
    // Validate file
    if ($file['size'] > 5000000) { // 5MB max
        $error = "File too large";
    } elseif (!in_array($file['type'], ['image/jpeg', 'image/png'])) {
        $error = "Only JPEG/PNG allowed";
    } else {
        // Move uploaded file
        $filename = uniqid() . '_' . $file['name'];
        move_uploaded_file($file['tmp_name'], $upload_dir . $filename);
    }
}
?>
```

### 7. Error Handling

```php
<?php
// Display error message
if (isset($error)) {
    echo '<div class="alert alert-danger">' . htmlspecialchars($error) . '</div>';
}

// Display success message
if (isset($success)) {
    echo '<div class="alert alert-success">' . htmlspecialchars($success) . '</div>';
}
?>
```

---

## Common Tasks

### Display All Rooms
```php
<?php
$sql = "SELECT * FROM rooms WHERE status = 'available'";
$result = $conn->query($sql);

while ($room = $result->fetch_assoc()) {
    echo '<div class="room-card">';
    echo '<h3>' . htmlspecialchars($room['room_number']) . '</h3>';
    echo '<p>Price: ' . $room['price_per_semester'] . '</p>';
    echo '</div>';
}
?>
```

### Search with Filters
```php
<?php
$sql = "SELECT * FROM rooms WHERE status = 'available'";

if (isset($_GET['room_type']) && !empty($_GET['room_type'])) {
    $type = $conn->real_escape_string($_GET['room_type']);
    $sql .= " AND room_type = '$type'";
}

if (isset($_GET['max_price']) && !empty($_GET['max_price'])) {
    $price = (int)$_GET['max_price'];
    $sql .= " AND price_per_semester <= $price";
}

$result = $conn->query($sql);
?>
```

### Create Booking
```php
<?php
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $user_id = $_SESSION['user_id'];
    $room_id = (int)$_POST['room_id'];
    $check_in = $_POST['check_in_date'];
    
    // Generate booking code
    $booking_code = strtoupper(substr(uniqid(), -8));
    
    // Insert booking
    $stmt = $conn->prepare("INSERT INTO bookings (user_id, room_id, booking_code, check_in_date, status) VALUES (?, ?, ?, ?, 'pending')");
    $stmt->bind_param("iiss", $user_id, $room_id, $booking_code, $check_in);
    
    if ($stmt->execute()) {
        $success = "Booking created! Code: " . $booking_code;
    } else {
        $error = "Error creating booking";
    }
}
?>
```

### Generate QR Code
```php
<?php
// Include QR code library
require_once 'app/lib/qrcode/QRCode.php';

// Generate QR code
$qr = new \chillerlan\QRCode\QRCode();
$qr->setWriterOptions(['imageBase64' => true]);
$qrCode = $qr->render('Booking:' . $booking_code);

// Display as image
echo '<img src="' . $qrCode . '" />';
?>
```

### Send Email Notification
```php
<?php
use PHPMailer\PHPMailer\PHPMailer;

require 'vendor/autoload.php';

$mail = new PHPMailer();
$mail->isSMTP();
$mail->Host = MAIL_HOST;
$mail->SMTPAuth = true;
$mail->Username = MAIL_USERNAME;
$mail->Password = MAIL_PASSWORD;
$mail->SMTPSecure = 'tls';
$mail->Port = MAIL_PORT;

$mail->setFrom(MAIL_FROM, APP_NAME);
$mail->addAddress($student_email);
$mail->Subject = 'Booking Confirmation';
$mail->Body = 'Your booking has been confirmed!';

if ($mail->send()) {
    echo 'Email sent successfully';
}
?>
```

---

## JavaScript Essentials

### Form Validation
```javascript
// Validate email
function validateEmail(email) {
    const regex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    return regex.test(email);
}

// Validate form before submit
document.getElementById('myForm').addEventListener('submit', function(e) {
    const email = document.getElementById('email').value;
    if (!validateEmail(email)) {
        e.preventDefault();
        alert('Invalid email');
    }
});
```

### AJAX for Filtering
```javascript
// Fetch rooms via AJAX
fetch('api/rooms.php?room_type=single&max_price=500')
    .then(response => response.json())
    .then(data => {
        console.log(data);
        // Display rooms
    });
```

### Show/Hide Elements
```javascript
// Toggle element visibility
document.getElementById('filterBtn').addEventListener('click', function() {
    const filter = document.getElementById('filter');
    filter.style.display = filter.style.display === 'none' ? 'block' : 'none';
});
```

### Image Preview
```javascript
// Preview uploaded image
document.getElementById('photoInput').addEventListener('change', function(e) {
    const file = e.target.files[0];
    const reader = new FileReader();
    
    reader.onload = function(e) {
        document.getElementById('preview').src = e.target.result;
    };
    
    reader.readAsDataURL(file);
});
```

---

## CSS Responsive Design

### Mobile-First Approach
```css
/* Mobile (default) */
.room-card {
    width: 100%;
    padding: 10px;
}

/* Tablet */
@media (min-width: 768px) {
    .room-card {
        width: 50%;
        padding: 15px;
    }
}

/* Desktop */
@media (min-width: 1024px) {
    .room-card {
        width: 33.33%;
        padding: 20px;
    }
}
```

---

## Deployment Checklist

Before going live:

- [ ] Test all forms and validations
- [ ] Test payment flow in sandbox mode
- [ ] Check all database queries
- [ ] Verify file upload paths
- [ ] Test email sending
- [ ] Set proper file permissions (uploads/ = 755)
- [ ] Configure error logging
- [ ] Set up database backups
- [ ] Enable HTTPS
- [ ] Test on different browsers
- [ ] Test on mobile devices
- [ ] Verify CSRF protection works
- [ ] Check password security
- [ ] Test SQL injection prevention

---

## Debugging Tips

### Enable Error Reporting
```php
<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
?>
```

### Log Errors to File
```php
<?php
error_log("Error message here", 3, "logs/errors.log");
?>
```

### Debug Variables
```php
<?php
echo '<pre>';
print_r($array);
echo '</pre>';

// Or
var_dump($variable);
?>
```

### Check Database Connection
```php
<?php
if ($conn->connect_error) {
    die("Connection Error: " . $conn->connect_error);
}
echo "Connected successfully";
?>
```

---

## Performance Tips

1. **Use Indexes** on frequently queried columns:
   ```sql
   ALTER TABLE users ADD INDEX idx_email (email);
   ```

2. **Optimize Images**: Use appropriate formats and sizes

3. **Cache Data**: Store frequently accessed data in variables

4. **Minimize Database Queries**: Combine queries when possible

5. **Use Prepared Statements**: Prevents SQL injection and improves performance

---

## Useful Commands

```bash
# Start services
sudo service apache2 start
sudo service mysql start

# Stop services
sudo service apache2 stop
sudo service mysql stop

# Access MySQL
mysql -u root -p hostel_management

# View error logs
tail -f /var/log/apache2/error.log
```

---

## Resources

- [PHP Documentation](https://www.php.net/manual/)
- [MySQL Tutorial](https://dev.mysql.com/doc/)
- [Bootstrap Documentation](https://getbootstrap.com/docs/)
- [JavaScript MDN](https://developer.mozilla.org/en-US/docs/Web/JavaScript/)

---

**Happy Coding! 🚀**

For questions or issues, refer to DEVELOPMENT_PLAN.md or PROJECT_INITIALIZATION_CHECKLIST.md
