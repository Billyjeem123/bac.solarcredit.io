<?php
require_once( '../../assets/initializer.php' );
$data = ( array ) json_decode( file_get_contents( 'php://input' ), true );

$WithdrawalRequest = new WithdrawalRequest( $db );

if ( $_SERVER[ 'REQUEST_METHOD' ] !== 'POST' ) {
    header( 'HTTP/1.1 405 Method Not Allowed' );
    header( 'Allow: POST' );
    exit();
}


$getAllWithdrawalRequests = $WithdrawalRequest->getAllWithdrawalRequests();
if ( $getAllWithdrawalRequests ) {
    $WithdrawalRequest->outputData( true, "Fetched All withdrawals", $getAllWithdrawalRequests );
} else {
    $WithdrawalRequest->outputData( false, $_SESSION[ 'err' ],  null );

}
unset( $WithdrawalRequest );
unset( $db );

