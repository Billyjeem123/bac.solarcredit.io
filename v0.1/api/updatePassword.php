<?php
require_once( '../assets/initializer.php' );
$data = ( array ) json_decode( file_get_contents( 'php://input' ), true );

$user = new Users( $db );

$validKeys = [ 'apptoken', 'usertoken', 'fpword', 'npword' ];
$errors = [];

#   Check if only valid input fields are provided
$invalidKeys = array_diff( array_keys( $data ), $validKeys );
if ( !empty( $invalidKeys ) ) {
    foreach ( $invalidKeys as $key ) {
        $errors[] = "$key is not a valid input field";
    }
    if ( !empty( $errors ) ) {
        $this->respondUnprocessableEntity( $errors );
        return;
    }
}

#   Check if required fields are empty
foreach ( $validKeys as $key ) {
    if ( empty( $data[ $key ] ) ) {
        $errors[] = ( $key ) . ' is required';
    }else{
        $data[$key] = $user->sanitizeInput($data[$key]); # Sanitize input
    }
}

if ( !empty( $errors ) ) {
    $user->respondUnprocessableEntity( $errors );
    return;
}
$updatePassword = $user->updatePassword( $data );
unset($user);
unset($db);

