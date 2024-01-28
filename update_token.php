<?php
//This is for cart/update API webhook
include_once('includes/function.php');
include_once('includes/gigl_shipping_api.php');

// Receive the JSON payload from Shopify
$jsonPayload = file_get_contents('php://input');
$data = json_decode($jsonPayload, true);

global $shop_url; global $get_origin_data_value; global $get_shop; global $login_credentials; global $expired_token;
$parameters= array(); //$replace_all = array('https://','/');
$parameters['shop']= $_SERVER['HTTP_X_SHOPIFY_SHOP_DOMAIN'];//str_replace($replace_all,"",'https://quickstart-ac4df350.myshopify.com/');//$request['referring_site']
$get_shop = get_shop($parameters);
$get_origin_data = get_origin_data($parameters);
$db_token_data = json_decode($get_origin_data['transact_token']);
$login_credentials = $db_token_data;
$api_token_mode = $get_origin_data['call_mode'];
$api__meta = unserialize($get_origin_data['meta']);
$api__mode = $api__meta['mode'];
if($api__mode != $api_token_mode){
    $login_credentials = get_token_api($get_origin_data);
    $encodeToken = json_encode($login_credentials);
    update_shop_token($encodeToken);
}
elseif(!isset($db_token_data->Object->access_token)){
    $login_credentials = get_token_api($get_origin_data);
    $encodeToken = json_encode($login_credentials);
    update_shop_token($encodeToken);
}
elseif(isset($db_token_data->Object->{".expires"})){
    // Your date string
    $dateString = $db_token_data->Object->{".expires"};

    // Create a DateTime object from the date string
    $dateTime = new DateTime($dateString);

    // Get the current date and time as a DateTime object
    $currentDateTime = new DateTime();

    // Compare the two DateTime objects
    if ($dateTime < $currentDateTime) {
        $expired_token = 'expired';
        $login_credentials = get_token_api($get_origin_data);
        if($login_credentials['Code']==200){
            $encodeToken = json_encode($login_credentials);
            update_shop_token($encodeToken);
        }
    } 
}

$logFile = fopen('debugExpire.log', 'a');
            // Check if the log file was opened successfully
            if ($logFile) {
                // Use print_r to display the array contents
                ob_start(); // Start output buffering
                print_r(array('$encodeToken')); // Print the array contents
                $output = ob_get_clean(); // Capture the printed output
                fwrite($logFile, $output); // Write the output to the log file
                fclose($logFile); // Close the log file
        
                // Now the array contents are saved in the 'debug.log' file
            }








