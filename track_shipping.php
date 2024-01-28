<?php

// Verify the webhook request is authentic (using HMAC validation)
$shopifySecret = 'your-shopify-app-secret';
$webhookData = file_get_contents('php://input');
$webhookHmac = $_SERVER['HTTP_X_SHOPIFY_HMAC_SHA256'];

$calculatedHmac = base64_encode(hash_hmac('sha256', $webhookData, $shopifySecret, true));
$logFile = fopen('newthankyou.log', 'a');
			// Check if the log file was opened successfully
			if ($logFile) {
				// Use print_r to display the array contents
				ob_start(); // Start output buffering
				print_r($calculatedHmac); // Print the array contents
				$output = ob_get_clean(); // Capture the printed output
				fwrite($logFile, $output); // Write the output to the log file
				fclose($logFile); // Close the log file

				// Now the array contents are saved in the 'debug.log' file
			}
if ($calculatedHmac === $webhookHmac) {
    $webhookData = json_decode($webhookData, true);
    $logFile = fopen('newthankyoujson.log', 'a');
    // Check if the log file was opened successfully
    if ($logFile) {
        // Use print_r to display the array contents
        ob_start(); // Start output buffering
        print_r($webhookData); // Print the array contents
        $output = ob_get_clean(); // Capture the printed output
        fwrite($logFile, $output); // Write the output to the log file
        fclose($logFile); // Close the log file

        // Now the array contents are saved in the 'debug.log' file
    }
    // Check if the webhook is for the thank_you event
    //if ($webhookData['event'] === 'checkout/thank_you') {
        // Add your custom content here
        $customContent = '<a href="https://example.com" class="btn btn-primary">Custom Button</a>';

        // Modify the content of the thank-you page
        $webhookData['data']['content']['text'] .= $customContent;

        // Respond to the webhook
        header('HTTP/1.1 200 OK');
        echo json_encode(['success' => true]);
        exit;
    //}
}

// Respond to unauthorized requests
header('HTTP/1.1 401 Unauthorized');
echo json_encode(['error' => 'Unauthorized']);
exit;