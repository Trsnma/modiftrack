</main>
<?php if (!empty($shell)): $b = $base ?? '../'; ?>
</div>
<footer class="land-foot app-foot">
  <div class="foot-grid">
    <div>
      <p class="foot-brand">ModifTrack</p>
      <p>Katalog dan estimasi biaya modifikasi kendaraan.</p>
    </div>
    <nav aria-label="Menu">
      <h3>Menu</h3>
      <ul>
        <li><a href="<?= $b ?>pages/dashboard.php">Home</a></li>
        <li><a href="<?= $b ?>pages/kendaraan.php">Garasi</a></li>
        <li><a href="<?= $b ?>pages/katalog.php">Katalog</a></li>
        <li><a href="<?= $b ?>pages/build_plan.php">Build Plan</a></li>
        <?php if (($_SESSION['role'] ?? '') === 'admin'): ?>
          <li><a href="<?= $b ?>pages/admin_katalog.php">Admin</a></li>
        <?php endif; ?>
      </ul>
    </nav>
    <nav aria-label="Akun">
      <h3>Akun</h3>
      <ul>
        <li><a href="<?= $b ?>index.php">Beranda situs</a></li>
        <li><a href="<?= $b ?>auth/logout.php">Keluar</a></li>
      </ul>
    </nav>
  </div>
  <p class="foot-copy">Copyright &copy; <?= date('Y') ?> ModifTrack. All rights reserved.</p>
</footer>
<?php endif; ?>
<script src="<?= $base ?? '../' ?>assets/js/app.js"></script>
</body>
</html>