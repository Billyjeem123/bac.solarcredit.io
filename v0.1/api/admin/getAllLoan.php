<?php

require_once( '../../assets/initializer.php' );

$Loan = new Loan( $db );

#  Check for rge requests method
if ( $_SERVER[ 'REQUEST_METHOD' ] !== 'POST' ) {
    header( 'HTTP/1.1 405 Method Not Allowed' );
    header( 'Allow: POST' );
    exit();
}
#Your method should be here
$getAllLoanRecord = $Loan->getAllLoanRecord();
if ( $getAllLoanRecord ) {
   echo $Loan->outputData( true, 'Fetched Loan', $getAllLoanRecord );
} else {

    $Loan->outputData( false,  $_SESSION[ 'err' ], null );
}

unset( $Loan );
unset( $db );

