<?php
    require_once( '../../assets/initializer.php' );
    $data = ( array ) json_decode( file_get_contents( 'php://input' ), true );

    $payment = new Payment( $db );

        #  Check for rge requests method
        if ( $_SERVER[ 'REQUEST_METHOD' ] !== 'POST' ) {
            header( 'HTTP/1.1 405 Method Not Allowed' );
            header( 'Allow: POST' );
            exit();
        }

        $auth = new Auth( $db );

        if ( !$auth->authenticateUser( $data[ 'usertoken' ] ) ) {
            $auth->outputData( false, $_SESSION[ 'err' ], null );
            unset( $auth );
            exit;
        }

        $getAccountBalance = $payment->getAccountBalance($data['usertoken']);
        if($getAccountBalance['totalBalannce'] < $data['Totalprice']){
            $payment->outputData(false, 'Insufficient funds. Kindly fund your wallet', null);
			exit;

        }


      $token  = $payment->token();

      # Record Ongoing Transaction
     if(!$payment->recordTransaction($token, $data[ 'usertoken' ], $payment->formatCurrency($data[ 'Totalprice' ]), $_ENV['creditOrDebit'] = "Debit")){
        $payment->outputData(false, $_SESSION['err'], null);
        exit;
     }

     #Debit user wallet.
     if(!$payment->updateUserAccountBalance($data[ 'Totalprice' ],  $data[ 'usertoken' ], $_ENV['transactionType'] = "debit")){
        $payment->outputData(false, $_SESSION['err'], null);
        exit;

     }

      # Save usertoken of buyer during purchase.
      if(!$payment->saveUserTokenDuringPurchase($token,  $data[ 'usertoken' ], $_ENV['PAYMENT_OPTION'] = "Paid-Once")){
        $payment->outputData(false, $_SESSION['err'], null);
        exit;

     }
     $mailer = new Mailer();
     $count = 0;
     
     foreach ($data['products'] as $key => $allProducts) {

         if ($allProducts['productType'] === "Userproduct") {

            # To future dev reading this code, In the later on,  
            # we might need to alert product owners for the code below, but right now i think we shouldn't.
            # Just in case the project ownsers asked for this,worry no more. just uncomment this code, if need be.
            # It should definely do the work for you.
            # I hope i helped you . 

             $ownerRecord = $payment->getUserdata($allProducts['ownertoken']);
            
            try {
                $mailer->notifyOwnersOfSale($ownerRecord['mail'], $ownerRecord['fname'], $allProducts['productname']);
        
            } catch (Exception $e) {
                # Handle the error or log it as needed
                $errorMessage = date('[Y-m-d H:i:s] ') ."Error sending mail  for " . __METHOD__ . "  " . PHP_EOL . $e->getMessage();
                error_log($errorMessage, 3, 'mail.log');
            }

     
             $saveProductBoughtTransaction = $payment->saveProductBoughtTransaction(
                 $token, 
                 intval($allProducts['productToken']),
                 intval($allProducts['productQuantity']),   
                 $allProducts['productPrice'],  
                 $allProducts['productType'],  
                 $_ENV['MODE_OF_PAYMENT'] = "Account-Wallet",
                 $allProducts['productname'],
                 $allProducts['productimage'],
             );
     
             if (!$saveProductBoughtTransaction) {
                 $payment->outputData(false, $_SESSION['err'], null);
                 exit;
             }
     
             $count++;
         }else{
            $count = 0;
            $saveProductBoughtTransaction = $payment->saveProductBoughtTransaction(
                $token, 
                intval($allProducts['productToken']),
                intval($allProducts['productQuantity']),   
                $allProducts['productPrice'],  
                $allProducts['productType'],  
                $_ENV['MODE_OF_PAYMENT'] = "Account-Wallet",
                $allProducts['productname'],
                $allProducts['productimage'],
            );

            if (!$saveProductBoughtTransaction) {
                $payment->outputData(false, $_SESSION['err'], null);
                exit;
            }

            $count++;

         }
         $payment->removeProductFromCart($data['usertoken'],  $allProducts['productToken']);
         
     }

         #Send Notofication to admin of new purchase alert.
        try {
            $mailer->confirmFullPaymentAndProductPurchaseToAdmin($data['products'], $payment->formatCurrency($data[ 'Totalprice' ]));

        } catch (Exception $e) {
            # Handle the error or log it as needed
            $errorMessage = date('[Y-m-d H:i:s] ') ."Error sending mail  for " . __METHOD__ . "  " . PHP_EOL . $e->getMessage();
            error_log($errorMessage, 3, 'mail.log');
        }
     
     $message = "Dear valued user, we are pleased to inform you that your wallet has been debited with an amount of $data[Totalprice]";

     $notification = new Notification($db);
     $isNotificationSent = $notification->notifyUser($message, $data['usertoken']);
     
     if($isNotificationSent){
         unset($notification);
    #        Notify the user of a successful transaction
         http_response_code(200);
         $payment->outputData(true, 'Transaction successful', null);
         exit;
      }else{
         #  Notify the user of a transaction failure and provide error details
         $payment->outputData(false, 'Transaction failed', $_SESSION['err']);
      }
     
    #   Release the resources used by the Payment instance
     unset($payment);
     

