<?php
/**
 * Home Page - Online Hostel Management System
 * Pearls of Wisdom Hostel
 */

require_once '../config/config.php';
require_once APP_PATH . '/helpers/functions.php';

$page_title = 'Welcome';
?>

<?php include VIEWS_PATH . '/layouts/header.php'; ?>

<style>
/* ── Landing page light overrides ── */
body { background: #ffffff !important; }

/* Navbar override for landing */
.navbar {
    background: rgba(255,255,255,0.95) !important;
    border-bottom: 1px solid #e8edf5 !important;
    box-shadow: 0 1px 12px rgba(0,0,0,0.06) !important;
}
.navbar-brand, .nav-link { color: #1e1b4b !important; }
.nav-link:hover { background: #eef2ff !important; color: #4f46e5 !important; }

/* ── Hero ── */
.lp-hero {
    position: relative;
    min-height: 92vh;
    display: flex;
    align-items: center;
    overflow: hidden;
    background: linear-gradient(135deg, #1e1b4b 0%, #312e81 50%, #4f46e5 100%);
}

.lp-hero-bg {
    position: absolute;
    inset: 0;
    background: url('assets/images/hostel-exterior.jpg') center center / cover no-repeat;
    opacity: 0.22;
}

.lp-hero-overlay {
    position: absolute;
    inset: 0;
    background: linear-gradient(135deg, rgba(30,27,75,0.92) 0%, rgba(79,70,229,0.75) 100%);
}

.lp-hero-content {
    position: relative;
    z-index: 2;
    padding: 80px 0 60px;
}

.lp-badge {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    background: rgba(255,255,255,0.15);
    border: 1px solid rgba(255,255,255,0.25);
    color: #e0e7ff;
    font-size: 0.78rem;
    font-weight: 700;
    letter-spacing: 0.06em;
    text-transform: uppercase;
    padding: 6px 16px;
    border-radius: 999px;
    margin-bottom: 24px;
    backdrop-filter: blur(8px);
}

.lp-hero h1 {
    font-size: clamp(2.2rem, 5vw, 3.6rem);
    font-weight: 900;
    color: #ffffff;
    line-height: 1.15;
    letter-spacing: -0.03em;
    margin-bottom: 20px;
}

.lp-hero h1 span { color: #a5b4fc; }

.lp-hero p {
    font-size: 1.1rem;
    color: rgba(224,231,255,0.85);
    max-width: 520px;
    line-height: 1.7;
    margin-bottom: 36px;
}

.lp-btn-primary {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    background: #ffffff;
    color: #4f46e5;
    font-weight: 800;
    font-size: 0.95rem;
    padding: 14px 32px;
    border-radius: 999px;
    text-decoration: none;
    box-shadow: 0 8px 28px rgba(0,0,0,0.2);
    transition: all 0.2s ease;
}

.lp-btn-primary:hover {
    background: #eef2ff;
    color: #3730a3;
    transform: translateY(-2px);
    box-shadow: 0 12px 36px rgba(0,0,0,0.25);
}

.lp-btn-outline {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    background: transparent;
    color: #ffffff;
    font-weight: 700;
    font-size: 0.95rem;
    padding: 14px 32px;
    border-radius: 999px;
    text-decoration: none;
    border: 2px solid rgba(255,255,255,0.45);
    transition: all 0.2s ease;
}

.lp-btn-outline:hover {
    background: rgba(255,255,255,0.12);
    border-color: rgba(255,255,255,0.7);
    color: #ffffff;
    transform: translateY(-2px);
}

.lp-hero-stats {
    display: flex;
    gap: 36px;
    margin-top: 48px;
    flex-wrap: wrap;
}

.lp-stat { text-align: left; }
.lp-stat-num {
    font-size: 1.8rem;
    font-weight: 900;
    color: #ffffff;
    line-height: 1;
}
.lp-stat-label {
    font-size: 0.78rem;
    color: rgba(199,210,254,0.8);
    font-weight: 500;
    margin-top: 4px;
}

/* ── Features strip ── */
.lp-features {
    background: #f8f7ff;
    padding: 72px 0;
    border-top: 1px solid #e8edf5;
    border-bottom: 1px solid #e8edf5;
}

.lp-section-label {
    font-size: 0.75rem;
    font-weight: 800;
    letter-spacing: 0.1em;
    text-transform: uppercase;
    color: #4f46e5;
    margin-bottom: 10px;
}

.lp-section-title {
    font-size: clamp(1.6rem, 3vw, 2.2rem);
    font-weight: 900;
    color: #1e1b4b;
    letter-spacing: -0.02em;
    margin-bottom: 14px;
}

.lp-section-sub {
    color: #64748b;
    font-size: 1rem;
    max-width: 520px;
    margin: 0 auto;
    line-height: 1.7;
}

.lp-feature-card {
    background: #ffffff;
    border: 1px solid #e8edf5;
    border-radius: 18px;
    padding: 28px 24px;
    height: 100%;
    transition: all 0.25s ease;
    box-shadow: 0 2px 12px rgba(79,70,229,0.05);
}

.lp-feature-card:hover {
    border-color: #c7d2fe;
    box-shadow: 0 8px 32px rgba(79,70,229,0.12);
    transform: translateY(-4px);
}

.lp-feature-icon {
    width: 52px; height: 52px;
    border-radius: 14px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.3rem;
    margin-bottom: 18px;
}

.lp-feature-card h5 {
    font-size: 1rem;
    font-weight: 800;
    color: #1e1b4b;
    margin-bottom: 8px;
}

.lp-feature-card p {
    font-size: 0.875rem;
    color: #64748b;
    line-height: 1.6;
    margin: 0;
}

/* ── About section ── */
.lp-about {
    padding: 80px 0;
    background: #ffffff;
}

.lp-about-img {
    width: 100%;
    height: 380px;
    object-fit: cover;
    border-radius: 20px;
    box-shadow: 0 16px 48px rgba(79,70,229,0.12);
}

.lp-check-list {
    list-style: none;
    padding: 0;
    margin: 20px 0 28px;
}

.lp-check-list li {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 10px 0;
    border-bottom: 1px solid #f1f5f9;
    font-size: 0.9rem;
    color: #374151;
    font-weight: 500;
}

.lp-check-list li:last-child { border-bottom: none; }

.lp-check-list li i {
    width: 22px; height: 22px;
    background: #eef2ff;
    color: #4f46e5;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 0.65rem;
    flex-shrink: 0;
}

/* ── Room types ── */
.lp-rooms {
    background: #f8f7ff;
    padding: 80px 0;
}

.lp-room-card {
    background: #ffffff;
    border: 1px solid #e8edf5;
    border-radius: 20px;
    overflow: hidden;
    height: 100%;
    transition: all 0.25s ease;
    box-shadow: 0 2px 12px rgba(0,0,0,0.04);
}

.lp-room-card:hover {
    border-color: #c7d2fe;
    box-shadow: 0 12px 40px rgba(79,70,229,0.12);
    transform: translateY(-4px);
}

.lp-room-card.featured {
    border-color: #4f46e5;
    box-shadow: 0 8px 32px rgba(79,70,229,0.18);
}

.lp-room-header {
    padding: 24px 24px 0;
}

.lp-room-badge {
    display: inline-block;
    background: #4f46e5;
    color: white;
    font-size: 0.7rem;
    font-weight: 800;
    letter-spacing: 0.06em;
    text-transform: uppercase;
    padding: 4px 12px;
    border-radius: 999px;
    margin-bottom: 14px;
}

.lp-room-badge.popular { background: linear-gradient(135deg, #f59e0b, #d97706); }

.lp-room-card h5 {
    font-size: 1.1rem;
    font-weight: 800;
    color: #1e1b4b;
    margin-bottom: 8px;
}

.lp-room-card p {
    font-size: 0.875rem;
    color: #64748b;
    line-height: 1.6;
    margin-bottom: 16px;
}

.lp-room-price {
    font-size: 1.5rem;
    font-weight: 900;
    color: #4f46e5;
    margin-bottom: 20px;
    padding: 0 24px;
}

.lp-room-price span {
    font-size: 0.8rem;
    font-weight: 500;
    color: #94a3b8;
}

.lp-room-footer {
    padding: 0 24px 24px;
}

.lp-room-btn {
    display: block;
    text-align: center;
    background: #eef2ff;
    color: #4f46e5;
    font-weight: 700;
    font-size: 0.875rem;
    padding: 12px;
    border-radius: 10px;
    text-decoration: none;
    transition: all 0.2s ease;
}

.lp-room-btn:hover, .lp-room-card.featured .lp-room-btn {
    background: #4f46e5;
    color: white;
}

/* ── CTA ── */
.lp-cta {
    background: linear-gradient(135deg, #1e1b4b 0%, #4f46e5 100%);
    padding: 80px 0;
    text-align: center;
}

.lp-cta h2 {
    font-size: clamp(1.8rem, 3.5vw, 2.6rem);
    font-weight: 900;
    color: #ffffff;
    letter-spacing: -0.02em;
    margin-bottom: 14px;
}

.lp-cta p { color: rgba(199,210,254,0.85); font-size: 1rem; margin-bottom: 32px; }

/* ── Contact ── */
.lp-contact {
    background: #ffffff;
    padding: 72px 0;
    border-top: 1px solid #e8edf5;
}

.lp-contact-card {
    text-align: center;
    padding: 28px 20px;
    border-radius: 16px;
    background: #f8f7ff;
    border: 1px solid #e8edf5;
    transition: all 0.2s ease;
}

.lp-contact-card:hover {
    border-color: #c7d2fe;
    box-shadow: 0 6px 24px rgba(79,70,229,0.1);
}

.lp-contact-icon {
    width: 56px; height: 56px;
    background: #eef2ff;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    margin: 0 auto 16px;
    font-size: 1.3rem;
    color: #4f46e5;
}

.lp-contact-card h5 {
    font-size: 0.9rem;
    font-weight: 800;
    color: #1e1b4b;
    margin-bottom: 6px;
    text-transform: uppercase;
    letter-spacing: 0.05em;
}

.lp-contact-card a, .lp-contact-card p {
    font-size: 0.9rem;
    color: #4f46e5;
    text-decoration: none;
    font-weight: 600;
    margin: 0;
}

/* ── Footer override ── */
footer {
    background: #1e1b4b !important;
    margin-top: 0 !important;
}
</style>

<!-- ═══════════════════════════════════════════════ -->
<!-- HERO                                            -->
<!-- ═══════════════════════════════════════════════ -->
<section class="lp-hero">
    <div class="lp-hero-bg"></div>
    <div class="lp-hero-overlay"></div>
    <div class="container lp-hero-content">
        <div class="row align-items-center">
            <div class="col-lg-7">
                <div class="lp-badge">
                    <i class="fas fa-map-marker-alt"></i> Fort Portal, Western Uganda
                </div>
                <h1>Your Home Away<br>From <span>Home</span></h1>
                <p>Premium student accommodation at Pearls of Wisdom Hostel. Comfortable, secure, and affordable rooms designed for academic success.</p>

                <?php if (!isLoggedIn()): ?>
                <div class="d-flex gap-3 flex-wrap">
                    <a href="auth/register.php" class="lp-btn-primary">
                        <i class="fas fa-user-plus"></i> Get Started
                    </a>
                    <a href="auth/login.php" class="lp-btn-outline">
                        <i class="fas fa-sign-in-alt"></i> Sign In
                    </a>
                </div>
                <?php else: ?>
                <div class="d-flex gap-3 flex-wrap">
                    <a href="rooms.php" class="lp-btn-primary">
                        <i class="fas fa-door-open"></i> Browse Rooms
                    </a>
                    <a href="dashboard.php" class="lp-btn-outline">
                        <i class="fas fa-th-large"></i> My Dashboard
                    </a>
                </div>
                <?php endif; ?>

                <div class="lp-hero-stats">
                    <div class="lp-stat">
                        <div class="lp-stat-num">200+</div>
                        <div class="lp-stat-label">Students Housed</div>
                    </div>
                    <div class="lp-stat">
                        <div class="lp-stat-num">3</div>
                        <div class="lp-stat-label">Room Types</div>
                    </div>
                    <div class="lp-stat">
                        <div class="lp-stat-num">24/7</div>
                        <div class="lp-stat-label">Security</div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- ═══════════════════════════════════════════════ -->
<!-- FEATURES                                        -->
<!-- ═══════════════════════════════════════════════ -->
<section class="lp-features">
    <div class="container">
        <div class="text-center mb-5">
            <div class="lp-section-label">Why Choose Us</div>
            <h2 class="lp-section-title">Everything You Need to Thrive</h2>
            <p class="lp-section-sub">We provide more than just a room — we create an environment where students can focus, grow, and succeed.</p>
        </div>

        <div class="row g-4">
            <div class="col-md-4 col-sm-6">
                <div class="lp-feature-card">
                    <div class="lp-feature-icon" style="background:#eef2ff;">
                        <i class="fas fa-map-marker-alt" style="color:#4f46e5;"></i>
                    </div>
                    <h5>Strategic Location</h5>
                    <p>Close to campus, shopping centres, restaurants, and public transport in Fort Portal.</p>
                </div>
            </div>
            <div class="col-md-4 col-sm-6">
                <div class="lp-feature-card">
                    <div class="lp-feature-icon" style="background:#f0fdf4;">
                        <i class="fas fa-shield-alt" style="color:#16a34a;"></i>
                    </div>
                    <h5>24/7 Security</h5>
                    <p>Professional security staff on-site around the clock ensuring your safety and peace of mind.</p>
                </div>
            </div>
            <div class="col-md-4 col-sm-6">
                <div class="lp-feature-card">
                    <div class="lp-feature-icon" style="background:#eff6ff;">
                        <i class="fas fa-wifi" style="color:#2563eb;"></i>
                    </div>
                    <h5>High-Speed Wi-Fi</h5>
                    <p>Reliable internet connectivity throughout the hostel for studies and entertainment.</p>
                </div>
            </div>
            <div class="col-md-4 col-sm-6">
                <div class="lp-feature-card">
                    <div class="lp-feature-icon" style="background:#fdf4ff;">
                        <i class="fas fa-book" style="color:#9333ea;"></i>
                    </div>
                    <h5>Study Spaces</h5>
                    <p>Dedicated quiet areas for focused studying and collaborative group work sessions.</p>
                </div>
            </div>
            <div class="col-md-4 col-sm-6">
                <div class="lp-feature-card">
                    <div class="lp-feature-icon" style="background:#fff7ed;">
                        <i class="fas fa-user-friends" style="color:#ea580c;"></i>
                    </div>
                    <h5>Great Community</h5>
                    <p>A vibrant community of students with regular social events and support networks.</p>
                </div>
            </div>
            <div class="col-md-4 col-sm-6">
                <div class="lp-feature-card">
                    <div class="lp-feature-icon" style="background:#fefce8;">
                        <i class="fas fa-money-bill-alt" style="color:#ca8a04;"></i>
                    </div>
                    <h5>Affordable Rates</h5>
                    <p>Competitive semester pricing with flexible deposit payment options available.</p>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- ═══════════════════════════════════════════════ -->
<!-- ABOUT                                           -->
<!-- ═══════════════════════════════════════════════ -->
<section class="lp-about">
    <div class="container">
        <div class="row align-items-center g-5">
            <div class="col-lg-6">
                <img src="assets/images/hostel-exterior.jpg"
                     alt="Pearls of Wisdom Hostel Building"
                     class="lp-about-img">
            </div>
            <div class="col-lg-6">
                <div class="lp-section-label">About Us</div>
                <h2 class="lp-section-title" style="text-align:left;">Pearls of Wisdom Hostel</h2>
                <p style="color:#64748b;line-height:1.8;margin-bottom:8px;">
                    A premium student accommodation provider located in the heart of Fort Portal, Western Uganda. We offer a safe, comfortable, and affordable living environment designed to support your academic journey.
                </p>
                <ul class="lp-check-list">
                    <li><i class="fas fa-check"></i> Comfortable Single, Double &amp; Self-Contained Rooms</li>
                    <li><i class="fas fa-check"></i> High-Speed Wi-Fi &amp; 24/7 Security</li>
                    <li><i class="fas fa-check"></i> Study Spaces &amp; Laundry Facilities</li>
                    <li><i class="fas fa-check"></i> Affordable Semester Rates with Deposit Options</li>
                    <li><i class="fas fa-check"></i> Proximity to Campus &amp; Shopping Centres</li>
                    <li><i class="fas fa-check"></i> Excellent Student Support Services</li>
                </ul>
                <?php if (!isLoggedIn()): ?>
                <a href="auth/register.php" class="lp-btn-primary" style="width:fit-content;">
                    <i class="fas fa-user-plus"></i> Join Us Today
                </a>
                <?php endif; ?>
            </div>
        </div>
    </div>
</section>

<!-- ═══════════════════════════════════════════════ -->
<!-- ROOM TYPES                                      -->
<!-- ═══════════════════════════════════════════════ -->
<section class="lp-rooms">
    <div class="container">
        <div class="text-center mb-5">
            <div class="lp-section-label">Accommodation</div>
            <h2 class="lp-section-title">Choose Your Room</h2>
            <p class="lp-section-sub">Three room types to suit every student's needs and budget.</p>
        </div>

        <div class="row g-4 justify-content-center">
            <div class="col-md-4">
                <div class="lp-room-card">
                    <div class="lp-room-header">
                        <div class="lp-room-badge">Single</div>
                        <h5>Single Room</h5>
                        <p>Perfect for students who prefer privacy. Includes private bathroom and all furniture.</p>
                    </div>
                    <div class="lp-room-price">UGX 250,000 <span>/ semester</span></div>
                    <div class="lp-room-footer">
                        <a href="<?php echo isLoggedIn() ? 'rooms.php' : 'auth/login.php'; ?>" class="lp-room-btn">
                            <?php echo isLoggedIn() ? 'Browse Rooms' : 'View Details'; ?>
                        </a>
                    </div>
                </div>
            </div>

            <div class="col-md-4">
                <div class="lp-room-card featured">
                    <div class="lp-room-header">
                        <div class="lp-room-badge popular">Most Popular</div>
                        <h5>Double Room</h5>
                        <p>Share with a roommate. Spacious with shared amenities and bathroom facilities.</p>
                    </div>
                    <div class="lp-room-price">UGX 350,000 <span>/ semester</span></div>
                    <div class="lp-room-footer">
                        <a href="<?php echo isLoggedIn() ? 'rooms.php' : 'auth/login.php'; ?>" class="lp-room-btn">
                            <?php echo isLoggedIn() ? 'Browse Rooms' : 'View Details'; ?>
                        </a>
                    </div>
                </div>
            </div>

            <div class="col-md-4">
                <div class="lp-room-card">
                    <div class="lp-room-header">
                        <div class="lp-room-badge">Self-Contained</div>
                        <h5>Self-Contained</h5>
                        <p>Complete independence with kitchenette, private bathroom, and all facilities.</p>
                    </div>
                    <div class="lp-room-price">UGX 450,000 <span>/ semester</span></div>
                    <div class="lp-room-footer">
                        <a href="<?php echo isLoggedIn() ? 'rooms.php' : 'auth/login.php'; ?>" class="lp-room-btn">
                            <?php echo isLoggedIn() ? 'Browse Rooms' : 'View Details'; ?>
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- ═══════════════════════════════════════════════ -->
<!-- CTA                                             -->
<!-- ═══════════════════════════════════════════════ -->
<section class="lp-cta">
    <div class="container">
        <h2>Ready to Book Your Room?</h2>
        <p>Join hundreds of students who trust Pearls of Wisdom for their accommodation.</p>
        <?php if (!isLoggedIn()): ?>
        <a href="auth/register.php" class="lp-btn-primary" style="margin:0 auto;width:fit-content;">
            <i class="fas fa-user-plus"></i> Create Your Account
        </a>
        <?php else: ?>
        <a href="rooms.php" class="lp-btn-primary" style="margin:0 auto;width:fit-content;">
            <i class="fas fa-door-open"></i> Browse Available Rooms
        </a>
        <?php endif; ?>
    </div>
</section>

<!-- ═══════════════════════════════════════════════ -->
<!-- CONTACT                                         -->
<!-- ═══════════════════════════════════════════════ -->
<section class="lp-contact">
    <div class="container">
        <div class="text-center mb-5">
            <div class="lp-section-label">Get In Touch</div>
            <h2 class="lp-section-title">Contact Us</h2>
        </div>
        <div class="row g-4 justify-content-center">
            <div class="col-md-4">
                <div class="lp-contact-card">
                    <div class="lp-contact-icon"><i class="fas fa-phone"></i></div>
                    <h5>Phone</h5>
                    <a href="tel:+256765536881">+256 765 536 881</a>
                </div>
            </div>
            <div class="col-md-4">
                <div class="lp-contact-card">
                    <div class="lp-contact-icon"><i class="fas fa-envelope"></i></div>
                    <h5>Email</h5>
                    <a href="mailto:info@pearlswisdom.com">info@pearlswisdom.com</a>
                </div>
            </div>
            <div class="col-md-4">
                <div class="lp-contact-card">
                    <div class="lp-contact-icon"><i class="fas fa-map-marker-alt"></i></div>
                    <h5>Address</h5>
                    <p>Kasindikwa Village, Fort Portal, Western Uganda</p>
                </div>
            </div>
        </div>
    </div>
</section>

<?php include VIEWS_PATH . '/layouts/footer.php'; ?>
