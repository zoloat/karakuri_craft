<?php
declare(strict_types=1);

/**
 * Shared dashboard page header.
 * Expected vars:
 * - $pageTitle (string)
 * - $pageSubtitle (string|null)
 */
$pageTitle = isset($pageTitle) ? (string) $pageTitle : 'Karakuri';
$pageSubtitle = isset($pageSubtitle) ? (string) $pageSubtitle : '';
?>
<section class="odeko-bar">
  <div>
    <h1><?= htmlspecialchars($pageTitle, ENT_QUOTES, 'UTF-8') ?></h1>
    <?php if ($pageSubtitle !== ''): ?>
      <p><?= htmlspecialchars($pageSubtitle, ENT_QUOTES, 'UTF-8') ?></p>
    <?php endif; ?>
  </div>
  <?= kr_lang_switcher_html() ?>
</section>
