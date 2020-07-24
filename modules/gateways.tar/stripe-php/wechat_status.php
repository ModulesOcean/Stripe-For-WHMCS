<?php
use WHMCS\ClientArea;
use WHMCS\Database\Capsule;

require_once __DIR__ . '/../../../init.php';

if (!isset($_GET['invoice_id'])) {
    die("Wrong Request");
}

$ca = new ClientArea;
if ($ca->isLoggedIn()) {
    $invoice = Capsule::table('tblinvoices')
        ->where('userid', $ca->getUserId())
        ->where('id', $_GET['invoice_id'])
        ->first(['status']);

    $invoiceStatus = [
        'invoice_status' => $invoice->status
    ];
} else {
    $invoiceStatus = [
        'invoice_status' => -1
    ];
}

header('Content-Type: application/json');
die(json_encode($invoiceStatus));