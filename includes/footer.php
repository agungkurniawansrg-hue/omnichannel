</main>
<footer class="app-footer">&copy; <?= date('Y') ?> <?= e(APP_FULL_NAME) ?> &middot; v<?= e(APP_VERSION) ?></footer>
</div><!-- /app-main -->
</div><!-- /app-shell -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="<?= url('assets/js/app.js') ?>"></script>
<?php if (!empty($preJs)) echo '<script>' . $preJs . '</script>'; ?>
<?php if (!empty($extraJs)) foreach ($extraJs as $js): ?>
<script src="<?= e($js) ?>"></script>
<?php endforeach; ?>
<?php if (!empty($inlineJs)) echo '<script>' . $inlineJs . '</script>'; ?>
</body>
</html>
