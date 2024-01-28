<?php
include_once("mysql_connect.php");
include_once('includes/function.php');
include_once('includes/gigl_shipping_api.php');
include_once("includes/shopify.php");
// Receive the JSON payload from Shopify
$jsonPayload = file_get_contents('php://input');
$data = json_decode($jsonPayload, true);
$logFile = fopen('newlyfullfil.log', 'a');
        // Check if the log file was opened successfully
        if ($logFile) {
            // Use print_r to display the array contents
            ob_start(); // Start output buffering
            print_r($data); // Print the array contents
            $output = ob_get_clean(); // Capture the printed output
            fwrite($logFile, $output); // Write the output to the log file
            fclose($logFile); // Close the log file
            // Now the array contents are saved in the 'debug.log' file
        }
$giglShippingExists = false;
foreach ($data['shipping_lines'] as $shippingLine) {
    if (isset($shippingLine['source']) && $shippingLine['source'] === 'GIGL') {
        $giglShippingExists = true;
        break; // Stop the loop once 'GIGL' is found
    }
}
if($giglShippingExists){
	$shopify = new Shopify();
	global $shop_url; global $get_origin_data_value; global $get_shop; global $login_credentials;
	$parameters= array(); $replace_all = array('https://');
	$repShopUrl = str_replace($replace_all,"",$data['order_status_url']);
	$explodeShopUrl = explode('/',$repShopUrl);
	$parameters['shop']= $shop_url = $explodeShopUrl[0];
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
	$preShipmentItems = array();
	foreach( $data['line_items'] as $item ){
		$eachProductItem = array(
								"SpecialPackageId" => 56372,//$item['product_id'], 
								"Quantity" => $item['quantity'], 
								"Weight" => (($item['grams']) ? $item['grams'] : "1"), 
								"ItemType" => "Normal", 
								"WeightRange" => $item['grams'], 
								"ItemName" => $item['name'], 
								"Value" => $item['price'], 
								"ShipmentType" => "Regular",
								"ItemRequiresShipping" => $item['requires_shipping']
								);

		$preShipmentItems[] = $eachProductItem;

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
			if($delivery_postcode == '' || empty($delivery_postcode) ) {
				if( !in_array($delivery_address, $scrap_out)){
						$new_location = $get_location;
						
						$delivery_coordinate = get_lat_lng($delivery_address,$delivery_receiver_email); //geolocation($delivery_address);
						$updatedAPICorditate = $delivery_coordinate;
				}
			}else{
				if(!in_array($delivery_address, $scrap_out)){
					
					$new_location = $get_location;
					$delivery_coordinate = get_lat_lng($delivery_address, $delivery_receiver_email);//geolocation($delivery_address);
					$updatedAPICorditate = $delivery_coordinate;
				}
				
			}
	}else{
		$delivery_coordinate = $getDeliveryCachedAddressData;
	}
	$pickup_address = trim("$pickup_base_address $pickup_city, $pickup_state, $pickup_country");
	if(!$getPickupCachedAddressData){
		if($delivery_postcode == '' || empty($delivery_postcode) ) {
			if(!in_array($pickup_address, $scrap_out)){
				$new_location = $get_pickup_location;
				$pickup_coordinate = get_lat_lng($pickup_address,null);
				
			}
			
		}else{
			if(!in_array($pickup_address, $scrap_out)){
				$new_location = $get_pickup_location;
				$pickup_coordinate = get_lat_lng($pickup_address, null);
				
			}
		}
	}else{
		$pickup_coordinate = $getPickupCachedAddressData;
	}
	if (isset($data['financial_status']) && $data['financial_status'] === 'paid') {
	
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
			"PreShipmentItems" => $preShipmentItems
			);
			
			try {
				
				$res = create_task($params);
				$logFile = fopen('newfufiling.log', 'a');
			// Check if the log file was opened successfully
			if ($logFile) {
				// Use print_r to display the array contents
				ob_start(); // Start output buffering
				print_r(array($res, 'reference'=>$data['reference'], 'token'=>$data['token'])); // Print the array contents
				$output = ob_get_clean(); // Capture the printed output
				fwrite($logFile, $output); // Write the output to the log file
				fclose($logFile); // Close the log file

				// Now the array contents are saved in the 'debug.log' file
			}
			} catch (\Exception $e) {
				$res = 0;
			}
			if ($res["Code"] == 200) {
				$order_data = json_encode($preShipmentItems);
				log_order_data_value($data['id'], $data['reference'], $data['token'], $order_data,$res['Object']['waybill'],'http://test.giglogisticsse.com/api/thirdparty/TrackAllShipment/'.$res['Object']['waybill'], $data['financial_status']);
			}else{
			    $order_data = json_encode($preShipmentItems);
				log_order_data_value($data['id'],$data['reference'], $data['token'], $order_data,"","", 'pending');
			}
		}
	}else{
		$order_data = json_encode($preShipmentItems);
		log_order_data_value($data['id'], $data['reference'], $data['token'], $order_data,"","", $data['financial_status']);
	}
}
		
		
		
		

