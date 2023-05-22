<?php
require_once( '../../assets/initializer.php' );
$data = ( array ) json_decode( file_get_contents( 'php://input' ), true );

$user = new Users( $db );

if ( $_SERVER[ 'REQUEST_METHOD' ] !== 'POST' ) {
    header( 'HTTP/1.1 405 Method Not Allowed' );
    header( 'Allow: POST' );
    exit();
}

#  Check for params  if matches required parametes
$validKeys = [ 'apptoken', 'usertoken'];
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
$getUserdata = $user->getUserdata($data['usertoken']);
$getKYCData = $user->getKYCData($data['usertoken']);
$getAllHistoryLogs = $user->getAllHistoryLogs($data['usertoken']);

if ( $getUserdata !== false ) {

    $userBioData = [
        'success' => true,
        'message' => 'Fetch user data',
        'userData' => $getUserdata,
        'kycData' => $getKYCData,
        'getUserHistoryLogs' => $getAllHistoryLogs
    ];

    $ecodeUserBioData = json_encode($userBioData);
    echo $ecodeUserBioData;

} else {
    $user->outputData( false, $_SESSION[ 'err' ],  null );

}
unset( $user );
unset( $db );

