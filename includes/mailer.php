<?php
// includes/mailer.php
require_once __DIR__ . '/config.php';

function sendEmail(string $to, string $toName, string $subject, string $htmlBody): bool {
    if (USE_SMTP) {
        // If you have PHPMailer installed via Composer, use it here.
        // For now falls through to mail().
    }

    $headers  = "MIME-Version: 1.0\r\n";
    $headers .= "Content-Type: text/html; charset=UTF-8\r\n";
    $headers .= "From: " . MAIL_FROM_NAME . " <" . MAIL_FROM . ">\r\n";
    $headers .= "Reply-To: " . MAIL_FROM . "\r\n";
    $headers .= "X-Mailer: PHP/" . phpversion();

    return @mail($to, $subject, $htmlBody, $headers);
}

function sendVerificationEmail(array $user): bool {
    $verifyUrl = APP_URL . '/verify.php?token=' . urlencode($user['verification_token']);
    $subject   = 'Verify Your CvSU ERB Account';

    $html = <<<HTML
<!DOCTYPE html>
<html>
<head><meta charset="UTF-8"></head>
<body style="font-family:'Segoe UI',Arial,sans-serif;background:#f4f6f9;margin:0;padding:30px;">
  <div style="max-width:600px;margin:auto;background:#fff;border-radius:12px;overflow:hidden;box-shadow:0 4px 24px rgba(0,0,0,.08);">
    <div style="background:linear-gradient(135deg,#1a4731 0%,#2d7a4f 100%);padding:36px 40px;text-align:center;">
      <h1 style="color:#f5c842;font-size:22px;margin:0 0 6px;">Cavite State University</h1>
      <p style="color:#a8d5b5;margin:0;font-size:14px;">Ethics Review Board — Online Submission System</p>
    </div>
    <div style="padding:40px;">
      <h2 style="color:#1a4731;font-size:20px;margin:0 0 16px;">Verify Your Email Address</h2>
      <p style="color:#444;line-height:1.7;">Dear <strong>{$user['full_name']}</strong>,</p>
      <p style="color:#444;line-height:1.7;">Thank you for registering. Please click the button below to verify your email address and activate your account.</p>
      <div style="text-align:center;margin:32px 0;">
        <a href="{$verifyUrl}"
           style="background:#2d7a4f;color:#fff;text-decoration:none;padding:14px 36px;border-radius:8px;font-size:15px;font-weight:600;display:inline-block;">
          Verify My Account
        </a>
      </div>
      <p style="color:#888;font-size:13px;line-height:1.7;">Or copy and paste this link into your browser:<br>
        <a href="{$verifyUrl}" style="color:#2d7a4f;word-break:break-all;">{$verifyUrl}</a>
      </p>
      <p style="color:#888;font-size:12px;margin-top:24px;">This link will remain active. If you did not register, please ignore this email.</p>
    </div>
    <div style="background:#f0f0f0;padding:20px 40px;text-align:center;">
      <p style="margin:0;color:#888;font-size:12px;">© <?= date('Y') ?> Cavite State University — Ethics Review Board</p>
    </div>
  </div>
</body>
</html>
HTML;

    return sendEmail($user['email'], $user['full_name'], $subject, $html);
}

function sendSubmissionConfirmation(array $user, array $submission): bool {
    $subject = "ERB Submission Received – {$submission['tracking_number']}";
    $date    = date('F d, Y \a\t h:i A', strtotime($submission['submitted_at']));
    $typeMap = [
        'initial'          => 'Initial Submission',
        'review_student'   => 'For Review (Student)',
        'review_funding'   => 'For Review (Funding)',
        'resubmission'     => 'Resubmission',
    ];
    $typeLabel = $typeMap[$submission['submission_type']] ?? $submission['submission_type'];

    $html = <<<HTML
<!DOCTYPE html>
<html>
<head><meta charset="UTF-8"></head>
<body style="font-family:'Segoe UI',Arial,sans-serif;background:#f4f6f9;margin:0;padding:30px;">
  <div style="max-width:600px;margin:auto;background:#fff;border-radius:12px;overflow:hidden;box-shadow:0 4px 24px rgba(0,0,0,.08);">
    <div style="background:linear-gradient(135deg,#1a4731 0%,#2d7a4f 100%);padding:36px 40px;text-align:center;">
      <h1 style="color:#f5c842;font-size:22px;margin:0 0 6px;">Cavite State University</h1>
      <p style="color:#a8d5b5;margin:0;font-size:14px;">Ethics Review Board — Online Submission System</p>
    </div>
    <div style="padding:40px;">
      <h2 style="color:#1a4731;font-size:20px;margin:0 0 20px;">Submission Confirmed ✓</h2>
      <p style="color:#444;line-height:1.7;">Dear <strong>{$user['full_name']}</strong>,</p>
      <p style="color:#444;line-height:1.7;">Your ERB submission has been received. Below are your submission details for your records.</p>
      <table style="width:100%;border-collapse:collapse;margin:24px 0;font-size:14px;">
        <tr style="background:#f0f7f3;">
          <td style="padding:12px 16px;color:#555;font-weight:600;width:40%;">Tracking Number</td>
          <td style="padding:12px 16px;color:#1a4731;font-weight:700;font-size:16px;">{$submission['tracking_number']}</td>
        </tr>
        <tr>
          <td style="padding:12px 16px;color:#555;font-weight:600;">Date Submitted</td>
          <td style="padding:12px 16px;color:#333;">{$date}</td>
        </tr>
        <tr style="background:#f0f7f3;">
          <td style="padding:12px 16px;color:#555;font-weight:600;">Proponent</td>
          <td style="padding:12px 16px;color:#333;">{$submission['proponent_name']}</td>
        </tr>
        <tr>
          <td style="padding:12px 16px;color:#555;font-weight:600;">College / Unit</td>
          <td style="padding:12px 16px;color:#333;">{$submission['college']}</td>
        </tr>
        <tr style="background:#f0f7f3;">
          <td style="padding:12px 16px;color:#555;font-weight:600;">Submission Type</td>
          <td style="padding:12px 16px;color:#333;">{$typeLabel}</td>
        </tr>
        <tr>
          <td style="padding:12px 16px;color:#555;font-weight:600;">Status</td>
          <td style="padding:12px 16px;"><span style="background:#fff3cd;color:#856404;padding:4px 12px;border-radius:20px;font-size:12px;font-weight:600;">PENDING REVIEW</span></td>
        </tr>
      </table>
      <div style="background:#e8f4ec;border-left:4px solid #2d7a4f;padding:16px 20px;border-radius:6px;margin-bottom:24px;">
        <p style="margin:0;color:#1a4731;font-size:13px;line-height:1.6;">
          Please keep your tracking number safe. You may use it to follow up on the status of your submission with the ERB Secretariat.
        </p>
      </div>
      <p style="color:#666;font-size:13px;line-height:1.7;">
        If you have questions, please contact the ERB Secretariat at <a href="mailto:erb@cvsu.edu.ph" style="color:#2d7a4f;">erb@cvsu.edu.ph</a>.
      </p>
    </div>
    <div style="background:#f0f0f0;padding:20px 40px;text-align:center;">
      <p style="margin:0;color:#888;font-size:12px;">© 2024 Cavite State University — Ethics Review Board</p>
      <p style="margin:4px 0 0;color:#aaa;font-size:11px;">This is an automated message. Please do not reply directly to this email.</p>
    </div>
  </div>
</body>
</html>
HTML;

    return sendEmail($user['email'], $user['full_name'], $subject, $html);
}
