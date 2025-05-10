<?php
include_once 'inc/db/db_lx.php';
include_once 'inc/db/db_fd.php';
include_once 'inc/lx_orders.php';
include_once 'inc/lx_lager.php';

$Lx_Artikel = new Lx_Artikel();
$Lx_Orders = new Lx_Orders();

$errors = [];
$data = [];

function logError($message) {
    $logFile = LOGGING['websocket_log'];
    $timestamp = date('Y-m-d H:i:s');
    file_put_contents($logFile, "[$timestamp] ERROR: $message\n", FILE_APPEND);
}

if ($_POST) {
    try {
        if ($_POST['do'] == "SetDeliveryStatus") {
            if (empty($_POST['AuftragId'])) { 
                throw new Exception('AuftragId is required.'); 
            }
            if (empty($_POST['Tag'])) { 
                throw new Exception('Tag is required.'); 
            }

            $Lx_Orders->SetOrderTags($_POST['AuftragId'], 4);
            $Lx_Orders->SetDeliveryTime($_POST['AuftragId'], date('d.m.Y H:i'));

            $data['success'] = true;
            $data['message'] = 'Gespeichert!';
            // Update LocalStorage
            $orders = json_decode($_POST['storedOrders'] ?? '{}', true);
            $orders[$_POST['AuftragId']] = [
                'Status' => 4,
                'AuftragsKennung' => 2,
                'Tags' => [['lTagId' => 1, 'szName' => 'Versendet']],
                'ShowPos' => $orders[$_POST['AuftragId']]['ShowPos'] ?? 1
            ];
            $data['orders'] = $orders;
        }
    } catch (Exception $e) {
        $errors['general'] = $e->getMessage();
        $data['success'] = false;
        $data['errors'] = $errors;
        logError("SetDeliveryStatus failed: " . $e->getMessage());
    }

    echo json_encode($data, JSON_PRETTY_PRINT);
}

if ($_GET) {
    try {
        if ($_GET['do'] == "get_warengruppen") {
            $output = $Lx_Artikel->Get_Warengruppen();
            echo json_encode(array_values($output), JSON_PRETTY_PRINT);
        }
    } catch (Exception $e) {
        $data['success'] = false;
        $data['errors'] = ['general' => $e->getMessage()];
        echo json_encode($data, JSON_PRETTY_PRINT);
        logError("get_warengruppen failed: " . $e->getMessage());
    }
}
?>