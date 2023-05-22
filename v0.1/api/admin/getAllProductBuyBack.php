<?php
require_once( '../../assets/initializer.php' );

$product = new Product( $db );

#  Check for rge requests method
if ( $_SERVER[ 'REQUEST_METHOD' ] !== 'POST' ) {
    header( 'HTTP/1.1 405 Method Not Allowed' );
    header( 'Allow: POST' );
    exit();
}

 
#Your method should be here
$getAllBuyBackProduct = $product->getAllBuyBackProduct();
if ( $getAllBuyBackProduct ) {
    http_response_code(200);
    $product->outputData( true, 'Fetched Prdocuts', $getAllBuyBackProduct );
} else {

    $product->outputData( false,  $_SESSION[ 'err' ], null );
}

unset( $category );
unset( $db );

