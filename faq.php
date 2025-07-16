<?php include 'components/header.php'; ?>

<style>
    .faq-search-section {
        background: #f8fafc;
        padding: 60px 0;
        text-align: center;
    }
    .faq-search-input {
        max-width: 600px;
        margin: 20px auto 0;
        position: relative;
    }
     .faq-search-input .form-control {
        padding-right: 40px;
     }
    .faq-search-input i {
        position: absolute;
        top: 50%;
        right: 15px;
        transform: translateY(-50%);
        color: #6c757d;
    }
     .popular-topics-card {
        background-color: #fff;
        border: none;
        border-radius: 8px;
        padding: 20px;
        text-align: center;
        height: 100%;
        box-shadow: 0 2px 10px rgba(0, 0, 0, 0.08);
    }
    .popular-topics-icon {
        font-size: 2rem;
        color: #2563eb;
        margin-bottom: 10px;
    }
    .accordion-button:not(.collapsed) {
        color: #2563eb;
        background-color: #e9ecef;
        box-shadow: inset 0 -1px 0 rgba(0, 0, 0, .125);
    }
     .accordion-button {
        font-weight: bold;
     }
     .accordion-item {
        border: 1px solid rgba(0, 0, 0, .125);
        margin-bottom: 10px;
        border-radius: 8px !important;
        overflow: hidden;
     }
    .accordion-body {
        color: #495057;
    }
    .contact-support-section {
        text-align: center;
        padding: 50px 0;
    }
</style>

<div class="faq-search-section">
    <div class="container">
        <h2 class="fw-bold">How can we help you?</h2>
        <div class="faq-search-input">
            <input type="text" class="form-control form-control-lg" placeholder="Search for help...">
            <i class="fas fa-search"></i>
        </div>
    </div>
</div>

<div class="container py-5">
    <h3 class="fw-bold text-center mb-5">Popular Topics</h3>
    <div class="row">
        <div class="col-md-4 mb-4">
            <div class="popular-topics-card">
                <i class="fas fa-file-invoice-dollar popular-topics-icon"></i>
                <h5 class="fw-bold">Billing & Payments</h5>
                <p class="text-muted mb-0">Manage your subscription and payment methods</p>
            </div>
        </div>
        <div class="col-md-4 mb-4">
            <div class="popular-topics-card">
                 <i class="fas fa-user-cog popular-topics-icon"></i>
                <h5 class="fw-bold">Account Settings</h5>
                <p class="text-muted mb-0">Update your profile and preferences</p>
            </div>
        </div>
        <div class="col-md-4 mb-4">
            <div class="popular-topics-card">
                 <i class="fas fa-shield-alt popular-topics-icon"></i>
                <h5 class="fw-bold">Security</h5>
                <p class="text-muted mb-0">Password and authentication help</p>
            </div>
        </div>
    </div>
</div>

<div class="py-5" style="background: #f8fafc;">
    <div class="container">
        <h3 class="fw-bold text-center mb-5">Frequently Asked Questions</h3>
        <div class="accordion" id="faqAccordion">
            <div class="accordion-item">
                <h2 class="accordion-header" id="headingOne">
                    <button class="accordion-button" type="button" data-bs-toggle="collapse" data-bs-target="#collapseOne" aria-expanded="true" aria-controls="collapseOne">
                        How do I reset my password?
                    </button>
                </h2>
                <div id="collapseOne" class="accordion-collapse collapse show" aria-labelledby="headingOne" data-bs-parent="#faqAccordion">
                    <div class="accordion-body">
                        Click on the "Forgot Password" link on the login page and follow the instructions sent to your email.
                    </div>
                </div>
            </div>
            <div class="accordion-item">
                <h2 class="accordion-header" id="headingTwo">
                    <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseTwo" aria-expanded="false" aria-controls="collapseTwo">
                        How do I cancel my subscription?
                    </button>
                </h2>
                <div id="collapseTwo" class="accordion-collapse collapse" aria-labelledby="headingTwo" data-bs-parent="#faqAccordion">
                    <div class="accordion-body">
                        Go to Account Settings > Billing > Cancel Subscription. Follow the prompts to complete cancellation.
                    </div>
                </div>
            </div>
            <div class="accordion-item">
                <h2 class="accordion-header" id="headingThree">
                    <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseThree" aria-expanded="false" aria-controls="collapseThree">
                        How can I update my payment method?
                    </button>
                </h2>
                <div id="collapseThree" class="accordion-collapse collapse" aria-labelledby="headingThree" data-bs-parent="#faqAccordion">
                    <div class="accordion-body">
                        Navigate to Account Settings > Billing > Payment Methods to add or update your payment information.
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="container py-5">
    <div class="contact-support-section">
        <h3 class="fw-bold mb-3">Still need help?</h3>
        <p class="text-muted mb-4">Our support team is here to assist you</p>
        <a href="#" class="btn btn-primary btn-lg"><i class="far fa-life-ring me-2"></i> Contact Support</a>
    </div>
</div>

<?php include 'components/footer.php'; ?> 