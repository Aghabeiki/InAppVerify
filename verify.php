<?php

$orderID = "";
$purchaseToken = "";
$developerPayload = "";
$packageName = "";
$purchaseState = "";
$purchaseTime = "";
$productId = "";
require_once ( "A6A/base.php");
require_once('A6A/lib.php');
require_once('A6A/mysql.php');
define(BILLING_RESPONSE_RESULT_OK, 0);
define(BILLING_RESPONSE_RESULT_USER_CANCELED, 1);
define(BILLING_RESPONSE_RESULT_BILLING_UNAVAILABLE, 3);
define(BILLING_RESPONSE_RESULT_ITEM_UNAVAILABLE, 4);
define(BILLING_RESPONSE_RESULT_DEVELOPER_ERROR, 5);
define(BILLING_RESPONSE_RESULT_ERROR, 6);
define(BILLING_RESPONSE_RESULT_ITEM_ALREADY_OWNED, 7);
define(BILLING_RESPONSE_RESULT_ITEM_NOT_OWNED, 8);

function fnEncrypt($message, $initialVector, $secretKey) {
    return base64_encode(
                    mcrypt_encrypt(
                            MCRYPT_RIJNDAEL_128, md5($secretKey), $message, MCRYPT_MODE_CFB, $initialVector
                    )
    );
}

function verifySignatureTransaction($signed_data, $signature, $public_key_base64) {
    $key = "-----BEGIN PUBLIC KEY-----\n" .
            chunk_split($public_key_base64, 64, "\n") .
            '-----END PUBLIC KEY-----';
    //using PHP to create an RSA key

    $key = openssl_pkey_get_public($key);


    if ($key === false) {
        //throw new \InvalidArgumentException("Public key not valid");
        die("Public key not valid");
    }
    $signature = base64_decode($signature);
    //using PHP's native support to verify the signature
    $result = openssl_verify(
            $signed_data, $signature, $key, OPENSSL_ALGO_SHA1
    );

    if (0 === $result) {
        return false;
    } else {
        if (1 !== $result) {
            return false;
        } else {
            return true;
        }
    }
}

define('PUBLIC_KEY', 'MIHNMA0GCSqGSIb3DQEBAQUAA4G7ADCBtwKBrwD22i2MZCduwjQ2h+yo77c7hA0Wk+Q4PuHC4FVMkuVdUcEAw++OOKxnV2MkPDsfDZu1uui99ONo7dyni2g++MASG1kGIfkqMFSvWa2fdwRlaCCBzfN52UwCcZ0VBkn5cU6Ss1H61MC3ecvUQDcVcz1nimUqNUpgs0iwt1f5x7RsjMXwN11Co8ML4/OyHnK3SEjDxNM/W3zQJ8CIcV3LtEVso6LJt4cXpHiQmJ/vMhMCAwEAAQ==');

define('PACKAGE_NAME', 'com.raianraika.magic');

$devID = $_GET["did"];
$devID = CleanHackerTXT($devID);
$responseData = base64_decode($_GET['rsd']);
//$responseData='{"orderId": "Qda85m87jnSV8Pex", "purchaseToken": "Qda85m87jnSV8Pex", "developerPayload": "ajab rasmi shodeha", "packageName": "com.raianraika.magic", "purchaseState": 0, "purchaseTime": 1394041928813, "productId": "666"}';
$signature = base64_decode($_GET['sng']);
//$signature='NiNXZJqvtThtpumxo9VGf5oiEfR08360HZQbAhZkFWweb6InrSauV8RULTtOM  3mpFx1HodEssMTjno0Dc0UJ8N1CQwS94XHl4qQpx5IoLCDbSmhHd82QPYbMIe2dwUsSo19y6S4NU2tDgro BuADiRR24pgXzAvcZjTlByUpvJApe8hWCApiGVwrQpXpa0J87D0SCJUapWwSdC5FFLnOSPMQopAhWp/YbzqMZW';

if (verifySignatureTransaction($responseData, $signature, PUBLIC_KEY)) {

    $responseData = str_replace("{", "", $responseData);
    $responseData = str_replace("}", "", $responseData);
    $parts = explode(',', $responseData);




    for ($i = 0; $i < count($parts); $i++) {

        $data = explode(':', $parts[$i]);

        $temp = str_replace('"', "", $data[0]);
        $temp = trim($temp);
        if ($temp == "orderId") {
            $orderID = str_replace('"', "", $data[1]);
        }
        if ($temp == "purchaseToken") {

            $purchaseToken = str_replace('"', "", $data[1]);
        }
        if ($temp == "developerPayload") {
            $developerPayload = str_replace('"', "", $data[1]);
        }
        if ($temp == "packageName") {
            $packageName = str_replace('"', "", $data[1]);
        }
        if ($temp == "purchaseState") {
            $purchaseState = str_replace('"', "", $data[1]);
        }
        if ($temp == "purchaseTime") {
            $purchaseTime = str_replace('"', "", $data[1]);
        }
        if ($temp == "productId") {
            $productId = str_replace('"', "", $data[1]);
        }
    }


    if (empty($orderID) || $orderID == "") {
        die(" order id missed");
    } else if (empty($purchaseToken) || $purchaseToken == "") {
        die(" purchase token missed");
    } else if (empty($developerPayload) || $developerPayload == "") {
        die(" developer payload missed");
    } else if (empty($packageName) || $packageName == "") {
        die(" package name missed");
    } else if (empty($purchaseState) || $purchaseState == "") {
        die("purchase state missed");
    } else if (empty($purchaseTime) || $purchaseTime == "") {
        die(" purchase time missed");
    } else if (empty($productId) || $productId == "") {
        die(" product id missed");
    }

    //echo "all thing is ok";
    // all thing is ok let check it 

    if (trim($packageName) != PACKAGE_NAME) {
        die("pkg is invalid");
    }

    $productId = trim($productId);
    $qr = "select pid from product where `productID`='" . $productId . "'";

    $res = FetchSqltoArray($qr);
    if ($res) {
        $PID = $res[0]['pid'];
    } else {
        die("product id not valid");
    }


    // check pkg is valid and dev id is valid 
    $qr = "select * from buyer where `devcode`='" . $devID . "'";
    $res = FetchSqltoArray($qr);
    if ($res) { // buyer is valid
        $BID = $res[0]['bid'];
    } else {// buyer is not valid
        die("USER_PROBLEM");
    }

    $qr = "select * from `buyProsses` where `bid`='$BID' and `pid`='$PID' ";
    $res = FetchSqltoArray($qr);
    if ($res) {
        $bid_server = $res[0]['bid'];
        $pid_server = $res[0]['pid'];
        $rnd_server = $res[0]['rnd'];
        $UNIC = $devID + $rnd_server;
        $UNIC = $UNIC + $pid_server;
        $crcDevpayload = md5($UNIC);

        if (trim($developerPayload) == $crcDevpayload) {
            // all thing is ok 
            // buy prosses should end
            // make dl link and send password
            
            if (trim($purchaseState) == BILLING_RESPONSE_RESULT_OK) {
                $qr = "SELECT `path`,`password` FROM  `product` where `pid`='$pid_server' and `productID`='$productId'";

                $res = FetchSqltoArray($qr);
                if ($res) {
                    $qr = "DELETE FROM `buyProsses` WHERE `buyProsses`.`bid` = '$bid_server' AND `buyProsses`.`pid` = '$pid_server'  LIMIT 1";
                    insert($qr);
                    $qr = "INSERT INTO `buyed` (`bid` ,`pid`,`orderID`)VALUES ('" . $bid_server . "',  '" . $pid_server . "',  '" . $orderID . "');";
                    insert($qr);


                    $returnVal = $res[0]['path'] . "aminOmid" . $res[0]['password'];

                    $returnVal = fnEncrypt($returnVal, "1234567890abcdef", $devID);
                    echo $returnVal;
                }
            } else {
                die("PAYMENT NOT ACCEPTED");
            }
        } else {
            die("PayLoad ID not accepted");
        }
    } else {
        die(" this buyer not accepted");
    }
} else {
    echo "false";
}

