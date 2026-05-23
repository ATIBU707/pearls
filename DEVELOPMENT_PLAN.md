# Online Hostel Management System - Development Plan
**Pearls of Wisdom Hostel Case Study**  
**Project: BCS 2204 - Individual Programming Project**  
**Students: Wasswa Atibu & Karim Abdul**

---

## 1. Project Overview

### Main Objective
Develop a comprehensive Online Hostel Management System that digitizes the booking and management process for Pearls of Wisdom Hostel, replacing the manual counter-based system.

### Key Features
- **Student Portal**: Room browsing, booking, secure mobile money payments, e-receipts, QR codes, personal dashboard
- **Admin Dashboard**: Room management, occupancy tracking, booking/payment management, notifications, reporting
- **Security**: Secure authentication, encrypted payments, user data protection
- **Real-time Updates**: Live room availability, instant confirmations, automated notifications

---

## 2. Technology Stack Recommendations

### Frontend
- **Language**: HTML5, CSS3, JavaScript (ES6+)
- **Framework/Library**: Vanilla JavaScript or jQuery (optional)
- **Styling**: Bootstrap 5 for responsive design
- **Form Validation**: HTML5 validation + JavaScript validation
- **QR Code Generation**: QRCode.js library
- **Icons**: Font Awesome or Material Icons

### Backend
- **Language**: PHP 7.4 or 8.0+
- **Architecture**: MVC (Model-View-Controller) with modular structure
- **Database**: MySQL 5.7+ or MariaDB
- **Server**: Apache with mod_rewrite enabled (WAMP/LAMP)
- **Payment Integration**: Pesapal/Flutterwave PHP SDK (for MTN MoMo & Airtel Money)
- **Authentication**: PHP Sessions + CSRF tokens
- **Email**: PHPMailer or Swift Mailer
- **File Storage**: Local server storage with organized folders
- **Security**: Password hashing with password_hash(), prepared statements

### Additional Tools
- **Version Control**: Git/GitHub
- **API Documentation**: Postman or API Blueprint
- **Testing**: PHPUnit for backend, manual testing for frontend
- **Deployment**: WAMP Stack (Windows Apache MySQL PHP)

---

## 3. System Architecture

```
┌─────────────────────────────────────────────────────────┐
│                   Client Layer                          │
├──────────────────────┬──────────────────────────────────┤
│  Student Portal      │      Admin Dashboard             │
│  HTML/CSS/JS Pages   │  HTML/CSS/JS Pages               │
│  - Login             │  - Room Management               │
│  - Browse Rooms      │  - Booking Management            │
│  - Book Room         │  - Payment Tracking              │
│  - Payments          │  - Reports & Analytics           │
│  - Dashboard         │  - Notifications                 │
└──────────────────────┴──────────────────────────────────┘
              ↓           ↓           ↓
┌─────────────────────────────────────────────────────────┐
│              PHP Backend Layer (MVC)                     │
├─────────────────────────────────────────────────────────┤
│  Controllers (Handle Requests)                          │
│  - AuthController    - BookingController               │
│  - UserController    - PaymentController               │
│  - RoomController    - NotificationController          │
│  - ReportController                                     │
│                                                          │
│  Models (Business Logic & Database)                     │
│  - User, Room, Booking, Payment, Maintenance          │
│                                                          │
│  Views (Template Rendering)                             │
│  - Page templates using PHP                            │
│                                                          │
│  Services (Utilities)                                   │
│  - PaymentService, EmailService, ValidationService    │
└─────────────────────────────────────────────────────────┘
              ↓
┌─────────────────────────────────────────────────────────┐
│          Database Layer (MySQL)                         │
├─────────────────────────────────────────────────────────┤
│  Users | Rooms | Bookings | Payments | Maintenance     │
│  Notifications | Receipts | Room Types | Facilities    │
└─────────────────────────────────────────────────────────┘
```

---

## 4. Database Design

### Core Tables

#### Users Table
```
users
├── user_id (PK)
├── email (UNIQUE)
├── phone_number
├── password_hash
├── first_name
├── last_name
├── identification_type
├── identification_number
├── student_id
├── profile_photo
├── role (student/admin)
├── is_active
├── created_at
└── updated_at
```

#### Rooms Table
```
rooms
├── room_id (PK)
├── room_number (UNIQUE)
├── room_type (single/double/self-contained)
├── capacity
├── price_per_semester
├── description
├── facilities (JSON)
├── photos (JSON array of URLs)
├── status (available/booked/occupied/maintenance)
├── created_at
└── updated_at
```

#### Bookings Table
```
bookings
├── booking_id (PK)
├── user_id (FK)
├── room_id (FK)
├── booking_code (UNIQUE)
├── qr_code
├── check_in_date
├── check_out_date (nullable)
├── semester
├── status (pending/confirmed/checked_in/checked_out)
├── created_at
└── updated_at
```

#### Payments Table
```
payments
├── payment_id (PK)
├── booking_id (FK)
├── amount
├── payment_method (cash/mtn_momo/airtel_money)
├── transaction_reference
├── status (pending/completed/failed)
├── payment_date
├── created_at
└── updated_at
```

#### Maintenance Requests Table
```
maintenance_requests
├── request_id (PK)
├── room_id (FK)
├── user_id (FK)
├── title
├── description
├── priority (low/medium/high)
├── status (open/in_progress/resolved)
├── created_at
└── updated_at
```

#### Room Types Table
```
room_types
├── type_id (PK)
├── type_name
├── description
└── icon_url
```

#### Facilities Table
```
facilities
├── facility_id (PK)
├── facility_name
└── description
```

#### Notifications Table
```
notifications
├── notification_id (PK)
├── user_id (FK)
├── title
├── message
├── type (booking/payment/maintenance/general)
├── is_read
├── created_at
└── read_at (nullable)
```

---

## 5. Module Breakdown

### Phase 1: Foundation & Setup (Week 1-2)
- [ ] Project setup (Git repository, folder structure)
- [ ] Database design & schema creation
- [ ] Development environment setup
- [ ] API skeleton & routing structure

### Phase 2: Authentication & User Management (Week 3-4)
- [ ] User registration (student/admin)
- [ ] Email verification
- [ ] Login & JWT token generation
- [ ] Password reset functionality
- [ ] User profile management
- [ ] Role-based access control (RBAC)

**Deliverables:**
- User authentication API endpoints
- JWT token validation middleware
- User profile endpoints

### Phase 3: Room Management System (Week 5-6)
- [ ] Room CRUD operations (admin)
- [ ] Room type management
- [ ] Facility management
- [ ] Room availability logic
- [ ] Room search & filtering API
- [ ] Room details endpoint with photos

**Deliverables:**
- Room management endpoints
- Search & filter functionality

### Phase 4: Booking Engine (Week 7-8)
- [ ] Booking creation logic
- [ ] Booking code generation
- [ ] QR code generation
- [ ] Booking validation (no double bookings)
- [ ] Booking status management
- [ ] Check-in/check-out functionality
- [ ] Booking history endpoints

**Deliverables:**
- Booking API endpoints
- QR code generation
- Booking validation logic

### Phase 5: Payment Processing (Week 9-10)
- [ ] Payment gateway integration (Pesapal/Flutterwave)
- [ ] MTN MoMo integration
- [ ] Airtel Money integration
- [ ] Payment status tracking
- [ ] Receipt generation (e-receipt with unique code)
- [ ] Payment verification endpoints

**Deliverables:**
- Payment endpoints
- E-receipt generation
- Payment reconciliation logic

### Phase 6: Notifications & Communication (Week 11-12)
- [ ] SMS notification service (for payment confirmations)
- [ ] Email notification service (for booking confirmations)
- [ ] In-app notifications
- [ ] Notification preferences
- [ ] Automated reminder system

**Deliverables:**
- Notification endpoints
- Email/SMS service integration

### Phase 7: Maintenance Requests (Week 13)
- [ ] Create maintenance request endpoints
- [ ] Maintenance request tracking
- [ ] Admin maintenance management
- [ ] Status updates for users

**Deliverables:**
- Maintenance request API
- Request tracking system

### Phase 8: Reporting & Analytics (Week 14)
- [ ] Dashboard statistics (occupancy, revenue)
- [ ] Booking reports
- [ ] Payment reports
- [ ] Student reports
- [ ] Export to CSV/PDF

**Deliverables:**
- Analytics/reporting endpoints
- Report generation

### Phase 9: Frontend - Student Portal (Week 15-18)
- [ ] Login/Registration pages
- [ ] Room browsing interface
- [ ] Room details page with photos
- [ ] Booking workflow (select room → provide details → payment)
- [ ] Payment interface
- [ ] E-receipt display & download
- [ ] Student dashboard
- [ ] Booking history
- [ ] Maintenance requests page
- [ ] Personal profile management

**Deliverables:**
- Responsive student portal UI
- Complete booking workflow

### Phase 10: Frontend - Admin Dashboard (Week 19-21)
- [ ] Admin login
- [ ] Room management interface
- [ ] Occupancy/availability view
- [ ] Booking management interface
- [ ] Payment tracking dashboard
- [ ] Student management
- [ ] Reports & analytics view
- [ ] Notification management
- [ ] Maintenance request handling

**Deliverables:**
- Complete admin dashboard
- All management features

### Phase 11: Testing (Week 22-23)
- [ ] Unit tests (backend modules)
- [ ] Integration tests (API endpoints)
- [ ] Frontend tests (component tests)
- [ ] End-to-end tests (complete workflows)
- [ ] Payment integration testing
- [ ] Security testing (SQL injection, XSS, etc.)

**Deliverables:**
- Test coverage report
- Bug fixes from testing

### Phase 12: Deployment & Documentation (Week 24)
- [ ] Server setup & configuration
- [ ] Database migration to production
- [ ] Environment configuration
- [ ] Security hardening
- [ ] SSL/HTTPS setup
- [ ] Deployment to WAMP/hosting
- [ ] API documentation (Postman/Swagger)
- [ ] User manuals (student & admin)
- [ ] System documentation

**Deliverables:**
- Live system on production
- Complete documentation

---

## 6. Routes & Entry Points

### Authentication Routes
```
POST /auth/register.php          - Student registration
POST /auth/admin-register.php    - Admin registration
POST /auth/login.php             - User login
GET  /auth/logout.php            - User logout
POST /auth/forgot-password.php   - Password reset
GET  /auth/verify-email.php      - Email verification
```

### Student Portal Routes
```
GET  /index.php                  - Home page
GET  /rooms.php                  - Browse rooms
GET  /room-details.php?id=X      - Room details
GET  /booking.php?room_id=X      - Booking form
POST /process-booking.php        - Create booking
GET  /dashboard.php              - Student dashboard
GET  /bookings.php               - View bookings
GET  /receipt.php?booking_id=X   - View e-receipt
GET  /profile.php                - User profile
POST /update-profile.php         - Update profile
GET  /maintenance.php            - View maintenance requests
POST /submit-maintenance.php     - Submit maintenance request
```

### Payment Routes
```
POST /payment/initiate.php       - Start payment process
GET  /payment/verify.php         - Verify payment
GET  /payment/receipt.php?id=X   - Get e-receipt
```

### Admin Routes
```
GET  /admin/                     - Admin dashboard
GET  /admin/rooms.php            - Room management
POST /admin/add-room.php         - Add room
POST /admin/edit-room.php        - Edit room
POST /admin/delete-room.php      - Delete room
GET  /admin/bookings.php         - Booking management
GET  /admin/students.php         - Student management
GET  /admin/payments.php         - Payment tracking
GET  /admin/maintenance.php      - Maintenance requests
GET  /admin/reports.php          - Reports & analytics
GET  /admin/notifications.php    - Notifications
```

### API (JSON) Endpoints (Optional AJAX calls)
```
GET  /api/rooms.php?filter=type  - Get rooms data
GET  /api/availability.php       - Check availability
GET  /api/booking-details.php    - Get booking info
POST /api/save-draft.php         - Save draft booking
```

---

## 7. Development Guidelines

### Code Standards
- **Version Control**: Use Git with meaningful commit messages
- **Naming Conventions**: 
  - camelCase for variables and functions
  - PascalCase for class names
  - UPPER_SNAKE_CASE for constants
- **PHP Standards**: Follow PSR-12 (PHP Framework Interoperability Group)
- **Code Comments**: Document complex logic with clear comments
- **Error Handling**: Use try-catch and meaningful error messages
- **Logging**: Log all errors and important transactions
- **Indentation**: 4 spaces (not tabs)

### Security Measures
- [ ] Hash passwords using password_hash() with PASSWORD_BCRYPT
- [ ] Use prepared statements for all database queries (mysqli_prepare)
- [ ] Validate all user inputs using filter_var() and custom validators
- [ ] Implement CSRF tokens on all forms
- [ ] Use mysqli with proper connection handling
- [ ] Never store sensitive data in plain text
- [ ] Implement input sanitization with htmlspecialchars()
- [ ] Use secure session handling with secure cookies
- [ ] Implement rate limiting on login/payment endpoints
- [ ] Set proper HTTP security headers (Content-Security-Policy, X-Frame-Options, etc.)
- [ ] Store sensitive config in config/config.php (not committed to Git)

### Performance Optimization
- [ ] Implement caching for room availability data
- [ ] Optimize database queries (add indexes, avoid N+1 queries)
- [ ] Minimize file includes, use autoloaders if using Composer
- [ ] Compress CSS and JavaScript files
- [ ] Optimize image sizes (use appropriate formats: WebP, JPEG, PNG)
- [ ] Use lazy loading for images
- [ ] Implement query result caching
- [ ] Use browser caching with proper headers
- [ ] Minimize database connections

### PHP Best Practices
- Use dependency injection for services
- Keep controllers thin, business logic in models/services
- Use constants for repeated values
- Avoid global variables
- Use type hints for function parameters
- Use return type declarations
- Keep functions focused (single responsibility)

---

## 8. Testing Strategy

### Backend Testing (PHP)
- **Unit Testing**: Test individual functions and methods using PHPUnit
  - Test model methods (validation, calculations)
  - Test service layer functions
  - Test helper functions
- **Integration Testing**: Test database interactions and complete workflows
  - Test controller actions with database
  - Test payment flow end-to-end
  - Test booking validation across modules
- **Coverage Target**: 70-80% for critical functionality

### Frontend Testing (JavaScript/HTML)
- **Manual Testing**: Test forms and user interactions
- **Browser Testing**: Test across different browsers (Chrome, Firefox, Safari, Edge)
- **Responsive Testing**: Test on mobile, tablet, and desktop sizes
- **JavaScript Testing**: Test form validation and dynamic behaviors
  - Use browser console for debugging
  - Test AJAX calls manually

### Security Testing
- [ ] SQL injection vulnerability tests
- [ ] XSS (Cross-Site Scripting) prevention verification
- [ ] CSRF token validation tests
- [ ] Authentication/authorization tests
- [ ] Password security validation
- [ ] File upload security tests

### Testing Tools
- **PHPUnit**: For PHP unit and integration testing
- **Browser DevTools**: For frontend debugging
- **Postman**: For manual API/endpoint testing
- **Validator Tools**: For HTML/CSS validation

### Manual Testing Checklist
- [ ] Test all user registration workflows
- [ ] Test login with correct/incorrect credentials
- [ ] Test room browsing and filtering
- [ ] Test complete booking workflow
- [ ] Test payment process (sandbox mode)
- [ ] Test payment verification
- [ ] Test receipt generation and download
- [ ] Test admin room management
- [ ] Test admin booking management
- [ ] Test notification generation
- [ ] Test maintenance request submission
- [ ] Test password reset functionality

---

## 9. Project Structure (Recommended)

```
online-hostel-management/
├── public/                      (Web root - accessible from browser)
│   ├── index.php               (Home page)
│   ├── rooms.php               (Browse rooms)
│   ├── room-details.php        (Room details)
│   ├── booking.php             (Booking page)
│   ├── dashboard.php           (Student dashboard)
│   ├── profile.php             (User profile)
│   ├── maintenance.php         (Maintenance requests)
│   ├── payment/
│   │   ├── initiate.php
│   │   ├── verify.php
│   │   └── receipt.php
│   ├── auth/
│   │   ├── login.php
│   │   ├── register.php
│   │   ├── logout.php
│   │   └── forgot-password.php
│   ├── admin/
│   │   ├── index.php           (Admin dashboard)
│   │   ├── rooms.php
│   │   ├── bookings.php
│   │   ├── students.php
│   │   ├── payments.php
│   │   ├── maintenance.php
│   │   ├── reports.php
│   │   └── settings.php
│   ├── api/                    (AJAX endpoints - optional)
│   │   ├── rooms.php
│   │   ├── availability.php
│   │   └── booking-details.php
│   ├── assets/
│   │   ├── css/
│   │   │   ├── style.css
│   │   │   ├── responsive.css
│   │   │   └── admin.css
│   │   ├── js/
│   │   │   ├── main.js
│   │   │   ├── form-validation.js
│   │   │   ├── booking.js
│   │   │   ├── payment.js
│   │   │   └── admin.js
│   │   ├── images/
│   │   ├── icons/
│   │   └── lib/
│   │       ├── qrcode.js
│   │       ├── bootstrap.min.css
│   │       ├── bootstrap.min.js
│   │       └── jquery.min.js
│   └── uploads/                (Room photos, receipts)
│       ├── rooms/
│       ├── receipts/
│       └── user-profiles/
├── app/                        (Application logic)
│   ├── controllers/
│   │   ├── AuthController.php
│   │   ├── UserController.php
│   │   ├── RoomController.php
│   │   ├── BookingController.php
│   │   ├── PaymentController.php
│   │   ├── MaintenanceController.php
│   │   ├── NotificationController.php
│   │   └── ReportController.php
│   ├── models/
│   │   ├── User.php
│   │   ├── Room.php
│   │   ├── RoomType.php
│   │   ├── Facility.php
│   │   ├── Booking.php
│   │   ├── Payment.php
│   │   ├── MaintenanceRequest.php
│   │   ├── Notification.php
│   │   └── Receipt.php
│   ├── services/
│   │   ├── AuthService.php
│   │   ├── BookingService.php
│   │   ├── PaymentService.php
│   │   ├── EmailService.php
│   │   ├── ValidationService.php
│   │   ├── QRCodeService.php
│   │   └── ReportService.php
│   ├── middleware/
│   │   ├── AuthMiddleware.php
│   │   ├── CsrfProtection.php
│   │   ├── RoleMiddleware.php
│   │   └── ValidationMiddleware.php
│   └── helpers/
│       ├── functions.php
│       ├── constants.php
│       ├── DatabaseHelper.php
│       └── DateTimeHelper.php
├── database/
│   ├── schema.sql              (Database schema)
│   ├── seed.sql                (Sample data)
│   └── migrations/             (Database updates)
├── views/                      (Shared templates)
│   ├── layouts/
│   │   ├── header.php
│   │   ├── footer.php
│   │   ├── sidebar.php
│   │   └── navbar.php
│   ├── components/
│   │   ├── room-card.php
│   │   ├── booking-form.php
│   │   ├── payment-form.php
│   │   └── notifications.php
│   └── partials/
│       ├── messages.php
│       ├── errors.php
│       └── success.php
├── config/
│   ├── database.php            (DB connection)
│   ├── config.php              (General config)
│   ├── constants.php
│   └── .env.example
├── docs/
│   ├── API_DOCUMENTATION.md
│   ├── DATABASE_SCHEMA.md
│   ├── SETUP_GUIDE.md
│   ├── USER_MANUAL.md
│   ├── ADMIN_GUIDE.md
│   └── TROUBLESHOOTING.md
├── tests/
│   ├── unit/
│   ├── integration/
│   └── fixtures/
├── logs/                       (Error logs, activity logs)
│   ├── errors.log
│   ├── activity.log
│   └── payments.log
├── cache/                      (Temporary cache files)
├── .gitignore
├── .htaccess                   (Apache rewrite rules)
├── README.md
├── DEVELOPMENT_PLAN.md
├── PROJECT_INITIALIZATION_CHECKLIST.md
└── composer.json               (If using Composer for dependencies)
```

---

## 10. Timeline & Milestones

| Phase | Duration | Milestone |
|-------|----------|-----------|
| Foundation & Setup | 2 weeks | Database & API skeleton ready |
| Auth & User Mgmt | 2 weeks | User authentication working |
| Room Management | 2 weeks | Rooms CRUD & search functional |
| Booking Engine | 2 weeks | Booking & QR code generation |
| Payment Processing | 2 weeks | Payment integration complete |
| Notifications | 2 weeks | Email/SMS notifications working |
| Maintenance & Reports | 2 weeks | Full admin features |
| Frontend - Student | 4 weeks | Student portal complete |
| Frontend - Admin | 3 weeks | Admin dashboard complete |
| Testing & QA | 2 weeks | 80%+ test coverage |
| Deployment & Docs | 1 week | Production ready |
| **Total** | **24 weeks** | **System fully deployed** |

---

## 11. Success Metrics

✓ All core features implemented and tested  
✓ 80%+ test coverage achieved  
✓ Payment integration working reliably  
✓ Real-time room availability accurate  
✓ Zero booking conflicts  
✓ Sub-2 second API response times  
✓ User feedback score > 4/5 stars  
✓ System uptime > 99.5%  

---

## 12. Risks & Mitigation

| Risk | Impact | Mitigation |
|------|--------|-----------|
| Payment gateway delays | High | Use reliable API, test thoroughly, have fallback |
| Database design changes | High | Early review with all stakeholders |
| Scope creep | Medium | Strict requirement documentation |
| Insufficient testing | High | Allocate adequate testing time |
| Mobile money API issues | High | Maintain provider documentation & support |
| Hosting/server issues | High | Use reliable LAMP hosting, set up backups |

---

## 13. Next Steps

1. **Set up project repository** on GitHub
2. **Create folder structure** as per project structure recommendation
3. **Initialize backend** with Node.js/Express or preferred stack
4. **Initialize frontend** with React or preferred framework
5. **Create database schema** and import into MySQL
6. **Start Phase 1** - Foundation & Setup
7. **Weekly progress reviews** against timeline

---

**Project Start Date**: April 22, 2026  
**Planned Completion**: October 2026  

