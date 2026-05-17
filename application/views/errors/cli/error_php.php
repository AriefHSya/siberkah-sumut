<?php defined('BASEPATH') OR exit('No direct script access allowed'); ?>

PHP Error: <?php echo isset($severity) ? $severity : 'Error'; ?>

<?php echo isset($message) ? $message : ''; ?>

<?php if (isset($filepath)): ?>
File: <?php echo $filepath; ?><?php echo isset($line) ? ' (line '.(int)$line.')' : ''; ?>

<?php endif; ?>
