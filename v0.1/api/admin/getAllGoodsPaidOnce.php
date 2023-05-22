<?php

require_once( '../../assets/initializer.php' );

$Product = new Product( $db );

#  Check for rge requests method
if ( $_SERVER[ 'REQUEST_METHOD' ] !== 'POST' ) {
    header( 'HTTP/1.1 405 Method Not Allowed' );
    header( 'Allow: POST' );
    exit();
}
#Your method should be here
$getAllPurchasedItemsPaidOnce = $Product->getAllPurchasedItemsPaidOnce();
if ( $getAllPurchasedItemsPaidOnce ) {
    http_response_code(200);
    $Product->outputData( true, 'Fetched products', $getAllPurchasedItemsPaidOnce );
    exit;
} else {

    $Product->outputData( false,  $_SESSION[ 'err' ], null );
}

unset( $Product );
unset( $db );

