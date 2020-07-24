<?php

use WHMCS\Database\Capsule;

require_once __DIR__ . '/../../../init.php';

$config = [
	'publishableKey',
	'secretKey',
	'webhooksSigningSecret',
	'identifier',
];

foreach ( $config as $val ) {
	$stripeConfig[$val] = getStripeConfig($val);
}

function getStripeConfig( $value ) {
	return Capsule::table('tblpaymentgateways')->where('gateway', 'LIKE', '%stripe2%')->where('setting', $value)->first()->value;
}