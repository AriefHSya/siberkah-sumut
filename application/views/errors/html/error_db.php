<?php defined('BASEPATH') OR exit('No direct script access allowed'); ?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="utf-8">
<title>Database Error — SIBERKAH SUMUT</title>
<style>
  body { background:#f8f9fa; font-family: Arial, sans-serif; margin:0; padding:40px; color:#333; }
  .box { background:#fff; border:1px solid #e0e0e0; border-left:5px solid #6f42c1; border-radius:4px; max-width:700px; margin:0 auto; padding:30px 35px; }
  h1 { margin:0 0 8px; font-size:20px; color:#6f42c1; }
  p { margin:8px 0; font-size:14px; line-height:1.6; }
  .badge { display:inline-block; background:#6f42c1; color:#fff; font-size:11px; padding:2px 8px; border-radius:3px; margin-bottom:16px; }
</style>
</head>
<body>
<div class="box">
  <span class="badge">Database Error</span>
  <h1><?php echo $heading; ?></h1>
  <?php if (is_array($message)): foreach ($message as $msg): ?>
    <p><?php echo $msg; ?></p>
  <?php endforeach; else: ?>
    <p><?php echo $message; ?></p>
  <?php endif; ?>
</div>
</body>
</html>
