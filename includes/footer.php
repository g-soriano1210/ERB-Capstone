<?php // includes/footer.php ?>
<footer class="site-footer">
  <div class="footer-container">
    <div class="footer-brand">
      <span class="logo-leaf">🌿</span>
      <span><strong>CvSU</strong> Ethics Review Board</span>
    </div>
    <p class="footer-copy">
      © <?= date('Y') ?> Cavite State University — All rights reserved.<br>
      <small>Per Section 2 of the Data Privacy Act of 2012, all personal data is handled with strict confidentiality.</small>
    </p>
    <div class="footer-links">
      <a href="mailto:erb@cvsu.edu.ph">erb@cvsu.edu.ph</a>
      <span>·</span>
      <a href="<?= APP_URL ?>/index.php">Home</a>
    </div>
  </div>
</footer>

<div id="navOverlay" class="nav-overlay" onclick="toggleMobileNav()"></div>

<script src="<?= APP_URL ?>/assets/js/main.js"></script>
</body>
</html>
