<?php
use Stripe\Stripe;
use Stripe\Refund;

if (!defined("WHMCS")) {
    die("This file cannot be accessed directly");
}

require_once(__DIR__ . '/stripe-php/vendor/autoload.php');

function stripe3dsecure_config() {
    return [
        'FriendlyName' => [
            'Type' => 'System',
            'Value' => 'Stripe 3D Secure'
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

function stripe3dsecure_link($params) {
	
    $publishableKey = $params['PublishableKey'];
    $secretKey = $params['SecretKey'];
    $webhooksSigningSecret = $params['WebhooksSigningSecret'];
    $identifier = $params['Identifier'];
	
	$amount = abs($params['amount'] * 100);
    $currency = strtolower($params['currency']);
    $invoiceid = $params['invoiceid'];

    if(isset($_GET['pay'])) return '<div class="alert alert-success" role="alert">支付完成，请刷新页面或返回用户中心</div>';
	
	if(!strpos($_SERVER['PHP_SELF'], 'viewinvoice')) {
/*
		$html = '<form action="' . $params['systemurl'] . 'viewinvoice.php' . '" method="get">';
		$html .= '<input type="hidden" name="id" value="' . $invoiceid . '" />';
		$html .= '<input type="submit" class="btn btn-primary" value="' . $params['langpaynow'] . '" /></form>';
*/		$html = '<img src="data:image/svg+xml;base64,PD94bWwgdmVyc2lvbj0iMS4wIiBlbmNvZGluZz0idXRmLTgiPz4KPCEtLSBHZW5lcmF0b3I6IEFkb2JlIElsbHVzdHJhdG9yIDIwLjEuMCwgU1ZHIEV4cG9ydCBQbHVnLUluIC4gU1ZHIFZlcnNpb246IDYuMDAgQnVpbGQgMCkgIC0tPgo8c3ZnIHZlcnNpb249IjEuMSIgaWQ9IkxheWVyXzEiIHhtbG5zPSJodHRwOi8vd3d3LnczLm9yZy8yMDAwL3N2ZyIgeG1sbnM6eGxpbms9Imh0dHA6Ly93d3cudzMub3JnLzE5OTkveGxpbmsiIHg9IjBweCIgeT0iMHB4IgoJIHZpZXdCb3g9IjAgMCA0NjggMjIyLjUiIHN0eWxlPSJlbmFibGUtYmFja2dyb3VuZDpuZXcgMCAwIDQ2OCAyMjIuNTsiIHhtbDpzcGFjZT0icHJlc2VydmUiPgo8c3R5bGUgdHlwZT0idGV4dC9jc3MiPgoJLnN0MHtmaWxsLXJ1bGU6ZXZlbm9kZDtjbGlwLXJ1bGU6ZXZlbm9kZDtmaWxsOiM2NzcyRTU7fQo8L3N0eWxlPgo8ZyBpZD0iU3RyaXBlIj4KCTxwYXRoIGNsYXNzPSJzdDAiIGQ9Ik00MTQsMTEzLjRjMC0yNS42LTEyLjQtNDUuOC0zNi4xLTQ1LjhjLTIzLjgsMC0zOC4yLDIwLjItMzguMiw0NS42YzAsMzAuMSwxNyw0NS4zLDQxLjQsNDUuMwoJCWMxMS45LDAsMjAuOS0yLjcsMjcuNy02LjVWMTMyYy02LjgsMy40LTE0LjYsNS41LTI0LjUsNS41Yy05LjcsMC0xOC4zLTMuNC0xOS40LTE1LjJoNDguOUM0MTMuOCwxMjEsNDE0LDExNS44LDQxNCwxMTMuNHoKCQkgTTM2NC42LDEwMy45YzAtMTEuMyw2LjktMTYsMTMuMi0xNmM2LjEsMCwxMi42LDQuNywxMi42LDE2SDM2NC42eiIvPgoJPHBhdGggY2xhc3M9InN0MCIgZD0iTTMwMS4xLDY3LjZjLTkuOCwwLTE2LjEsNC42LTE5LjYsNy44bC0xLjMtNi4yaC0yMmwwLDExNi42bDI1LTUuM2wwLjEtMjguM2MzLjYsMi42LDguOSw2LjMsMTcuNyw2LjMKCQljMTcuOSwwLDM0LjItMTQuNCwzNC4yLTQ2LjFDMzM1LjEsODMuNCwzMTguNiw2Ny42LDMwMS4xLDY3LjZ6IE0yOTUuMSwxMzYuNWMtNS45LDAtOS40LTIuMS0xMS44LTQuN2wtMC4xLTM3LjEKCQljMi42LTIuOSw2LjItNC45LDExLjktNC45YzkuMSwwLDE1LjQsMTAuMiwxNS40LDIzLjNDMzEwLjUsMTI2LjUsMzA0LjMsMTM2LjUsMjk1LjEsMTM2LjV6Ii8+Cgk8cG9seWdvbiBjbGFzcz0ic3QwIiBwb2ludHM9IjIyMy44LDYxLjcgMjQ4LjksNTYuMyAyNDguOSwzNiAyMjMuOCw0MS4zIAkiLz4KCTxyZWN0IHg9IjIyMy44IiB5PSI2OS4zIiBjbGFzcz0ic3QwIiB3aWR0aD0iMjUuMSIgaGVpZ2h0PSI4Ny41Ii8+Cgk8cGF0aCBjbGFzcz0ic3QwIiBkPSJNMTk2LjksNzYuN2wtMS42LTcuNGgtMjEuNnY4Ny41aDI1Vjk3LjVjNS45LTcuNywxNS45LTYuMywxOS01LjJ2LTIzQzIxNC41LDY4LjEsMjAyLjgsNjUuOSwxOTYuOSw3Ni43eiIvPgoJPHBhdGggY2xhc3M9InN0MCIgZD0iTTE0Ni45LDQ3LjZsLTI0LjQsNS4ybC0wLjEsODAuMWMwLDE0LjgsMTEuMSwyNS43LDI1LjksMjUuN2M4LjIsMCwxNC4yLTEuNSwxNy41LTMuM1YxMzUKCQljLTMuMiwxLjMtMTksNS45LTE5LTguOVY5MC42aDE5VjY5LjNoLTE5TDE0Ni45LDQ3LjZ6Ii8+Cgk8cGF0aCBjbGFzcz0ic3QwIiBkPSJNNzkuMyw5NC43YzAtMy45LDMuMi01LjQsOC41LTUuNGM3LjYsMCwxNy4yLDIuMywyNC44LDYuNFY3Mi4yYy04LjMtMy4zLTE2LjUtNC42LTI0LjgtNC42CgkJQzY3LjUsNjcuNiw1NCw3OC4yLDU0LDk1LjljMCwyNy42LDM4LDIzLjIsMzgsMzUuMWMwLDQuNi00LDYuMS05LjYsNi4xYy04LjMsMC0xOC45LTMuNC0yNy4zLTh2MjMuOGM5LjMsNCwxOC43LDUuNywyNy4zLDUuNwoJCWMyMC44LDAsMzUuMS0xMC4zLDM1LjEtMjguMkMxMTcuNCwxMDAuNiw3OS4zLDEwNS45LDc5LjMsOTQuN3oiLz4KPC9nPgo8L3N2Zz4K" alt="Stripe" style="max-width: 150px;" />';
		return $html;
	}

    $returnUrl = $params['systemurl'] . 'modules/gateways/stripe-php/return.php';
    $ownerInfo = json_encode([
        'owner' => [
            'name' => $params['clientdetails']['fullname'],
            'address' => [
                'line1' => $params['clientdetails']['address1'],
                'line2' => $params['clientdetails']['address2'],
                'city' => $params['clientdetails']['city'],
                'state' => $params['clientdetails']['fullstate'],
                'postal_code' => $params['clientdetails']['postcode'],
                'country' => $params['clientdetails']['country'],
            ],
            'email' => $params['clientdetails']['email'],
        ]
    ]);

    $html = <<<html
<style>
    .StripeElement {
        background-color: white;
        height: 40px;
        padding: 10px 12px;
        border-radius: 4px;
        border: 1px solid transparent;
        box-shadow: 0 1px 3px 0 #e6ebf1;
        -webkit-transition: box-shadow 150ms ease;
        transition: box-shadow 150ms ease;
    }
    .StripeElement--focus {
        box-shadow: 0 1px 3px 0 #cfd7df;
    }
    .StripeElement--invalid {
        border-color: #fa755a;
    }
    .StripeElement--webkit-autofill {
        background-color: #fefde5 !important;
    }
    #payment-button {
        border: none;
        border-radius: 4px;
        outline: none;
        text-decoration: none;
        color: #fff;
        background: #32325d;
        white-space: nowrap;
        display: inline-block;
        height: 40px;
        line-height: 40px;
        padding: 0 14px;
        box-shadow: 0 4px 6px rgba(50, 50, 93, .11), 0 1px 3px rgba(0, 0, 0, .08);
        border-radius: 4px;
        font-size: 15px;
        font-weight: 600;
        letter-spacing: 0.025em;
        text-decoration: none;
        -webkit-transition: all 150ms ease;
        transition: all 150ms ease;
        float: left;
        margin-left: 12px;
        margin-top: 28px;
    }
</style>
<script src="https://js.stripe.com/v3/"></script>
<form action="" method="post" id="payment-form">
    <div class="form-row">
        <label for="card-element">
            Visa, Mastercard and American Express
        </label>
        <div id="card-element">
            <!-- A Stripe Element will be inserted here. -->
        </div>

        <!-- Used to display Element errors. -->
        <div id="card-errors" role="alert"></div>
    </div>

    <button id="payment-button">提交付款</button>
</form>
<script>
    var stripe = Stripe('$publishableKey');
    var elements = stripe.elements();
    var style = {
        base: {
            color: '#32325d',
            lineHeight: '18px',
            fontFamily: '"Helvetica Neue", Helvetica, sans-serif',
            fontSmoothing: 'antialiased',
            fontSize: '16px',
            '::placeholder': {
                color: '#aab7c4'
            }
        },
        invalid: {
            color: '#fa755a',
            iconColor: '#fa755a'
        }
    };

    // Create an instance of the card Element.
    var card = elements.create('card', {style: style});

    // Add an instance of the card Element into the `card-element` <div>.
    card.mount('#card-element');

    card.addEventListener('change', function (event) {
        var displayError = document.getElementById('card-errors');
        if (event.error) {
            displayError.textContent = event.error.message;
        } else {
            displayError.textContent = '';
        }
    });

    var form = document.getElementById('payment-form');
    var button = document.getElementById('payment-button');
    var errorElement = document.getElementById('card-errors');
    
    var ownerInfo = $ownerInfo;
    form.addEventListener('submit', function (event) {
        event.preventDefault();
        
        
        button.disabled = true;
        button.style.backgroundColor = 'buttonface';

        stripe.createSource(card, ownerInfo).then(function (result) {
            if (result.error) {
                errorElement.textContent = result.error.message;
                button.disabled = false;
                button.style.backgroundColor = '#32325d';
            } else {
                if (result.source.card.three_d_secure === 'not_supported') {
                    errorElement.textContent = '信用卡不支持 3D 安全验证，请更换信用卡';
                    button.disabled = false;
                    button.style.backgroundColor = '#32325d';
                    return false;
                }

                var src = (result.source.id);
                stripe.createSource({
                    type: 'three_d_secure',
                    amount: $amount,
                    currency: "$currency",
                    three_d_secure: {
                        card: src
                    },
                    redirect: {
                        return_url: '$returnUrl'
                    },
                    metadata: {
                        invoice_id: '$invoiceid',
                        identifier: '$identifier'
                    }
                }).then(function (result) {
                    if (result.error) {
                        errorElement.textContent = result.error.message;
                        button.disabled = false;
                        button.style.backgroundColor = '#32325d';
                    } else {
                        window.location = result.source.redirect.url;
                    }
                });
            }
        });
    });
</script>
html;
    return $html;
}

function stripe3dsecure_refund($params) {
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