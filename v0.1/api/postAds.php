<?php
require_once( '../assets/initializer.php' );
$data = ( array ) json_decode( file_get_contents( 'php://input' ), true );

$product = new Product( $db );

#  Check for rge requests method
if ( $_SERVER[ 'REQUEST_METHOD' ] !== 'POST' ) {
    header( 'HTTP/1.1 405 Method Not Allowed' );
    header( 'Allow: POST' );
    exit();
}

$requiredKeys = ['catid', 'location', 'pname', 'apptoken', 'pdesc', 'brand', 'condition', 'price', 'pquantity', 'pimage', 'usertoken', 'volt', 'unit', 'size'];
$optionalKeys = ['phone'];
$validKeys = array_merge($requiredKeys, $optionalKeys);
$invalidKeys = array_diff(array_keys($data), $validKeys);
if (!empty($invalidKeys)) {
    foreach ($invalidKeys as $key) {
        if (!in_array($key, $optionalKeys)) {
            $errors[] = "$key is not a valid input field";
        }
    }
    if (!empty($errors)) {
        $product->respondUnprocessableEntity($errors);
        return;
    }
}

# Check for fields if empty
foreach ($requiredKeys as $key) {
    if (empty($data[$key])) {
        $errors[] = ucfirst($key) . ' is required';
    } else {
        $data[$key] = $product->sanitizeInput($data[$key]);
        # Sanitize input
    }
}

# Check for optional fields
foreach ($optionalKeys as $key) {
    if (!isset($data[$key])) {
        $data[$key] = ''; // Set empty string if the optional field is not present
    } else {
        $data[$key] = $product->sanitizeInput($data[$key]);
        # Sanitize input
    }
}

if (!empty($errors)) {
    $product->respondUnprocessableEntity($errors);
    return;
}
#Your method should be here
$product->uploadSellerProducts($data);
unset( $product );
unset( $db );

