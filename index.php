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
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css"/>
    <link rel="stylesheet" href="css/style.css">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.7.1/jquery.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jqueryui/1.13.3/jquery-ui.min.js"></script>
</head>
<body class="bg-gray-800 text-white font-mono">
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
<div class="drag-container max-w-6xl mx-auto flex flex-col min-h-screen">
    <section class="section mb-5">
        <div class="drag-column-header flex justify-between items-center">
            <div id="MyDateDisplay" class="clock w-1/3 text-left pl-2 text-xl font-bold text-white"></div>
            <a href="lager.php"><div class="fd-button w-32 text-center py-1 border border-gray-400 bg-gray-700 rounded text-white hover:bg-gray-500">Lagerverwaltung</div></a>
            <a href="calendar/index.php"><div class="fd-button w-32 text-center py-1 border border-gray-400 bg-gray-700 rounded text-white hover:bg-gray-500">Kalender</div></a>
            <div id="MyClockDisplay" class="clock w-1/3 text-right pr-2 text-xl font-bold text-white"></div>
        </div>
    </section>
    <ul class="drag-list flex gap-2">
        <li class="drag-column flex-1 m-1 p-2 bg-black bg-opacity-20 rounded-lg min-h-[400px]">
            <span class="drag-column-header bg-orange-500 text-center uppercase font-bold py-2 px-4 rounded"><h2>NEU</h2></span>
            <div class="order-container-col min-h-[300px] overflow-y-auto">
                <ul class="drag-inner-list" id="column-1"></ul>
            </div>
        </li>
        <li class="drag-column flex-1 m-1 p-2 bg-black bg-opacity-20 rounded-lg min-h-[400px]">
            <span class="drag-column-header bg-blue-500 text-center uppercase font-bold py-2 px-4 rounded"><h2>Produktion</h2></span>
            <div class="order-container-col min-h-[300px] overflow-y-auto">
                <ul class="drag-inner-list" id="column-2"></ul>
            </div>
        </li>
        <li class="drag-column flex-1 m-1 p-2 bg-black bg-opacity-20 rounded-lg min-h-[400px]">
            <span class="drag-column-header bg-yellow-500 text-center uppercase font-bold py-2 px-4 rounded"><h2>Versandvorbereitung</h2></span>
            <div class="order-container-col min-h-[300px] overflow-y-auto">
                <ul class="drag-inner-list" id="column-3"></ul>
            </div>
        </li>
        <li class="drag-column flex-1 m-1 p-2 bg-black bg-opacity-20 rounded-lg min-h-[400px]">
            <span class="drag-column-header bg-green-500 text-center uppercase font-bold py-2 px-4 rounded"><h2>Auslieferung</h2></span>
            <div class="order-container-col min-h-[300px] overflow-y-auto">
                <ul class="drag-inner-list" id="column-4"></ul>
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