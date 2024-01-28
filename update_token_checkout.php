<?php
include_once('includes/function.php');
include_once('includes/gigl_shipping_api.php');
include_once("includes/shopify.php");


$shopify = new Shopify();
// Receive the JSON payload from Shopify
$jsonPayload = file_get_contents('php://input');
$data = json_decode($jsonPayload, true);
$logFile = fopen('newlythank.log', 'a');
// Check if the log file was opened successfully
if ($logFile) {
    // Use print_r to display the array contents
    ob_start(); // Start output buffering
    print_r(array($data, date("Y-m-d h : i : s"))); // Print the array contents
    $output = ob_get_clean(); // Capture the printed output
    fwrite($logFile, $output); // Write the output to the log file
    fclose($logFile); // Close the log file

    // Now the array contents are saved in the 'debug.log' file
}
global $shop_url; global $get_origin_data_value; global $get_shop; global $login_credentials;
$parameters= array(); 
$replace_all = array('https://');
// $repShopUrl = str_replace($replace_all,"",$data['abandoned_checkout_url']);
// $explodeShopUrl = explode('/',$repShopUrl);
$parameters['shop']= $shop_url = $_SERVER['HTTP_X_SHOPIFY_SHOP_DOMAIN'];//$explodeShopUrl[0];
$get_shop = get_shop($parameters);
$get_origin_data = get_origin_data($parameters);
if(!$get_origin_data){
    $login_credentials = get_token_api($get_origin_data);
}else{
    $login_credentials = json_decode($get_origin_data['transact_token']);
    get_host_url($get_origin_data);
}

    $destination = $data['shipping_address'];
    if($destination){
        $get_location = array('city'=>$destination['city'], 'address1'=>$destination['address1'], 'province'=>$destination['province'], 'company_name'=>$shop_url, 'postal_code'=>'',//$destination['zip'], 
        'country'=>$destination['country']);
        $delivery_country = $destination['country'];$delivery_state = $destination['province'];$delivery_city = $destination['city'];
        $delivery_postcode =  $destination['zip'];$delivery_base_address = $destination['address1'];$delivery_receiver_name = $destination['name'];
        if($delivery_receiver_name ){
            $author_obj = (object)['display_name'=>$delivery_receiver_name, 'user_email'=>$data['email'], 'phone'=>$destination['phone']];
            $delivery_base_receiver_name = $author_obj->display_name; $delivery_base_receiver_email = $author_obj->user_email;
            $delivery_base_receiver_phone = $phone = $destination['phone'];
        }else{
            $delivery_base_receiver_name = "Not login user";
            $delivery_base_receiver_email = "nouser@demo.com";
            $delivery_base_receiver_phone = "08030000000";
        }
    }
    $get_sender = unserialize($get_origin_data['meta']);
    $sender_name        = $get_sender['shipping_sender_name'];$sender_phone  = $get_sender['shipping_sender_phone'];
    $pickup_city 		= $get_sender['shipping_pickup_city'];$pickup_postcode 	= $get_sender['shipping_pickup_postcode'];
    $pickup_state 		= $get_sender['shipping_pickup_state'];$pickup_base_address = $get_sender['shipping_pickup_address'];
    $pickup_country 	= $get_sender['shipping_pickup_country'];
    $get_pickup_location = array('address' => $pickup_base_address, 'poster_code' => $pickup_postcode, 'city' => $pickup_city, 'state' => $pickup_state, 'country' => $pickup_country, 'store_url' => $shop_url);
    if (trim($pickup_country) == '') {
        $pickup_country = 'Nigeria';
    }
    $getDeliveryCachedAddressData = get_user_address($get_location, $shop_url);
    $getPickupCachedAddressData = get_user_address($get_pickup_location, $shop_url);
   
    $scrap_out = array(', ,',',',', , ',',,,',', , ,',',,,nigeria',', , nigeria');
    $delivery_address = trim("$delivery_base_address $delivery_city, $delivery_state, nigeria");
    if(!$getDeliveryCachedAddressData){
        //($get_address_zip){$delivery_coordinate = $get_address_zip; }else{
            
            if($delivery_postcode == '' || empty($delivery_postcode) ) {
                //$delivery_address = trim("$delivery_base_address $delivery_city, $delivery_state, nigeria");
                if( !in_array($delivery_address, $scrap_out)){
                        $new_location = $get_location;
                        
                        $delivery_coordinate = get_lat_lng($delivery_address,$delivery_receiver_email); //geolocation($delivery_address);
                        $updatedAPICorditate = $delivery_coordinate;
                }
            }else{
                //$delivery_address = trim($delivery_postcode . ',' . $delivery_city . ',' . $delivery_state . ',nigeria');
                if(!in_array($delivery_address, $scrap_out)){
                    
                    $new_location = $get_location;
                    //$delivery_addressd = trim("$delivery_base_address $delivery_city, $delivery_state, $delivery_country,$delivery_postcode");
                    $delivery_coordinate = get_lat_lng($delivery_address, $delivery_receiver_email);//geolocation($delivery_address);
                    $updatedAPICorditate = $delivery_coordinate;
                }
                
            }
        //}
    }else{
        $delivery_coordinate = $getDeliveryCachedAddressData;
    }
    $pickup_address = trim("$pickup_base_address $pickup_city, $pickup_state, $pickup_country");
    if(!$getPickupCachedAddressData){
        if($delivery_postcode == '' || empty($delivery_postcode) ) {
            //$pickup_address = trim("$pickup_base_address $pickup_city, $pickup_state, $pickup_country");
            if(!in_array($pickup_address, $scrap_out)){
                $new_location = $get_pickup_location;
                $pickup_coordinate = get_lat_lng($pickup_address,null);//geolocation($pickup_address); 
                // if (!isset($pickup_coordinate->Latitude) && !isset($pickup_coordinate->Longitude)) {
                //     $pickup_coordinate = get_lat_lng("$pickup_city, $pickup_state, $pickup_country",null);//geolocation("$pickup_city, $pickup_state, $pickup_country");
                // }
            }
            
        }else{
            //$pickup_address = trim($pickup_postcode . ',' . $pickup_city . ',' . $pickup_state . ',nigeria');
            //$pickup_addressd = trim("$pickup_base_address $pickup_city, $pickup_state, $pickup_country, $pickup_postcode");
            if(!in_array($pickup_address, $scrap_out)){
                $new_location = $get_pickup_location;
                $pickup_coordinate = get_lat_lng($pickup_address, null);//geolocation($pickup_address); 
                // if (!isset($pickup_coordinate->Latitude) && !isset($pickup_coordinate->Longitude)) {
                //     $pickup_coordinate = get_lat_lng("$pickup_address",null);//geolocation($pickup_address); 
                // }
            }
        }
    }else{
        $pickup_coordinate = $getPickupCachedAddressData;
    } 
    //$delivery_address = $delivery_postcode . ',' . $delivery_city . ',' . $delivery_state . ',nigeria';
    if(!in_array($delivery_address, $scrap_out)){ 
        $receiverLocation = array(
            "Latitude" => $delivery_coordinate->Latitude,
            "Longitude" => $delivery_coordinate->Longitude
            );

        $senderLocation = array(
            "Latitude" => $pickup_coordinate->Latitude,
            "Longitude" => $pickup_coordinate->Longitude
            );

        $params = array(
        "ReceiverAddress" => $delivery_address,
        "SenderLocality" => $pickup_city,
        "SenderAddress" => $pickup_postcode . ',' . $pickup_city . ',' . $pickup_state . ',nigeria', 
        "ReceiverPhoneNumber" => $delivery_base_receiver_phone, 
        "VehicleType" => "BIKE", 
        "SenderPhoneNumber" => $sender_phone, 
        "SenderName" => $sender_name,
        "ReceiverName" => $delivery_receiver_name, 
        "ReceiverLocation" => $receiverLocation,
        "SenderLocation" => $senderLocation,
        "PreShipmentItems" => $data['line_items']//$preShipmentItems
        );
        try {
            
            $res = calculate_pricing($params);
            $actualCostT = $res['Object']['DeliveryPrice'];
        } catch (\Exception $e) {
            $res = 0;
        }
        
        
        if($actualCostT){
            $shopify->set_url($parameters['shop']);
            $shopify->set_token($get_shop['access_token']);

            $carrier_service_get = $shopify->rest_api('/admin/api/2023-10/carrier_services.json', array(), 'GET');
            $response_service_get = json_decode($carrier_service_get['body'], TRUE);
            $carrierServiceId = $response_service_get['carrier_services'][0]['id'];
            if($carrierServiceId){
                $webhook_for_shipping_name = array(
                    'carrier_service' => array(
                        'id' => $carrierServiceId,
                        'name' => 'GIGL',
                        "active" => true
                    ),
                );
                $carrier_service = $shopify->rest_api("/admin/api/2023-01/carrier_services/{$carrierServiceId}.json", $webhook_for_shipping_name, 'PUT');
                $responseData = json_decode($carrier_service['body'], TRUE);
            }
            $updatedShippingRates =[
                [
                    'service_name' => 'GIG-Logistics-Shippings',
                    'service_code' => 'standard',
                    'total_price' => ($actualCostT * 100), // Updated shipping cost
                    'currency' => 'NGN',
                    'description' => 'This is the fastest option to deliver your goods by far', 
                    'min_delivery_date' => '2023-12-01',
                    'max_delivery_date' => '2023-12-07',
                ],
            ];
            header('Content-Type: application/json');
            echo json_encode(['rates' => $updatedShippingRates]);
            if($updatedAPICorditate){
                $convert_to_json = json_encode($delivery_coordinate);
               // update_address_transist($get_location,$convert_to_json,$delivery_postcode,$delivery_receiver_email);
            }
            if(isset($res['Object']['DeliveryPrice'])){
                //cacheData($get_location, $res['Object']['DeliveryPrice'], 6000000, $shop_url);
            }
        }else{
            $updatedShippingRates =[
                [
                    'service_name' => 'GIG-Logistics-Shippings',
                    'service_code' => 'standard',
                    'total_price' => 70000.00,//($actualCostT * 100), // Updated shipping cost
                    'currency' => 'NGN',
                    'description' => 'This is the fastest option to deliver your goods by far', 
                    'min_delivery_date' => '2023-12-01',
                    'max_delivery_date' => '2023-12-07',
                ],
            ];
            header('Content-Type: application/json');
            echo json_encode(['rates' => $updatedShippingRates]);
        }
    }



