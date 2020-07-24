<?php

use WHMCS\Database\Capsule;
use Stripe\Stripe;
use Stripe\Source;
use Stripe\Charge;

require_once __DIR__ . '/../../../init.php';
require_once __DIR__ . '/../../../includes/gatewayfunctions.php';
require_once __DIR__ . '/../../../includes/invoicefunctions.php';

require_once(__DIR__ . '/vendor/autoload.php');

require_once(__DIR__ . '/config.php');

if (!isset($_GET['source'])) {
    die("错误的请求");
}

$publishableKey = $stripeConfig['publishableKey'];
$secretKey = $stripeConfig['secretKey'];
$webhooksSigningSecret = $stripeConfig['webhooksSigningSecret'];
$identifier = $stripeConfig['identifier'];

Stripe::setApiKey($secretKey);

$source = Source::retrieve($_GET['source']);

if ($source['status'] == 'chargeable' && $source['type'] == 'three_d_secure'
    && $source['metadata']['identifier'] == $identifier) {
    try {
        $params = getGatewayVariables('stripe3dsecure');
        $count = Capsule::table('tblgatewaylog')->where('data', 'like', '%' . $source['id'] . '%')->count();
        if ($count > 0) {
            header('Location: /viewinvoice.php?' . http_build_query([
                    'id' => $source['metadata']['invoice_id'],
                    'pay' => true
                ]));
            exit();
        }
        logTransaction($params['paymentmethod'], $source, 'success(source)-return');

        $charge = Charge::create([
            'amount' => $source['amount'],
            'currency' => $source['currency'],
            'source' => $_GET['source'],
            'description' => $params['companyname'] . " Invoice#" . $source['metadata']['invoice_id']
        ]);

        if ($charge['paid']) {
            checkCbInvoiceID($source['metadata']['invoice_id'], $params['name']);//如果发票号无效，则将停止回调脚本执行。
            checkCbTransID($charge['id']);//如果回调ID重复，则将停止回调脚本执行。

            $amount = $source['amount'] / 100;
            if (!empty(trim($params['convertto']))) {
                $currencyType = Capsule::table('tblcurrencies')->where('id', $params['convertto'])->first();
                $userInfo = Capsule::table('tblinvoices')->where('id', $source['metadata']['invoice_id'])->first();
                $currency = Capsule::table('tblclients')->where('id', $userInfo->userid)->first();
                $amount = convertCurrency($amount, $currencyType->id, $currency->currency);//$currency->currency 为用户货币种类ID
            }

            $transactionFee = ($amount * ($params['transactionFeePer'] / 100)) + ($params['transactionFeeFixed'] / 100);

            addInvoicePayment(
                $source['metadata']['invoice_id'],
                $charge['id'],
                $amount,
                $transactionFee,
                $params['paymentmethod']
            );

            logTransaction($params['paymentmethod'], $charge, 'success(charge)-return');
        }
    } catch (Exception $e) {

    }
}

if ($source['status'] == 'chargeable' && $source['type'] == 'alipay'
    && $source['metadata']['identifier'] == $identifier) {
    try {
        $params = getGatewayVariables('stripe2alipay');
        $count = Capsule::table('tblgatewaylog')->where('data', 'like', '%' . $source['id'] . '%')->count();
        if ($count > 0) {
            header('Location: /viewinvoice.php?' . http_build_query([
                    'id' => $source['metadata']['invoice_id'],
                    'pay' => true
                ]));
            exit();
        }
        logTransaction($params['paymentmethod'], $source, 'success(source)-return');

        $charge = Charge::create([
            'amount' => $source['amount'],
            'currency' => $source['currency'],
            'source' => $_GET['source'],
            'description' => $params['companyname'] . " Invoice#" . $source['metadata']['invoice_id']
        ]);

        if ($charge['paid']) {
            checkCbInvoiceID($source['metadata']['invoice_id'], $params['name']);//如果发票号无效，则将停止回调脚本执行。
            checkCbTransID($charge['id']);//如果回调ID重复，则将停止回调脚本执行。

            $amount = $source['amount'] / 100;
            if (!empty(trim($params['convertto']))) {
                $currencyType = Capsule::table('tblcurrencies')->where('id', $params['convertto'])->first();
                $userInfo = Capsule::table('tblinvoices')->where('id', $source['metadata']['invoice_id'])->first();
                $currency = Capsule::table('tblclients')->where('id', $userInfo->userid)->first();
                $amount = convertCurrency($amount, $currencyType->id, $currency->currency);//$currency->currency 为用户货币种类ID
            }

            $transactionFee = ($amount * ($params['transactionFeePer'] / 100)) + ($params['transactionFeeFixed'] / 100);

            addInvoicePayment(
                $source['metadata']['invoice_id'],
                $charge['id'],
                $amount,
                $transactionFee,
                $params['paymentmethod']
            );

            logTransaction($params['paymentmethod'], $charge, 'success(charge)-return');
        }
    } catch (Exception $e) {

    }
}
header('Location: /viewinvoice.php?' . http_build_query([
        'id' => $source['metadata']['invoice_id'],
    ]));