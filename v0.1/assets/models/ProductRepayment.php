<?php

class ProductRepayment extends AbstractClasses
{

    private   $conn;

    public function __construct(Database $database)
    {
        $this->conn = $database->connect();
    }

    public function checkIfTaskIsAvailable()
    {
        $dataArray = array();
        $currentDate = $this->currentDate();
        try {
            $sql = "SELECT *  FROM loan_product_purchases  WHERE dueDate = :currentDate
            AND payyment_status = 0  LIMIT 10";
            $stmt = $this->conn->prepare($sql);
            $stmt->bindParam(':currentDate', $currentDate);
            $stmt->execute();
            $rows = $stmt->rowCount();
            if ($rows === 0) {
                return null;
            } else {
                $currentDate = $stmt->fetchAll(PDO::FETCH_ASSOC);
                foreach ($currentDate as $value) {

                    $array = [
                        'product_loan_token' => $value['token'],
                        'usertoken' => $value['usertoken'],
                        'monthExpectedToPay' => $value['dueDate'],
                        'amountExpectedToPay' => $value['priceToPay'],
                        'payyment_status' => $value['payyment_status'],
                        'remind_a_week_before_status' => $value['remind_a_week_before_status'],
                        'remind_a_day_before_status' => $value['remind_a_day_before_status']
                    ];

                    array_push($dataArray, $array);
                }
            }
        } catch (PDOException $e) {
            echo 'Error: ' . $e->getMessage();
        } finally {
            $stmt = null;
        }
        return $dataArray;
    }


    public function handleUserRepaymentSchedule()
    {
           $checkIfTaskIsAvailable = $this->checkIfTaskIsAvailable();
           if ( !$checkIfTaskIsAvailable ) {
               return null;
           }
   
           foreach ( $checkIfTaskIsAvailable as $taskAvailable ) {
               # do something with $taskAvailable
               $checkAccountBalance = $this->getAccountBalance( $taskAvailable[ 'usertoken' ] );
               $AccountBalance = $checkAccountBalance[ 'totalBalannce' ];
               $getDebtorsBioData = $this->getUserdata( $taskAvailable[ 'usertoken' ] );
               $mailer = new Mailer();
   
               if ( $AccountBalance === 0.00 or $AccountBalance < $taskAvailable[ 'amountExpectedToPay' ] ) {
   
                   $this->markInstallmentAsUnPaid( $taskAvailable[ 'usertoken' ], $taskAvailable[ 'monthExpectedToPay' ] );
   
                   try {
                       $mailer->notifyUserOfUnPaidOutstanding($getDebtorsBioData[ 'mail' ], $getDebtorsBioData[ 'fname' ]);
                   } catch ( Exception $e ) {
                       # Handle the error or log it as needed
                       $errorMessage = date( '[Y-m-d H:i:s] ' ) . 'Error sending mail loan for ' . __METHOD__ . '  ' . PHP_EOL . $e->getMessage();
                       error_log( $errorMessage, 3, 'crone.log' );
                   }
               } else {
                   # updateUserAccountBalance::Debit User Accordingly
                   $this->updateUserAccountBalance(
                       $taskAvailable[ 'amountExpectedToPay'],
                       $getDebtorsBioData[ 'usertoken' ],
                       $_ENV[ 'transactionType' ] = 'debit'
                   );
   
                   $this->updateInstallmentSatatus( $taskAvailable[ 'usertoken' ], $taskAvailable[ 'monthExpectedToPay' ] );
                   $this->updateInstallmentAmount( $taskAvailable[ 'amountExpectedToPay' ], $taskAvailable[ 'product_loan_token' ] );
   
                   try {
   
                       $mailer->notifyUserForDeductingLoanAmountFromWallet(
                           $getDebtorsBioData[ 'mail' ],
                           $getDebtorsBioData[ 'fname' ],
                           $taskAvailable[ 'amountExpectedToPay' ],
                           $taskAvailable[ 'monthExpectedToPay' ]
                       );
                   } catch ( Exception $e ) {
                       # Handle the error or log it as needed
                       $errorMessage = date( '[Y-m-d H:i:s] ' ) . 'Error sending mail loan for ' . __METHOD__ . '  ' . PHP_EOL . $e->getMessage();
                       error_log( $errorMessage, 3, 'crone.log' );
                   }
   
                   $this->recordTransaction(
                       $taskAvailable[ 'product_loan_token' ],
                       $taskAvailable[ 'usertoken' ],
                       $taskAvailable[ 'amountExpectedToPay' ],
                       $_ENV[ 'creditOrDebit' ] = 'Debit'
                   );
   
                   $message = 'Dear valued user, a deduction of ' . $this->formatCurrency( $taskAvailable[ 'amountExpectedToPay' ] ) . ' naira  has been made from your wallet to cover the loan servicing charges.';
                   $this->notifyUserMessage( $message, $taskAvailable[ 'usertoken' ] );
               }
           }
           unset( $mailer );
           # Free mail  resources
           return true;
       }


           # markInstallmentAsUnPaid: This method marks an Repayment field as Unpaid

    private function markInstallmentAsUnPaid( $usertoken, $currentDate )
    {
           try {
               $sql = 'UPDATE loan_product_purchases SET payyment_status = 2 WHERE
            usertoken = :usertoken AND dueDate = :currentDate';
               $stmt = $this->conn->prepare( $sql );
               $stmt->bindParam( ':usertoken', $usertoken );
               $stmt->bindParam( ':currentDate', $currentDate );
               $stmt->execute();
   
               return true;
           } catch ( PDOException $e ) {
               # Handle any exceptions here
               echo  'Error while updating requests:' . $e->getMessage();
   
               return false;
           }
       }

           # updateInstallmentSatatus: This method marks an Repayment field as 'Paid' Opposite of??Yes! you get it right. The method is right up.

    private function updateInstallmentSatatus($usertoken, $currentDate )
    {
           try {
               $sql = 'UPDATE loan_product_purchases SET payyment_status = 1 WHERE
            usertoken = :usertoken AND dueDate = :currentDate';
               $stmt = $this->conn->prepare( $sql );
               $stmt->bindParam( ':usertoken', $usertoken );
               $stmt->bindParam( ':currentDate', $currentDate );
               $stmt->execute();
   
               return true;
           } catch ( PDOException $e ) {
               # Handle any exceptions here
               echo  'Error while updating requests:' . $e->getMessage();
   
               return false;
           }
       }
   

          # updateInstallmentAmount: This method Updates the anount paid in the 'AMOUNT_DEBITED_SO_FAR' column  in loan_records table

    private function updateInstallmentAmount( $amount_debited_so_far, $token )
    {
           try {
               $sql = "UPDATE tbl_installment_purchases SET amount_debited_so_far = amount_debited_so_far
                 + :amount_debited_so_far WHERE token = :token ";
               $stmt = $this->conn->prepare( $sql );
               $stmt->bindParam( ':amount_debited_so_far', $amount_debited_so_far );
               $stmt->bindParam( ':token', $token );
               $stmt->execute();
   
               return true;
           } catch ( PDOException $e ) {
               # Handle any exceptions here
               echo  'Error while updating InstallmentAmount requests:' . $e->getMessage();
   
               return false;
           }
       }


       # checkIfUserFailsToPayOnDueDate::This meth check if user fails to pay on due date
       public function checkIfUserFailsToPayOnDueDate()
 {
        $dataArray = array();
        $currentDate = $this->currentDate();
        try {
             $sql = " SELECT * FROM loan_product_purchases
            WHERE dueDate < :currentDate AND payyment_status = 2";
            $stmt = $this->conn->prepare( $sql );
            $stmt->bindParam( ':currentDate', $currentDate );
            $stmt->execute();
            $rows = $stmt->rowCount();
            if ( $rows === 0 ) {
                return false;
            } else {
                $currentDate = $stmt->fetchAll( PDO::FETCH_ASSOC );
                foreach ( $currentDate as $value ) {

                    $array = [
                        'product_loan_token' => $value[ 'token' ],
                        'usertoken' => $value[ 'usertoken' ],
                        'monthExpectedToPay' => $value[ 'dueDate' ],
                        'amountExpectedToPay' => $value[ 'priceToPay' ],
                        'payyment_status' => $value[ 'payyment_status' ]
                        
                    ];

                    array_push( $dataArray, $array );
                }
            }
        } catch ( PDOException $e ) {
            echo 'Error: ' . $e->getMessage();
        }
        finally {
            $stmt = null;
        }
        return $dataArray;
    }


  #autoDebitLoanAfterDueDate::This method checks if the user fails to pay  productloan on due date
    public function autoDebitLoanAfterDueDate()
    {
   
           $checkIfUserFailsToPayOnDueDate = $this->checkIfUserFailsToPayOnDueDate();
           if ( !$checkIfUserFailsToPayOnDueDate ) {
               return null;
           }
           $allActionsSuccessful = false;
           foreach ( $checkIfUserFailsToPayOnDueDate as $taskAvailable ) {
   
               $checkAccountBalance = $this->getAccountBalance($taskAvailable[ 'usertoken' ] );
               $AccountBalance = $checkAccountBalance[ 'totalBalannce' ];
               $getDebtorsBioData = $this->getUserdata( $taskAvailable[ 'usertoken' ] );
               $mailer = new Mailer();
   
               if ( $AccountBalance === 0.00 or $AccountBalance < $taskAvailable['amountExpectedToPay'] ) {
   
                   #Insufficient funds... Payment status remains 2
                   $allActionsSuccessful = false;
                   #  Set flag to false
                   #   echo 'null';
                   #  break;
                   #  Exit loop
               } else {
                   # updateUserAccountBalance::Debit User Accordingly
                   $this->updateUserAccountBalance(
                        $taskAvailable['amountExpectedToPay'],
                       $taskAvailable[ 'usertoken' ],
                       $_ENV[ 'transactionType' ] = 'debit'
                   );
   
                   $this->updateInstallmentSatatus( $taskAvailable[ 'usertoken' ], $taskAvailable[ 'monthExpectedToPay' ] );
                   $this->updateInstallmentAmount( $taskAvailable[ 'amountExpectedToPay' ], $taskAvailable[ 'product_loan_token' ] );
   
                   try {
   
                       $mailer->notifyUserForDeductingLoanAmountFromWallet(
                           $getDebtorsBioData[ 'mail' ],
                           $getDebtorsBioData[ 'fname' ],
                           $taskAvailable[ 'amountExpectedToPay' ],
                           $taskAvailable[ 'monthExpectedToPay' ]
                       );
                   } catch ( Exception $e ) {
                       # Handle the error or log it as needed
   
                       $errorMessage = date( '[Y-m-d H:i:s] ' ) . 'Error sending mail loan for ' . __METHOD__ . '  ' . PHP_EOL . $e->getMessage();
                       error_log( $errorMessage, 3, 'crone.log' );
                   }
   
                   $this->recordTransaction(
                       $taskAvailable[ 'product_loan_token' ],
                       $taskAvailable[ 'usertoken' ],
                       $taskAvailable[ 'amountExpectedToPay' ],
                       $_ENV[ 'creditOrDebit' ] = 'Debit'
                   );
   
                   $message = 'Dear valued user, a deduction of ' . $this->formatCurrency( $taskAvailable[ 'amountExpectedToPay' ] ) . ' naira  has been made from your wallet to cover the loan servicing charges.';
                   $this->notifyUserMessage( $message, $taskAvailable[ 'usertoken' ] );
                   $allActionsSuccessful = true;
               }
           }
           return $allActionsSuccessful;
       }
   



       # REMIN USER A  DAY REMINDER BEFORE DUE DATE....................

    public function remindADayBeforeDueDate()
    {
         $currentDate = $this->currentDate();

           $sql = " SELECT usertoken, a_day_before, remind_a_day_before_status
               FROM loan_product_purchases 
               WHERE a_day_before = '$currentDate'
               AND remind_a_day_before_status = 0
               ORDER BY a_day_before ASC
               LIMIT 10";
   
           $stmt = $this->conn->query( $sql );
   
           if ( !$stmt->execute() ) {
               return false;
           }
           $remindADayBeforeDueDate = $stmt->fetchAll( PDO::FETCH_ASSOC );
   
           if ( !$remindADayBeforeDueDate ) {
               return false;
           }
   
           $mailer = new  Mailer();
           $sentReminder = false;
           foreach ( $remindADayBeforeDueDate as $key => $value ) {
               # code...
               $getUserdata = $this->getUserdata( $value[ 'usertoken' ] );
               $getProductName = $this->getProductByItRelatedToken("l");
   
               try {
   
                   $mailer->NotifyUserOfProductLoanADayToDueDate(
                       $getUserdata[ 'mail' ],
                       $getUserdata[ 'fname' ],
                       "productname"
                   );
               } catch ( Exception $e ) {
                   # Handle the error or log it as needed
                   $errorMessage = date( '[Y-m-d H:i:s] ' ) . 'Error sending mail loan for ' . __METHOD__ . '  ' . PHP_EOL . $e->getMessage();
                   error_log( $errorMessage, 3, 'crone.log' );
               }
   
               $this->updateADayReminderStatusIfMailSent( $value[ 'a_day_before' ], false );
               $sentReminder = true;
           }
           unset( $mailer );
           return $sentReminder;
       }
       


   
}
