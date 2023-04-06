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
    <title>Bytespree | <?php if (is_array(session()->get('breadcrumbs'))) echo session()->get('breadcrumbs')[array_key_last(session()->get('breadcrumbs'))]['title']; else echo ''; ?></title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/twitter-bootstrap/4.0.0/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.10.2/css/all.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/summernote/0.8.12/summernote-bs4.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/vue-multiselect/2.1.0/vue-multiselect.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/notyf/3.10.0/notyf.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/vue-select/3.18.3/vue-select.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/vue-prism-editor@1.3/dist/prismeditor.min.css" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/prism-themes/1.9.0/prism-coy-without-shadows.min.css">
    <link rel="stylesheet" href="/assets/css/tribute.css">
    <link rel="stylesheet" href="<?php echo getenv('DMIUX_URL') ?>/css/main.css?#{release}#">
    <link rel="stylesheet" href="/assets/css/styles.css?#{release}#">

    <script src="/assets/js/jquery-3.4.1.min.js"></script>
    <script src="/assets/js/jquery-ui.min.js"></script>
    <script src="/assets/js/jquery.ui.touch-punch.min.js"></script>
    <script src="/assets/js/tribute.min.js"></script>
    <script src="/assets/js/modules/version_compare.js"></script>
    <script src="<?php echo config('services.dmiux.url'); ?>/js/tooltipster.bundle.min.js?#{release}#"></script>
    <?php if (app()->isProduction()) : ?>
        <?php $token = getenv('BP_ROLLBAR_ACCESS_TOKEN'); ?>
        <script>
            var global_dmiux_path = "<?php echo getenv('DMIUX_URL') ?>";
            var _rollbarConfig = {
                accessToken: "<?php echo $token ?>",
                captureUncaught: true,
                captureUnhandledRejections: true,
                payload: {
                    environment: "<?php echo config('app.env') ?>"
                }
            };
            // Rollbar Snippet
            !function(r){var e={};function o(n){if(e[n])return e[n].exports;var t=e[n]={i:n,l:!1,exports:{}};return r[n].call(t.exports,t,t.exports,o),t.l=!0,t.exports}o.m=r,o.c=e,o.d=function(r,e,n){o.o(r,e)||Object.defineProperty(r,e,{enumerable:!0,get:n})},o.r=function(r){"undefined"!=typeof Symbol&&Symbol.toStringTag&&Object.defineProperty(r,Symbol.toStringTag,{value:"Module"}),Object.defineProperty(r,"__esModule",{value:!0})},o.t=function(r,e){if(1&e&&(r=o(r)),8&e)return r;if(4&e&&"object"==typeof r&&r&&r.__esModule)return r;var n=Object.create(null);if(o.r(n),Object.defineProperty(n,"default",{enumerable:!0,value:r}),2&e&&"string"!=typeof r)for(var t in r)o.d(n,t,function(e){return r[e]}.bind(null,t));return n},o.n=function(r){var e=r&&r.__esModule?function(){return r.default}:function(){return r};return o.d(e,"a",e),e},o.o=function(r,e){return Object.prototype.hasOwnProperty.call(r,e)},o.p="",o(o.s=0)}([function(r,e,o){var n=o(1),t=o(4);_rollbarConfig=_rollbarConfig||{},_rollbarConfig.rollbarJsUrl=_rollbarConfig.rollbarJsUrl||"https://cdnjs.cloudflare.com/ajax/libs/rollbar.js/2.13.0/rollbar.min.js",_rollbarConfig.async=void 0===_rollbarConfig.async||_rollbarConfig.async;var a=n.setupShim(window,_rollbarConfig),l=t(_rollbarConfig);window.rollbar=n.Rollbar,a.loadFull(window,document,!_rollbarConfig.async,_rollbarConfig,l)},function(r,e,o){var n=o(2);function t(r){return function(){try{return r.apply(this,arguments)}catch(r){try{console.error("[Rollbar]: Internal error",r)}catch(r){}}}}var a=0;function l(r,e){this.options=r,this._rollbarOldOnError=null;var o=a++;this.shimId=function(){return o},"undefined"!=typeof window&&window._rollbarShims&&(window._rollbarShims[o]={handler:e,messages:[]})}var i=o(3),s=function(r,e){return new l(r,e)},d=function(r){return new i(s,r)};function c(r){return t(function(){var e=Array.prototype.slice.call(arguments,0),o={shim:this,method:r,args:e,ts:new Date};window._rollbarShims[this.shimId()].messages.push(o)})}l.prototype.loadFull=function(r,e,o,n,a){var l=!1,i=e.createElement("script"),s=e.getElementsByTagName("script")[0],d=s.parentNode;i.crossOrigin="",i.src=n.rollbarJsUrl,o||(i.async=!0),i.onload=i.onreadystatechange=t(function(){if(!(l||this.readyState&&"loaded"!==this.readyState&&"complete"!==this.readyState)){i.onload=i.onreadystatechange=null;try{d.removeChild(i)}catch(r){}l=!0,function(){var e;if(void 0===r._rollbarDidLoad){e=new Error("rollbar.js did not load");for(var o,n,t,l,i=0;o=r._rollbarShims[i++];)for(o=o.messages||[];n=o.shift();)for(t=n.args||[],i=0;i<t.length;++i)if("function"==typeof(l=t[i])){l(e);break}}"function"==typeof a&&a(e)}()}}),d.insertBefore(i,s)},l.prototype.wrap=function(r,e,o){try{var n;if(n="function"==typeof e?e:function(){return e||{}},"function"!=typeof r)return r;if(r._isWrap)return r;if(!r._rollbar_wrapped&&(r._rollbar_wrapped=function(){o&&"function"==typeof o&&o.apply(this,arguments);try{return r.apply(this,arguments)}catch(o){var e=o;throw e&&("string"==typeof e&&(e=new String(e)),e._rollbarContext=n()||{},e._rollbarContext._wrappedSource=r.toString(),window._rollbarWrappedError=e),e}},r._rollbar_wrapped._isWrap=!0,r.hasOwnProperty))for(var t in r)r.hasOwnProperty(t)&&(r._rollbar_wrapped[t]=r[t]);return r._rollbar_wrapped}catch(e){return r}};for(var p="log,debug,info,warn,warning,error,critical,global,configure,handleUncaughtException,handleAnonymousErrors,handleUnhandledRejection,captureEvent,captureDomContentLoaded,captureLoad".split(","),u=0;u<p.length;++u)l.prototype[p[u]]=c(p[u]);r.exports={setupShim:function(r,e){if(r){var o=e.globalAlias||"Rollbar";if("object"==typeof r[o])return r[o];r._rollbarShims={},r._rollbarWrappedError=null;var a=new d(e);return t(function(){e.captureUncaught&&(a._rollbarOldOnError=r.onerror,n.captureUncaughtExceptions(r,a,!0),e.wrapGlobalEventHandlers&&n.wrapGlobals(r,a,!0)),e.captureUnhandledRejections&&n.captureUnhandledRejections(r,a,!0);var t=e.autoInstrument;return!1!==e.enabled&&(void 0===t||!0===t||"object"==typeof t&&t.network)&&r.addEventListener&&(r.addEventListener("load",a.captureLoad.bind(a)),r.addEventListener("DOMContentLoaded",a.captureDomContentLoaded.bind(a))),r[o]=a,a})()}},Rollbar:d}},function(r,e){function o(r,e,o){if(e.hasOwnProperty&&e.hasOwnProperty("addEventListener")){for(var n=e.addEventListener;n._rollbarOldAdd&&n.belongsToShim;)n=n._rollbarOldAdd;var t=function(e,o,t){n.call(this,e,r.wrap(o),t)};t._rollbarOldAdd=n,t.belongsToShim=o,e.addEventListener=t;for(var a=e.removeEventListener;a._rollbarOldRemove&&a.belongsToShim;)a=a._rollbarOldRemove;var l=function(r,e,o){a.call(this,r,e&&e._rollbar_wrapped||e,o)};l._rollbarOldRemove=a,l.belongsToShim=o,e.removeEventListener=l}}r.exports={captureUncaughtExceptions:function(r,e,o){if(r){var n;if("function"==typeof e._rollbarOldOnError)n=e._rollbarOldOnError;else if(r.onerror){for(n=r.onerror;n._rollbarOldOnError;)n=n._rollbarOldOnError;e._rollbarOldOnError=n}e.handleAnonymousErrors();var t=function(){var o=Array.prototype.slice.call(arguments,0);!function(r,e,o,n){r._rollbarWrappedError&&(n[4]||(n[4]=r._rollbarWrappedError),n[5]||(n[5]=r._rollbarWrappedError._rollbarContext),r._rollbarWrappedError=null);var t=e.handleUncaughtException.apply(e,n);o&&o.apply(r,n),"anonymous"===t&&(e.anonymousErrorsPending+=1)}(r,e,n,o)};o&&(t._rollbarOldOnError=n),r.onerror=t}},captureUnhandledRejections:function(r,e,o){if(r){"function"==typeof r._rollbarURH&&r._rollbarURH.belongsToShim&&r.removeEventListener("unhandledrejection",r._rollbarURH);var n=function(r){var o,n,t;try{o=r.reason}catch(r){o=void 0}try{n=r.promise}catch(r){n="[unhandledrejection] error getting `promise` from event"}try{t=r.detail,!o&&t&&(o=t.reason,n=t.promise)}catch(r){}o||(o="[unhandledrejection] error getting `reason` from event"),e&&e.handleUnhandledRejection&&e.handleUnhandledRejection(o,n)};n.belongsToShim=o,r._rollbarURH=n,r.addEventListener("unhandledrejection",n)}},wrapGlobals:function(r,e,n){if(r){var t,a,l="EventTarget,Window,Node,ApplicationCache,AudioTrackList,ChannelMergerNode,CryptoOperation,EventSource,FileReader,HTMLUnknownElement,IDBDatabase,IDBRequest,IDBTransaction,KeyOperation,MediaController,MessagePort,ModalWindow,Notification,SVGElementInstance,Screen,TextTrack,TextTrackCue,TextTrackList,WebSocket,WebSocketWorker,Worker,XMLHttpRequest,XMLHttpRequestEventTarget,XMLHttpRequestUpload".split(",");for(t=0;t<l.length;++t)r[a=l[t]]&&r[a].prototype&&o(e,r[a].prototype,n)}}}},function(r,e){function o(r,e){this.impl=r(e,this),this.options=e,function(r){for(var e=function(r){return function(){var e=Array.prototype.slice.call(arguments,0);if(this.impl[r])return this.impl[r].apply(this.impl,e)}},o="log,debug,info,warn,warning,error,critical,global,configure,handleUncaughtException,handleAnonymousErrors,handleUnhandledRejection,_createItem,wrap,loadFull,shimId,captureEvent,captureDomContentLoaded,captureLoad".split(","),n=0;n<o.length;n++)r[o[n]]=e(o[n])}(o.prototype)}o.prototype._swapAndProcessMessages=function(r,e){var o,n,t;for(this.impl=r(this.options);o=e.shift();)n=o.method,t=o.args,this[n]&&"function"==typeof this[n]&&("captureDomContentLoaded"===n||"captureLoad"===n?this[n].apply(this,[t[0],o.ts]):this[n].apply(this,t));return this},r.exports=o},function(r,e){r.exports=function(r){return function(e){if(!e&&!window._rollbarInitialized){for(var o,n,t=(r=r||{}).globalAlias||"Rollbar",a=window.rollbar,l=function(r){return new a(r)},i=0;o=window._rollbarShims[i++];)n||(n=o.handler),o.handler._swapAndProcessMessages(l,o.messages);window[t]=n,window._rollbarInitialized=!0}}}}]);
            // End Rollbar Snippet
        </script>
        <script src="https://cdn.lr-ingest.io/LogRocket.min.js" crossorigin="anonymous"></script>
        <script>
            window.LogRocket && window.LogRocket.init(
                'qotanr/bytespree'
            );

            LogRocket.identify("<?php echo session()->get("username"); ?>", {
                name: "<?php echo session()->get("user_full_name"); ?>",
                email: "<?php echo session()->get("email"); ?>",
            });
        </script>
        <script src="https://cdnjs.cloudflare.com/ajax/libs/vue/2.6.14/vue.min.js"></script>
    <?php else: ?>
        <script src="https://cdnjs.cloudflare.com/ajax/libs/vue/2.6.14/vue.js"></script>
    <?php endif; ?>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/vue-router/3.5.4/vue-router.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/ace/1.4.12/ace.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/vue-select/3.18.3/vue-select.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/vue-multiselect/2.1.0/vue-multiselect.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/Sortable/1.8.4/Sortable.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/Vue.Draggable/2.20.0/vuedraggable.umd.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/notyf/3.10.0/notyf.min.js"></script>
    <script src="<?php echo getenv('DMIUX_URL') ?>/js/jquery.dataTables.js?#{release}#"></script>
    <script src="https://cdn.jsdelivr.net/npm/vue-prism-editor@1.3/dist/prismeditor.umd.production.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/prism/1.23.0/prism.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/prism/1.23.0/components/prism-sql.min.js"></script>
    <script src="/assets/js/blueprint.global.js?#{release}#"></script>
    <script src="https://js.pusher.com/7.1/pusher.min.js"></script>
    <script>
        var baseUrl = "";

        <?php if(app()->isLocal()): ?>Pusher.logToConsole = true;<?php endif; ?>

        // Set up the javascript side of pusher 
        var pusher = new Pusher('<?php echo config('broadcasting.connections.pusher.key'); ?>', {
            cluster: '<?php echo config('broadcasting.connections.pusher.cluster'); ?>',
            authEndpoint: '/broadcasting/auth',
            userAuthentication: {
                endpoint: "/broadcasting/auth",
                transport: "ajax",
                params: {},
                headers: {},
                customHandler: null,
            },
        });

        var team_channel = pusher.subscribe('private-team-<?php echo session()->get("team"); ?>');
        var user_channel = pusher.subscribe('private-user-<?php echo session()->get("orchestration_id"); ?>');
    </script>
</head>

<body id="dmiux_body">
    <?php if(app()->isProduction()): ?>
        <script>
            window.intercomSettings = {
                app_id: "ubbjplne",
                user_id:    <?php echo json_encode(session()->get("username")) ?>,
                email:      <?php echo json_encode(session()->get("email")) ?>,
                user_hash:  <?php echo json_encode(session()->get("user_hash")) ?>,
                name:       <?php echo json_encode(session()->get("user_full_name")) ?>, // Full name
                created_at: <?php echo json_encode(session()->get("user_created_at")) ?> // Signup date as a Unix timestamp
            };

            // We pre-filled your app ID in the widget URL: 'https://widget.intercom.io/widget/ubbjplne'
            (function(){var w=window;var ic=w.Intercom;if(typeof ic==="function"){ic('reattach_activator');ic('update',w.intercomSettings);}else{var d=document;var i=function(){i.c(arguments);};i.q=[];i.c=function(args){i.q.push(args);};w.Intercom=i;var l=function(){var s=d.createElement('script');s.type='text/javascript';s.async=true;s.src='https://widget.intercom.io/widget/ubbjplne';var x=d.getElementsByTagName('script')[0];x.parentNode.insertBefore(s,x);};if(w.attachEvent){w.attachEvent('onload',l);}else{w.addEventListener('load',l,false);}}})();
        </script>
    <?php endif; ?>
    <script>
        // Create an instance of Notyf
        var notyf = new Notyf({
            duration: 3500,
            dismissible: true,
            position: {
                x: 'left',
                y: 'bottom'
            },
            types: [
                {
                    type: 'warning',
                    background: '#e8c031'
                },
                {
                    type: 'danger',
                    background: 'rgba(255,0,26)'
                },
                {
                    type: 'success',
                    background: '#47a33c'
                },
                {
                    type: 'info',
                    background: '#163C74'
                }
            ]
        });
    </script>
    <?php echo view("components/modals/user_profile"); ?>
    <?php echo view("components/modals/join_team"); ?>
    <?php echo view("components/chars-remaining"); ?>

    <!-- For BYT-284-remove-team-creation-from-the-user-menu -->
    <!-- This was commented out in case it needs to be put back in later -->
        <!-- php echo view("components/modals/create_team");  -->
    <!-- End of BYT-284-remove-team-creation-from-the-user-menu -->

    <?php echo view("components/modals/notifications"); ?>
    <div class="dmiux_page">
        <?php echo view("components/loading"); ?>
        <?php echo view("components/notify"); ?>
        <div class="progress loader" id="explorer-loader">
            <div class="progress-bar progress-bar-striped progress-bar-animated" role="progressbar" aria-valuenow="100" aria-valuemin="0" aria-valuemax="100" style="width: 100%"></div>
        </div>
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
                <div class="dmiux_grid-col dmiux_grid-col_auto">
                    <nav class="dmiux_app-nav">
                        <button type="button" class="dmiux_app-nav__toggle">
                            <span><?php echo session()->get("team"); ?></span>
                            <svg viewbox="0 0 24 24">
                                <polyline points="6 9 12 15 18 9"></polyline>
                            </svg>
                        </button>
                        <div class="dmiux_app-nav__dropdown">
                            <div class="dmiux_app-nav__search dmiux_input">
                                <input type="text" placeholder="Search Teams" class="dmiux_input__input">
                                <div class="dmiux_input__icon">
                                    <svg height="16" viewbox="0 0 16 16" width="16" xmlns="http://www.w3.org/2000/svg">
                                    <path d="M265.7,19.2298137 C266.6,18.0372671 267.1,16.6459627 267.1,15.0559006 C267.1,11.1801242 264,8 260.1,8 C256.2,8 253,11.1801242 253,15.0559006 C253,18.931677 256.2,22.1118012 260.1,22.1118012 C261.7,22.1118012 263.2,21.6149068 264.3,20.7204969 L267.3,23.7018634 C267.5,23.9006211 267.8,24 268,24 C268.2,24 268.5,23.9006211 268.7,23.7018634 C269.1,23.3043478 269.1,22.7080745 268.7,22.310559 L265.7,19.2298137 Z M260.05,20.1 C257.277451,20.1 255,17.9 255,15.1 C255,12.3 257.277451,10 260.05,10 C262.822549,10 265.1,12.3 265.1,15.1 C265.1,17.9 262.822549,20.1 260.05,20.1 Z" fill="currentColor" transform="translate(-253 -8)"></path></svg>
                                </div>
                            </div>
                            <div id="user_teams" class="dmiux_app-nav__overflow">
                                <?php $o = config('orchestration.url'); $teams = session()->get("teams"); foreach($teams ?? [] as $team): ?>
                                    <?php if($team['domain'] == session()->get("team")): ?>
                                        <a href="<?php echo rtrim($o, "/"); ?>/app/team/<?php echo $team['domain']; ?>" class="dmiux_app-nav__link dmiux_app-nav__link_active"><?php echo $team['domain']; ?></a>
                                    <?php else: ?>
                                        <a href="<?php echo rtrim($o, "/"); ?>/app/team/<?php echo $team['domain']; ?>" class="dmiux_app-nav__link"><?php echo $team['domain']; ?></a>
                                    <?php endif; ?>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </nav>
                </div>
                <div class="dmiux_grid-col dmiux_grid-col_auto">
                    <nav class="dmiux_main-nav">
                        <?php if(auth()->user()->hasPermissionTo(permission: 'datalake_access', product: 'datalake')): ?> 
                        <div class="dmiux_main-nav__item">
                            <a href="/data-lake" <?php if(session()->get("toptab") == "datalake"): ?> style="color: #0056b3;" <?php endif; ?> class="dmiux_main-nav__link">Data Lake</a>
                        </div>
                        <?php endif; ?>

                        <?php if(auth()->user()->hasPermissionTo(permission: 'studio_access', product: 'studio')): ?> 
                        <div class="dmiux_main-nav__item">
                            <a href="/studio" <?php if(session()->get("toptab") == "studio"): ?> style="color: #0056b3;" <?php endif; ?> class="dmiux_main-nav__link">Studio</a>
                        </div>
                        <?php endif; ?>

                        <?php if(auth()->user()->is_admin): ?> 
                            <div class="dmiux_main-nav__item">
                                <a href="/admin" <?php if(session()->get("toptab") == "admin"): ?> style="color: #0056b3;" <?php endif; ?>class="dmiux_main-nav__link">Admin</a>
                            </div>
                        <?php endif; ?>
                    </nav>
                </div>
                <div class="dmiux_grid-col dmiux_grid-col_lg-12">
                    <div class="dmiux_header__hr dmiux_removed dmiux_blocked_lg"></div>
                </div>
                <div id="header-icons" class="dmiux_grid-col dmiux_grid-col_auto">
                    <nav class="dmiux_main-nav pr-0">
                        <?php /* TODO: Bring back running jobs page
                        if(auth()->user()->hasPermissionTo(permission: 'datalake_access', product: 'datalake')): 
                        <div class="dmiux_main-nav__item mr-2">
                            <span @click="openRunningJobs()" id="connector-syncs" :title="getJobTooltip()" class="fa-stack fa-sm" :class="job_count == 0 ? ' fa-disabled' : ' tooltip_pretty has-badge cursor-p'" :data-count="job_count">
                                <i class="fa fa-circle fa-stack-2x"></i>
                                <i class="fa fa-running fa-stack-1x fa-inverse"></i>
                            </span>
                        </div>
                        endif; */ ?>
                        <div class="dmiux_main-nav__item">
                            <span @click="openNotifications()" id="notification-bell" title="No new notifications" class="fa-stack fa-sm tooltip_pretty" :class="notification_count == 0 ? ' fa-disabled' : ' has-badge cursor-p'" :data-count="notification_count > 100 ? '100+' : notification_count">
                                <i class="fa fa-circle fa-stack-2x"></i>
                                <i class="fas fa-bell fa-stack-1x fa-inverse"></i>
                            </span>
                        </div>
                    </nav>
                </div>
                <div class="dmiux_grid-col dmiux_grid-col_auto min-width-200">
                    <nav class="dmiux_user-nav">
                        <div class="text-center">
                            <button class="dmiux_user-nav__toggle" type="button"><?php echo session()->get("user_full_name"); ?>
                                <svg viewbox="0 0 24 24">
                                    <polyline points="6 9 12 15 18 9"></polyline>
                                </svg>
                            </button>
                        </div>
                        <div class="dmiux_user-nav__dropdown">
                            <a class="dmiux_user-nav__link" href="#" onclick="openModal('#modal-user_profile');">Manage My Account</a>
                            <a class="dmiux_user-nav__link" href="#" onclick="openModal('#modal-join_team');">Accept Team Invitation</a>
                            <?php if(session()->get("is_orch_admin") === true): ?> 
                                <a class="dmiux_user-nav__link" href="<?php echo config('orchestration.url'); ?>/admin" target="_blank">Orchestration Admin</a>
                            <?php endif; ?>

                            <!-- For BYT-284-remove-team-creation-from-the-user-menu -->
                            <!-- This was commented out in case it needs to be put back in later -->
                                <!-- <a class="dmiux_user-nav__link" href="#" onclick="openModal('#modal-create_team');">Create new team</a> -->
                            <!-- End of BYT-284-remove-team-creation-from-the-user-menu -->

                            <a class="dmiux_user-nav__link" href="/auth/logout">Logout</a>
                        </div>
                    </nav>
                </div>
            </div>
            <button class="dmiux_burger" id="dmiux_burger" type="button">Menu<i></i></button>
            <div id="dmiux_mobile-nav" class="dmiux_mobile-nav">
                <div id="dmiux_mobile-nav__slide_0" class="dmiux_mobile-nav__slide dmiux_mobile-nav__slide_visible">
                    <div class="dmiux_mobile-nav__hr">
                        <?php if(auth()->user()->hasPermissionTo(permission: 'datalake_access', product: 'datalake')): ?> 
                            <div class="dmiux_main-nav__item">
                                <a href="/data-lake" <?php if(session()->get("toptab") == "data-lake"): ?> style="color: #0056b3;" <?php endif; ?> class="dmiux_mobile-nav__link">Data Lake</a>
                            </div>
                        <?php endif; ?>

                        <?php if(auth()->user()->hasPermissionTo(permission: 'studio_access', product: 'studio')): ?>
                            <div class="dmiux_main-nav__item">
                                <a href="/studio" <?php if(session()->get("toptab") == "studio"): ?> style="color: #0056b3;" <?php endif; ?> class="dmiux_mobile-nav__link">Studio</a>
                            </div>
                        <?php endif; ?>

                        <?php if(auth()->user()->is_admin): ?>
                            <a href="#dmiux_mobile-nav__slide_3" class="dmiux_mobile-nav__link dmiux_mobile-nav__link_parent"><?php if(session()->get("toptab") == "admin"): ?><?php endif; ?>Admin</a>
                        <?php endif; ?>

                        <a href="#dmiux_mobile-nav__slide_4" class="dmiux_mobile-nav__link dmiux_mobile-nav__link_parent"><?php echo session()->get("user_full_name"); ?></a>
                    </div>                         
                </div>
                <div id="dmiux_mobile-nav__slide_3" class="dmiux_mobile-nav__slide">
                    <a href="#dmiux_mobile-nav__slide_0" class="dmiux_mobile-nav__back">Back</a>
                    <div class="dmiux_mobile-nav__hr"></div>
                    <a href="/admin/users" class="dmiux_mobile-nav__link active">Users</a>
                    <a href="/admin/connectors" class="dmiux_mobile-nav__link">Connectors</a>
                    <a href="/admin/schemas" class="dmiux_mobile-nav__link">Schemas</a>
                    <a href="/admin/servers" class="dmiux_mobile-nav__link">Servers</a>
                    <a href="/admin/tags" class="dmiux_mobile-nav__link active">Tags</a>
                    <a href="/admin/roles" class="dmiux_mobile-nav__link">Roles</a>
                </div>               
                <div id="dmiux_mobile-nav__slide_4" class="dmiux_mobile-nav__slide">
                    <a href="#dmiux_mobile-nav__slide_0" class="dmiux_mobile-nav__back">Back</a>
                    <div class="dmiux_mobile-nav__hr"></div>
                    <a class="dmiux_mobile-nav__link" href="#" onclick="openModal('#modal-user_profile');">Manage My Account</a>
                    <a class="dmiux_mobile-nav__link" href="#" onclick="openModal('#modal-join_team');">Accept Team Invitation</a>
                    <?php if(session()->get("is_orch_admin") === true): ?> 
                        <a class="dmiux_mobile-nav__link" href="<?php echo config('orchestration.url'); ?>/admin" target="_blank">Orchestration Admin</a>
                    <?php endif; ?>

                    <!-- For BYT-284-remove-team-creation-from-the-user-menu -->
                    <!-- This was commented out in case it needs to be put back in later -->
                        <!-- <a class="dmiux_mobile-nav__link" href="#" onclick="openModal('#modal-create_team');">Create new team</a> -->
                    <!-- End of BYT-284-remove-team-creation-from-the-user-menu -->

                    <a class="dmiux_mobile-nav__link" href="/auth/logout">Logout</a>
                </div>               
                <button type="button" id="dmiux_mobile-nav__close" class="dmiux_mobile-nav__close"></button>
            </div>
        </header>
