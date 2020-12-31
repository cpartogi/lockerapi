<?php
$mid = "popbox";//merchant Identity
$tokenIPG="082EE702C274E8F5319E37B61E123EDC"; // merchant IPG Token

$UrlIpgTicket = "https://sandbox.mandiri-ecash.com/ecommgateway/services/ecommgwws?wsdl"; // endpoint requesst ticket
$urlIPGGateWay="https://sandbox.mandiri-ecash.com/ecommgateway/payment.html?id="; // URL IPG 

$amount = 50000; // amount of transaction
$description = "Payment test Rp. 50000"; // transaction description;
$orderId = "6661"; // Transaction id;
$returnURL = "http://".$_SERVER['SERVER_ADDR'].$_SERVER['REQUEST_URI']."complete.php"; // return URL payment transcation
$hash = sha1(strtoupper($mid) . $amount . $_SERVER['REMOTE_ADDR']); // hashkey

//raw xml request

$xml_post_string = '<soapenv:Envelope xmlns:soapenv="http://schemas.xmlsoap.org/soap/envelope/" xmlns:ws="http://ws.service.gateway.ecomm.ptdam.com/">
                <soapenv:Header/>
                    <soapenv:Body>
                        <ws:generate>
                            <params>
                                <amount>' . $amount. '</amount>
                                <clientAddress>' . $_SERVER['REMOTE_ADDR'] . '</clientAddress>
                                <description>' . $description . '</description>
                                <memberAddress>' . $_SERVER['SERVER_ADDR'] . '</memberAddress>
                                <returnUrl>' . $returnURL . '</returnUrl>
                                <toUsername>' . $mid . '</toUsername>
                                <hash>' . $hash . '</hash>
                                <trxid>' . $orderId . '</trxid>
                            </params>
                        </ws:generate>
                    </soapenv:Body>
             </soapenv:Envelope>';
 //============end request

 //===========header request
        $headers = array(
            "Content-type: text/xml;charset=\"utf-8\"",
            "Accept: text/xml",
            "Cache-Control: no-cache",
            "Pragma: no-cache",
            "Content-length: " . strlen($xml_post_string),
        );
//=============end header

//==================CURL 
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $UrlIpgTicket);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 30);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
        curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_ANY);
        curl_setopt($ch, CURLOPT_USERPWD, $mid . ":" . $tokenIPG);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $xml_post_string);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        $data = curl_exec($ch);
        if ($data == false) {
            echo(curl_error($ch)."code error adalah :".curl_errno($ch));
            curl_close($ch);
        } else {
            if (!empty($data)) {
                $xml = simplexml_load_string($data);
                if (isset($xml)) {
                    $id = $xml->xpath('//return[1]');
                    header("Location:".$urlIPGGateWay.$id[0]);
                    die();
                }
            }
        }
?>