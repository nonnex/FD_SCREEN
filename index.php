<?php
include 'inc/config.php';
if (APP_MODE === 'offline') {
    include 'inc/mock_orders.php';
    include 'inc/mock_orders_virtual.php';
    include 'inc/mock_events_calendar.php';
    $Lx_Orders = new Mock_Lx_Orders();
    $Lx_Orders_Virtual = new Mock_Lx_Orders_Virtual();
    $Lx_Events = new Mock_Lx_Events();
} else {
    include 'inc/db/db_lx.php';
    include 'inc/db/db_fd.php';
    include 'inc/lx_orders.php';
    include 'inc/lx_lager.php';
    include 'inc/lx_events.php';
    include 'cron.php';
    $Lx_Orders = new Lx_Orders();
    $Lx_Events = new Lx_Events();
}

$LxData_AB = $Lx_Orders->GetAllOpenOrdersFromLX(1);
$LxData_LS = $Lx_Orders->GetAllOpenOrdersFromLX(2);
$MinOrder = APP_MODE === 'offline' ? $Lx_Orders_Virtual->CreateMindestbestandOrder() : $Lx_Orders->CreateMindestbestandOrder();

// Assign virtual order to distinct key
$LxData_AB['V_99999'] = $MinOrder;

$Events = $Lx_Events->Get_Events();

// Prepare initial orders for LocalStorage
$orders_init = [];
$virtual_orders_init = [];
$seen_auftrag_ids = [];
foreach (array_merge($LxData_AB, $LxData_LS) as $auftragsNr => $order) {
    if (!$order['AuftragId'] || $order['AuftragId'] === '0' || in_array($order['AuftragId'], $seen_auftrag_ids)) {
        error_log("Invalid or duplicate AuftragId for AuftragsNr: $auftragsNr, Order: " . print_r($order, true));
        continue;
    }
    $seen_auftrag_ids[] = $order['AuftragId'];
    if (strpos($auftragsNr, 'V_') === 0 || strpos($auftragsNr, 'E_') === 0) {
        $virtual_orders_init[$auftragsNr] = [
            'Status' => $order['Status'],
            'AuftragsKennung' => $order['AuftragsKennung'] ?: 1,
            'Tags' => $order['Tags'],
            'ShowPos' => $order['ShowPos']
        ];
    } else {
        $orders_init[$order['AuftragId']] = [
            'Status' => $order['Status'],
            'AuftragsKennung' => $order['AuftragsKennung'] ?: 1,
            'Tags' => $order['Tags'],
            'ShowPos' => $order['ShowPos']
        ];
    }
}
$orders_json = json_encode($orders_init);
$virtual_orders_json = json_encode($virtual_orders_init);
error_log("orders_init: " . print_r($orders_init, true));
error_log("virtual_orders_init: " . print_r($virtual_orders_init, true));
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
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/jqueryui/1.13.2/themes/base/jquery-ui.min.css"/>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/jqueryui/1.13.2/themes/base/theme.min.css"/>
    <link rel="stylesheet" href="css/style.css">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.6.1/jquery.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jqueryui/1.13.2/jquery-ui.min.js"></script>
</head>
<body>
<script>
// Prevent Form resubmit on "F5"
if (window.history.replaceState) {
    window.history.replaceState(null, null, window.location.href);
}

// Expose APP_MODE to JavaScript
const APP_MODE = '<?php echo APP_MODE; ?>';

// Initialize LocalStorage with order statuses and ShowPos
const initialOrders = <?php echo $orders_json; ?>;
const initialVirtualOrders = <?php echo $virtual_orders_json; ?>;
let storedOrders = JSON.parse(localStorage.getItem('orders') || '{}');
let storedVirtualOrders = JSON.parse(localStorage.getItem('virtual_orders') || '{}');

// Flatten malformed structure
function flattenOrders(orders, prefix = '') {
    if (orders[prefix + '99999'] && Object.keys(orders[prefix + '99999']).some(key => key !== 'Status' && key !== 'AuftragsKennung' && key !== 'Tags' && key !== 'ShowPos')) {
        console.log(`Flattening nested LocalStorage under ${prefix}99999:`, orders[prefix + '99999']);
        return orders[prefix + '99999'];
    } else if (orders['0'] && Object.keys(orders['0']).some(key => key !== 'Status' && key !== 'AuftragsKennung' && key !== 'Tags' && key !== 'ShowPos')) {
        console.log('Flattening nested LocalStorage under 0:', orders['0']);
        return orders['0'];
    }
    return orders;
}

storedOrders = flattenOrders(storedOrders);
storedVirtualOrders = flattenOrders(storedVirtualOrders, 'V_');

// Remove invalid keys
Object.keys(storedOrders).forEach(key => {
    if (key === '0' || !key || isNaN(key) || !storedOrders[key].AuftragsKennung) {
        console.warn(`Removing invalid LocalStorage key (orders): ${key}, Data:`, storedOrders[key]);
        delete storedOrders[key];
    }
});
Object.keys(storedVirtualOrders).forEach(key => {
    if (!key || !storedVirtualOrders[key].AuftragsKennung) {
        console.warn(`Removing invalid LocalStorage key (virtual_orders): ${key}, Data:`, storedVirtualOrders[key]);
        delete storedVirtualOrders[key];
    }
});

// Merge initial orders
Object.keys(initialOrders).forEach(auftragsNr => {
    if (!storedOrders[auftragsNr]) {
        storedOrders[auftragsNr] = initialOrders[auftragsNr];
    }
});
Object.keys(initialVirtualOrders).forEach(auftragsNr => {
    if (!storedVirtualOrders[auftragsNr]) {
        storedVirtualOrders[auftragsNr] = initialVirtualOrders[auftragsNr];
    }
});

localStorage.setItem('orders', JSON.stringify(storedOrders));
localStorage.setItem('virtual_orders', JSON.stringify(storedVirtualOrders));
console.log('Initialized LocalStorage (orders):', storedOrders);
console.log('Initialized LocalStorage (virtual_orders):', storedVirtualOrders);
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
                <ul class="drag-inner-list" id="1">
                    <?= APP_MODE === 'offline' ? $Lx_Orders->GetOrderContainer($LxData_AB, 1) : $Lx_Orders->GetOrderContainer($LxData_AB, 1) ?>
                    <?= $Lx_Events->Print_Events($Events) ?>
                </ul>
            </div>
        </li>
        <li class="drag-column drag-column-in-progress">
            <span class="drag-column-header"><h2>Produktion</h2></span>
            <div class="order-container-col">
                <ul class="drag-inner-list" id="2">
                    <?= APP_MODE === 'offline' ? ($Lx_Orders->GetOrderContainer($LxData_AB, 2) . $Lx_Orders_Virtual->GetOrderContainer([$MinOrder], 2)) : $Lx_Orders->GetOrderContainer($LxData_AB, 2) ?>
                </ul>
            </div>
        </li>
        <li class="drag-column drag-column-needs-review">
            <span class="drag-column-header"><h2>Versandvorbereitung</h2></span>
            <div class="order-container-col">
                <ul class="drag-inner-list" id="3">
                    <?= APP_MODE === 'offline' ? $Lx_Orders->GetOrderContainer($LxData_AB, 3) : $Lx_Orders->GetOrderContainer($LxData_AB, 3) ?>
                </ul>
            </div>
        </li>
        <li class="drag-column drag-column-approved">
            <span class="drag-column-header"><h2>Auslieferung</h2></span>
            <div class="order-container-col">
                <ul class="drag-inner-list" id="4">
                    <?= APP_MODE === 'offline' ? $Lx_Orders->GetOrderContainer($LxData_LS) : $Lx_Orders->GetOrderContainer($LxData_LS) ?>
                </ul>
            </div>
        </li>
    </ul>
</div>
<script src='https://cdnjs.cloudflare.com/ajax/libs/dragula/3.7.3/dragula.min.js'></script>
<script src='./js/fd_dash.js'></script>
<script src='./js/fd_script.js'></script>
</body>
</html>