<?php
include 'config.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta http-equiv="content-type" content="text/html; charset=utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Auftragsübersicht</title>
    <link href="https://fonts.googleapis.com/css?family=Inconsolata" rel="stylesheet"/>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.2.3/css/bootstrap.min.css"/>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/normalize/8.0.1/normalize.min.css"/>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/dragula/3.7.3/dragula.min.css"/>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/jqueryui/1.13.3/themes/base/jquery-ui.min.css"/>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/jqueryui/1.13.3/themes/base/theme.min.css"/>
    <link rel="stylesheet" href="css/style.css">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.7.1/jquery.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jqueryui/1.13.3/jquery-ui.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.2.3/js/bootstrap.bundle.min.js"></script>
</head>
<body>
<script>
// Prevent Form resubmit on "F5"
if (window.history.replaceState) {
    window.history.replaceState(null, null, window.location.href);
}

// Expose APP_MODE and WebSocket URI to JavaScript
const APP_MODE = '<?php echo APP_MODE; ?>';
const WEBSOCKET_URI = '<?php echo WEBSOCKET_SERVER["uri"]; ?>';
</script>
<div id="dialog"></div>
<div id="dialog_DB" title="Basic dialog"></div>
<div class="drag-container">
    <section class="section" style="margin:0px;padding:0px;">
        <div class="drag-column-header" style="margin:0px;padding:0px;">
            <div id="MyDateDisplay" class="clock" onload="showTime()" style="width:35%; text-align:left; padding-left:5px;border:0px solid green;"></div>
            <a href="lager.php"><div class="fd-button">Lagerverwaltung</div></a>
            <a href="calendar/index.php"><div class="fd-button">Kalender</div></a>
            <div id="MyClockDisplay" class="clock" onload="showTime()" style="width:30%; text-align:right; padding-right:5px;border:0px solid green;"></div>
        </div>
    </section>
    <ul class="drag-list">
        <li class="drag-column drag-column-on-hold">
            <span class="drag-column-header"><h2>NEU</h2></span>
            <div class="order-container-col">
                <ul class="drag-inner-list" id="column-1">
                    <!-- Orders and events will be populated via WebSocket -->
                </ul>
            </div>
        </li>
        <li class="drag-column drag-column-in-progress">
            <span class="drag-column-header"><h2>PRODUKTION</h2></span>
            <div class="order-container-col">
                <ul class="drag-inner-list" id="column-2">
                    <!-- Orders will be populated via WebSocket -->
                </ul>
            </div>
        </li>
        <li class="drag-column drag-column-needs-review">
            <span class="drag-column-header"><h2>VERSANDVORBEREITUNG</h2></span>
            <div class="order-container-col">
                <ul class="drag-inner-list" id="column-3">
                    <!-- Orders will be populated via WebSocket -->
                </ul>
            </div>
        </li>
        <li class="drag-column drag-column-approved">
            <span class="drag-column-header"><h2>AUSLIEFERUNG</h2></span>
            <div class="order-container-col">
                <ul class="drag-inner-list" id="column-4">
                    <!-- Orders will be populated via WebSocket -->
                </ul>
            </div>
        </li>
    </ul>
</div>
<script src='https://cdnjs.cloudflare.com/ajax/libs/dragula/3.7.3/dragula.min.js'></script>
<script src='./js/utils.js'></script>
<script src='./js/render.js'></script>
<script src='./js/websocket.js'></script>
<script src='./js/ui.js'></script>
<script src='./js/fd_dash.js'></script>
<script src='./js/fd_script.js'></script>
</body>
</html>