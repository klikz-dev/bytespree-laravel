<?php
defined('BASEPATH') OR exit('No direct script access allowed');
$unhandled = ($uncaught) ? ' unhandled ' : ' ';
?>

An<?php echo $unhandled ?>error was encontered

Level      : <?php echo $level; ?>

Message    : <?php echo $message; ?>

<?php if (!empty($extra)) : ?>
Extra      :
<?php echo json_encode($extra, JSON_PRETTY_PRINT); ?>
<?php endif ?>

