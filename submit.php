<?php
ob_start();
// submit.php — Multi-step ERB Submission Form
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/mailer.php';
requireLogin();

$user = currentUser();

// Only researchers can submit
if ($user['role'] !== 'researcher') {
    header('Location: ' . APP_URL . '/dashboard.php');
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // ── Validate privacy agreement ──────────────────────────────────────────
    if (($_POST['privacy'] ?? '') !== 'agree') {
        $error = 'You must agree to the Data Privacy Notice to submit.';
    } else {
        $pdo = getPDO();

        $proponentName   = trim($_POST['proponent_name'] ?? '');
        $college         = trim($_POST['college'] ?? '');
        $designation     = trim($_POST['designation'] ?? '');
        $submissionType  = $_POST['submission_type'] ?? '';

        $validTypes = ['initial','review_student','review_funding','resubmission'];

        if (!$proponentName || !$college || !$designation || !in_array($submissionType, $validTypes)) {
            $error = 'Please fill in all required personal information fields.';
        } else {
            // ── File requirements by type ───────────────────────────────────
            $requiredFields = [
                'initial'          => ['informed_consent','research_instrument','short_description'],
                'review_student'   => ['cv_proponent','research_proposal','certificate_validation','routing_slip','protocol_package'],
                'review_funding'   => ['cv_proponent','research_proposal','certificate_validation','proof_of_review','protocol_package'],
                'resubmission'     => ['resubmission_request','resubmission_form'],
            ];

            $required = $requiredFields[$submissionType] ?? [];
            $missingFiles = [];

            foreach ($required as $field) {
                if (empty($_FILES[$field]['name']) || $_FILES[$field]['error'] !== UPLOAD_ERR_OK) {
                    $missingFiles[] = $field;
                }
            }

            if (!empty($missingFiles)) {
                $error = 'Please upload all required documents before submitting.';
            } else {
                // ── Insert submission ───────────────────────────────────────
                $tracking = generateTrackingNumber();
                $stmt = $pdo->prepare('
                    INSERT INTO submissions
                      (user_id, tracking_number, proponent_name, college, designation, submission_type, privacy_agreed)
                    VALUES (?, ?, ?, ?, ?, ?, 1)
                ');
                $stmt->execute([$user['id'], $tracking, $proponentName, $college, $designation, $submissionType]);
                $submissionId = $pdo->lastInsertId();

                // ── Save uploaded files ─────────────────────────────────────
                if (!is_dir(UPLOAD_DIR)) {
                    mkdir(UPLOAD_DIR, 0755, true);
                }

                $submissionDir = UPLOAD_DIR . $submissionId . '/';
                mkdir($submissionDir, 0755, true);

                foreach ($required as $field) {
                    $file = $_FILES[$field];
                    $origName = basename($file['name']);
                    $ext = strtolower(pathinfo($origName, PATHINFO_EXTENSION));

                    if (!in_array($ext, ALLOWED_EXTENSIONS)) {
                        continue; // skip disallowed
                    }

                    $storedName = $field . '_' . time() . '_' . bin2hex(random_bytes(4)) . '.' . $ext;
                    move_uploaded_file($file['tmp_name'], $submissionDir . $storedName);

                    $fStmt = $pdo->prepare('
                        INSERT INTO submission_files
                          (submission_id, file_field, original_name, stored_name, file_size, mime_type)
                        VALUES (?, ?, ?, ?, ?, ?)
                    ');
                    $fStmt->execute([
                        $submissionId, $field, $origName, $storedName,
                        $file['size'], mime_content_type($submissionDir . $storedName)
                    ]);
                }

                // ── Fetch full submission for email ─────────────────────────
                $sub = $pdo->prepare('SELECT * FROM submissions WHERE id = ?');
                $sub->execute([$submissionId]);
                $submission = $sub->fetch();

                // ── Send email ──────────────────────────────────────────────
                sendSubmissionConfirmation($user, $submission);

                // ── Redirect to success ─────────────────────────────────────
                startSession();
                $_SESSION['last_tracking'] = $tracking;
                $_SESSION['last_submission_id'] = $submissionId;
                header('Location: ' . APP_URL . '/success.php');
                exit;
            }
        }
    }
}

$pageTitle = 'Submit Protocol';
$activePage = 'submit';
include __DIR__ . '/includes/header.php';
?>

<main class="page-main">

<div class="page-header">
  <div class="container-md">
    <h1>📄 ERB Protocol Submission</h1>
    <p>Complete all steps below. Fields marked with <strong>*</strong> are required.</p>
  </div>
</div>

<section class="section-sm">
<div class="container-md">

  <?php if ($error): ?>
    <div class="alert alert-error" id="formAlert">⚠️ <?= sanitize($error) ?></div>
  <?php endif; ?>

  <!-- Step Indicator -->
  <div class="step-indicator">
    <div class="step-dot active" id="dot-1">
      <div class="step-circle">1</div>
      <span class="step-dot-label">Privacy</span>
    </div>
    <div class="step-dot" id="dot-2">
      <div class="step-circle">2</div>
      <span class="step-dot-label">Information</span>
    </div>
    <div class="step-dot" id="dot-3">
      <div class="step-circle">3</div>
      <span class="step-dot-label">Documents</span>
    </div>
  </div>

  <form method="POST" action="" enctype="multipart/form-data" id="submissionForm">

    <!-- ── STEP 1: Data Privacy Notice ──────────────────────────────────── -->
    <div class="form-step active" data-step="1">
      <div class="card">
        <div class="card-body">
          <h2 style="font-family:var(--font-display);font-size:22px;margin-bottom:4px;color:var(--green-800);">
            Data Privacy Notice
          </h2>
          <p style="color:var(--neutral-500);font-size:13px;margin-bottom:20px;">
            Please read the following carefully before proceeding.
          </p>

          <div class="privacy-box">
            <h4>Republic Act No. 10173 — Data Privacy Act of 2012</h4>
            <p>
              Data Privacy Notice: Per Section 2(Declaration of Policy) of the Data Privacy Act of 2012, 
              it is the policy of the State to protect the fundamental human right of privacy, of 
              communication while ensuring free flow of information to promote innovation and growth. 
              The State recognizes the vital role of information and communications technology in nation- 
              building and its inherent obligation to ensure that personal information in information and 
              communications systems in the government and in the private sector are secured and protected. 
              As such, information collected from this form shall be held in strict confidence and shall 
              only be used solely for records keeping purposes.
            </p>
          </div>

          <div class="form-group" data-required-radio="privacy">
            <label class="form-label">Do you agree to the Data Privacy Notice? <span class="req">*</span></label>
            <div class="radio-group">
              <label class="radio-option">
                <input type="radio" name="privacy" value="agree" required>
                <span>✅ I agree and consent to the terms of the Data Privacy Notice.</span>
              </label>
              <label class="radio-option">
                <input type="radio" name="privacy" value="disagree">
                <span>❌ I do not agree. (You will not be able to proceed.)</span>
              </label>
            </div>
            <p class="radio-error form-error" style="display:none;">Please select an option to proceed.</p>
          </div>

          <div style="display:flex;justify-content:flex-end;margin-top:8px;">
            <button type="button" class="btn btn-green" onclick="validatePrivacyAndNext()">
              Next: Personal Information →
            </button>
          </div>
        </div>
      </div>
    </div>

    <!-- ── STEP 2: Personal Information ─────────────────────────────────── -->
    <div class="form-step" data-step="2">
      <div class="card">
        <div class="card-body">
          <h2 style="font-family:var(--font-display);font-size:22px;margin-bottom:20px;color:var(--green-800);">
            Personal Information
          </h2>

          <div class="form-group">
            <label class="form-label">
              Name of Proponent / Project Leader
              <span class="req">*</span>
            </label>
            <input type="text" name="proponent_name" class="form-control"
                   placeholder="Surname, First Name, Middle Initial"
                   value="<?= sanitize($_POST['proponent_name'] ?? $user['full_name']) ?>"
                   required>
            <p class="form-hint">e.g. Dela Cruz, Juan A.</p>
          </div>

          <div class="form-group">
            <label class="form-label">College / Campus / Unit <span class="req">*</span></label>
            <select name="college" class="form-control" required>
              <option value="">— Select College / Campus / Unit —</option>
              <?php
              $colleges = [
                'CVMBS',
                'CON',
                'CAS',
                'CEIT',
                'CEMDS',
                'CSPEAR',
                'CCJ',
                'CED',
                'CAFENR',
                'GSOLC',
                'GEN. TRIAS',
                'TANZA',
                'TRECE MARTIRES CITY',
                'CARMONA',
                'NAIC',
                'CAVITE CITY',
                'CCAT',
                'IMUS',
                'SILANG',
                'BACOOR',
                'NCRDEC',
                'SPRINT',
                'GAD',
                'MRDRIC',
                'BRITE Center',
                'DASMARIÑAS',
                'CTHM',
                'COM',
              ];
              $selCollege = $_POST['college'] ?? $user['college'] ?? '';
              foreach ($colleges as $c):
              ?>
              <option value="<?= sanitize($c) ?>" <?= $selCollege === $c ? 'selected' : '' ?>><?= sanitize($c) ?></option>
              <?php endforeach; ?>
            </select>
          </div>

          <div class="form-group">
            <label class="form-label">Designation <span class="req">*</span></label>
            <select name="designation" class="form-control" required>
              <option value="">— Select Designation —</option>
              <?php
              $designations = [
                // Faculty
                'Undergraduate Student',
                'Graduate Student',
                'Faculty',
                'Staff',
              ];
              $selDesig = $_POST['designation'] ?? $user['designation'] ?? '';
              foreach ($designations as $d):
              ?>
              <option value="<?= sanitize($d) ?>" <?= $selDesig === $d ? 'selected' : '' ?>><?= sanitize($d) ?></option>
              <?php endforeach; ?>
            </select>
          </div>


          <div class="form-group">
            <label class="form-label">Type of Submission <span class="req">*</span></label>
            <select name="submission_type" class="form-control" required
                    onchange="handleSubmissionType(this.value)">
              <option value="">— Select submission type —</option>
              <option value="initial"          <?= ($_POST['submission_type']??'')==='initial'         ?'selected':'' ?>>Initial</option>
              <option value="review_student"   <?= ($_POST['submission_type']??'')==='review_student'  ?'selected':'' ?>>For Review (Student)</option>
              <option value="review_funding"   <?= ($_POST['submission_type']??'')==='review_funding'  ?'selected':'' ?>>For Review (Funding)</option>
              <option value="resubmission"     <?= ($_POST['submission_type']??'')==='resubmission'    ?'selected':'' ?>>Resubmission</option>
            </select>
          </div>

          <div style="display:flex;gap:12px;justify-content:space-between;margin-top:8px;flex-wrap:wrap;">
            <button type="button" class="btn btn-outline" onclick="prevStep(1)" style="color:var(--neutral-700);border-color:var(--neutral-300);">
              ← Back
            </button>
            <button type="button" class="btn btn-green" onclick="validateInfoAndNext()">
              Next: Upload Documents →
            </button>
          </div>
        </div>
      </div>
    </div>

    <!-- ── STEP 3: Document Upload (adaptive) ───────────────────────────── -->
    <div class="form-step" data-step="3">
      <div class="card">
        <div class="card-body">
          <h2 style="font-family:var(--font-display);font-size:22px;margin-bottom:6px;color:var(--green-800);">
            Upload Required Documents
          </h2>
          <p style="color:var(--neutral-500);font-size:13px;margin-bottom:24px;">
            Accepted formats: PDF, DOC, DOCX, JPG, PNG. Max size: 20MB per file.
          </p>

          <div id="upload-placeholder" style="display:block;">
            <div class="alert alert-info">⬅️ Please go back and select a <strong>Type of Submission</strong> first to see the required documents.</div>
          </div>

          <!-- Initial -->
          <div data-submission-type="initial" style="display:none;">
            <?php $initialFields = [
              ['informed_consent','Informed Consent Form'],
              ['research_instrument','Research Instrument (Survey, Interview Guide Questions, etc.)'],
              ['short_description','Short Description Form'],
            ]; ?>
            <?php foreach ($initialFields as [$name, $label]): ?>
            <div class="form-group">
              <label class="form-label"><?= $label ?> <span class="req">*</span></label>
              <div class="file-upload-wrap">
                <input type="file" name="<?= $name ?>" accept=".pdf,.doc,.docx,.jpg,.jpeg,.png">
                <div class="file-upload-icon">📎</div>
                <p class="file-upload-label"><strong>Click to upload</strong> or drag and drop</p>
                <p class="file-upload-name"></p>
              </div>
            </div>
            <?php endforeach; ?>
          </div>

          <!-- For Review (Student) -->
          <div data-submission-type="review_student" style="display:none;">
            <?php $studentFields = [
              ['cv_proponent','Curriculum Vitae of Proponent/s and Adviser'],
              ['research_proposal','Research Proposal (with page numbers)'],
              ['certificate_validation','Three (3) Certificate of Validation of Instrument'],
              ['routing_slip','Routing Slip'],
              ['protocol_package','Protocol Package ERB Forms 1–4 (WORD FILE)'],
            ]; ?>
            <?php foreach ($studentFields as [$name, $label]): ?>
            <div class="form-group">
              <label class="form-label"><?= $label ?> <span class="req">*</span></label>
              <div class="file-upload-wrap">
                <input type="file" name="<?= $name ?>" accept=".pdf,.doc,.docx,.jpg,.jpeg,.png">
                <div class="file-upload-icon">📎</div>
                <p class="file-upload-label"><strong>Click to upload</strong> or drag and drop</p>
                <p class="file-upload-name"></p>
              </div>
            </div>
            <?php endforeach; ?>
          </div>

          <!-- For Review (Funding) -->
          <div data-submission-type="review_funding" style="display:none;">
            <?php $fundingFields = [
              ['cv_proponent','Curriculum Vitae of Proponent/s and Adviser'],
              ['research_proposal','Research Proposal (with page numbers)'],
              ['certificate_validation','Three (3) Certificate of Validation of Instrument'],
              ['proof_of_review','Proof of Review'],
              ['protocol_package','Protocol Package ERB Forms 1–4 (WORD FILE)'],
            ]; ?>
            <?php foreach ($fundingFields as [$name, $label]): ?>
            <div class="form-group">
              <label class="form-label"><?= $label ?> <span class="req">*</span></label>
              <div class="file-upload-wrap">
                <input type="file" name="<?= $name ?>" accept=".pdf,.doc,.docx,.jpg,.jpeg,.png">
                <div class="file-upload-icon">📎</div>
                <p class="file-upload-label"><strong>Click to upload</strong> or drag and drop</p>
                <p class="file-upload-name"></p>
              </div>
            </div>
            <?php endforeach; ?>
          </div>

          <!-- Resubmission -->
          <div data-submission-type="resubmission" style="display:none;">
            <?php $resubFields = [
              ['resubmission_request','Resubmission Request'],
              ['resubmission_form','Resubmission Form'],
            ]; ?>
            <?php foreach ($resubFields as [$name, $label]): ?>
            <div class="form-group">
              <label class="form-label"><?= $label ?> <span class="req">*</span></label>
              <div class="file-upload-wrap">
                <input type="file" name="<?= $name ?>" accept=".pdf,.doc,.docx,.jpg,.jpeg,.png">
                <div class="file-upload-icon">📎</div>
                <p class="file-upload-label"><strong>Click to upload</strong> or drag and drop</p>
                <p class="file-upload-name"></p>
              </div>
            </div>
            <?php endforeach; ?>
          </div>

          <div style="display:flex;gap:12px;justify-content:space-between;margin-top:8px;flex-wrap:wrap;">
            <button type="button" class="btn btn-outline" onclick="prevStep(2)" style="color:var(--neutral-700);border-color:var(--neutral-300);">
              ← Back
            </button>
            <button type="submit" class="btn btn-green btn-lg">
              ✅ Submit Protocol
            </button>
          </div>
        </div>
      </div>
    </div>

  </form>
</div>
</section>
</main>

<script>
function validatePrivacyAndNext() {
  const privacyAgree = document.querySelector('input[name="privacy"][value="agree"]');
  const privacyDisagree = document.querySelector('input[name="privacy"][value="disagree"]');

  if (!privacyAgree.checked && !privacyDisagree.checked) {
    document.querySelector('.radio-error').style.display = 'block';
    return;
  }
  if (privacyDisagree.checked) {
    alert('You must agree to the Data Privacy Notice to proceed with your submission.');
    return;
  }
  nextStep(2);
}

function validateInfoAndNext() {
  const name  = document.querySelector('[name="proponent_name"]').value.trim();
  const col   = document.querySelector('[name="college"]').value.trim();
  const desig = document.querySelector('[name="designation"]').value.trim();
  const type  = document.querySelector('[name="submission_type"]').value;

  if (!name || !col || !desig || !type) {
    alert('Please fill in all required fields and select a submission type.');
    return;
  }

  // Show correct upload section
  handleSubmissionType(type);
  document.getElementById('upload-placeholder').style.display = 'none';

  showStep(3);
}

// Re-sync on step 3 if user goes back and changes type
document.querySelector('[name="submission_type"]').addEventListener('change', function() {
  handleSubmissionType(this.value);
  document.getElementById('upload-placeholder').style.display = 'none';
});

// Pre-select if POST back
<?php if (!empty($_POST['submission_type'])): ?>
handleSubmissionType('<?= sanitize($_POST['submission_type']) ?>');
document.getElementById('upload-placeholder').style.display = 'none';
<?php endif; ?>
</script>

<?php include __DIR__ . '/includes/footer.php'; ?>
