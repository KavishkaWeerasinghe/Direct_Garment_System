<?php include 'components/header.php'; ?>

<style>
    .contact-card {
        background-color: #fff;
        border-radius: 8px;
        padding: 30px;
        text-align: center;
        box-shadow: 0 2px 10px rgba(0, 0, 0, 0.08);
        margin-bottom: 20px;
    }
    .contact-icon {
        font-size: 2.5rem;
        color: #2563eb;
        margin-bottom: 15px;
    }
    .form-control {
        border-radius: 4px;
    }
    .form-label {
        font-weight: 500;
    }
</style>

<div class="py-5" style="background: #f8fafc;">
    <div class="container">
        <div class="text-center mb-5">
            <h2 class="fw-bold">Contact Us</h2>
            <p class="text-muted">Have questions or need assistance? We're here to help! Reach out to our team through any of the methods below.</p>
        </div>

        <div class="row justify-content-center mb-5">
            <div class="col-md-4">
                <div class="contact-card">
                    <i class="fas fa-phone contact-icon"></i>
                    <h5 class="fw-bold">Phone</h5>
                    <p class="mb-1">+1 (555) 123-4567</p>
                    <small class="text-muted">Mon-Fri, 9:00 AM - 6:00 PM</small>
                </div>
            </div>
            <div class="col-md-4">
                <div class="contact-card">
                    <i class="fas fa-envelope contact-icon"></i>
                    <h5 class="fw-bold">Email</h5>
                    <p class="mb-1">support@company.com</p>
                    <small class="text-muted">We'll respond within 24 hours</small>
                </div>
            </div>
            <div class="col-md-4">
                <div class="contact-card">
                    <i class="fas fa-map-marker-alt contact-icon"></i>
                    <h5 class="fw-bold">Office</h5>
                    <p class="mb-1">123 Business Ave, Suite 100</p>
                    <small class="text-muted">San Francisco, CA 94107</small>
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-md-6">
                <h3 class="fw-bold mb-4">Send us a message</h3>
                <form>
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label for="firstName" class="form-label">First Name</label>
                            <input type="text" class="form-control" id="firstName">
                        </div>
                        <div class="col-md-6">
                            <label for="lastName" class="form-label">Last Name</label>
                            <input type="text" class="form-control" id="lastName">
                        </div>
                    </div>
                    <div class="mb-3">
                        <label for="emailAddress" class="form-label">Email Address</label>
                        <input type="email" class="form-control" id="emailAddress">
                    </div>
                     <div class="mb-3">
                        <label for="subject" class="form-label">Subject</label>
                        <input type="text" class="form-control" id="subject">
                    </div>
                    <div class="mb-3">
                        <label for="message" class="form-label">Message</label>
                        <textarea class="form-control" id="message" rows="5"></textarea>
                    </div>
                    <button type="submit" class="btn btn-primary btn-lg">Send Message</button>
                </form>
            </div>
            <div class="col-md-6">
                <img src="src/images/web/map.png" alt="Map" class="img-fluid rounded shadow-sm">
            </div>
        </div>

    </div>
</div>

<?php include 'components/footer.php'; ?> 