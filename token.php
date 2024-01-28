<?php
include_once("includes/mysql_connect.php");

$api_key = 'a5a71d71b73c3e546909bdeefde3c3a1';
$secret_key = 'e76a53892c1b60c45c3cce75e4cf79d0';
$parameters = $_GET;
$shop_url = $parameters['shop'];
$hmac = $parameters['hmac'];
$parameters = array_diff_key($parameters, array('hmac' => ''));
ksort($parameters);
	// ENGINE=InnoDB  DEFAULT CHARSET=latin1 AUTO_INCREMENT=54
$shops = "CREATE TABLE IF NOT EXISTS shops(
        id INT(11) NOT NULL AUTO_INCREMENT PRIMARY KEY, 
        shop_url VARCHAR(30) NOT NULL,
        access_token LONGTEXT NOT NULL,
        install_date DATE
        )";
$mysql->query($shops);
	
$orders = "CREATE TABLE IF NOT EXISTS orders(
        id INT(32) NOT NULL AUTO_INCREMENT PRIMARY KEY, 
        checkout_id VARCHAR(255) NOT NULL,
        reference VARCHAR(255) NOT NULL,
        token VARCHAR(255) NOT NULL,
        items LONGTEXT DEFAULT NULL,
        waybill VARCHAR(255) DEFAULT NULL,
        tracking_url VARCHAR(255) DEFAULT NULL,
        status VARCHAR(255) DEFAULT NULL,
        curr_date DATE NOT NULL DEFAULT CURRENT_TIMESTAMP,
        UNIQUE KEY checkout_ref (checkout_id,reference)
        )";
$mysql->query($orders);

$delivery_api = "CREATE TABLE IF NOT EXISTS delivery_api(
        id INT(32) NOT NULL AUTO_INCREMENT PRIMARY KEY, 
        shop_url VARCHAR(255) DEFAULT NULL,
        meta LONGTEXT DEFAULT NULL,
        test_key VARCHAR(255) DEFAULT NULL,
        test_secret LONGTEXT DEFAULT NULL,
        live_key VARCHAR(255) DEFAULT NULL,
        live_secret VARCHAR(255) DEFAULT NULL,
        transact_token LONGTEXT DEFAULT NULL,
        date DATE DEFAULT NULL,
        modify_date DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        call_mode VARCHAR(100) DEFAULT NULL,
        UNIQUE KEY shop_delivery (shop_url)
        )";
$mysql->query($delivery_api);
	
$billings = "CREATE TABLE IF NOT EXISTS billings(
        id INT(11) NOT NULL AUTO_INCREMENT PRIMARY KEY, 
        gen_id VARCHAR(255) DEFAULT NULL,
        shop_url VARCHAR(255) DEFAULT NULL,
        meta_value VARCHAR(255) DEFAULT NULL,
        created_date DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        UNIQUE KEY gen_id_shop (gen_id,shop_url)
        )";
$mysql->query($billings);

$address_transient = "CREATE TABLE IF NOT EXISTS address_transient(
        id INT(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
        ship_address LONGTEXT DEFAULT NULL,
        cordinate LONGTEXT DEFAULT NULL,
        zip_code INT(11) DEFAULT NULL,
        email VARCHAR(255) DEFAULT NULL,
        expiry VARCHAR(255) DEFAULT NULL,
        UNIQUE KEY address (ship_address)
        )";
$mysql->query($address_transient);

$new_hmac = hash_hmac('sha256', http_build_query($parameters), $secret_key);

if( hash_equals($hmac, $new_hmac) ) {
    $access_token_endpoint = 'https://' .  $shop_url . '/admin/oauth/access_token';
    $var = array(
        "client_id" => $api_key,
        "client_secret" => $secret_key,
        "code" => $parameters['code']
    );

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $access_token_endpoint);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, count($var));
    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($var));
    $response = curl_exec($ch);
    curl_close($ch);

    $response = json_decode($response, true);

    echo print_r($response);

    $query = "INSERT INTO shops (shop_url, access_token, install_date) VALUES ('" . $shop_url . "','" . $response['access_token'] . "', NOW()) ON DUPLICATE KEY UPDATE access_token='" . $response['access_token'] . "'";
    if($mysql->query($query)) {
        echo "<script>top.window.location = 'https://" . $shop_url . "/admin/apps'</script>";
        die;
    }
} else {
    echo 'This is not coming from Shopify and probably someone is hacking.';
}