<?php
require_once( '../assets/initializer.php' );

$Product = new Product( $db );

# Check for the request method
if ( $_SERVER[ 'REQUEST_METHOD' ] !== 'POST' ) {
    header( 'HTTP/1.1 405 Method Not Allowed' );
    header( 'Allow: POST' );
    exit();
}

# Check for required parameters
$validKeys = [ 'image' ];
$invalidKeys = array_diff( array_keys( $_FILES ), $validKeys );
if ( !empty( $invalidKeys ) ) {
    foreach ( $invalidKeys as $key ) {
        $errors[] = "$key is not a valid input field";
    }

    if ( !empty( $errors ) ) {
        $Product->respondUnprocessableEntity( $errors );
        return;
    }
}

# Check for required fields
if ( empty( $_FILES['image']['name'] ) ) {
    $errors[] = 'image is required';
}
if ( !empty( $errors ) ) {
    $Product->respondUnprocessableEntity( $errors );
    return;
}

# Your method should be here
$uploadImageToServer = $Product->uploadImageToServer( $_FILES[ 'image' ] );
if ( $uploadImageToServer ) {
    $Product->outputData( true, 'Fetched image', $uploadImageToServer );
} else {
    $Product->outputData( false, $_SESSION[ 'err' ], null );
}
unset( $Product );
unset( $db );
