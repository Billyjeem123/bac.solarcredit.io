<?php
require_once( '../assets/initializer.php' );
$data = ( array ) json_decode( file_get_contents( 'php://input' ), true );

$Loan = new Loan( $db );

#  Check for rge requests method
if ( $_SERVER[ 'REQUEST_METHOD' ] !== 'POST' ) {
    header( 'HTTP/1.1 405 Method Not Allowed' );
    header( 'Allow: POST' );
    exit();
}

#  Check for params  if matches required parametes
$validKeys = [ 'plan', 'fullname', 'amountToBorrow',
'usertoken', 'identity_number', 'means_of_identity',
'occupation', 'passport', 'purpose_of_loan', 'guarantor',
'collecteral', 'apptoken' ];
$invalidKeys = array_diff( array_keys( $data ), $validKeys );
if ( !empty( $invalidKeys ) ) {
    foreach ( $invalidKeys as $key ) {
        $errors[] = "$key is not a valid input field";
    }

    if ( !empty( $errors ) ) {

        $Loan->respondUnprocessableEntity( $errors );
        return;
    }

}

#  Check for fields  if empty
foreach ( $validKeys as $key ) {
    if ( empty( $data[ $key ] ) ) {
        $errors[] = ucfirst( $key ) . ' is required';
    }
    if ( !empty( $errors ) ) {

        $Loan->respondUnprocessableEntity( $errors );
        return;
    } else {
        if ( $key !== 'guarantor' && $key !== 'collecteral' ) {
            # Do not santize the granrantor and Colleterl Mainly because it is an array.
            $data[ $key ] = $Loan->sanitizeInput( $data[ $key ] );
        }
    }

}
#Your method should be here
$Loan->initiateLoanRequest( $data );
unset( $Loan );
unset( $db );

