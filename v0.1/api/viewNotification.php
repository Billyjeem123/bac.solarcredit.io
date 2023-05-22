<?php
require_once( '../assets/initializer.php' );
$data = ( array ) json_decode( file_get_contents( 'php://input' ), true );

$notify = new Notification( $db );

#  Check for rge requests method
if ( $_SERVER[ 'REQUEST_METHOD' ] !== 'POST' ) {
    header( 'HTTP/1.1 405 Method Not Allowed' );
    header( 'Allow: POST' );
    exit();
}

#  Check for params  if matches required parametes
$validKeys = [ 'usertoken', 'notifyToken', 'apptoken'];
$invalidKeys = array_diff( array_keys( $data ), $validKeys );
if ( !empty( $invalidKeys ) ) {
    foreach ( $invalidKeys as $key ) {
        $errors[] = "$key is not a valid input field";
    }

    if ( !empty( $errors ) ) {

        $notify->respondUnprocessableEntity( $errors );
        return;
    }

}

#  Check for fields  if empty
foreach ( $validKeys as $key ) {
    if ( empty( $data[ $key ] ) ) {
        $errors[] = ucfirst( $key ) . ' is required';
    }
    if ( !empty( $errors ) ) {

        $notify->respondUnprocessableEntity( $errors );
        return;
    } else {
        $data[ $key ] = $notify->sanitizeInput( $data[ $key ] );
        # Sanitize input
    }
}
#Your method should be here
$viewNotification = $notify->viewNotification($data['notifyToken']);

if ( $viewNotification ) {
    $notify->outputData( true, 'Fetched notification', $viewNotification);
    exit;
} else {
    $notify->outputData( false, $_SESSION[ 'mark' ],  null );

}
unset( $notify );
unset( $db );

