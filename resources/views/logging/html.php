<?php
defined('BASEPATH') OR exit('No direct script access allowed');
$unhandled = ($uncaught) ? 'unhandled ' : '';
?>

<div style="border:1px solid #990000;padding-left:20px;margin:0 0 10px 0;">

<h4>An <?php echo $unhandled ?>error was encontered</h4>

<p>Level: <?php echo $level; ?></p>
<p>Message:  <?php echo $message; ?></p>

<?php if (!empty($extra)) : ?>
    <p>Extra :</p>
    <pre><?php echo json_encode($extra, JSON_PRETTY_PRINT); ?></pre>
<?php endif ?>
</div>