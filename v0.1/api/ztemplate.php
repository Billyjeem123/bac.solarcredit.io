<?php
require_once( '../assets/initializer.php' );
$data = ( array ) json_decode( file_get_contents( 'php://input' ), true );

$user = new Users( $db );

    #  Check for rge requests method
    if ( $_SERVER[ 'REQUEST_METHOD' ] !== 'POST' ) {
        header( 'HTTP/1.1 405 Method Not Allowed' );
        header( 'Allow: POST' );
        exit();
    }

    #  Check for params  if matches required parametes
    $validKeys = [ 'name', 'mail', 'phone', 'address', 'pword', 'apptoken' ];
    $invalidKeys = array_diff( array_keys( $data ), $validKeys );
    if ( !empty( $invalidKeys ) ) {
        foreach ( $invalidKeys as $key ) {
            $errors[] = "$key is not a valid input field";
        }

        if ( !empty( $errors ) ) {

            $user->respondUnprocessableEntity( $errors );
            return;
        }

    }

    #  Check for fields  if empty
    foreach ( $validKeys as $key ) {
        if ( empty( $data[ $key ] ) ) {
            $errors[] = ucfirst( $key ) . ' is required';
        }
        if ( !empty( $errors ) ) {

            $user->respondUnprocessableEntity( $errors );
            return;
        } else {
            $data[ $key ] = $user->sanitizeInput( $data[ $key ] );
            # Sanitize input
        }
    }
#Your method should be here
unset( $user );
unset( $db );

