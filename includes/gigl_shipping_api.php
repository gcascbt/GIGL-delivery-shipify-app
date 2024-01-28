
<?php
session_start();
	
	// defined( 'ABSPATH' ) or die( 'Hey, what are you doing here? You silly human!' );

		function get_token_api($ship = array())
		{
			global $env; global $login_credentials; global $request_url; global $origin_shipping_data;
			$origin_shipping_data = $ship;
			
			$settings = unserialize($ship['meta']);
			$env = isset($settings['mode']) ? $settings['mode'] : 'Test';
			
			if ($env == 'Live') {
				$username    = isset($ship['live_key']) ? $ship['live_key'] : '';
				$password = isset($ship['live_secret']) ? $ship['live_secret'] : '';    
				
				$request_url = get_host_url($ship); //'https://mobile.gigl-go.com/api/thirdparty/';

				$sender_name = isset($settings['shipping_sender_name']) ? $settings['shipping_sender_name'] : '';
				$sender_phone_number = isset($settings['shipping_sender_phone']) ? $settings['shipping_sender_phone'] : '';
			} else {
				$username    = isset($ship['test_key']) ? $ship['test_key'] : '';
				$password = isset($ship['test_secret']) ? $ship['test_secret'] : '';
				
				$request_url = get_host_url($ship); //'http://test.giglogisticsse.com/api/thirdparty/';

				$sender_name = isset($settings['shipping_sender_name']) ? $settings['shipping_sender_name'] : '';
				$sender_phone_number = isset($settings['shipping_sender_phone']) ? $settings['shipping_sender_phone'] : '';
			}
			
			$getAllToken = vendor_login($username, $password);
			return $getAllToken;
		}
		/**
			* Call the Gigl Delivery Login API
			*
			* @param string $username
			* @param string $password
			* @return void
		*/
		function vendor_login($username, $password)
		{
			global $login_credentials; global $expired_token;
			
			// Transient expired or doesn't exist, fetch the data
			if (empty($login_credentials) || $expired_token ==='expired') {
				$params = array(
               'username'        => $username,
               'Password'     => $password,
               'SessionObj'    => "",
				);
				
				$response = api_request(
                'login',
                $params
				);
				$login_credentials = $response;
				//$convertLogin = json_encode($response);
				//$update_token_id = update_token_($convertLogin);
			}
			 return $login_credentials;
		}
		function get_host_url($mode=[]){ 
			global $request_url;
			$settings = unserialize($mode['meta']);
			$env = isset($settings['mode']) ? $settings['mode'] : 'Test';
			if ($env == 'Live') {
				$request_url = 'https://thirdparty.gigl-go.com/api/thirdparty/';
			}else{
				$request_url = 'https://giglthirdpartyapitestenv.azurewebsites.net/api/thirdparty/';
			}
			return $request_url;
		}
		function get_order_details($waybill)
		{
			global  $login_credentials;
			$access_token = $login_credentials->Object->access_token;
			$params = [];
			
			return api_request('TrackAllShipment/'.$waybill, $params, 'GET', $access_token);
		}
		function create_task($params)
		{
			global  $login_credentials;
			$access_token = $login_credentials->Object->access_token;
			$params['UserId'] = $login_credentials->Object->UserId;
			$params['CustomerCode'] = $login_credentials->Object->UserName; 
         	$params['ReceiverStationId'] = "4";
          	$params['SenderStationId'] = "4";
			
			return api_request('captureshipment', $params, 'POST', $access_token);
		}
		function track_details($waybill)
		{
			global  $login_credentials;
			$access_token = $login_credentials->Object->access_token;
			$params = [];
			
			return api_request('TrackAllShipment/'.$waybill, $params, 'GET', $access_token);
		}
		function calculate_pricing($params)
		{
			global  $login_credentials;
			$access_token = $login_credentials->Object->access_token;
			$params['UserId'] = $login_credentials->Object->UserId;
			$params['CustomerCode'] = $login_credentials->Object->UserName; 
         	$params['ReceiverStationId'] = "4";
          	$params['SenderStationId'] = "4";
			$thu = json_encode($params);
			 
			return api_request('price', $params, 'POST', $access_token);
		}
		
		function get_lat_lng($address, $email)
		{
			global  $login_credentials; global $new_location;
			$access_token = $login_credentials->Object->access_token;
			if (!empty($address)) {
				$params = array('Address' => $address);
				$geocodeResponse = api_request('getaddressdetails', $params,'POST',$access_token);
			 	$coordinate['Latitude']  = $geocodeResponse["Object"]["Latitude"];
			 	$coordinate['Longitude'] = $geocodeResponse["Object"]["Longitude"];
				//  $convert_to_json = json_encode($coordinate);
				 $coordinate = (object) $coordinate;
			 	//set_transient('gigl_delivery_addr_geocode_' . $address, $coordinate, DAY_IN_SECONDS * 90);
				//create_address_cordinate($new_location,$convert_to_json,$email);
				
			}
			 return $coordinate;
		}
		
		/**
			* Send HTTP Request
			* @param string $endpoint API request path
			* @param array $args API request arguments
			* @param string $method API request method
			* * @param string $token API request token
			* @return JSON decoded transaction object. NULL on API error.
		*/
		function api_request(
        $endpoint,
        $args = array(),
        $method = 'POST', $token = NULL
		) {
			global  $request_url;
			 $uri = "{$request_url}{$endpoint}";
			 $query = json_encode($args);
			 
				$curl = curl_init();
				curl_setopt($curl, CURLOPT_URL, $uri);
				//curl_setopt($curl, CURLOPT_HEADER, true);
				curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
				curl_setopt($curl, CURLOPT_ENCODING, '');
				curl_setopt($curl, CURLOPT_FOLLOWLOCATION, true);
				curl_setopt($curl, CURLOPT_MAXREDIRS, 10);
				curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
		
				curl_setopt($curl, CURLOPT_TIMEOUT, 0);
				curl_setopt($curl, CURLOPT_CONNECTTIMEOUT, 45);
				curl_setopt($curl, CURLOPT_CUSTOMREQUEST, $method);
				curl_setopt($curl, CURLOPT_HTTPHEADER, get_header($token),);
		
				if( $method != 'GET' && in_array($method, array('POST', 'PUT'))) {
				 	if( is_array($args) ) $query = json_encode($args);
				 	curl_setopt($curl, CURLOPT_POSTFIELDS, $query);
				 }
		
				$response = curl_exec($curl);
				$error = curl_errno($curl);
				$error_msg = curl_error($curl);
				
				curl_close($curl);
				if($error) {
					return $error_msg;
				} else {
					$response = json_decode($response, true);
					return $response;
				}
			 
			
		}
		
		/**
			* Generates the headers to pass to API request.
		*/
		function get_header($token)
		{
			if(!empty($token)){
				$getHead = array(
            "Authorization: Bearer {$token}",
            "Content-Type: application/json",
        	);
			}else{
				$getHead = array("Content-Type: application/json",);
			}

			return $getHead;
			
		}
