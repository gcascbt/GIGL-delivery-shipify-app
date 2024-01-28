<?php
/**
 * Summary.
 *
 * Description: this is the shipping webhook for carrier service call(carrier_service_provider)
 *
 * @since Version 3 digits
 */

global $get_request;
$query = "SELECT * FROM shops WHERE shop_url='" . $parameters['shop'] . "' ORDER BY id DESC LIMIT 1";
$result = $mysql->query($query);

if( $result->num_rows < 1 ) {
    header("Location: install.php?shop=" . $_GET['shop']);
    exit();
}
$store_data = $result->fetch_assoc();

$shopify->set_url($parameters['shop']);
$shopify->set_token($store_data['access_token']);

$carrier_service_get = $shopify->rest_api('/admin/api/2023-10/carrier_services.json', array(), 'GET');
$response_service_get = json_decode($carrier_service_get['body'], TRUE);
$carrierServiceId = $response_service_get['carrier_services'][0]['id'];
// print_r($response_service_get);
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
}else{
    $webhook_for_shipping_name = json_decode('
        {
            "carrier_service": {
                "name": "GIGL",
                "callback_url": "https://gigl.pushtechn.com/gigl-delivery-shipping/webhook_service.php",
                "service_discovery":true
            }
        }
    ', TRUE);
    $carrier_service = $shopify->rest_api('/admin/api/2023-10/carrier_services.json', $webhook_for_shipping_name, 'POST');
    $responseData = json_decode($carrier_service['body'], TRUE);
    //print_r($responseData);
    if (isset($responseData['carrier_service'])) {
        $carrierServiceId = $responseData['carrier_service']['id'];
    } else {
        $carrier_service_get = $shopify->rest_api('/admin/api/2023-10/carrier_services.json', array(), 'GET');
        $response_service_get = json_decode($carrier_service_get['body'], TRUE);
        $carrierServiceId = $response_service_get['carrier_services'][0]['id'];
        //print_r($response_service_get);
    } 
    if($carrierServiceId){
        // Associate the CarrierService with a CarrierService provider
        $carrierServiceProviderData = array(
            'carrier_service_provider' => array(
                'carrier_service_id' => $carrierServiceId,
                'name' => 'GIG Logistics',
                // Add any other relevant details for the CarrierService provider
            ),
        );

        // Convert data to JSON format
        $jsonProviderDatawe = json_encode($carrierServiceProviderData);
        $jsonProviderData = json_decode($jsonProviderDatawe,TRUE);

        // Set up cURL options to create the CarrierService provider
        $carrier_added_service = $shopify->rest_api('/admin/api/2021-07/carrier_service_providers.json', $jsonProviderData, 'POST');
        $responseProviderData = json_decode($carrier_added_service['body'], TRUE);
        //print_r($responseProviderData);
        // Check if the CarrierService provider was added successfully
        if (isset($responseProviderData['carrier_service_provider'])) {
            echo 'Carrier service and provider added successfully.';
        } else {
            echo 'Error adding CarrierService provider: ' . print_r($responseProviderData, true);
        }
    }else{
        echo 'Error adding CarrierService: ' . print_r($responseData, true);
    }
}
$webhookCheckoutData = array(
    'webhook' => array(
        'topic' => 'orders/paid',
        'address' => 'https://'.$_SERVER['HTTP_HOST'].'/gigl-delivery-shipping/order_fullfilment.php',//$webhookEndpoint,
        'format' => 'json'
    )
);
// Convert data to JSON format
$jsonEncodeDatafully_paid = json_encode($webhookCheckoutData);
$jsonDecodeDatafully_paid = json_decode($jsonEncodeDatafully_paid,TRUE);

// Set up cURL options to create the CarrierService provider
$checkout_fully_paid = $shopify->rest_api('/admin/api/2023-10/webhooks.json', $jsonDecodeDatafully_paid, 'POST');
$responseProviderData = json_decode($checkout_fully_paid['body'], TRUE);
//print_r($responseProviderData);

// $webhook_data = json_decode('
//     {
//         "webhook": {
//             "topic": "checkouts/update",
//             "address": "https://gigl.pushtechn.com/gigl-delivery-shipping/update_token_checkout.php",
//             "format": "json"
//         }
//     }
// ', TRUE);
// $webhook = $shopify->rest_api('/admin/api/2021-07/webhooks.json', $webhook_data, 'POST');
// $response = json_decode($webhook['body'], TRUE);
// print_r($response); 
$webhook_data_cart = json_decode('
    {
        "webhook": {
            "topic": "carts/update",
            "address": "https://'.$_SERVER['HTTP_HOST'].'/gigl-delivery-shipping/update_token.php",
            "format": "json"
        }
    }
', TRUE);
$webhook_cart = $shopify->rest_api('/admin/api/2023-10/webhooks.json', $webhook_data_cart, 'POST');
$response = json_decode($webhook_cart['body'], TRUE);
// print_r($response);
// $webhook_carts_d = $shopify->rest_api('/admin/api/2023-10/webhooks/1191477706919.json', array(), 'DELETE');
// $webhook_carts = $shopify->rest_api('/admin/api/2021-07/webhooks.json', array(), 'GET');
// $response = json_decode($webhook_carts['body'], TRUE);
// print_r($response);
