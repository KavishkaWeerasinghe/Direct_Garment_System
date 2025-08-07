<?php include 'components/header.php'; ?>

<style>
    .banner-section {
        background: url('src/images/web/aboutUs/img.png') no-repeat center center;
        background-size: cover;
        color: white;
        padding: 100px 0;
        position: relative;
    }
    .banner-section::before {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        background: rgba(0, 0, 0, 0.5); /* Overlay for text readability */
    }
    .banner-content {
        position: relative;
        z-index: 1;
    }
    .team-profile-img {
        width: 180px;
        height: 180px;
        object-fit: cover;
        border-radius: 50%;
        margin-bottom: 15px;
        border: 4px solid #fff;
        box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
    }
</style>

<div class="banner-section text-center">
    <div class="container banner-content">
        <h1 class="display-4 fw-bold">About Us</h1>
        <p class="lead">Building the future of commerce, one connection at a time</p>
    </div>
</div>

<div class="container py-5">
    <div class="row align-items-center">
        <div class="col-md-6">
            <h2 class="fw-bold mb-4">Our Mission</h2>
            <p>We're on a mission to revolutionize how businesses connect, trade, and grow in the digital age. Our platform brings together buyers and sellers, creating a seamless ecosystem for commerce.</p>
            <div class="row mt-4">
                <div class="col-md-6">
                    <div class="card mb-3">
                        <div class="card-body p-4">
                            <div class="d-flex align-items-center">
                                <i class="fas fa-users feature-icon me-3"></i>
                                <div>
                                    <h6 class="fw-bold mb-0">10K+ Users</h6>
                                    <small class="text-muted">Active community members</small>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-6">
                     <div class="card mb-3">
                        <div class="card-body p-4">
                             <div class="d-flex align-items-center">
                                <i class="fas fa-globe feature-icon me-3"></i>
                                <div>
                                    <h6 class="fw-bold mb-0">Global Reach</h6>
                                    <small class="text-muted">Operating in 30+ countries</small>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-6 text-center">
            <img src="src/images/web/aboutUs/img.png" alt="Our Mission" class="img-fluid rounded shadow">
        </div>
    </div>
</div>

<div class="py-5" style="background: #f8fafc;">
    <div class="container">
        <h2 class="text-center fw-bold mb-5">Our Journey</h2>
        <div class="row text-center">
            <div class="col-md-4">
                <div class="card h-100 p-4">
                    <div class="card-body">
                        <i class="fas fa-rocket feature-icon mb-3"></i>
                        <h5 class="card-title fw-bold">2020</h5>
                        <p class="card-text">Founded with a vision to transform digital commerce</p>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card h-100 p-4">
                    <div class="card-body">
                        <i class="fas fa-chart-line feature-icon mb-3"></i>
                        <h5 class="card-title fw-bold">2022</h5>
                        <p class="card-text">Expanded to 15 countries and reached 5K users</p>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card h-100 p-4">
                    <div class="card-body">
                        <i class="fas fa-globe-americas feature-icon mb-3"></i>
                        <h5 class="card-title fw-bold">2025</h5>
                        <p class="card-text">Leading platform with global recognition</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="container py-5">
    <h2 class="text-center fw-bold mb-5">Our Leadership Team</h2>
    <div class="row text-center">
        <div class="col-md-4">
            <img src="src/images/web/aboutUs/profile_1.png" alt="Sarah Johnson" class="team-profile-img">
            <h5 class="fw-bold mb-0">Sarah Johnson</h5>
            <p class="text-muted">CEO & Founder</p>
        </div>
        <div class="col-md-4">
            <img src="src/images/web/aboutUs/profile_2.png" alt="Michael Chen" class="team-profile-img">
            <h5 class="fw-bold mb-0">Michael Chen</h5>
            <p class="text-muted">Chief Technology Officer</p>
        </div>
        <div class="col-md-4">
            <img src="src/images/web/aboutUs/profile_3.png" alt="Emma Williams" class="team-profile-img">
            <h5 class="fw-bold mb-0">Emma Williams</h5>
            <p class="text-muted">Chief Operations Officer</p>
        </div>
    </div>
</div>

<div class="py-5 text-center" style="background: #2563eb; color: white;">
    <div class="container">
        <h2 class="fw-bold mb-3">Join Our Growing Community</h2>
        <p class="lead mb-4">Be part of the future of digital commerce</p>
        <a href="#" class="btn btn-light btn-lg me-2">Get Started</a>
        <a href="#" class="btn btn-outline-light btn-lg">Learn More</a>
    </div>
</div>

<?php include 'components/footer.php'; ?> 