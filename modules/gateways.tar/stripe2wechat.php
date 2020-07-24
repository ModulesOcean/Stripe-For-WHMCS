<?php
use Stripe\Stripe;
use Stripe\Source;
use Stripe\Refund;

if (!defined("WHMCS")) {
    die("This file cannot be accessed directly");
}

require_once(__DIR__ . '/stripe-php/vendor/autoload.php');

function stripe2wechat_config() {
    return [
        'FriendlyName' => [
            'Type' => 'System',
            'Value' => 'Stripe WePay'
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

function stripe2wechat_link($params) {
	
    $publishableKey = $params['PublishableKey'];
    $secretKey = $params['SecretKey'];
    $webhooksSigningSecret = $params['WebhooksSigningSecret'];
    $identifier = $params['Identifier'];

    if(isset($_GET['pay'])) return '<div class="alert alert-success" role="alert">支付完成，请刷新页面或返回用户中心</div>';
	
    if(!strpos($_SERVER['PHP_SELF'], 'viewinvoice')) {
/*
        $html = '<form action="' . $params['systemurl'] . 'viewinvoice.php' . '" method="get">';
        $html .= '<input type="hidden" name="id" value="' . $params['invoiceid'] . '" />';
        $html .= '<input type="submit" class="btn btn-primary" value="' . $params['langpaynow'] . '" /></form>';
*/
		$html = '<img src="data:image/svg+xml;base64,PD94bWwgdmVyc2lvbj0iMS4wIiBlbmNvZGluZz0idXRmLTgiPz4KPCEtLSBHZW5lcmF0b3I6IEFkb2JlIElsbHVzdHJhdG9yIDIwLjEuMCwgU1ZHIEV4cG9ydCBQbHVnLUluIC4gU1ZHIFZlcnNpb246IDYuMDAgQnVpbGQgMCkgIC0tPgo8c3ZnIHZlcnNpb249IjEuMSIgaWQ9IkxheWVyXzEiIHhtbG5zPSJodHRwOi8vd3d3LnczLm9yZy8yMDAwL3N2ZyIgeG1sbnM6eGxpbms9Imh0dHA6Ly93d3cudzMub3JnLzE5OTkveGxpbmsiIHg9IjBweCIgeT0iMHB4IgoJIHZpZXdCb3g9IjAgMCA0NjggMjIyLjUiIHN0eWxlPSJlbmFibGUtYmFja2dyb3VuZDpuZXcgMCAwIDQ2OCAyMjIuNTsiIHhtbDpzcGFjZT0icHJlc2VydmUiPgo8c3R5bGUgdHlwZT0idGV4dC9jc3MiPgoJLnN0MHtmaWxsLXJ1bGU6ZXZlbm9kZDtjbGlwLXJ1bGU6ZXZlbm9kZDtmaWxsOiM2NzcyRTU7fQo8L3N0eWxlPgo8ZyBpZD0iU3RyaXBlIj4KCTxwYXRoIGNsYXNzPSJzdDAiIGQ9Ik00MTQsMTEzLjRjMC0yNS42LTEyLjQtNDUuOC0zNi4xLTQ1LjhjLTIzLjgsMC0zOC4yLDIwLjItMzguMiw0NS42YzAsMzAuMSwxNyw0NS4zLDQxLjQsNDUuMwoJCWMxMS45LDAsMjAuOS0yLjcsMjcuNy02LjVWMTMyYy02LjgsMy40LTE0LjYsNS41LTI0LjUsNS41Yy05LjcsMC0xOC4zLTMuNC0xOS40LTE1LjJoNDguOUM0MTMuOCwxMjEsNDE0LDExNS44LDQxNCwxMTMuNHoKCQkgTTM2NC42LDEwMy45YzAtMTEuMyw2LjktMTYsMTMuMi0xNmM2LjEsMCwxMi42LDQuNywxMi42LDE2SDM2NC42eiIvPgoJPHBhdGggY2xhc3M9InN0MCIgZD0iTTMwMS4xLDY3LjZjLTkuOCwwLTE2LjEsNC42LTE5LjYsNy44bC0xLjMtNi4yaC0yMmwwLDExNi42bDI1LTUuM2wwLjEtMjguM2MzLjYsMi42LDguOSw2LjMsMTcuNyw2LjMKCQljMTcuOSwwLDM0LjItMTQuNCwzNC4yLTQ2LjFDMzM1LjEsODMuNCwzMTguNiw2Ny42LDMwMS4xLDY3LjZ6IE0yOTUuMSwxMzYuNWMtNS45LDAtOS40LTIuMS0xMS44LTQuN2wtMC4xLTM3LjEKCQljMi42LTIuOSw2LjItNC45LDExLjktNC45YzkuMSwwLDE1LjQsMTAuMiwxNS40LDIzLjNDMzEwLjUsMTI2LjUsMzA0LjMsMTM2LjUsMjk1LjEsMTM2LjV6Ii8+Cgk8cG9seWdvbiBjbGFzcz0ic3QwIiBwb2ludHM9IjIyMy44LDYxLjcgMjQ4LjksNTYuMyAyNDguOSwzNiAyMjMuOCw0MS4zIAkiLz4KCTxyZWN0IHg9IjIyMy44IiB5PSI2OS4zIiBjbGFzcz0ic3QwIiB3aWR0aD0iMjUuMSIgaGVpZ2h0PSI4Ny41Ii8+Cgk8cGF0aCBjbGFzcz0ic3QwIiBkPSJNMTk2LjksNzYuN2wtMS42LTcuNGgtMjEuNnY4Ny41aDI1Vjk3LjVjNS45LTcuNywxNS45LTYuMywxOS01LjJ2LTIzQzIxNC41LDY4LjEsMjAyLjgsNjUuOSwxOTYuOSw3Ni43eiIvPgoJPHBhdGggY2xhc3M9InN0MCIgZD0iTTE0Ni45LDQ3LjZsLTI0LjQsNS4ybC0wLjEsODAuMWMwLDE0LjgsMTEuMSwyNS43LDI1LjksMjUuN2M4LjIsMCwxNC4yLTEuNSwxNy41LTMuM1YxMzUKCQljLTMuMiwxLjMtMTksNS45LTE5LTguOVY5MC42aDE5VjY5LjNoLTE5TDE0Ni45LDQ3LjZ6Ii8+Cgk8cGF0aCBjbGFzcz0ic3QwIiBkPSJNNzkuMyw5NC43YzAtMy45LDMuMi01LjQsOC41LTUuNGM3LjYsMCwxNy4yLDIuMywyNC44LDYuNFY3Mi4yYy04LjMtMy4zLTE2LjUtNC42LTI0LjgtNC42CgkJQzY3LjUsNjcuNiw1NCw3OC4yLDU0LDk1LjljMCwyNy42LDM4LDIzLjIsMzgsMzUuMWMwLDQuNi00LDYuMS05LjYsNi4xYy04LjMsMC0xOC45LTMuNC0yNy4zLTh2MjMuOGM5LjMsNCwxOC43LDUuNywyNy4zLDUuNwoJCWMyMC44LDAsMzUuMS0xMC4zLDM1LjEtMjguMkMxMTcuNCwxMDAuNiw3OS4zLDEwNS45LDc5LjMsOTQuN3oiLz4KPC9nPgo8L3N2Zz4K" alt="Stripe" style="max-width: 150px;" />';
        return $html;
    }

    Stripe::setApiKey($publishableKey);

    try {
        $source = Source::create([
            'type' => 'wechat',
            'amount' => abs($params['amount'] * 100),
            'currency' => strtolower($params['currency']),
            'metadata' => [
                'invoice_id' => $params['invoiceid'],
                'identifier' => $identifier
            ],
        ]);
    } catch (Exception $e) {
        return '<div class="alert alert-danger" role="alert">支付网关错误，请联系客服进行处理</div>';
    }


    $invoiceStatus = $params['systemurl']
        .'/modules/gateways/stripe-php/wechat_status.php?invoice_id='
        . $params['invoiceid'];

    if ($source->status == 'pending') {
        $redirect = $source->wechat->qr_code_url;
        $html = <<<html
<style>
#wechat-qrcode {
    border-radius: 4px;
    padding: 5px;
    background-color: #FFF;
    display: flex;
    align-items: center;
    justify-content: center;
}
</style>
<script src="//cdn.jsdelivr.net/npm/davidshimjs-qrcodejs@0.0.2/qrcode.min.js"></script>
<div id="wechat-qrcode"></div>
<br>
<div class="alert alert-primary text-center">
请使用微信扫描二维码支付
</div>
<script>
    let wechatQrcode = document.getElementById("wechat-qrcode");
    new QRCode(wechatQrcode, {
        text: "$redirect",
        width: 220,
        height: 220
    });
    setInterval(() => {
        fetch("{$invoiceStatus}", {
            credentials: 'same-origin'
        })
            .then(e => e.json())
            .then(r => {
                if (r.invoice_status == 'Paid') {
                    window.location.reload(true)
                }
            })
            .catch()
    }, 2000)
</script>
html;
        return $html;
    }
    return '<div class="alert alert-danger" role="alert">发生错误，请创建工单联系客服处理</div>';
}

function stripe2wechat_refund($params) {
	
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
        if ($refund['status'] == 'succeeded' || $refund['status'] == 'pending') {
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