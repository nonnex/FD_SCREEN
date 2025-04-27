<?php
include 'inc/db/db_lx.php';
include 'inc/db/db_fd.php';
include 'inc/lx_orders.php';
include 'inc/lx_lager.php';

$Lx_Artikel = new Lx_Artikel();
$Lx_Orders = new Lx_Orders();

$errors = [];
$data = [];

if ($_POST) {
    if ($_POST['do'] == "SetDeliveryStatus") {
        if (empty($_POST['AuftragId'])) { $errors['AuftragId'] = 'AuftragId is required.'; }
        if (empty($_POST['Tag'])) { $errors['Tag'] = 'Tag is required.'; }

        $Lx_Orders->SetOrderTags($_POST['AuftragId'], 4);
        $Lx_Orders->SetDeliveryTime($_POST['AuftragId'], date('d.m.Y H:i'));

        if (!empty($errors)) {
            $data['success'] = false;
            $data['errors'] = $errors;
        } else {
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
    }

    echo json_encode($data, JSON_PRETTY_PRINT);
}

if ($_GET) {
    if ($_GET['do'] == "get_warengruppen") {
        $output = $Lx_Artikel->Get_Warengruppen();
        echo json_encode(array_values($output), JSON_PRETTY_PRINT);
    }
}
?>