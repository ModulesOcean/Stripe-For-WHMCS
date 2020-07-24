<?php
use Stripe\Stripe;
use Stripe\Source;
use Stripe\Refund;

if (!defined("WHMCS")) {
    die("This file cannot be accessed directly");
}

require_once(__DIR__ . '/stripe-php/vendor/autoload.php');

function stripe2alipay_config() {
    return [
        'FriendlyName' => [
            'Type' => 'System',
            'Value' => 'Stripe Alipay'
        ],
        'PublishableKey' => [
            'FriendlyName' => 'Publishable key',
            'Type' => 'text',
            'Size' => 30,
        ],
        'SecretKey' => [
            'FriendlyName' => 'Secret key',
            'Type' => 'text',
            'Size' => 30,
        ],
        'WebhooksSigningSecret' => [
            'FriendlyName' => 'Webhooks signing secret key',
            'Type' => 'text',
            'Size' => 30,
        ],
        'Identifier' => [
            'FriendlyName' => 'Site identifier',
            'Type' => 'text',
            'Size' => 30,
            'Default' => '',
        ],
        'transactionFeePer' => [
            'FriendlyName' => '交易手续费百分比',
            'Type' => 'text',
            'Size' => 5,
            'Default' => 2.9,
            'Description' => '每次成功收取的交易费用的百分比（例如：2.9％）'
        ],
        'transactionFeeFixed' => [
            'FriendlyName' => '固定手续费',
            'Type' => 'text',
            'Size' => 5,
            'Default' => 30,
            'Description' => '每次成功收取的固定交易费用（例如：30美分）'
        ]
    ];
}

function stripe2alipay_link($params) {
    $publishableKey = $params['PublishableKey'];
    $secretKey = $params['SecretKey'];
    $webhooksSigningSecret = $params['WebhooksSigningSecret'];
    $identifier = $params['Identifier'];

    if (isset($_GET['pay'])) return '<div class="alert alert-success" role="alert">支付完成，请刷新页面或返回用户中心</div>';

    Stripe::setApiKey($publishableKey);

    try {
        $source = Source::create([
            'type' => 'alipay',
            'amount' => abs($params['amount'] * 100),
            'currency' => strtolower($params['currency']),
            'metadata' => [
                'invoice_id' => $params['invoiceid'],
                'identifier' => $identifier
            ],
            'redirect' => [
                'return_url' => $params['systemurl'] . 'modules/gateways/stripe-php/return.php'
            ]
        ]);
    } catch (Exception $e){
        return '<div class="alert alert-danger text-center" role="alert">支付网关错误，请联系客服进行处理</div>';
    }


    if ($source->redirect->status == 'pending') {
		$redirect = parse_url($source->redirect->url);
		parse_str($redirect['query'], $parseList);
		$html = '<form action="' . $redirect['scheme'] . '://' . $redirect['host'] . $redirect['path'] . '" method="get">';
		foreach($parseList as $key => $value) {
			$html .= '<input type="hidden" name="' . $key . '" value="' . $value . '" />';
		}
		$html .= '<input type="submit" class="btn btn-primary" value="' . $params['langpaynow'] . '" /></form>';
		return $html;
    }
    return '<div class="alert alert-danger text-center" role="alert">发生错误，请创建工单联系客服处理</div>';
}

function stripe2alipay_refund($params) {
    $publishableKey = $params['PublishableKey'];
    $secretKey = $params['SecretKey'];
    $webhooksSigningSecret = $params['WebhooksSigningSecret'];
    $identifier = $params['Identifier'];

    Stripe::setApiKey($secretKey);

    try {
        $refund = Refund::create([
            'charge' => $params['transid'],
            'amount' => abs($params['amount'] * 100)
        ]);
        if ($refund['status'] == 'succeeded' || $refund['status'] == 'pending')
        {
            return [
                'status' => 'success',
                'rawdata' => $refund,
                'transid' => $refund['id'],
            ];
        } else {
            return [
                'status' => 'declined',
                'rawdata' => $refund,
                'transid' => $refund['id'],
            ];
        }

    } catch (Exception $e){
        return [
            'status' => 'error',
            'rawdata' => $e->getMessage()
        ];
    }
}