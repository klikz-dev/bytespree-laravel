<!DOCTYPE html>
<head>
    <title>Bytespree Heartbeat</title>
    <link rel="stylesheet" href="<?php echo getenv('DMIUX_URL'); ?>/css/main.css">
</head>
<body id="dmiux_body" class="dmiux_body dmiux_body_loaded">
    <div class="dmiux_grid-cont">
        <div class="dmiux_grid-row dmiux_mt100">
            <div class="dmiux_grid-col dmiux_grid-col_auto dmiux_fw700">Site is Up</div>
        </div>
        <div class="dmiux_grid-row dmiux_mt100">
            <div class="dmiux_grid-col dmiux_grid-col_auto">To login to Bytespree, visit <a href="<?php echo config('orchestration.url'); ?>"><?php echo config('orchestration.url'); ?></a></div>
        </div>
    </div>
</body>
<html>