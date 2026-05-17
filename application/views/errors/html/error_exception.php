<?php defined('BASEPATH') OR exit('No direct script access allowed'); ?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="utf-8">
<title>Exception — SIBERKAH SUMUT</title>
<style>
  body { background:#f8f9fa; font-family: Arial, sans-serif; margin:0; padding:40px; color:#333; }
  .box { background:#fff; border:1px solid #e0e0e0; border-left:5px solid #6f42c1; border-radius:4px; max-width:760px; margin:0 auto; padding:28px 32px; }
  h1 { margin:0 0 6px; font-size:18px; color:#6f42c1; }
  p { margin:6px 0; font-size:13px; line-height:1.6; }
  .meta { font-size:12px; color:#888; margin-top:12px; }
  .badge { display:inline-block; background:#6f42c1; color:#fff; font-size:11px; padding:2px 8px; border-radius:3px; margin-bottom:14px; }
</style>
</head>
<body>
<div class="box">
  <span class="badge">Exception</span>
  <h1><?php echo isset($exception) ? htmlspecialchars(get_class($exception)) : 'Exception'; ?></h1>
  <p><?php echo isset($exception) ? htmlspecialchars($exception->getMessage()) : ''; ?></p>
  <?php if (isset($exception)): ?>
  <p class="meta"><?php echo htmlspecialchars($exception->getFile()); ?> &bull; line <?php echo (int)$exception->getLine(); ?></p>
  <?php endif; ?>
</div>
</body>
</html>
