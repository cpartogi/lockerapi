<?php
$data = [];
if (env('APP_ENV') == "production") {
    $data = [
        'ip_access' => ['1.1.1.1', '1.1.1.1'],
        'chiper_text' => "sdsds",
        'chain' => '1',
        'ecash_path' => '',
        'ecash_mid' => '',
        'ecash_tokenIPG' => '',
        'ecash_server_merchant' => '1.1.1.1',
        'shop_host' => 'https://shop.clientname.id/',
        'api_host' => 'https://internalapi.clientname.id/',
        'redirect_popsend' => 'https://popsend.clientname.id/topup/process',
        'redirect_popshop' => 'https://shop.clientname.id/payment/dokutransaction',
        'domain_production' => 'https://shop.clientname.id',
        'popsend_url' => 'https://popsend.clientname.id',
        'img_location' => 'https://shop.clientname.id/assets/images',
        'email_sender1' => 'lidya@clientname.id',
        'email_sender2' => 'greta@clientname.id',
        'rollbar' => [
            'access_token' => '9258a514307642699fa06018168d4914',
            'level' => 'error',
        ],
        'jne' => [
            'url' => 'http://api.jne.co.id:8889/tracing/apitest/pricedev',
            'origin' => 'http://api.jne.co.id:8889/tracing/apitest/dest/key',
            'dest' => 'http://api.jne.co.id:8889/tracing/apitest/dest/key/',
            'username' => 'TESTAPI',
            'api_key' => '',
        ],
        "domain_email_merchant_reg" => "https://www.clientname.id",
        "email_merchant_reg" => "merchant@clientname.id",
        "email_order_shop_bcc" => "dyah@popbox.com",
        'molpay' => array(
            'ip_ipay' => array('127.0.0.1','111.67.33.90'),
            'url_submit' => 'https://www.onlinepayment.com.my/MOLPay/pay/popbox_Dev/index.php',
            'merchant_id' => 'popbox_Dev',
            'verify_key' => '433363bc15f8338a0562a13883345a62',
            'return_url' => 'https://internalapi.clientname.id/payment/molpay/response'
        ),
    ];
} else if (env('APP_ENV') == "staging") {
    $data = [
        'ip_access' => ['1.1.1.1', '1.1.1.1'],
        'chiper_text' => "p0pb0x.Asia",
        'chain' => 'NA',
        'ecash_path' => 'https://sandbox.mandiri-ecash.com/ecommgateway/',
        'ecash_mid' => 'popbox',
        'ecash_tokenIPG' => '',
        'ecash_server_merchant' => '1.1.1.1',
        'shop_host' => 'http://shopdev.clientname.id/',
        'api_host' => 'http://api-dev.clientname.id/',
        'redirect_popsend' => 'http://popsendev.clientname.id/topup/process',
        'redirect_popshop' => 'http://shopdev.clientname.id/payment/dokutransaction',
        'domain_production' => 'http://shopdev.clientname.id',
        'popsend_url' => 'http://popsendev.clientname.id',
        'img_location' => 'http://dimo.clientname.id/assets/images',
        'email_sender1' => 'christian@clientname.id',
        'email_sender2' => 'dyah@clientname.id',
        'rollbar' => [
            'access_token' => '9258a514307642699fa06018168d4914',
            'level' => 'error'
        ],
        'jne' => [
            'url' => 'http://api.jne.co.id:8889/tracing/apitest/pricedev',
            'origin' => 'http://api.jne.co.id:8889/tracing/apitest/dest/key',
            'dest' => 'http://api.jne.co.id:8889/tracing/apitest/dest/key/',
            'username' => 'TESTAPI',
            'api_key' => '',
        ],
        "domain_email_merchant_reg" => "http://beta.clientname.id",
        "email_merchant_reg" => "merchant@clientname.id",
        "email_order_shop_bcc" => "dyah@popbox.com",
        'molpay' => array(
            'ip_ipay' => array('127.0.0.1','1.1.1.1'),
            'url_submit' => 'https://www.onlinepayment.com.my/MOLPay/pay/test7776/',
            'merchant_id' => '',
            'verify_key' => '',
            'return_url' => 'http://api-dev.clientname.id/payment/molpay/response'
        ),
    ];
} else {
    $data = [
        'ip_access' => ['1.1.1.1', '1.1.1.1'],
        'chiper_text' => "sssss",
        'chain' => 'NA',
        'ecash_path' => 'https://sandbox.mandiri-ecash.com/ecommgateway/',
        'ecash_mid' => 'popbox',
        'ecash_tokenIPG' => '',
        'ecash_server_merchant' => '1.1.1.1',
        'shop_host' => 'http://popshopweb.dev/',
        'api_host' => 'http://apiv2.dev/',
        'redirect_popsend' => 'http://popsend.dev/topup/process',
        'redirect_popshop' => 'http://popshopweb.dev/payment/dokutransaction',
        'domain_production' => 'http://popshopweb.dev',
        'popsend_url' => 'http://popsend.dev',
        'img_location' => 'http://dimo.clientname.id/assets/images',
        'email_sender1' => 'nungky@clientname.id',
        'email_sender2' => 'nungky@clientname.id',
        'rollbar' => [
            'access_token' => '9258a514307642699fa06018168d4914',
            'level' => 'error',
        ],
        'jne' => [
            'url' => 'http://api.jne.co.id:8889/tracing/apitest/pricedev',
            'origin' => 'http://api.jne.co.id:8889/tracing/apitest/dest/key',
            'dest' => 'http://api.jne.co.id:8889/tracing/apitest/dest/key/',
            'username' => 'TESTAPI',
            'api_key' => '',
        ],
        "domain_email_merchant_reg" => "http://popbox.dev",
        "email_merchant_reg" => "lee.nungky@gmail.com",
        "email_order_shop_bcc" => "lee.nungky@gmail.com",
        'molpay' => array(
            'ip_ipay' => array('127.0.0.1','1.1.1.1'),
            'url_submit' => 'https://www.onlinepayment.com.my/MOLPay/pay/test7776/',
            'merchant_id' => 'test7776',
            'verify_key' => '',
            'return_url' => 'http://apiv2.dev/payment/molpay/response'
        ),
    ];
}
return $data;