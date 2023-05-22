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


       $token  =  (int)$payment->token();

       # Prepare to check for Installmentally-Plan value
       $checkSubscribedPlan = $payment->checkSubscribedPlan( intval( $data[ 'package' ] ) );

       if ( !$checkSubscribedPlan ) {
           $payment->outputData( false, $_SESSION[ 'err' ], null );
           exit;
       }

       #calculateAmountRemaining ::Check  and calculate  half of the payment.
       $calculateAmountRemaining = $payment->calculateAmountRemaining($data[ 'Totalprice' ]);
       if($data['amountPaid'] <  $calculateAmountRemaining){
          $payment->outputData(false, "Unable to process transaction.You must pay at least {$payment->formatCurrency($calculateAmountRemaining)} to process transaction", null);
        exit;
       }

        # Debit user wallet.
     if(!$payment->updateUserAccountBalance($data[ 'amountPaid' ],  $data[ 'usertoken' ], $_ENV['transactionType'] = "debit")){
        $payment->outputData(false, $_SESSION['err'], null);
        exit;

     }

      # Record Ongoing Transaction
     if(!$payment->recordTransaction($token, $data[ 'usertoken' ], $payment->formatCurrency($data[ 'amountPaid' ]), $_ENV['creditOrDebit'] = "Debit")){
        $payment->outputData(false, $_SESSION['err'], null);
        exit;
     }

     # Save usertoken of buyer during purchase.
     if(!$payment->saveUserTokenDuringPurchase($token,  $data[ 'usertoken' ], $_ENV['PAYMENT_OPTION'] = "Paid-Installmentally")){
       $payment->outputData(false, $_SESSION['err'], null);
       exit;

    }

    $calculateMonthlyRepayment = $payment->calculateMonthlyRepayment($calculateAmountRemaining, $checkSubscribedPlan['planid']);

    $calculateDateAhead = $payment->calculateDateAhead(+$checkSubscribedPlan['planid'] . '' . "month");


     #This method stores info about the product loan.
    $saveAllProductBoughtInstallmentally = $payment->saveAllProductBoughtInstallmentally(
        $data['Totalprice'],
        $data['amountPaid'],  
        $data['amountPaid'], 
        $checkSubscribedPlan['planid'],
        $calculateMonthlyRepayment,
        $token,
        $calculateDateAhead,
        $data['usertoken']

    );

     if (!$saveAllProductBoughtInstallmentally) {
         $payment->outputData(false, $_SESSION['err'], null);
         exit;
     }

     #calculateMonthsAheadAndSave:: Calulation month duration
    $calculateMonthsAheadAndSave = $payment->calculateMonthsAheadAndSave(
        $token,
        $data['usertoken'],
        $checkSubscribedPlan['planid'],
        $calculateMonthlyRepayment

    );
    if (!$calculateMonthsAheadAndSave) {
        $payment->outputData(false, $_SESSION['err'], null);
        exit;
    }

     $mailer = new Mailer();
     $count = 0;
     
     foreach ($data['productBought'] as $key => $allProducts) {

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

            $saveProductInstallments = $payment->saveProductInstallments(
                $token,
                $allProducts['productToken'],
                $allProducts['productQuantity'],
                $allProducts['productname'],
                $allProducts['productPrice'],
                $allProducts['productType'],  
                $_ENV['MODE_OF_PAYMENT'] = "Account-Wallet",
                $allProducts['productimage']

            );
    
            if (!$saveProductInstallments) {
                $payment->outputData(false, $_SESSION['err'], null);
                exit;
            }
     
             $count++;
         }else{
            $count = 0;
            
            $saveProductInstallments = $payment->saveProductInstallments(
                $token,
                $allProducts['productToken'],
                $allProducts['productQuantity'],
                $allProducts['productname'],
                $allProducts['productPrice'],
                $allProducts['productType'],  
               $_ENV['MODE_OF_PAYMENT'] = "Account-Wallet",
                $allProducts['productimage']

            );
    
            if (!$saveProductInstallments) {
                $payment->outputData(false, $_SESSION['err'], null);
                exit;
            }
            $count++;

         }
         $payment->removeProductFromCart($data['usertoken'],  $allProducts['productToken']);
         
     }

     #Send Notofication to admin of new purchase alert.
     
     try {
          $mailer->confirmPaymentInstallmentallyPurchaseToAdmin($data['productBought'], $payment->formatCurrency($data[ 'Totalprice' ]), $data['amountPaid']);
  
      } catch (Exception $e) {
          # Handle the error or log it as needed
          $errorMessage = date('[Y-m-d H:i:s] ') ."Error sending mail  for " . __METHOD__ . "  " . PHP_EOL . $e->getMessage();
          error_log($errorMessage, 3, 'mail.log');
      }
     
    $amountPaid =  $payment->formatCurrency($data['amountPaid']);
     
     $message = "Dear valued user, we are pleased to inform you that your wallet has been debited with an amount of $amountPaid naira ";

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
     unset($mailer);

