<?php
require_once( '../../assets/initializer.php' );
$data = ( array ) json_decode( file_get_contents( 'php://input' ), true );

$product = new Product( $db );

if ( $_SERVER[ 'REQUEST_METHOD' ] !== 'POST' ) {
    header( 'HTTP/1.1 405 Method Not Allowed' );
    header( 'Allow: POST' );
    exit();
}

$personalisedDashboard = $product->personalisedDashboard();
if ( $personalisedDashboard ) {
    $product->outputData( true, "Fetceed Admin dashboard", $personalisedDashboard );
} else {
    $product->outputData( false, $_SESSION[ 'err' ],  null );

}
unset( $product );
unset( $db );

