<?php
include_once('includes/function.php');
include_once('includes/gigl_shipping_api.php');
if($_SERVER['REQUEST_METHOD'] == 'POST') {
    
    if(isset($_POST['shipping_pickup_country']) && isset($_POST['shipping_mode']) && $_POST['action_type'] == 'setup_delivery_api') {
        // if($_POST['enable_shipping_method']){
        //     echo $_POST['enable_shipping_method'];
        // }else{
        //     echo $_POST['enable_shipping_method'];
        // }
        if((($_POST['shipping_mode']=='Live') && (!empty($_POST['shipping_live_secret']) && !empty($_POST['shipping_live_key']))) || (($_POST['shipping_mode']=='Test') && (!empty($_POST['shipping_test_secret']) && !empty($_POST['shipping_test_key'])))){
            $shop_url = $parameters['shop'];
            $enable_shipping_method = $_POST['enable_shipping_method']; 
           
            $shipping_mode = trim($_POST['shipping_mode']);
            $shipping_test_key = trim($_POST['shipping_test_key']);
            $shipping_test_secret = trim($_POST['shipping_test_secret']);
            $shipping_live_key = trim($_POST['shipping_live_key']);
            $shipping_live_secret = trim($_POST['shipping_live_secret']);
            $shipping_pickup_country = trim($_POST['shipping_pickup_country']);
            $shipping_pickup_state = trim($_POST['shipping_pickup_state']);
            $shipping_pickup_city = trim($_POST['shipping_pickup_city']);
            $shipping_pickup_postcode = trim($_POST['shipping_pickup_postcode']);
            $shipping_pickup_address = trim($_POST['shipping_pickup_address']);
            $shipping_sender_name = trim($_POST['shipping_sender_name']);
            $shipping_sender_phone = trim($_POST['shipping_sender_phone']); 
            $meta = array(
                'enable_shipping_method' => $enable_shipping_method,
                'mode' => $shipping_mode, 
                'shipping_pickup_country' => $shipping_pickup_country,
                'shipping_pickup_state' => $shipping_pickup_state,
                'shipping_pickup_city' => $shipping_pickup_city,
                'shipping_pickup_postcode' => $shipping_pickup_postcode,
                'shipping_pickup_address' => $shipping_pickup_address,
                'shipping_sender_name' => $shipping_sender_name,
                'shipping_sender_phone' => $shipping_sender_phone );
                 $meta = serialize($meta);
            //     $getallData = array('shop_url' => $shop_url, 'meta' => $meta, 'test_key' => $shipping_test_key, 'test_secret' => $shipping_test_secret,
            //     'live_key' => $shipping_live_key, 'live_secret' => $shipping_live_secret);
            //     echo '<pre>';
            //      print_r($getallData);
            //      echo '</pre>';
            // die();
            $query = "INSERT INTO delivery_api (shop_url, meta, test_key, test_secret, live_key, live_secret, date) VALUES ('" . $shop_url . "','" . $meta . "','" . $shipping_test_key . "', '" . $shipping_test_secret . "', '" . $shipping_live_key . "', '" . $shipping_live_secret . "', NOW()) ON DUPLICATE KEY UPDATE meta='" . $meta . "', test_key='" . $shipping_test_key . "', test_secret='" . $shipping_test_secret . "', live_key='" . $shipping_live_key . "', live_secret='" . $shipping_live_secret . "' ";
            if($mysql->query($query)) {
                global $new_location; global $login_credentials; global $expired_token;
                $get_pickup_location = array('address' => $shipping_pickup_address, 'poster_code' => $shipping_pickup_postcode, 'city' => $shipping_pickup_city, 'state' => $shipping_pickup_state, 'country' => $shipping_pickup_country, 'store_url' => $shop_url);
                $new_location = $get_pickup_location;
                $pickup_address = $shipping_pickup_postcode . ',' . $shipping_pickup_city . ',' . $shipping_pickup_state . ',nigeria';
                $pickup_address = trim("$pickup_address");
                $pickup_addressd = trim("$shipping_pickup_address $shipping_pickup_city, $shipping_pickup_state, $shipping_pickup_country, $shipping_pickup_postcode");
                if($pickup_address){
                    $parameters['shop']= $shop_url;
                    $get_origin_data = get_origin_data($parameters);
                    if(!$get_origin_data['transact_token']){
                        $login_credentials = get_token_api($get_origin_data);
                    }else{
                        $db_token_data = json_decode($get_origin_data['transact_token']);
                        
                        if(isset($db_token_data->Object->{".expires"})){
                            // Your date string
                            $dateString = $db_token_data->Object->{".expires"};
                        
                            // Create a DateTime object from the date string
                            $dateTime = new DateTime($dateString);
                        
                            // Get the current date and time as a DateTime object
                            $currentDateTime = new DateTime();
                        
                            // Compare the two DateTime objects
                            if ($dateTime < $currentDateTime) {
                                $expired_token = 'expired';
                                $login_credentials = get_token_api($get_origin_data);
                                if($login_credentials['Code']==200){
                                    print_r(array($login_credentials, $pickup_address));
                                    $encodeToken = json_encode($login_credentials);
                                    update_shop_token($encodeToken);
                                }else{
                                    print_r('<p class="" style="color:red">*Incorrect GIGL Login Details! Note: this credential is required to setup gigl shipping on your store </p>');
                                }
                            } 
                            
                        }else{
                            $login_credentials = $db_token_data;
                        }
                    }
                    
                    if($login_credentials['Code']==200){
                        $pickup_coordinate = get_lat_lng($pickup_address, null);
                        
                        if (!isset($pickup_coordinate->Latitude) && !isset($pickup_coordinate->Longitude)) {
                            $pickup_coordinate = get_lat_lng("$pickup_address",null);
                        }
                        if($pickup_coordinate){
                            $convert_to_json = json_encode($pickup_coordinate);
                            update_address_transist($get_pickup_location,$convert_to_json,$shipping_pickup_postcode,null);
                        }
                    }
                }
                //success message
                //echo "<script>top.window.location = 'https://" . $shop_url . "/admin/apps'</script>";
                print_r('<p class="" style="color:green;">Successfully store data info</p>');
            }else{
                //Error message
                print_r('<p class="" style="color:red">*this is now error </p>');
    
            }
        }else{
                //Error message
                print_r('<p class="" style="color:red">*Invalid field cannot be empty </p>');
    
            }
            
        // $product_data = array(
        //     "product" => array(
        //         "title" => $_POST['product_title'],
        //         "body_html" => $_POST['product_body_html'],
        //         "metafields" => [
        //             [
        //                 "namespace" => "global",
        //                 "key" => "example_metafield",
        //                 "value" => "This is a metafield value",
        //                 "value_type" => "string"
        //             ]
        //         ]
        //     )
        // );

        // $create_product = $shopify->rest_api('/admin/api/2021-04/products.json', $product_data, 'POST');
        // $create_product = json_decode($create_product['body'], true);

       
    }

    // if(isset($_POST['delete_id']) && $_POST['action_type'] == 'delete') {
    //     $delete = $shopify->rest_api('/admin/api/2021-04/products/' . $_POST['delete_id'] . '.json', array(), 'DELETE');
    //     $delete = json_decode($delete['body'], true);
    // }

    // if(isset($_POST['update_id']) && $_POST['action_type'] == 'update') {

    //     $getID = explode('/', $_POST['update_id']);

    //     $update_data = array(
    //         "product" => array(
    //             "id" => $_POST['update_id'],
    //             "title" => $_POST['update_name']
    //         )
    //     );

    //     $update = $shopify->rest_api('/admin/api/2021-04/products/' . end($getID) . '.json', $update_data, 'PUT');
    //     $update = json_decode($update['body'], true);

    //     echo print_r($update);
    // }
    
}
$query = "SELECT * FROM delivery_api WHERE shop_url='" . $parameters['shop'] . "' ORDER BY id DESC LIMIT 1";
$result = $mysql->query($query);

$gigl_delivery_api = $result->fetch_assoc();
if(isset($gigl_delivery_api['meta'])){
    $getClientMetaData = unserialize($gigl_delivery_api['meta']);
}
?>
 <section>
    <aside>
        <h2>Setup GIGL Delivery API </h2>
        <p>Fill out the following form and click the submit button to setup delivery api</p>
    </aside>
    <article>
        <div class="card">
            <form action="" method="post" id="account-setting">
                <input type="hidden" name="action_type" value="setup_delivery_api">
                <div class="row">
                    <label for="enableShippingMethod">Enable/Disable <span class="info-desc">(Enable this shipping method)</span></label>
                    <input type="checkbox" name="enable_shipping_method" id="enableShippingMethod" value="1" <?php echo (isset($getClientMetaData) ? (($getClientMetaData['enable_shipping_method']=='1') ? "checked" : "") : ""); ?> >
                    
                </div>
                <div class="row">
                    <label for="shippingMode">Mode <span class="info-desc">(Default is (test), choose (Live) when your ready to start processing orders via gigl delivery)</span></span></label>
                    <select name="shipping_mode" id="shippingMode">
                        <option value="Test" <?php echo ((isset($getClientMetaData) ? (($getClientMetaData['mode']=='Test') ? "selected": ""): '')); ?>>Test</option>
                        <option value="Live" <?php echo ((isset($getClientMetaData) ? (($getClientMetaData['mode']=='Live') ? "selected": ""): '')); ?>>Live</option>
                    </select>
                </div>
                <div class="row testAPI">
                    <label for="shippingTestKey">Test Username <span class="info-desc">(Your Sanbox Gigl delivery usernsme)</span><span class="errerTestKey"></span></label>
                    <input type="text" name="shipping_test_key" value="<?php echo (isset($gigl_delivery_api['test_key']) ? $gigl_delivery_api['test_key'] : ''); ?>" id="shippingTestKey">
                </div>
                <div class="row testAPI">
                    <label for="shippingTestSecret">Test Password <span class="info-desc">(Your Sanbox account password)</span><span class="errerTestSecret"></span></label>
                    <input type="password" name="shipping_test_secret" id="shippingTestSecret" value="<?php echo (isset($gigl_delivery_api['test_secret']) ? $gigl_delivery_api['test_secret'] : ''); ?>">
                </div>
                <div class="row liveAPI">
                    <label for="shippingLiveKey">Live Username <span class="info-desc">(Your Sanbox Gigl delivery usernsme)</span><span class="errerLiveKey"></span></label>
                    <input type="text" name="shipping_live_key" id="shippingLiveKey" value="<?php echo (isset($gigl_delivery_api['live_key']) ? $gigl_delivery_api['live_key'] : ''); ?>">
                </div>
                <div class="row liveAPI">
                    <label for="shippingLiveSecret">Live Password <span class="info-desc">(Your Sanbox account password)</span><span class="errerLiveSecret"></span></label>
                    <input type="password" name="shipping_live_secret" id="shippingLiveSecret" value="<?php echo (isset($gigl_delivery_api['live_secret']) ? $gigl_delivery_api['live_secret'] : ''); ?>">
                </div>
                
                <div class="row">
                    <label for="shippingPickupCountry">Kickup Country <span class="info-desc">(Gigl delivery/pickup is only available for Nigeria)</span><span class="errerPickupCountry"></span></label>
                    <select type="text" name="shipping_pickup_country" id="shippingPickupCountry"  required>
                        <option disabled selected>Select Country</option>
                        <option value="Nigeria" <?php echo ((isset($getClientMetaData) ? (($getClientMetaData['shipping_pickup_country']=='Nigeria') ? "selected": ""): '')); ?>>Nigeria</option>
                    </select>
                </div>
                <div class="row">
                    <label for="shippingPickupState">Pickup State <span class="info-desc">(Gigl delivery/pickup state)</span><span class="errerPickupState"></span></label>
                    <select name="shipping_pickup_state" id="shippingPickupState"  required>
                        <option disabled selected>Select State</option>
                        <option value="Abia" <?php echo ((isset($getClientMetaData) ? (($getClientMetaData['shipping_pickup_state']=='Abia') ? "selected": ""): '')); ?>>Abia</option>
                        <option value="Adamawa" <?php echo ((isset($getClientMetaData) ? (($getClientMetaData['shipping_pickup_state']=='Adamawa') ? "selected": ""): '')); ?>>Adamawa</option>
                        <option value="Akwa Ibom" <?php echo ((isset($getClientMetaData) ? (($getClientMetaData['shipping_pickup_state']=='Akwa Ibom') ? "selected": ""): '')); ?>>Akwa Ibom</option>
                        <option value="Anambra" <?php echo ((isset($getClientMetaData) ? (($getClientMetaData['shipping_pickup_state']=='Anambra') ? "selected": ""): '')); ?>>Anambra</option>
                        <option value="Bauchi" <?php echo ((isset($getClientMetaData) ? (($getClientMetaData['shipping_pickup_state']=='Bauchi') ? "selected": ""): '')); ?>>Bauchi</option>
                        <option value="Bayelsa" <?php echo ((isset($getClientMetaData) ? (($getClientMetaData['shipping_pickup_state']=='Bayelsa') ? "selected": ""): '')); ?>>Bayelsa</option>
                        <option value="Benue" <?php echo ((isset($getClientMetaData) ? (($getClientMetaData['shipping_pickup_state']=='Benue') ? "selected": ""): '')); ?>>Benue</option>
                        <option value="Borno" <?php echo ((isset($getClientMetaData) ? (($getClientMetaData['shipping_pickup_state']=='Borno') ? "selected": ""): '')); ?>>Borno</option>
                        <option value="Cross River" <?php echo ((isset($getClientMetaData) ? (($getClientMetaData['shipping_pickup_state']=='Cross River') ? "selected": ""): '')); ?>>Cross River</option>
                        <option value="Delta" <?php echo ((isset($getClientMetaData) ? (($getClientMetaData['shipping_pickup_state']=='Delta') ? "selected": ""): '')); ?>>Delta</option>
                        <option value="Ebonyi" <?php echo ((isset($getClientMetaData) ? (($getClientMetaData['shipping_pickup_state']=='Ebonyi') ? "selected": ""): '')); ?>>Ebonyi</option>
                        <option value="Edo" <?php echo ((isset($getClientMetaData) ? (($getClientMetaData['shipping_pickup_state']=='Edo') ? "selected": ""): '')); ?>>Edo</option>
                        <option value="Ekiti" <?php echo ((isset($getClientMetaData) ? (($getClientMetaData['shipping_pickup_state']=='Ekiti') ? "selected": ""): '')); ?>>Ekiti</option>
                        <option value="Enugu" <?php echo ((isset($getClientMetaData) ? (($getClientMetaData['shipping_pickup_state']=='Enugu') ? "selected": ""): '')); ?>>Enugu</option>
                        <option value="FCT" <?php echo ((isset($getClientMetaData) ? (($getClientMetaData['shipping_pickup_state']=='FCT') ? "selected": ""): '')); ?>>Federal Capital Territory</option>
                        <option value="Gombe" <?php echo ((isset($getClientMetaData) ? (($getClientMetaData['shipping_pickup_state']=='Gombe') ? "selected": ""): '')); ?>>Gombe</option>
                        <option value="Imo" <?php echo ((isset($getClientMetaData) ? (($getClientMetaData['shipping_pickup_state']=='Imo') ? "selected": ""): '')); ?>>Imo</option>
                        <option value="Jigawa" <?php echo ((isset($getClientMetaData) ? (($getClientMetaData['shipping_pickup_state']=='Jigawa') ? "selected": ""): '')); ?>>Jigawa</option>
                        <option value="Kaduna" <?php echo ((isset($getClientMetaData) ? (($getClientMetaData['shipping_pickup_state']=='Kaduna') ? "selected": ""): '')); ?>>Kaduna</option>
                        <option value="Kano" <?php echo ((isset($getClientMetaData) ? (($getClientMetaData['shipping_pickup_state']=='Kano') ? "selected": ""): '')); ?>>Kano</option>
                        <option value="Katsina" <?php echo ((isset($getClientMetaData) ? (($getClientMetaData['shipping_pickup_state']=='Katsina') ? "selected": ""): '')); ?>>Katsina</option>
                        <option value="Kebbi" <?php echo ((isset($getClientMetaData) ? (($getClientMetaData['shipping_pickup_state']=='Kebbi') ? "selected": ""): '')); ?>>Kebbi</option>
                        <option value="Kogi" <?php echo ((isset($getClientMetaData) ? (($getClientMetaData['shipping_pickup_state']=='Kogi') ? "selected": ""): '')); ?>>Kogi</option>
                        <option value="Kwara" <?php echo ((isset($getClientMetaData) ? (($getClientMetaData['shipping_pickup_state']=='Kwara') ? "selected": ""): '')); ?>>Kwara</option>
                        <option value="Lagos" <?php echo ((isset($getClientMetaData) ? (($getClientMetaData['shipping_pickup_state']=='Lagos') ? "selected": ""): '')); ?>>Lagos</option>
                        <option value="Nasarawa" <?php echo ((isset($getClientMetaData) ? (($getClientMetaData['shipping_pickup_state']=='Nasarawa') ? "selected": ""): '')); ?>>Nasarawa</option>
                        <option value="Niger" <?php echo ((isset($getClientMetaData) ? (($getClientMetaData['shipping_pickup_state']=='Niger') ? "selected": ""): '')); ?>>Niger</option>
                        <option value="Ogun" <?php echo ((isset($getClientMetaData) ? (($getClientMetaData['shipping_pickup_state']=='Ogun') ? "selected": ""): '')); ?>>Ogun</option>
                        <option value="Ondo" <?php echo ((isset($getClientMetaData) ? (($getClientMetaData['shipping_pickup_state']=='Ondo') ? "selected": ""): '')); ?>>Ondo</option>
                        <option value="Osun" <?php echo ((isset($getClientMetaData) ? (($getClientMetaData['shipping_pickup_state']=='Osun') ? "selected": ""): '')); ?>>Osun</option>
                        <option value="Oyo" <?php echo ((isset($getClientMetaData) ? (($getClientMetaData['shipping_pickup_state']=='Oyo') ? "selected": ""): '')); ?>>Oyo</option>
                        <option value="Plateau" <?php echo ((isset($getClientMetaData) ? (($getClientMetaData['shipping_pickup_state']=='Plateau') ? "selected": ""): '')); ?>>Plateau</option>
                        <option value="Rivers" <?php echo ((isset($getClientMetaData) ? (($getClientMetaData['shipping_pickup_state']=='Rivers') ? "selected": ""): '')); ?>>Rivers</option>
                        <option value="Sokoto" <?php echo ((isset($getClientMetaData) ? (($getClientMetaData['shipping_pickup_state']=='Sokoto') ? "selected": ""): '')); ?>>Sokoto</option>
                        <option value="Taraba" <?php echo ((isset($getClientMetaData) ? (($getClientMetaData['shipping_pickup_state']=='Taraba') ? "selected": ""): '')); ?>>Taraba</option>
                        <option value="Yobe" <?php echo ((isset($getClientMetaData) ? (($getClientMetaData['shipping_pickup_state']=='Yobe') ? "selected": ""): '')); ?>>Yobe</option>
                        <option value="Zamfara" <?php echo ((isset($getClientMetaData) ? (($getClientMetaData['shipping_pickup_state']=='Zamfara') ? "selected": ""): '')); ?>>Zamfara</option>
                    </select>
                </div>
                <div class="row">
                    <label for="shippingPickupCity">Pickup City <span class="info-desc">(The local area where the parcel will be picked up.)</span><span class="errerPickupCity"></span></label>
                    <input type="text" name="shipping_pickup_city" id="shippingPickupCity" value="<?php echo (isset($getClientMetaData) ? $getClientMetaData['shipping_pickup_city'] : ''); ?>"  required>
                </div>
                <div class="row">
                    <label for="shippingPickupPostcode">Pickup Postcode <span class="info-desc">(The local postcode where the parcel will be picked up.)</span><span class="errerPickupPostcode"></span></label>
                    <input type="text" name="shipping_pickup_postcode" id="shippingPickupPostcode" value="<?php echo (isset($getClientMetaData) ? $getClientMetaData['shipping_pickup_postcode'] : ''); ?>"  required>
                </div>
                <div class="row">
                    <label for="shippingPickupAddress">Pickup Address <span class="info-desc">(The street address where the parcel will be picked up.)</span><span class="errerPickupAddress"></span></label>
                    <textarea name="shipping_pickup_address" id="shippingPickupAddress"  required><?php echo (isset($getClientMetaData) ? $getClientMetaData['shipping_pickup_address'] : ''); ?></textarea>
                </div>
                <div class="row">
                    <label for="shippingSenderName">Sender Name <span class="errerSenderName"></span></label>
                    <input type="text" name="shipping_sender_name" id="shippingSenderName" value="<?php echo (isset($getClientMetaData) ? $getClientMetaData['shipping_sender_name'] : ''); ?>"  required>
                </div>
                <div class="row">
                    <label for="shippingSenderPhone">Sender Phone Number <span class="info-desc">(Used to coordinate pickup if the Gigl rider is outside attempting delivery. Must be a valid phone number)</span><span class="errerSenderPhone"></span></label>
                    <input type="tel" name="shipping_sender_phone" id="shippingSenderPhone" value="<?php echo (isset($getClientMetaData) ? $getClientMetaData['shipping_sender_phone'] : ''); ?>"  required>
                </div>
                <div class="row">
                    <button type="submit">Submit</button>
                </div>
            </form>
        </div>
    </article>
</section>
<script src="https://ajax.googleapis.com/ajax/libs/jquery/3.7.1/jquery.min.js"></script>
<script>
    jQuery('#account-setting').submit(function(e){
        //e.preventDefault();
         var shippingMode = jQuery('#shippingMode').val();
         var shippingPickupCity = jQuery('#shippingPickupCity').val();
         var shippingPickupPostcode = jQuery('#shippingPickupPostcode').val();
         var shippingPickupAddress = jQuery('#shippingPickupAddress').val();
         var shippingSenderName = jQuery('#shippingSenderName').val();
         var shippingSenderPhone = jQuery('#shippingSenderPhone').val();
         var shippingPickupCountry = jQuery('#shippingPickupCountry').val();
         var shippingPickupState = jQuery('#shippingPickupState').val();
         
        if(shippingMode=='live'){
            var shippingLiveKey = jQuery('#shippingLiveKey').val();
            var shippingLiveSecret = jQuery('#shippingLiveSecret').val();
            if(shippingLiveKey ==''){
                jQuery('.errerLiveKey').html('Field cannot be empty');
                settimeout(function(){
                    jQuery('.errerLiveKey').html('');
                },20000);
                return;
            }else if(shippingLiveSecret == ''){
                jQuery('.errerLiveSecret').html('Field cannot be empty');
                settimeout(function(){
                    jQuery('.errerLiveSecret').html('');
                },20000);
                return;
            }
            
        }else{
            var shippingTestKey = jQuery('#shippingTestKey').val();
            var shippingTestSecret = jQuery('#shippingTestSecret').val();
            if(shippingTestKey ==''){
                jQuery('.errerTestKey').html('Field cannot be empty');
                settimeout(function(){
                    jQuery('.errerTestSecret').html('');
                },20000);
                return;
            }else if(shippingTestSecret == ''){
                jQuery('.errerTestSecret').html('Field cannot be empty');
                settimeout(function(){
                    jQuery('.errerTestSecret').html('');
                return;
                },20000);
            }
            
        }
           
        if(shippingSenderPhone ==''){
             jQuery('.errerSenderPhone').html('<span></span><span>Contact number cannot be empty</span>');
             return;
        }else if(shippingSenderName == ''){
            jQuery('.errerSenderName').html('<span></span><span>Sender name cannot be empty</span>');
            return;
        }
        else if(shippingPickupAddress == ''){
            jQuery('.errerPickupAddress').html('<span></span><span>Pickup address cannot be empty</span>');
            return;
        }else if(shippingPickupPostcode == ''){
            jQuery('.errerPickupPostcode').html('<span></span><span>Pickup postcode cannot be empty</span>');
            return;
        }else if(shippingPickupCity == ''){
            jQuery('.errerPickupCity').html('<span></span><span>Pickup city cannot be empty</span>');
            return;
        }else if(shippingPickupCountry == ''){
             jQuery('.errerPickupCountry').html('<span></span><span>Pickup country cannot be empty</span>');
             return;
        }else if(shippingPickupState == ''){
             jQuery('.errerPickupState').html('<span></span><span>Pickup state cannot be empty</span>');
             return;
        }
    })
    var shippingMode = "<?php echo $getClientMetaData['shipping_mode']?>";
    if(shippingMode=='live'){
        jQuery('.liveAPI').css('display','block');
        jQuery('.testAPI').css('display','none');
    }else{
        jQuery('.testAPI').css('display','block');
        jQuery('.liveAPI').css('display','none');
    }
    jQuery('#shippingMode').change(function(){
        var shippingMode = jQuery('#shippingMode').find(":selected").val();
        if(shippingMode=='live'){
            jQuery('.liveAPI').css('display','block');
            jQuery('.testAPI').css('display','none');
        }else{
            jQuery('.testAPI').css('display','block');
            jQuery('.liveAPI').css('display','none');
        }
    })
</script>