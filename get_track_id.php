<?php
include_once('includes/function.php');
include_once('includes/gigl_shipping_api.php');
global $login_credentials;
    if (isset($_SERVER['HTTP_ORIGIN'])) {
        header("Access-Control-Allow-Origin: {$_SERVER['HTTP_ORIGIN']}");
        header('Access-Control-Allow-Credentials: true');
        header('Access-Control-Max-Age: 86400');    // cache for 1 day
    }
    
    if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
        if (isset($_SERVER['HTTP_ACCESS_CONTROL_REQUEST_METHOD']))
            header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
    
        if (isset($_SERVER['HTTP_ACCESS_CONTROL_REQUEST_HEADERS']))
            header("Access-Control-Allow-Headers: {$_SERVER['HTTP_ACCESS_CONTROL_REQUEST_HEADERS']}");
    
        exit(0);
    }
    header('Content-Type: application/json');
    $checkout_id = $_GET["token"];$parameters['shop'] = $_GET["site_url"];
    $get_shop = get_shop($parameters);
	$get_origin_data = get_origin_data($parameters);
	if(!$get_origin_data){
		$login_credentials = get_token_api($get_origin_data);
	}else{
		$login_credentials = json_decode($get_origin_data['transact_token']);
		get_host_url($get_origin_data);
	}
     $get_waybill = get_checkout_waybill($checkout_id);
     
    if(!$get_waybill){
        $get_waybill = get_checkout_waybill(null,$checkout_id);
    }
    if($get_waybill){
        $get_tracking_data = track_details($get_waybill);
    }

     $response = ['waybill'=>$get_waybill, 'shop_url' =>$parameters, 'tracking'=>$get_tracking_data];
     $jsonData = json_encode($response);
     error_reporting(E_ALL);
     ini_set('display_errors', 1);
     // Set the appropriate headers for JSON response
     
     
     echo $jsonData;