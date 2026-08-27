<?php
$activeNav = 'contact';
$pageTitle = 'Contact Us & FAQs - AVORA';
require_once __DIR__ . "/includes/session.php";
require_once __DIR__ . "/../config/mailer.php";

$submitted = false;
if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $subject = trim($_POST['subject'] ?? '');
    $message = trim($_POST['message'] ?? '');

    if (empty($name) || empty($email) || empty($message)) {
        setFlashMessage("Please fill in all required fields (Name, Email, and Message).", "error");
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        setFlashMessage("Please enter a valid email address.", "error");
    } else {
        $result = sendInquiryEmail($name, $email, $subject, $message);
        if (!empty($result['smtp_sent'])) {
            setFlashMessage("Thank you! Your inquiry has been sent directly to parthsapariyait7@gmail.com via SMTP.", "success");
        } else {
            setFlashMessage($result['message'] ?? "Thank you! Your message has been received.", "success");
        }
    }
    header("Location: contact.php");
    exit;
}

require __DIR__ . "/includes/header.php";
?>

<div class="app-container">
  <!-- Breadcrumbs -->
  <div class="breadcrumbs">
    <a href="index.php">Home</a>
    <span>/</span>
    <span class="active">Contact & Help</span>
  </div>

  <div class="section-header" style="margin-bottom: 2rem;">
    <div>
      <h1 class="section-title">We're Here to Help</h1>
      <p class="section-subtitle">Reach out with questions, inquiries, or feedback.</p>
    </div>
  </div>

  <div class="contact-layout">
    <!-- Contact Form -->
    <div class="summary-card">
      <h2 style="font-size: 1.25rem; font-weight: 800; margin-bottom: 1.25rem; display: flex; align-items: center; gap: 0.5rem;">
        <i data-lucide="mail" style="color: var(--primary-color); width: 20px; height: 20px;"></i>
        Send Us a Message
      </h2>

      <form method="POST" action="contact.php">
        <div class="form-group">
          <label class="form-label">Your Full Name *</label>
          <input type="text" name="name" class="form-input" required placeholder="e.g. Priyanshu Sharma" />
        </div>

        <div class="form-group">
          <label class="form-label">Your Email Address *</label>
          <input type="email" name="email" class="form-input" required placeholder="e.g. priyanshu@example.com" />
        </div>

        <div class="form-group">
          <label class="form-label">Subject</label>
          <input type="text" name="subject" class="form-input" placeholder="e.g. Order Inquiry / Product Question" />
        </div>

        <div class="form-group">
          <label class="form-label">Message *</label>
          <textarea name="message" class="form-textarea" rows="5" required placeholder="How can we assist you today?"></textarea>
        </div>

        <button type="submit" class="btn btn-primary btn-full btn-lg" style="margin-top: 1rem; display: flex; gap: 0.5rem; justify-content: center;">
          <i data-lucide="send" style="width: 18px; height: 18px;"></i> Send Message
        </button>
      </form>
    </div>

    <!-- FAQ Accordion & Quick Contacts -->
    <div>
      <!-- Quick Contact Info -->
      <div style="
        background-color: var(--bg-card);
        border: 1px solid var(--border-color);
        border-radius: var(--radius-lg);
        padding: 1.75rem;
        margin-bottom: 2rem;
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 1.5rem;
      ">
        <div style="display: flex; gap: 0.75rem; align-items: flex-start;">
          <div style="padding: 0.6rem; background: var(--primary-light); color: var(--primary-color); border-radius: 50%;">
            <i data-lucide="phone" style="width: 18px; height: 18px;"></i>
          </div>
          <div>
            <h4 style="font-weight: 700; font-size: 0.9rem;">Customer Helpline</h4>
            <p style="font-size: 0.85rem; color: var(--text-muted); margin-top: 0.15rem;">+91 98765 43210</p>
            <p style="font-size: 0.75rem; color: var(--text-light);">Mon - Sat, 9am - 8pm</p>
          </div>
        </div>

        <div style="display: flex; gap: 0.75rem; align-items: flex-start;">
          <div style="padding: 0.6rem; background: var(--primary-light); color: var(--primary-color); border-radius: 50%;">
            <i data-lucide="mail" style="width: 18px; height: 18px;"></i>
          </div>
          <div>
            <h4 style="font-weight: 700; font-size: 0.9rem;">Direct Inquiry Email</h4>
            <p style="font-size: 0.85rem; color: var(--text-muted); margin-top: 0.15rem; word-break: break-all;"><strong>parthsapariyait7@gmail.com</strong></p>
            <p style="font-size: 0.75rem; color: #16a34a; font-weight: 600; display: inline-flex; align-items: center; gap: 0.25rem; margin-top: 0.25rem;">
              <span style="display:inline-block; width:6px; height:6px; background:#16a34a; border-radius:50%;"></span> Direct SMTP Forwarding Active
            </p>
          </div>
        </div>
      </div>

      <!-- FAQ Section -->
      <h3 style="font-family: var(--font-serif); font-size: 1.5rem; font-weight: 700; color: var(--color-text-primary); margin-bottom: 1.25rem;">Frequently Asked Questions</h3>

      <div class="faq-item active">
        <div class="faq-question">
          <span>What are the shipping costs &amp; delivery timelines?</span>
          <div class="faq-icon-wrap">
            <i data-lucide="chevron-down" style="width: 16px; height: 16px;"></i>
          </div>
        </div>
        <div class="faq-answer">
          We offer FREE standard express shipping on all orders over ₹750.00. For orders under ₹750, a nominal shipping charge applies. Delivery usually takes 3 to 5 business days across India.
        </div>
      </div>

      <div class="faq-item">
        <div class="faq-question">
          <span>How does unit-based stock (e.g., 2kg, 500g) work?</span>
          <div class="faq-icon-wrap">
            <i data-lucide="chevron-down" style="width: 16px; height: 16px;"></i>
          </div>
        </div>
        <div class="faq-answer">
          Our store supports both piece count and unit-based inventory such as weight (2kg, 500g), volume (100ml), and custom variations. The exact stock denomination is displayed on every product card and checkout.
        </div>
      </div>

      <div class="faq-item">
        <div class="faq-question">
          <span>What is your return &amp; exchange policy?</span>
          <div class="faq-icon-wrap">
            <i data-lucide="chevron-down" style="width: 16px; height: 16px;"></i>
          </div>
        </div>
        <div class="faq-answer">
          We offer a 30-day hassle-free return window for all unused and unaltered items in their original packaging. Once inspected, refunds are initiated directly within 3-5 business days.
        </div>
      </div>

      <div class="faq-item">
        <div class="faq-question">
          <span>What payment methods are supported?</span>
          <div class="faq-icon-wrap">
            <i data-lucide="chevron-down" style="width: 16px; height: 16px;"></i>
          </div>
        </div>
        <div class="faq-answer">
          We support Cash on Delivery (COD), UPI (Google Pay, PhonePe, Paytm, BHIM), RuPay, Visa, MasterCard debit &amp; credit cards, and all major Indian net banking services.
        </div>
      </div>
    </div>
  </div>
</div>

<?php require __DIR__ . "/includes/footer.php"; ?>
