<?php
include_once("mysql_connect.php");

function get_shop($request=[]){
    global $mysql;
    if(empty($parameters['shop'])){
        $parameters = $request;
    }
    $query = "SELECT * FROM shops WHERE shop_url='" . $parameters['shop'] . "' ORDER BY id DESC LIMIT 1";
    $result = $mysql->query($query);
      
    if( $result->num_rows < 1 ) {
        header("Location: install.php?shop=" . $_GET['shop']);
        exit();
    }
    $store_data = $result->fetch_assoc();
    return $store_data;
}
function get_origin_data($request=[]){
    global $mysql;
    if(empty($parameters['shop'])){
        $parameters = $request;
    }
    $query = "SELECT * FROM delivery_api WHERE shop_url='" . $parameters['shop'] . "' ORDER BY id DESC LIMIT 1";
    $result = $mysql->query($query);
    $shipping_data = $result->fetch_assoc();
    return $shipping_data;
}
function update_shop_token($token){
    global $mysql; global $origin_shipping_data;
    $meta = unserialize($origin_shipping_data['meta']);
    $stmt = $mysql->prepare("UPDATE delivery_api SET transact_token = ?, call_mode = ? WHERE shop_url = ?");

    // Check if the statement was prepared successfully
    if ($stmt) {
        // Bind the variables to the statement
        $stmt->bind_param("sss", $token, $meta['mode'], $origin_shipping_data['shop_url']);
        
        // Execute the statement
        if ($stmt->execute()) {
            return true;
        } else {
            return false;
        }

        // Close the statement
        $stmt->close();
    } else {
        return false;
    }
}

function update_address_transist($address, $cordinate, $zip_code, $email){
    global $mysql;
    if((!empty($address)) && !empty($cordinate)){
        $cacheFile =  md5(serialize($address));
        $expiry = time() + $expiry;
        $query = "INSERT INTO address_transient (ship_address, cordinate, zip_code, email, expiry) VALUES ('" . $cacheFile . "','" . $cordinate . "','" . $zip_code . "','" . $email . "','" . $expiry . "') ON DUPLICATE KEY UPDATE cordinate='" . $cordinate . "', zip_code='" . $zip_code . "'";
        if($mysql->query($query)) {
            
            return true;
        }else{
            return false;
        }
    }else{
        return false;
    }
}
function get_user_address($address){
    global $mysql;
    $cacheFile =  md5(serialize($address));
    $query = "SELECT * FROM address_transient WHERE ship_address LIKE '" . $cacheFile . "' ORDER BY id DESC LIMIT 1";
    $result = $mysql->query($query);
    $shipping_data = $result->fetch_assoc();
    $coordinate = json_decode($shipping_data['cordinate']);
    return $coordinate;
}
function get_user_address_by_zip_code($zip_code){
    global $mysql;
    $query = "SELECT * FROM address_transient WHERE zip_code = " . $zip_code . " ORDER BY id DESC LIMIT 1";
    $result = $mysql->query($query);
    $shipping_data = $result->fetch_assoc();
    $coordinate = json_decode($shipping_data['cordinate']);
    return $coordinate;
}
function getCachedData($key,$shop=null) {
    global $mysql;
    $cacheFile =  md5(serialize($key));
    
    $query = "SELECT * FROM billings WHERE gen_id LIKE '" . $cacheFile . "' ORDER BY id DESC LIMIT 1";
    $result = $mysql->query($query);
    $shipping_data = $result->fetch_assoc();
    if ($shipping_data) {
        $cacheData = unserialize($shipping_data["meta_value"]);

        if ($cacheData['expiry'] > time()) {
            return $cacheData['data'];
        }
    }
    return $null;
}
function cacheData($key, $data, $expiry, $shop_url) {
    global $mysql;
    $cacheFile =  md5(serialize($key));
    
    $cacheData = array(
        'expiry' => time() + $expiry,
        'data' => $data,
    );
    $serialData = serialize($cacheData);
    $logFile = fopen('debug.log', 'a');
        // Check if the log file was opened successfully
        if ($logFile) {
            // Use print_r to display the array contents
            ob_start(); // Start output buffering
            print_r(array($cacheFile, $shop_url, $serialData, date('Y-m-d h: i : s'))); // Print the array contents
            $output = ob_get_clean(); // Capture the printed output
            fwrite($logFile, $output); // Write the output to the log file
            fclose($logFile); // Close the log file
    
            // Now the array contents are saved in the 'debug.log' file
        }
    $query = "INSERT INTO billings (gen_id, shop_url, meta_value) VALUES ('" . $cacheFile . "','" . $shop_url . "','" . $serialData . "') ON DUPLICATE KEY UPDATE meta_value='" . $serialData . "'";
    if($mysql->query($query)){
        return true;
    }
    return false;
}
function log_order_data_value($order_id=NULL, $reference=NULL, $token=NULL, $items=NULL, $waybill=NULL, $tracking_url=NULL, $status = 'pending'){
    include("mysql_connect.php");
    global $mysql;
	$logFile = fopen('newlyfufilfailed.log', 'a');
			// Check if the log file was opened successfully
			if ($logFile) {
				// Use print_r to display the array contents
				ob_start(); // Start output buffering
				print_r(array($order_id, $reference, $token, $items, $waybill, $tracking_url, $status)); // Print the array contents
				$output = ob_get_clean(); // Capture the printed output
				fwrite($logFile, $output); // Write the output to the log file
				fclose($logFile); // Close the log file

				// Now the array contents are saved in the 'debug.log' file
			}
	if((empty($waybill) && ($status == 'pending')) || (($waybill == NULL) && ($status = 'pending'))){
	    $query = "INSERT INTO orders (checkout_id, reference, token, items, waybill, tracking_url, status) VALUES  ('" . $order_id . "','" . $reference . "','" . $token . "','" . $items . "','" . $waybill . "','" . $tracking_url . "','" . $status . "')";
	   
	}elseif(empty($waybill) || ($waybill == NULL)){
	   $query = "INSERT INTO orders (checkout_id, reference, token, items, waybill, tracking_url, status) VALUES  ('" . $order_id . "','" . $reference . "','" . $token . "','" . $items . "','" . $waybill . "','" . $tracking_url . "','" . $status . "') ON DUPLICATE KEY UPDATE status='" . $status . "'";
	}else{
	    $query = "INSERT INTO orders (checkout_id, reference, token, items, waybill, tracking_url, status) VALUES  ('" . $order_id . "','" . $reference . "','" . $token . "','" . $items . "','" . $waybill . "','" . $tracking_url . "','" . $status . "') ON DUPLICATE KEY UPDATE status='" . $status . "', waybill='" . $waybill . "', tracking_url='" . $tracking_url . "'";
	}
    if($mysql->query($query)){
        return true;
    }
    return false;
}
function get_checkout_waybill($checkout_id=null, $checkout_ref=null){
    global $mysql;
    if($checkout_id !== null){
        $query = "SELECT * FROM orders WHERE reference = '" . $checkout_id . "' AND waybill IS NOT NULL ORDER BY id DESC LIMIT 1";
    }elseif($checkout_ref !== null){
        $query = "SELECT * FROM orders WHERE token = '" . $checkout_ref . "' AND waybill IS NOT NULL ORDER BY id DESC LIMIT 1";
    }else{
        return;
    }
    $result = $mysql->query($query);
    $shipping_data = $result->fetch_assoc();
    if($shipping_data){
        return $shipping_data['waybill'];
    }
    return;
    
}