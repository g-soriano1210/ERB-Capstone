<?php
// index.php — Landing Page
require_once __DIR__ . '/includes/config.php';
$pageTitle = 'Home';
$activePage = 'home';
include __DIR__ . '/includes/header.php';
$user = currentUser();
?>

<main class="page-main">

  <!-- ── Hero ────────────────────────────────────────────────────────────── -->
  <section class="hero">
    <div class="hero-container">
      <div class="hero-badge">🏛️ Cavite State University</div>
      <h1>
        Ethics Review Board<br>
        <span>Online Submission</span> System
      </h1>
      <p>
        A streamlined platform for submitting, tracking, and managing research ethics protocols
        in compliance with the Data Privacy Act of 2012 and national research ethics guidelines.
      </p>
      <div class="hero-actions">
        <?php if ($user && $user['role'] === 'researcher'): ?>
          <a href="<?= APP_URL ?>/submit.php" class="btn btn-primary btn-lg">📄 Submit a Protocol</a>
          <a href="<?= APP_URL ?>/my-submissions.php" class="btn btn-outline btn-lg">My Submissions</a>
        <?php elseif ($user && in_array($user['role'], ['reviewer','admin'])): ?>
          <a href="<?= APP_URL ?>/dashboard.php" class="btn btn-primary btn-lg">📊 Go to Dashboard</a>
        <?php else: ?>
          <a href="<?= APP_URL ?>/register.php" class="btn btn-primary btn-lg">Get Started</a>
          <a href="<?= APP_URL ?>/login.php" class="btn btn-outline btn-lg">Sign In</a>
        <?php endif; ?>
      </div>
    </div>
  </section>

  <!-- ── Features ────────────────────────────────────────────────────────── -->
  <section class="section" style="background: var(--white);">
    <div class="container">
      <div class="section-label">Why use this system</div>
      <h2 class="section-title">Research ethics made simple</h2>
      <p class="section-desc">
        Everything you need to submit your IRB/ERB protocol — from initial submission
        to final approval — in one secure, paperless platform.
      </p>

      <div class="features-grid">
        <div class="feature-card">
          <div class="feature-icon">🔒</div>
          <h3>Data Privacy Compliant</h3>
          <p>Built in full compliance with Republic Act 10173 — Data Privacy Act of 2012. Your research data is protected.</p>
        </div>
        <div class="feature-card">
          <div class="feature-icon">📋</div>
          <h3>Smart Form Flow</h3>
          <p>Adaptive submission forms that show only the documents required for your specific submission type.</p>
        </div>
        <div class="feature-card">
          <div class="feature-icon">📧</div>
          <h3>Instant Confirmation</h3>
          <p>Receive an email confirmation with your unique tracking number immediately after submission.</p>
        </div>
        <div class="feature-card">
          <div class="feature-icon">🎓</div>
          <h3>CvSU Exclusive</h3>
          <p>Restricted to verified <strong>@cvsu.edu.ph</strong> email addresses, ensuring institutional integrity.</p>
        </div>
        <div class="feature-card">
          <div class="feature-icon">👁️</div>
          <h3>Track Your Submission</h3>
          <p>Monitor the real-time status of your protocol — pending, under review, approved, or needing revision.</p>
        </div>
        <div class="feature-card">
          <div class="feature-icon">📂</div>
          <h3>Multiple Submission Types</h3>
          <p>Supports Initial, For Review (Student), For Review (Funding), and Resubmission protocols.</p>
        </div>
      </div>
    </div>
  </section>

  <!-- ── How It Works ─────────────────────────────────────────────────────── -->
  <section class="section">
    <div class="container" style="text-align:center;">
      <div class="section-label">How it works</div>
      <h2 class="section-title">From submission to approval</h2>

      <div class="steps-list">
        <div class="step-item">
          <div class="step-num">1</div>
          <div class="step-content">
            <h4>Register with your CvSU email</h4>
            <p>Only <strong>@cvsu.edu.ph</strong> email addresses are accepted. Create your account in seconds.</p>
          </div>
        </div>
        <div class="step-item">
          <div class="step-num">2</div>
          <div class="step-content">
            <h4>Agree to the Data Privacy Notice</h4>
            <p>Read and consent to the data handling policy as required by RA 10173.</p>
          </div>
        </div>
        <div class="step-item">
          <div class="step-num">3</div>
          <div class="step-content">
            <h4>Fill out your personal information</h4>
            <p>Enter your name, college, designation, and select your submission type.</p>
          </div>
        </div>
        <div class="step-item">
          <div class="step-num">4</div>
          <div class="step-content">
            <h4>Upload required documents</h4>
            <p>The system shows exactly which files are needed based on your submission type.</p>
          </div>
        </div>
        <div class="step-item">
          <div class="step-num">5</div>
          <div class="step-content">
            <h4>Receive your tracking number</h4>
            <p>Get an email confirmation with your ERB tracking number and submission details.</p>
          </div>
        </div>
      </div>

      <div style="margin-top:48px;">
        <?php if (!$user): ?>
          <a href="<?= APP_URL ?>/register.php" class="btn btn-green btn-lg">Create Your Account →</a>
        <?php endif; ?>
      </div>
    </div>
  </section>

  <!-- ── Submission Types ──────────────────────────────────────────────────── -->
  <section class="section" style="background: var(--green-900); color: var(--white);">
    <div class="container">
      <div class="section-label" style="color: var(--gold-400);">Submission types</div>
      <h2 class="section-title" style="color: var(--white);">What can you submit?</h2>

      <div class="features-grid" style="margin-top: 36px;">
        <?php
        $types = [
          ['Initial', '📝', 'For brand-new protocols not yet reviewed. Includes Informed Consent, Research Instrument, and Short Description Form.'],
          ['For Review (Student)', '🎓', 'Student research proposals for ERB review. Requires CV, Proposal, Certificates of Validation, Routing Slip, and Protocol Package.'],
          ['For Review (Funding)', '💰', 'Research with external funding seeking ERB clearance. Similar to student review with an added Proof of Review.'],
          ['Resubmission', '🔄', 'For protocols previously reviewed and returned. Submit your Resubmission Request and updated Resubmission Form.'],
        ];
        foreach ($types as [$title, $icon, $desc]):
        ?>
        <div class="feature-card" style="background: rgba(255,255,255,.06); border-color: rgba(255,255,255,.1);">
          <div class="feature-icon"><?= $icon ?></div>
          <h3 style="color: var(--gold-300);"><?= $title ?></h3>
          <p style="color: rgba(255,255,255,.65);"><?= $desc ?></p>
        </div>
        <?php endforeach; ?>
      </div>
    </div>
  </section>

</main>

<?php include __DIR__ . '/includes/footer.php'; ?>
