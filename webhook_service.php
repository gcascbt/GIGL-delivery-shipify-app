<?php
include_once('includes/function.php');
include_once('includes/gigl_shipping_api.php');
include_once('includes/countries_state.php');
$data = file_get_contents("php://input");
$request = json_decode($data, true);//$count=0;
global $shop_url; global $get_location; global $createDeliveryParam; global $updatedAPICorditate;
$shop_url = $_SERVER['HTTP_X_SHOPIFY_SHOP_DOMAIN'];//"quickstart-ac4df350.myshopify.com";

$get_location = array('city'=>$request['rate']['destination']['city'], 'address1'=>$request['rate']['destination']['address1'], 'province'=>((gigl_state()[$request['rate']['destination']['province']]) ? gigl_state()[$request['rate']['destination']['province']] : $request['rate']['destination']['province']), 'company_name'=>$shop_url, 'postal_code'=>'',//$request['rate']['destination']['postal_code'], 
'country'=>((gigl_countries()[$request['rate']['destination']['country']]) ? gigl_countries()[$request['rate']['destination']['country']] : $request['rate']['destination']['country']));

$shipCost = getCachedData($get_location, $shop_url);
if(!$shipCost){
    $shipCostResponse = calculateShippingCost($request);
    $shipCost = $shipCostResponse['Object']['DeliveryPrice'];
}
$todaydate =  date('Y-m-d H:i:s', time());
$pickup_date = date('Y-m-d H:i:s', strtotime($todaydate . ' +1 day'));
$delivery_date = date('Y-m-d H:i:s', strtotime($todaydate . ' +2 day'));
if(isset($request['rate']['items'][0]['name']) && !empty($request['rate']['items'][0]['name'])){
      global $get_location;//global $actualCostT; 
    $rates = [
        [
            'service_name' => 'GIG-Logistics-Shippings', // Name of the shipping service
            'service_code' => 'standard', // Unique code for the service
            'total_price' => ($shipCost * 100),//$actualCost, // Shipping cost for the item
            'description' => 'This is the fastest option to deliver your goods by far', 
            'currency' => 'NGN', // Currency code
            'min_delivery_date' => $pickup_date,
            'max_delivery_date' => $delivery_date,
        ],
    ];
    header('Content-Type: application/json');
    echo json_encode(['rates' => $rates]);
    if($shipCost){
        if($updatedAPICorditate){
            $delivery_receiver_email = (($request['rate']['destination']['email']) ? $request['rate']['destination']['email'] : $request['customer']['email']);
            $delivery_postcode = (isset($request['rate']['destination']['postal_code']) ? $request['rate']['destination']['postal_code'] : $request['rate']['destination']['zip'] );
            $convert_to_json = json_encode($updatedAPICorditate);     
            update_address_transist($get_location,$convert_to_json,$delivery_postcode, $delivery_receiver_email);
        }
        $logFile = fopen('debugfail.log', 'a');
            // Check if the log file was opened successfully
            if ($logFile) {
                // Use print_r to display the array contents
                ob_start(); // Start output buffering
                print_r($delivery_coordinate); // Print the array contents
                $output = ob_get_clean(); // Capture the printed output
                fwrite($logFile, $output); // Write the output to the log file
                fclose($logFile); // Close the log file
        
                // Now the array contents are saved in the 'debug.log' file
            }
        if(isset($shipCostResponse['Object']['DeliveryPrice'])){
            cacheData($get_location, $shipCostResponse['Object']['DeliveryPrice'], 6000000, $shop_url);
        }
    }
}else{
    global $actualCost;
    $actualCost = 8000.00;
    $rates = [
        [
            'service_name' => 'GIG-Logistics-Shippings', // Name of the shipping service
            'service_code' => 'standard', // Unique code for the service
            'total_price' => $actualCost, // Shipping cost for the item
            'description' => 'This is the fastest option to deliver your goods by far', 
            'currency' => 'NGN' // Currency code
        ],
    ];
    header('Content-Type: application/json');
    echo json_encode(['rates' => $rates]);
}

function calculateShippingCost($request) {
    global $shop_url; global $get_location; global $new_location; global $get_origin_data_value; global $createDeliveryParam;
    global $get_pickup_location; global $updatedAPICorditate;global $login_credentials;
    // Extract relevant data from the request
    $destination = (isset($request['rate']['destination']) ? $request['rate']['destination'] : $request['billing_address'] );
   $origin = (isset($request['rate']['origin']) ? $request['rate']['origin'] : '' );
   $items = (isset($request['rate']['items']) ? $request['rate']['items'] : $request['line_items']);
   //$fff = returnsnew();
   $title = 'Gigl Delivery';
   $parameters['shop']= $shop_url;
    $get_origin_data = get_origin_data($parameters);
    if(!$get_origin_data['transact_token']){   //new update on 18/12/2023
        $login_credentials = get_token_api($get_origin_data);
    }else{
        $login_credentials = json_decode($get_origin_data['transact_token']);
        get_host_url($get_origin_data);
    }
    $delivery_country_code = $destination['country'];
    $delivery_state_code = ((gigl_state()[$destination['province']]) ? gigl_state()[$destination['province']] : $destination['province']);
    $delivery_city = $destination['city'];
    $delivery_postcode = (isset($destination['postal_code']) ? $destination['postal_code'] : $destination['zip'] );
    $delivery_base_address = $destination['address1'];

        $delivery_receiver_name      = $destination['name'];
        $delivery_receiver_email     = (($destination['email']) ? $destination['email'] : $request['customer']['email']);
        $delivery_receiver_phone     = $destination['phone'];

    $delivery_base_cart_subtotal = $package['cart_subtotal'];
        
    $delivery_state = $delivery_state_code;
    $delivery_country = $delivery_country_code;
    $get_sender = unserialize($get_origin_data['meta']);// $sender_state_code = (isset($origin['province']) ? $origin['province'] : ''); $sender_country_code = (isset($origin['country']) ? $origin['country'] : '');
    
    $sender_name        = $get_sender['shipping_sender_name'];$sender_phone  = $get_sender['shipping_sender_phone'];
    $pickup_city 		= $get_sender['shipping_pickup_city'];$pickup_postcode 	= $get_sender['shipping_pickup_postcode'];
    $pickup_state 		= $get_sender['shipping_pickup_state'];$pickup_base_address = $get_sender['shipping_pickup_address'];
    $pickup_country 	= $get_sender['shipping_pickup_country'];
    
    if (trim($pickup_country) == '') {
            $pickup_country = 'Nigeria';
    }
    $get_pickup_location = array('address' => $pickup_base_address, 'poster_code' => $pickup_postcode, 'city' => $pickup_city, 'state' => $pickup_state, 'country' => $pickup_country, 'store_url' => $shop_url);
    if($delivery_receiver_name ){
        $author_obj = (object)['display_name'=>$delivery_receiver_name, 'user_email'=>$delivery_receiver_email, 'phone'=>$delivery_receiver_phone];
        $delivery_base_receiver_name = $author_obj->display_name; $delivery_base_receiver_email = $author_obj->user_email;
        $delivery_base_receiver_phone = $phone = $delivery_receiver_phone;
    }else{
        $delivery_base_receiver_name = "Not login user";
        $delivery_base_receiver_email = "nouser@demo.com";
        $delivery_base_receiver_phone = "08030000000";
    }

    $receiver_name      = $delivery_base_receiver_name;
    $receiver_email     = $delivery_base_receiver_email;
    $receiver_phone     = $delivery_base_receiver_phone;

    $getPickupCachedAddressData = get_user_address($get_pickup_location, $shop_url);
    $getDeliveryCachedAddressData = get_user_address($get_location, $shop_url);
    $scrap_out = array(', ,',',',', , ',',,,',', , ,',',,,nigeria',', , nigeria');
    $delivery_address = trim("$delivery_base_address, $delivery_city, $delivery_state, nigeria");
    
    if(!$getDeliveryCachedAddressData){
        $get_address_zip = get_user_address_by_zip_code($delivery_postcode);
        if($get_address_zip){ $delivery_coordinate = $get_address_zip; }else{
            if($delivery_postcode == '' || empty($delivery_postcode) ) {
                if( !in_array($delivery_address, $scrap_out)){
                        $new_location = $get_location;
                        $delivery_coordinate = get_lat_lng($delivery_address,$delivery_receiver_email); //geolocation($delivery_address);
                    
                    if (!isset($delivery_coordinate->Latitude) && !isset($delivery_coordinate->Longitude)) {
                    $delivery_coordinate = get_lat_lng("$delivery_city, $delivery_state, $delivery_country", $delivery_receiver_email);//geolocation("$delivery_city, $delivery_state, $delivery_country");
                    }
                    $updatedAPICorditate = $delivery_coordinate;
                
                }

            }else{
                
                if(!in_array($delivery_address, $scrap_out)){
                    $new_location = $get_location;
                    $delivery_coordinate = get_lat_lng($delivery_address, $delivery_receiver_email);//geolocation($delivery_address);
                    
                    if (!isset($delivery_coordinate->Latitude) && !isset($delivery_coordinate->Longitude)) {
                        $delivery_address = $delivery_postcode . ',' . $delivery_city . ',' . $delivery_state . ',nigeria';
                        $delivery_coordinate = get_lat_lng("$delivery_address", $delivery_receiver_email);//geolocation($delivery_address);
                    }
                    $updatedAPICorditate = $delivery_coordinate;
                }
            
            }
            $logFile = fopen('debugsss.log', 'a');
            // Check if the log file was opened successfully
            if ($logFile) {
                // Use print_r to display the array contents
                ob_start(); // Start output buffering
                print_r($delivery_coordinate); // Print the array contents
                $output = ob_get_clean(); // Capture the printed output
                fwrite($logFile, $output); // Write the output to the log file
                fclose($logFile); // Close the log file
        
                // Now the array contents are saved in the 'debug.log' file
            }
        }
    }else{
        $delivery_coordinate = $getDeliveryCachedAddressData;
    }
    if(!$getPickupCachedAddressData){
        if($delivery_postcode == '' || empty($delivery_postcode) ) {
            $pickup_address = trim("$pickup_base_address $pickup_city, $pickup_state, $pickup_country");
            if(!in_array($pickup_address, $scrap_out)){
                $new_location = $get_pickup_location;
                $pickup_coordinate = get_lat_lng($pickup_address,null);//geolocation($pickup_address); 
                
                if (!isset($pickup_coordinate->Latitude) && !isset($pickup_coordinate->Longitude)) {
                    $pickup_coordinate = get_lat_lng("$pickup_city, $pickup_state, $pickup_country",null);//geolocation("$pickup_city, $pickup_state, $pickup_country");
                }
            }
        }else{
            $pickup_address = $pickup_postcode . ',' . $pickup_city . ',' . $pickup_state . ',nigeria';
            $pickup_address = trim("$pickup_address");
            $pickup_addressd = trim("$pickup_base_address $pickup_city, $pickup_state, $pickup_country, $pickup_postcode");
            if(!in_array($pickup_address, $scrap_out)){
                $new_location = $get_pickup_location; 
                $pickup_coordinate = get_lat_lng($pickup_address, null);//geolocation($pickup_address); 
            
            }
        }
    }else{
        $pickup_coordinate = $getPickupCachedAddressData;
    }  
    
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
                        "SenderAddress" => $pickup_address, 
                        "ReceiverPhoneNumber" => $receiver_phone, 
                        "VehicleType" => "BIKE", 
                        "SenderPhoneNumber" => $sender_phone, 
                        "SenderName" => $sender_name,
                        "ReceiverName" => $receiver_name, 
                        "ReceiverLocation" => $receiverLocation,
                        "SenderLocation" => $senderLocation,
                        "PreShipmentItems" => $items//$preShipmentItems
                        );
                        
        try {
            
            $res = calculate_pricing($params);
            $logFile = fopen('debugsCost.log', 'a');
            // Check if the log file was opened successfully
            if ($logFile) {
                // Use print_r to display the array contents
                ob_start(); // Start output buffering
                print_r($res); // Print the array contents
                $output = ob_get_clean(); // Capture the printed output
                fwrite($logFile, $output); // Write the output to the log file
                fclose($logFile); // Close the log file
        
                // Now the array contents are saved in the 'debug.log' file
            }
            return $res;
            } catch (\Exception $e) {
                $res = 0;
        }
        return $res;
    }
    return 0;
}

