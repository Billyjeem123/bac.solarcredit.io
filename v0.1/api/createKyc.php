<?php
require_once( '../assets/initializer.php' );
$data = ( array ) json_decode( file_get_contents( 'php://input' ), true );

$Kyc = new Kyc( $db );

#  Check for rge requests method
if ( $_SERVER[ 'REQUEST_METHOD' ] !== 'POST' ) {
    header( 'HTTP/1.1 405 Method Not Allowed' );
    header( 'Allow: POST' );
    exit();
}

#  Check for params  if matches required parametes
$validKeys = [ 'usertoken', 'fname', 'occupation', 'identity', 'identity_num', 'apptoken', 'address', 'image'];
$invalidKeys = array_diff( array_keys( $data ), $validKeys );
if ( !empty( $invalidKeys ) ) {
    foreach ( $invalidKeys as $key ) {
        $errors[] = "$key is not a valid input field";
    }

    if ( !empty( $errors ) ) {

        $Kyc->respondUnprocessableEntity( $errors );
        return;
    }

}

#  Check for fields  if empty
foreach ( $validKeys as $key ) {
    if ( empty( $data[ $key ] ) ) {
        $errors[] = ucfirst( $key ) . ' is required';
    }
    if ( !empty( $errors ) ) {

        $Kyc->respondUnprocessableEntity( $errors );
        return;
    } else {
        $data[ $key ] = $Kyc->sanitizeInput( $data[ $key ] );
        # Sanitize input
    }
}
#Your method should be here
$Kyc->registerUserKyc($data);
unset( $Kyc );
unset( $db );

