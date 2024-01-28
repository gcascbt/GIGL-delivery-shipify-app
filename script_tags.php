<?php
include_once("includes/mysql_connect.php");
include_once("includes/shopify.php");

//this is for showcasing waybill on thankyou page/order completed
$shopify = new Shopify();
$parameters = $_GET;

include_once("includes/check_token.php");
$query = "SELECT * FROM shops WHERE shop_url='" . $parameters['shop'] . "' ORDER BY id DESC LIMIT 1";
$result = $mysql->query($query);

$store_data = $result->fetch_assoc();

$shopify->set_url($parameters['shop']);
$shopify->set_token($store_data['access_token']);
$script_url = 'https://'.$_SERVER['HTTP_HOST'].'/gigl-delivery-shipping/scripts/gigl.js';
$script_array = array('src' => 'https://'.$_SERVER['HTTP_HOST'].'/gigl-delivery-shipping/scripts/gigl.js');
function isUrlInArray($array, $targetUrl) {
    foreach ($array as $item) {
        if (isset($item['src']) && $item['src'] === $targetUrl) {
            return true; // URL exists in the array
        }
    }
    return false; // URL not found in the array
}
    $script_tag = array(
            'script_tag' => array('event' => 'onload','src' => $script_url));
        
                $get_script = $shopify->rest_api('/admin/api/2021-04/script_tags.json', $script_tag, 'GET');
                $script_data = json_decode($get_script['body'], true);
    foreach($script_array as $key=>$script_){
            if(isUrlInArray($script_data['script_tags'], $script_) === false){
                 $registerScript = array(
                        "event" => "onload",
                        "src" => $script_
                    );
                if($key=='href'){
                    $registerScript["display_scope"] = "online_store";
                }
            $scriptTag_data = array("script_tag" =>$registerScript);
        
            $create_script = $shopify->rest_api('/admin/api/2021-04/script_tags.json', $scriptTag_data, 'POST');
            $create_script = json_decode($create_script['body'], true);
        }
    }
?>