<!doctype html>
<html lang="en">
    <head>
        <!-- Required meta tags -->
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1, user-scalable=no">
        <link rel="apple-touch-icon" sizes="57x57" href="/assets/fav/apple-icon-57x57.png">
        <link rel="apple-touch-icon" sizes="60x60" href="/assets/fav/apple-icon-60x60.png">
        <link rel="apple-touch-icon" sizes="72x72" href="/assets/fav/apple-icon-72x72.png">
        <link rel="apple-touch-icon" sizes="76x76" href="/assets/fav/apple-icon-76x76.png">
        <link rel="apple-touch-icon" sizes="114x114" href="/assets/fav/apple-icon-114x114.png">
        <link rel="apple-touch-icon" sizes="120x120" href="/assets/fav/apple-icon-120x120.png">
        <link rel="apple-touch-icon" sizes="144x144" href="/assets/fav/apple-icon-144x144.png">
        <link rel="apple-touch-icon" sizes="152x152" href="/assets/fav/apple-icon-152x152.png">
        <link rel="apple-touch-icon" sizes="180x180" href="/assets/fav/apple-icon-180x180.png">
        <link rel="icon" type="image/png" sizes="192x192"  href="/assets/fav/android-icon-192x192.png">
        <link rel="icon" type="image/png" sizes="32x32" href="/assets/fav/favicon-32x32.png">
        <link rel="icon" type="image/png" sizes="96x96" href="/assets/fav/favicon-96x96.png">
        <link rel="icon" type="image/png" sizes="16x16" href="/assets/fav/favicon-16x16.png">
        <link rel="manifest" href="/assets/fav/manifest.json">
        <meta name="msapplication-TileColor" content="#ffffff">
        <meta name="msapplication-TileImage" content="/assets/fav/ms-icon-144x144.png">
        <meta name="theme-color" content="#ffffff">
        <!-- Bootstrap CSS -->
        <title>Bytespree</title>
        <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/4.0.0/css/bootstrap.min.css">
        <link rel="stylesheet" href="<?php echo getenv('DMIUX_URL') ?>/css/main.css?#{release}#">
        <link rel="stylesheet" type="text/css" href="/assets/css/styles.css?#{release}#">
        <script src="/assets/js/jquery-3.4.1.min.js"></script>
    </head>
    
    <body id="dmiux_body">
        <header class="dmiux_header">
            <div class="dmiux_grid-row dmiux_grid-row_nog dmiux_grid-row_aic">
                <div class="dmiux_grid-col dmiux_grid-col_auto">
                    <a href="/" class="dmiux_logo">
                        <img draggable="false"
                                height="30"
                                width="132"
                                src="/assets/images/logo.png" 
                                alt="bytespree">
                    </a>
                </div>
            </div>
        </header>

        <div class="dmiux_content dmiux_grid-cont">
            <div class="dmiux_grid-row">
                <div class="dmiux_grid-col">
                    <div class="alert alert-danger" role="alert">
                        <h4 class="alert-heading">Uh oh! The page should have been redirected!</h4>
                        <a href="<?php echo $redirect_url; ?>">Click here to get back to where you belong.</a>
                    </div>
                </div>
            </div>
        </div>
        <script>
            $('#dmiux_body').hide();
            try {
                var redirect_uri = "<?php echo $redirect_url; ?>";
                if(window.location.hash) {
                    redirect_uri += encodeURIComponent(window.location.hash);
                }
                location.href = redirect_uri;
            } 
            catch(e) {
                $('#dmiux_body').show();
            }
        </script>
    </body>
</html>