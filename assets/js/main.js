// assets/js/main.js

// ── Navigation ───────────────────────────────────────────────────────────────
function toggleMobileNav() {
  const links = document.querySelector('.nav-links');
  const overlay = document.getElementById('navOverlay');
  const isOpen = links.classList.toggle('open');
  overlay.style.display = isOpen ? 'block' : 'none';
}

function toggleProfileMenu() {
  const dd = document.getElementById('profileDropdown');
  dd.classList.toggle('open');
}

document.addEventListener('click', function(e) {
  const wrap = document.querySelector('.nav-profile-wrap');
  const dd = document.getElementById('profileDropdown');
  if (wrap && dd && !wrap.contains(e.target)) {
    dd.classList.remove('open');
  }
});

// ── Multi-step Form ──────────────────────────────────────────────────────────
let currentStep = 1;

function showStep(n) {
  const steps = document.querySelectorAll('.form-step');
  const dots = document.querySelectorAll('.step-dot');

  steps.forEach((s, i) => {
    s.classList.toggle('active', i + 1 === n);
  });

  dots.forEach((d, i) => {
    d.classList.remove('active', 'done');
    if (i + 1 === n) d.classList.add('active');
    if (i + 1 < n)  d.classList.add('done');
  });

  currentStep = n;
  window.scrollTo({ top: 0, behavior: 'smooth' });
}

function nextStep(n) {
  if (!validateStep(currentStep)) return;
  showStep(n);
}

function prevStep(n) { showStep(n); }

function validateStep(n) {
  const step = document.querySelector(`.form-step[data-step="${n}"]`);
  if (!step) return true;

  let valid = true;

  // Radio groups (privacy)
  step.querySelectorAll('[data-required-radio]').forEach(group => {
    const name = group.dataset.requiredRadio;
    const selected = step.querySelector(`input[name="${name}"]:checked`);
    const err = group.querySelector('.radio-error');
    if (!selected) {
      if (err) err.style.display = 'block';
      valid = false;
    } else {
      if (err) err.style.display = 'none';
    }
  });

  // Privacy must be 'agree'
  const privacyYes = step.querySelector('input[name="privacy"][value="agree"]');
  if (privacyYes && step.querySelector('input[name="privacy"]:checked')) {
    if (!step.querySelector('input[name="privacy"][value="agree"]').checked) {
      showAlert('You must agree to the Data Privacy Notice to proceed.', 'error');
      valid = false;
    }
  }

  // Text inputs
  step.querySelectorAll('[required]').forEach(input => {
    if (input.type === 'radio') return;
    const val = input.value.trim();
    if (!val) {
      input.classList.add('error');
      valid = false;
    } else {
      input.classList.remove('error');
    }
  });

  if (!valid && !step.querySelector('[data-required-radio]')) {
    showAlert('Please fill in all required fields.', 'error');
  }

  return valid;
}

function showAlert(msg, type = 'info') {
  let existing = document.getElementById('formAlert');
  if (!existing) {
    existing = document.createElement('div');
    existing.id = 'formAlert';
    const form = document.getElementById('submissionForm');
    if (form) form.prepend(existing);
  }
  existing.className = `alert alert-${type}`;
  existing.textContent = msg;
  existing.scrollIntoView({ behavior: 'smooth', block: 'center' });
  setTimeout(() => existing.remove(), 5000);
}

// ── Submission Type Toggle ───────────────────────────────────────────────────
function handleSubmissionType(value) {
  const sections = document.querySelectorAll('[data-submission-type]');
  sections.forEach(s => {
    const show = s.dataset.submissionType === value;
    s.style.display = show ? 'block' : 'none';
    s.querySelectorAll('input[type="file"]').forEach(f => {
      f.required = show;
    });
  });
}

// ── Radio styling ────────────────────────────────────────────────────────────
document.addEventListener('change', function(e) {
  if (e.target.type === 'radio') {
    const group = e.target.closest('.radio-group');
    if (group) {
      group.querySelectorAll('.radio-option').forEach(o => o.classList.remove('selected'));
      e.target.closest('.radio-option').classList.add('selected');
    }
  }
});

// ── File Upload Labels ───────────────────────────────────────────────────────
document.addEventListener('change', function(e) {
  if (e.target.type === 'file') {
    const wrap = e.target.closest('.file-upload-wrap');
    if (!wrap) return;
    const nameEl = wrap.querySelector('.file-upload-name');
    if (nameEl) {
      nameEl.textContent = e.target.files.length
        ? e.target.files[0].name
        : '';
    }
  }
});

// Drag and drop
document.addEventListener('dragover', function(e) {
  const wrap = e.target.closest('.file-upload-wrap');
  if (wrap) { e.preventDefault(); wrap.classList.add('dragover'); }
});

document.addEventListener('dragleave', function(e) {
  const wrap = e.target.closest('.file-upload-wrap');
  if (wrap) wrap.classList.remove('dragover');
});

document.addEventListener('drop', function(e) {
  const wrap = e.target.closest('.file-upload-wrap');
  if (!wrap) return;
  e.preventDefault();
  wrap.classList.remove('dragover');
  const input = wrap.querySelector('input[type="file"]');
  if (input && e.dataTransfer.files.length) {
    input.files = e.dataTransfer.files;
    const nameEl = wrap.querySelector('.file-upload-name');
    if (nameEl) nameEl.textContent = e.dataTransfer.files[0].name;
  }
});
