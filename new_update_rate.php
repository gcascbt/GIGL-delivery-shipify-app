<?php
// function get_data_update($parameters, $store_data){
//     $shopify->set_url($parameters['shop']);
//     $shopify->set_token($store_data['access_token']);
//     $carrier_service_get = $shopify->rest_api('/admin/api/2023-10/carrier_services.json', array(), 'GET');
//     $response_service_get = json_decode($carrier_service_get['body'], TRUE);
//     $carrierServiceId = $response_service_get['carrier_services'][0]['id'];
//     print_r($response_service_get);
//     if($carrierServiceId){
//         $webhook_for_shipping_name = array(
//             'carrier_service' => array(
//                 'id' => $carrierServiceId,
//                 'name' => 'GIGL',
//                 "active" => true
//             ),
//         );
//         $carrier_service = $shopify->rest_api("/admin/api/2023-01/carrier_services/{$carrierServiceId}.json", $webhook_for_shipping_name, 'PUT');
//         $responseData = json_decode($carrier_service['body'], TRUE);
//     }
//     return $responseData;
// }

session_start();
include_once('includes/function.php');
include_once('includes/gigl_shipping_api.php');
include_once('includes/countries_state.php');


function calculateShippingCost($request) {
    // Extract relevant data from the request
    $destination = (isset($request['rate']['destination']) ? $request['rate']['destination'] : $request['billing_address'] );
   $origin = (isset($request['rate']['origin']) ? $request['rate']['origin'] : '' );
   $items = (isset($request['rate']['items']) ? $request['rate']['items'] : $request['line_items']);
   
   $title = 'Gigl Delivery';
   $parameters= array(); $replace_all = array('https://','/');
   $parameters['shop']= str_replace($replace_all,"",'https://quickstart-ac4df350.myshopify.com/');//$request['referring_site']
    $get_shop = get_shop($parameters);
    $get_origin_data = get_origin_data($parameters);
    // $logFile = fopen('newlogss.log', 'a');
    // // Check if the log file was opened successfully
    // if ($logFile) {
    //     // Use print_r to display the array contents
    //     ob_start(); // Start output buffering
    //     print_r($request); // Print the array contents
    //     $output = ob_get_clean(); // Capture the printed output
    //     fwrite($logFile, $output); // Write the output to the log file
    //     fclose($logFile); // Close the log file

    //     // Now the array contents are saved in the 'debug.log' file
    // } 
    $delivery_country_code = $destination['country'];
    $delivery_state_code = $destination['province'];
    $delivery_city = $destination['city'];
    $delivery_postcode = (isset($destination['postal_code']) ? $destination['postal_code'] : $destination['zip'] );
    $delivery_base_address = $destination['address1'];
    
        $delivery_receiver_name      = $destination['name'];
        $delivery_receiver_email     = (($destination['email']) ? $destination['email'] : $request['customer']['email']);
        $delivery_receiver_phone     = $destination['phone'];

    $delivery_base_cart_subtotal = $package['cart_subtotal'];
        
    $delivery_state = (gigl_state()[$delivery_state_code]) ? gigl_state()[$delivery_state_code] : $delivery_state_code;
    $delivery_country = (gigl_countries()[$delivery_country_code]) ? gigl_countries()[$delivery_country_code] : $delivery_country_code;

    
    if(empty($delivery_postcode)){
        $delivery_postcode='';
    }
    get_token_api($get_origin_data);
    
    $get_sender = unserialize($get_origin_data['meta']); $sender_state_code = (isset($origin['province']) ? $origin['province'] : ''); $sender_country_code = (isset($origin['country']) ? $origin['country'] : '');
    
    $sender_name        =  (!empty($get_sender['shipping_sender_name']) ? $get_sender['shipping_sender_name'] : $origin['company_name']);
    $sender_phone       = (!empty($get_sender['shipping_sender_phone']) ? $get_sender['shipping_sender_phone'] : $origin['phone']);
    $pickup_city 		= (!empty($get_sender['shipping_pickup_city']) ? $get_sender['shipping_pickup_city'] : $origin['city']);
    $pickup_postcode 	= (!empty($get_sender['shipping_pickup_postcode']) ? $get_sender['shipping_pickup_postcode'] : $origin['postal_code']);
    $pickup_state 		= (!empty($get_sender['shipping_pickup_state']) ? ($get_sender['shipping_pickup_state']) : ((gigl_state()[$sender_state_code]) ? gigl_state()[$sender_state_code] : $sender_state_code));
    $pickup_base_address = (!empty($get_sender['shipping_pickup_address']) ? $get_sender['shipping_pickup_address'] : $origin['address1']);
    $pickup_country 	= (!empty($get_sender['shipping_pickup_country']) ? ($get_sender['shipping_pickup_country']) : ((gigl_countries()[$sender_country_code]) ? gigl_countries()[$sender_country_code] : $sender_country_code));
    
    if (trim($pickup_country) == '') {
            $pickup_country = 'Nigeria';
    }
    $ert = array($sender_name, $sender_phone, $pickup_city, $pickup_postcode, $pickup_state, $pickup_base_address, $pickup_country);
    
    if($delivery_receiver_email ){
        $author_obj = (object)['display_name'=>$delivery_receiver_name, 'user_email'=>$delivery_receiver_email, 'phone'=>$delivery_receiver_phone];
        $delivery_base_receiver_name = $author_obj->display_name;
        $delivery_base_receiver_email = $author_obj->user_email;
        $delivery_base_receiver_phone = $phone = $delivery_receiver_phone;

    }else{
        $delivery_base_receiver_name = "Not login user";
        $delivery_base_receiver_email = "nouser@demo.com";
        $delivery_base_receiver_phone = "08030000000";
    }

    $receiver_name      = $delivery_base_receiver_name;
    $receiver_email     = $delivery_base_receiver_email;
    $receiver_phone     = $delivery_base_receiver_phone;
    
    
    $preShipmentItems = array();
    foreach( $items as $item ){
        
        $product_id = $item["product_id"];
        $itemWeight = $item['grams']/1000;
        
        $eachProductItem = array(
            "SpecialPackageId" => "0", 
            "Quantity" => "1", 
            "Weight" => "1", 
            "ItemType" => "Normal", 
            "WeightRange" => "0", 
            "ItemName" => "Shoe Lace", 
            "Value" => "1000", 
            "ShipmentType" => "Regular" );

        $preShipmentItems[] = $eachProductItem;

    }    
    
    $todaydate =  date('Y-m-d H:i:s', time());
    $pickup_date = date('Y-m-d H:i:s', strtotime($todaydate . ' +1 day'));
    $delivery_date = date('Y-m-d H:i:s', strtotime($todaydate . ' +2 day'));
        
        $get_crap_out_add = array(', ,',',',', , ');
    if($delivery_postcode == '' || empty($delivery_postcode) ) { 
         
        $delivery_address = trim("$delivery_base_address $delivery_city, $delivery_state, $delivery_country");
        if( !in_array($delivery_address, $get_crap_out_add)){
                $delivery_coordinate = get_lat_lng($delivery_address,$delivery_receiver_email);
            
            if (!isset($delivery_coordinate->Latitude) && !isset($delivery_coordinate->Longitude)) {
                $delivery_coordinate = get_lat_lng("$delivery_city, $delivery_state, $delivery_country", $delivery_receiver_email);
            }
            if (!isset($delivery_coordinate->Latitude) && !isset($delivery_coordinate->Longitude)) {
                $delivery_coordinate = get_lat_lng("$delivery_state, $delivery_country", $delivery_receiver_email);
            }
        }
        
        $pickup_address = trim("$pickup_base_address $pickup_city, $pickup_state, $pickup_country");
        if(!in_array($pickup_address, $get_crap_out_add)){
        
            $pickup_coordinate = get_lat_lng($pickup_address,null);
            
            if (!isset($pickup_coordinate->Latitude) && !isset($pickup_coordinate->Longitude)) {
                $pickup_coordinate = get_lat_lng("$pickup_city, $pickup_state, $pickup_country",null);
            }
        }
         
    }else {
       
    
        $delivery_address = $delivery_postcode . ',' . $delivery_city . ',' . $delivery_state . ',nigeria';
        $delivery_address = trim("$delivery_address");
        if(!in_array($delivery_address, $get_crap_out_add)){
            $delivery_addressd = trim("$delivery_base_address $delivery_city, $delivery_state, $delivery_country,$delivery_postcode");
            $delivery_coordinate = get_lat_lng($delivery_address, $delivery_receiver_email);
            
            if (!isset($delivery_coordinate->Latitude) && !isset($delivery_coordinate->Longitude)) {
                $delivery_coordinate = get_lat_lng("$delivery_address", $delivery_receiver_email);
            }
        }
        $pickup_address = $pickup_postcode . ',' . $pickup_city . ',' . $pickup_state . ',nigeria';
        $pickup_address = trim("$pickup_address");
        $pickup_addressd = trim("$pickup_base_address $pickup_city, $pickup_state, $pickup_country, $pickup_postcode");
        if(!in_array($pickup_address, $get_crap_out_add)){
            $pickup_coordinate = get_lat_lng($pickup_address, null);
        
            if (!isset($pickup_coordinate->Latitude) && !isset($pickup_coordinate->Longitude)) {
                $pickup_coordinate = get_lat_lng("$pickup_address",null);
            }
        }
        
        if(!in_array($delivery_address, $get_crap_out_add)){
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
                            "PreShipmentItems" => $preShipmentItems
                            );
                            
                        
        }
            try {
                $res = calculate_pricing($params);
                
                } catch (\Exception $e) {
                    $res = 0;
            }

            $data_res = $res;
            sleep(30);
            return $data_res;
    }
}

// function calculateShippingRates($request){
//     // if(isset($request['rate']['items'][0]['name']) && !empty($request['rate']['items'][0]['name'])){
        
       
//         return $cost;
//     // }
// }

// Usage
$data = file_get_contents("php://input");
$request = json_decode($data, true);//$count=0;//global $actualCost;
if(isset($request['rate']['items'][0]['name']) && !empty($request['rate']['items'][0]['name'])){
   // $count++;
    global $actualCost;
//     // if($count < 2){
    
    $cacheKey = 'shipping_cost_' . md5(serialize($request['rate']['destination']));
    
    if (apcu_exists($cacheKey)) {
       $logFile = fopen('newlog.log', 'a');
    // Check if the log file was opened successfully
    if ($logFile) {
        // Use print_r to display the array contents
        ob_start(); // Start output buffering
        print_r($cacheKey); // Print the array contents
        $output = ob_get_clean(); // Capture the printed output
        fwrite($logFile, $output); // Write the output to the log file
        fclose($logFile); // Close the log file

        // Now the array contents are saved in the 'debug.log' file
    } 
        $actualCost = apcu_fetch($cacheKey);
    } else {
        $logFile = fopen('newlo.log', 'a');
    // Check if the log file was opened successfully
    if ($logFile) {
        // Use print_r to display the array contents
        ob_start(); // Start output buffering
        print_r($cacheKey); // Print the array contents
        $output = ob_get_clean(); // Capture the printed output
        fwrite($logFile, $output); // Write the output to the log file
        fclose($logFile); // Close the log file

        // Now the array contents are saved in the 'debug.log' file
    }
        // Calculate the shipping cost
        $cost = calculateShippingCost($request);
        $actualCost = (isset($cost['Object']['DeliveryPrice']) ? (($cost['Object']['DeliveryPrice']) * 100) : 7000.00);
        // Cache the result for future use
        apcu_add($cacheKey, $actualCost, 3600); // Cache for 1 hour
    }
    
    //$_SESSION["cost"] = $actualCost;
    $rates = [
        [
            'service_name' => 'GIG-Logistics-Shippings', // Name of the shipping service
            'service_code' => 'standard', // Unique code for the service
            'total_price' => $actualCost, // Shipping cost for the item
            'description' => 'This is the fastest option to deliver your goods by far', 
            'currency' => 'NGN', // Currency code
            'min_delivery_date' => '2023-12-11',
            'max_delivery_date' => '2023-12-12',
        ],
    ];
    header('Content-Type: application/json');
    echo json_encode(['rates' => $rates]);
    $logFile = fopen('newlogs.log', 'a');
    // Check if the log file was opened successfully
    if ($logFile) {
        // Use print_r to display the array contents
        ob_start(); // Start output buffering
        print_r($actualCost); // Print the array contents
        $output = ob_get_clean(); // Capture the printed output
        fwrite($logFile, $output); // Write the output to the log file
        fclose($logFile); // Close the log file

        // Now the array contents are saved in the 'debug.log' file
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
            'currency' => 'NGN', // Currency code
            'min_delivery_date' => '2023-12-11',
            'max_delivery_date' => '2023-12-12',
        ],
    ];
    header('Content-Type: application/json');
    echo json_encode(['rates' => $rates]);
}
   
    
   
   //` }
