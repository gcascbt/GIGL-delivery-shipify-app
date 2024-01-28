<?php
$_API_KEY = 'a5a71d71b73c3e546909bdeefde3c3a1';
$_NGROK_URL = 'https://gigl.pushtechn.com';
$shop = $_GET['shop'];
$scopes = 'read_products,write_products,read_orders,write_orders,read_checkouts,write_checkouts,read_script_tags,read_shipping,write_shipping, write_script_tags';
$redirect_uri = $_NGROK_URL . '/gigl-delivery-shipping/token.php';
$nonce = bin2hex( random_bytes( 12 ) );
$access_mode = 'per-user';

$oauth_url = 'https://' . $shop . '/admin/oauth/authorize?client_id=' . $_API_KEY . '&scope=' . $scopes . '&redirect_uri=' . urlencode($redirect_uri) . '&state=' . $nonce . '&grant_options[]=' . $access_mode;

header("Location: " . $oauth_url);
exit();
