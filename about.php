<?php
require 'config/db.php';
require 'includes/header.php';
require 'includes/navbar.php';

// Optional: Check login
checkLogin(); 
?>

<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600;700;800&display=swap" rel="stylesheet">

<style>
    :root {
        --brand-primary: #0077b6;
        --brand-secondary: #00b4d8;
        --brand-dark: #023e8a;
        --brand-accent: #caf0f8;
        --text-dark: #2b2d42;
        --text-muted: #6c757d;
    }

    body {
        font-family: 'Poppins', sans-serif;
        background-color: #f8f9fa;
        color: var(--text-dark);
        overflow-x: hidden;
    }

    /* --- MODERN HERO SECTION --- */
    .modern-hero {
        position: relative;
        background: linear-gradient(135deg, var(--brand-dark) 0%, var(--brand-primary) 100%);
        padding: 120px 0 160px; /* Default Desktop Padding */
        color: white;
        text-align: center;
        overflow: hidden;
        border-radius: 0 0 50% 50% / 40px;
    }

    /* Animated Background Shapes */
    .modern-hero::before, .modern-hero::after {
        content: '';
        position: absolute;
        width: 400px;
        height: 400px;
        background: rgba(255, 255, 255, 0.05);
        border-radius: 50%;
        z-index: 0;
        animation: floatShapes 20s infinite linear;
    }
    .modern-hero::before { top: -100px; left: -100px; }
    .modern-hero::after { bottom: -100px; right: -100px; animation-delay: -10s; }

    @keyframes floatShapes {
        0% { transform: translate(0, 0) rotate(0deg); }
        50% { transform: translate(50px, 50px) rotate(180deg); }
        100% { transform: translate(0, 0) rotate(360deg); }
    }

    .hero-content {
        position: relative;
        z-index: 2;
        padding: 0 15px; /* Safety padding for mobile */
    }

    /* --- MOVING LETTERS (Gradient Animation) --- */
    .moving-text {
        font-weight: 800;
        font-size: 3.5rem;
        text-transform: uppercase;
        background: linear-gradient(
            to right,
            #ffffff 0%,
            #caf0f8 20%,
            #00b4d8 40%,
            #ffffff 60%,
            #caf0f8 80%,
            #00b4d8 100%
        );
        background-size: 200% auto;
        color: #fff;
        background-clip: text;
        -webkit-background-clip: text;
        -webkit-text-fill-color: transparent;
        animation: shine 3s linear infinite;
        letter-spacing: -1px;
        margin-bottom: 1rem;
        display: inline-block;
    }

    @keyframes shine {
        to {
            background-position: 200% center;
        }
    }

    .hero-subtitle {
        font-size: 1.2rem;
        font-weight: 300;
        opacity: 0.9;
        max-width: 700px;
        margin: 0 auto;
        animation: fadeInUp 1s ease-out forwards;
    }

    @keyframes fadeInUp {
        from { opacity: 0; transform: translateY(20px); }
        to { opacity: 1; transform: translateY(0); }
    }

    /* --- FLOATING STATS CARDS --- */
    .stats-wrapper {
        margin-top: -100px; /* Pull up to overlap hero */
        position: relative;
        z-index: 5;
        padding-bottom: 60px;
    }

    .stat-card-modern {
        background: rgba(255, 255, 255, 0.95);
        backdrop-filter: blur(10px);
        padding: 30px;
        border-radius: 20px;
        text-align: center;
        box-shadow: 0 15px 35px rgba(0, 119, 182, 0.1);
        border: 1px solid rgba(255, 255, 255, 0.5);
        transition: transform 0.3s ease, box-shadow 0.3s ease;
        height: 100%; /* Ensure equal height */
        display: flex;
        flex-direction: column;
        justify-content: center;
        align-items: center;
    }

    .stat-card-modern:hover {
        transform: translateY(-10px);
        box-shadow: 0 20px 40px rgba(0, 119, 182, 0.2);
    }

    .stat-num {
        font-size: 2.5rem;
        font-weight: 800;
        background: linear-gradient(45deg, var(--brand-dark), var(--brand-secondary));
        -webkit-background-clip: text;
        -webkit-text-fill-color: transparent;
        margin-bottom: 5px;
        display: block;
    }

    /* --- FEATURE CARDS --- */
    .feature-card {
        background: white;
        border: none;
        border-radius: 20px;
        padding: 40px 30px;
        height: 100%;
        position: relative;
        overflow: hidden;
        transition: all 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275);
        box-shadow: 0 5px 15px rgba(0,0,0,0.03);
    }

    .feature-card:hover {
        transform: translateY(-10px);
        box-shadow: 0 20px 40px rgba(0,0,0,0.08);
    }

    /* Bottom colored line on hover */
    .feature-card::after {
        content: "";
        position: absolute;
        bottom: 0;
        left: 0;
        width: 100%;
        height: 4px;
        background: linear-gradient(90deg, var(--brand-primary), var(--brand-secondary));
        transform: scaleX(0);
        transform-origin: left;
        transition: transform 0.4s ease;
    }
    .feature-card:hover::after {
        transform: scaleX(1);
    }

    .icon-box {
        width: 70px;
        height: 70px;
        background: var(--brand-accent);
        color: var(--brand-primary);
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.8rem;
        margin: 0 auto 25px;
        transition: all 0.4s ease;
    }

    .feature-card:hover .icon-box {
        background: var(--brand-primary);
        color: white;
        transform: rotateY(360deg);
    }

    /* --- CONTACT SECTION --- */
    .contact-modern {
        background: white;
        border-radius: 20px;
        padding: 50px;
        box-shadow: 0 10px 30px rgba(0,0,0,0.05);
        position: relative;
        overflow: hidden;
    }
    
    .contact-item {
        display: flex;
        align-items: center;
        margin-bottom: 25px;
        padding: 15px;
        border-radius: 12px;
        transition: background 0.2s;
    }
    
    .contact-item:hover {
        background: #f8f9fa;
    }

    .contact-icon {
        width: 50px;
        height: 50px;
        background: var(--brand-accent);
        color: var(--brand-dark);
        border-radius: 12px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.2rem;
        margin-right: 20px;
        flex-shrink: 0; /* Prevent icon from shrinking on mobile */
    }

    .social-link {
        display: inline-flex;
        align-items: center;
        color: #1877f2;
        font-weight: 700;
        text-decoration: none;
        transition: transform 0.2s;
    }
    .social-link:hover {
        transform: translateX(5px);
        text-decoration: underline;
    }

    /* --- FOOTER --- */
    .modern-footer {
        margin-top: 80px;
        padding-top: 30px;
        border-top: 1px solid #eee;
        color: var(--text-muted);
        font-size: 0.9rem;
    }

    /* --- RESPONSIVE OPTIMIZATIONS (MOBILE & TABLET) --- */
    @media (max-width: 991px) {
        /* Tablet & Mobile Adjustments */
        .modern-hero {
            padding: 80px 0 120px; /* Reduce padding */
        }
        .moving-text {
            font-size: 2.5rem; /* Smaller Headline */
        }
        .contact-modern {
            padding: 30px;
        }
    }

    @media (max-width: 767px) {
        /* Mobile Specific Adjustments */
        .modern-hero {
            padding: 60px 0 100px;
            border-radius: 0 0 30% 30% / 20px; /* Flatter curve for mobile */
        }
        .moving-text {
            font-size: 2rem; /* Fit screen */
        }
        .hero-subtitle {
            font-size: 1rem;
        }
        .stats-wrapper {
            margin-top: -60px; /* Adjust overlapping */
        }
        .stat-card-modern {
            padding: 15px; /* More compact stats */
        }
        .stat-num {
            font-size: 1.8rem;
        }
        .stat-card-modern small {
            font-size: 0.7rem;
        }
        .contact-modern {
            padding: 20px;
        }
        .contact-item {
            flex-direction: row; /* Keep icon side-by-side but allow wrapping text if needed */
            align-items: flex-start;
        }
        .contact-icon {
            width: 40px;
            height: 40px;
            font-size: 1rem;
            margin-right: 15px;
            margin-top: 5px;
        }
    }
</style>

<section class="modern-hero">
    <div class="container hero-content">
        <h1 class="moving-text">Bansalan Coop Helpdesk</h1>
        <p class="hero-subtitle">
            "Your Success, is our Success!"<br>
            A secure, efficient, and transparent support platform for our Branches.
        </p>
    </div>
</section>

<div class="container stats-wrapper">
    <div class="row g-3 g-md-4"> <?php
        // Fetch Live Stats
        $users_count = $conn->query("SELECT COUNT(*) as c FROM users")->fetch_assoc()['c'];
        $tickets_count = $conn->query("SELECT COUNT(*) as c FROM tickets")->fetch_assoc()['c'];
        $resolved_count = $conn->query("SELECT COUNT(*) as c FROM tickets WHERE status='Closed' OR status='Resolved'")->fetch_assoc()['c'];
        $avg_query = $conn->query("SELECT AVG(rating) as avg FROM tickets WHERE rating > 0")->fetch_assoc();
        $avg_rating = $avg_query['avg'] ? number_format($avg_query['avg'], 1) : "0.0";
        ?>
        
        <div class="col-md-3 col-6">
            <div class="stat-card-modern">
                <span class="stat-num"><?= number_format($users_count) ?></span>
                <small class="text-uppercase fw-bold text-muted">Active Users</small>
            </div>
        </div>
        <div class="col-md-3 col-6">
            <div class="stat-card-modern">
                <span class="stat-num"><?= number_format($tickets_count) ?></span>
                <small class="text-uppercase fw-bold text-muted">Tickets Filed</small>
            </div>
        </div>
        <div class="col-md-3 col-6">
            <div class="stat-card-modern">
                <span class="stat-num" style="--brand-dark: #198754; --brand-secondary: #20c997;"><?= number_format($resolved_count) ?></span>
                <small class="text-uppercase fw-bold text-muted">Issues Solved</small>
            </div>
        </div>
        <div class="col-md-3 col-6">
            <div class="stat-card-modern">
                <span class="stat-num" style="--brand-dark: #ffc107; --brand-secondary: #ffca2c;">★ <?= $avg_rating ?></span>
                <small class="text-uppercase fw-bold text-muted">Satisfaction</small>
            </div>
        </div>
    </div>
</div>

<div class="container mb-5">
    
    <div class="row justify-content-center mb-5">
        <div class="col-lg-8 text-center">
            <h6 class="text-primary fw-bold text-uppercase letter-spacing-2">Who We Are</h6>
            <h2 class="fw-bold mb-4" style="color: var(--brand-dark);">Empowering Our Community</h2>
            <p class="text-muted fs-5" style="line-height: 1.8;">
                Bansalan Multipurpose Cooperative is dedicated to generating funds and extending loans for providential and productive purposes. 
                With over <strong>17 branches</strong> across Region 11 and 12, we are a 
                <span class="text-primary fw-bold">FOCCUS Certified</span> organization—Safe, Sound, and Sustainable.
            </p>
        </div>
    </div>

    <div class="row g-4 mb-5">
        <div class="col-md-4">
            <div class="feature-card text-center">
                <div class="icon-box"><i class="fas fa-shield-alt"></i></div>
                <h5 class="fw-bold mb-3">Secure & Reliable</h5>
                <p class="text-muted">Built on FOCCUS Prudential Standards to ensure financial stability and data security for all members.</p>
            </div>
        </div>
        <div class="col-md-4">
            <div class="feature-card text-center">
                <div class="icon-box"><i class="fas fa-hand-holding-heart"></i></div>
                <h5 class="fw-bold mb-3">Member Focused</h5>
                <p class="text-muted">From Agricultural Loans to Micro-Financing, our services are tailored to help you succeed.</p>
            </div>
        </div>
        <div class="col-md-4">
            <div class="feature-card text-center">
                <div class="icon-box"><i class="fas fa-headset"></i></div>
                <h5 class="fw-bold mb-3">Dedicated Support</h5>
                <p class="text-muted">Our new Helpdesk system ensures that every query is tracked, managed, and resolved efficiently.</p>
            </div>
        </div>
    </div>

    <div class="row justify-content-center">
        <div class="col-lg-10">
            <div class="contact-modern">
                <div class="row align-items-center">
                    <div class="col-md-5 mb-4 mb-md-0 text-center text-md-start">
                        <h3 class="fw-bold mb-2">Get in Touch</h3>
                        <p class="text-muted">Visit our main branch or contact us online.</p>
                        <a href="https://bansalancoop.com/" target="_blank" class="btn btn-primary rounded-pill px-4 fw-bold shadow-sm mt-2">
                            Visit Official Website <i class="fas fa-external-link-alt ms-2"></i>
                        </a>
                    </div>
                    <div class="col-md-7">
                        <div class="contact-item">
                            <div class="contact-icon"><i class="fas fa-map-marker-alt"></i></div>
                            <div>
                                <h6 class="fw-bold mb-0">Head Office</h6>
                                <small class="text-muted">2nd Floor, Bansalan Coop Bldg, Ramon Delos Cientos St,<br>Poblacion Uno, Bansalan, Davao del Sur</small>
                            </div>
                        </div>
                        <div class="contact-item">
                            <div class="contact-icon"><i class="fas fa-envelope"></i></div>
                            <div>
                                <h6 class="fw-bold mb-0">Email Us</h6>
                                <small class="text-muted">ho@bansalancoop.com</small>
                            </div>
                        </div>
                        <div class="contact-item">
                            <div class="contact-icon"><i class="fas fa-phone-alt"></i></div>
                            <div>
                                <h6 class="fw-bold mb-0">Call Us</h6>
                                <small class="text-muted">0985-960-9213</small>
                            </div>
                        </div>
                        <div class="contact-item">
                            <div class="contact-icon"><i class="fab fa-facebook-f"></i></div>
                            <div>
                                <h6 class="fw-bold mb-0">Facebook</h6>
                                <a href="https://www.facebook.com/bansalancoop" target="_blank" class="social-link">
                                    facebook.com/bansalancoop
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="modern-footer text-center">
        <p class="mb-1 fw-bold">Bansalan Coop Helpdesk v1.0</p>
        <p>&copy; <?= date('Y') ?> Bansalan Multipurpose Cooperative. All Rights Reserved.</p>
    </div>

</div>

<?php require 'includes/footer.php'; ?>