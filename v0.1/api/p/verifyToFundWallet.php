<?php
    require_once( '../../assets/initializer.php' );
    $data = ( array ) json_decode( file_get_contents( 'php://input' ), true );

    $payment = new Payment( $db );

    if ( empty( $data[ 'reference' ] ) ) {
        $payment->outputData( false, 'Reference is missing', null );
        exit;
    }
    $verifyWalletCurl = $payment->processWallet( $data[ 'reference' ] );
    $decodeWalletCurl = json_decode( $verifyWalletCurl, true );

    if ( $decodeWalletCurl[ 'status' ] !== true || $decodeWalletCurl[ 'message' ] !== 'Verification successful' ) {
        $payment->outputData( false, 'Cannot process transaction', null );
        exit;
    }

    $auth = new Auth( $db );

    if ( !$auth->authenticateUser( $data[ 'usertoken' ] ) ) {
        $auth->outputData( false, $_SESSION[ 'err' ], null );
        unset( $auth );
        exit;
    }

    $chargedAmt = $decodeWalletCurl['data'][ 'amount' ];
    $amountTopay = $data['amountToPay' ];
    $transactionToken = $payment->token();

     if($chargedAmt < $amountTopay){
        $payment->outputData(false, 'Transaction is deemed fraudlent', null);
        exit;
     }

     if(!$payment->recordTransaction($data[ 'reference' ], $data[ 'usertoken' ], $data[ 'amount' ], $_ENV['creditOrDebit'] = "Credit")){
        $payment->outputData(false, $_SESSION['err'], null);
        exit;
     }

     if(!$payment->updateUserAccountBalance($data[ 'amount' ],  $data[ 'usertoken' ], $_ENV['transactionType'] = "credit")){
        $payment->outputData(false, $_SESSION['err'], null);
        exit;

     }

     $notification = new Notification($db);
     $chargedAmtThousand =  $payment->formatCurrency($data[ 'amount' ]);
     $message = "Dear valued user, we are pleased to inform you that your wallet has been credited with an amount of $chargedAmtThousand naira";
     $isNotificationSent = $notification->notifyUser($message, $data['usertoken']);
     
     if($isNotificationSent){
         // Release the resources used by the Notification instance
         unset($notification);
         // Notify the user of a successful transaction
         $payment->outputData(true, 'Transaction successful', null);
         exit;
     }else{
         // Notify the user of a transaction failure and provide error details
         $payment->outputData(false, 'Transaction failed', $_SESSION['err']);
     }
     
     // Release the resources used by the Payment instance
     unset($payment);
     

