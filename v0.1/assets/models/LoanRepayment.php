<?php

class LoanRepayment extends AbstractClasses
 {

    private   $conn;

    public function __construct( Database $database )
 {
        $this->conn = $database->connect();
    }

    public function checkIfTaskIsAvailable()
 {
        $dataArray = array();
        try {
            $sql = ' SELECT *  FROM loan_repayments  WHERE month = CURDATE() AND payyment_status = 0  LIMIT 5 ';
            $stmt = $this->conn->prepare( $sql );
            $stmt->execute();
            $rows = $stmt->rowCount();
            if ( $rows === 0 ) {
                return null;
            } else {
                $currentDate = $stmt->fetchAll( PDO::FETCH_ASSOC );
                foreach ( $currentDate as $value ) {

                    $array = [
                        'loanToken' => $value[ 'tbloan_token' ],
                        'usertoken' => $value[ 'usertoken' ],
                        'monthExpectedToPay' => $value[ 'month' ],
                        'amountExpectedToPay' => $value[ 'priceToPay' ],
                        'debitStatus' => $value[ 'payyment_status' ],
                        'remind_a_week_before_status' => $value[ 'remind_a_week_before_status' ],
                        'remind_a_day_before_status' => $value[ 'remind_a_day_before_status' ]
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
                $this->imposePenaltyFee(
                    $taskAvailable[ 'usertoken' ],
                    $taskAvailable[ 'monthExpectedToPay' ],
                    $taskAvailable[ 'amountExpectedToPay' ]
                );

                try {
                    $mailer->sendLoanPenaltyEmail( $getDebtorsBioData[ 'mail' ], $getDebtorsBioData[ 'fname' ], $taskAvailable[ 'amountExpectedToPay' ], $taskAvailable[ 'monthExpectedToPay' ] );
                } catch ( Exception $e ) {
                    # Handle the error or log it as needed
                    $errorMessage = date( '[Y-m-d H:i:s] ' ) . 'Error sending mail loan for ' . __METHOD__ . '  ' . PHP_EOL . $e->getMessage();
                    error_log( $errorMessage, 3, 'crone.log' );
                }
            } else {
                # updateUserAccountBalance::Debit User Accordingly
                $this->updateUserAccountBalance(
                    $taskAvailable[ 'amountExpectedToPay' ],
                    $getDebtorsBioData[ 'usertoken' ],
                    $_ENV[ 'transactionType' ] = 'debit'
                );

                $this->updateInstallmentSatatus( $taskAvailable[ 'usertoken' ], $taskAvailable[ 'monthExpectedToPay' ] );
                $this->updateInstallmentAmount( $taskAvailable[ 'amountExpectedToPay' ], $taskAvailable[ 'loanToken' ] );

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
                    $taskAvailable[ 'loanToken' ],
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
            $sql = 'UPDATE loan_repayments SET payyment_status = 2 WHERE
         usertoken = :usertoken AND month = :currentDate';
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

    #imposePenaltyFee:: This method imposes 20% Extra fee to individual who fails to pay when due

    private function imposePenaltyFee( $usertoken, $currentDate, $priceToPay )
 {
        # Calculate the penalty amount
        $penalty_amount = $priceToPay * $_ENV[ 'loan_penalty' ];

        # Update the loan repayment record
        try {
            $sql = 'UPDATE loan_repayments SET penalty = penalty + :penalty_amount 
        WHERE usertoken = :usertoken AND month = :month';
            $stmt = $this->conn->prepare( $sql );
            $stmt->bindParam( ':penalty_amount', $penalty_amount );
            $stmt->bindParam( ':usertoken', $usertoken );
            $stmt->bindParam( ':month', $currentDate );
            $stmt->execute();
        } catch ( PDOException $e ) {
            echo 'Error while updating penalty request: ' . $e->getMessage();
            return false;
        }

        # Return the result
        return true;
    }

    # updateInstallmentSatatus: This method marks an Repayment field as 'Paid' Opposite of??Yes! you get it right. The method is right up.

    private function updateInstallmentSatatus( $usertoken, $currentDate )
 {
        try {
            $sql = 'UPDATE loan_repayments SET payyment_status = 1 WHERE
         usertoken = :usertoken AND month = :currentDate';
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
            $sql = "UPDATE loan_records SET amount_debited_so_far = amount_debited_so_far
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

    # AUTO DEBIT USER IF USER FAILS TO PAY ON DUE DATE....................

    public function checkIfUserFailsToPayOnDueDate()
 {
        $dataArray = array();
        try {
            $sql = " SELECT * FROM loan_repayments
            WHERE month < CURDATE() AND payyment_status = 2";
            $stmt = $this->conn->prepare( $sql );
            $stmt->execute();
            $rows = $stmt->rowCount();
            if ( $rows === 0 ) {
                return false;
            } else {
                $currentDate = $stmt->fetchAll( PDO::FETCH_ASSOC );
                foreach ( $currentDate as $value ) {

                    $array = [
                        'loanToken' => $value[ 'tbloan_token' ],
                        'usertoken' => $value[ 'usertoken' ],
                        'monthExpectedToPay' => $value[ 'month' ],
                        'amountExpectedToPay' => $value[ 'priceToPay' ],
                        'loanPenalty' => $value[ 'penalty' ],
                        'debitStatus' => $value[ 'payyment_status' ],
                        'remind_a_week_before_status' => $value[ 'remind_a_week_before_status' ],
                        'remind_a_day_before_status' => $value[ 'remind_a_day_before_status' ]
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

    public function autoDebitLoanAfterDueDate()
 {

        $checkIfUserFailsToPayOnDueDate = $this->checkIfUserFailsToPayOnDueDate();
        if ( !$checkIfUserFailsToPayOnDueDate ) {
            return null;
        }
        $allActionsSuccessful = false;
        foreach ( $checkIfUserFailsToPayOnDueDate as $taskAvailable ) {

            $checkAccountBalance = $this->getAccountBalance( $taskAvailable[ 'usertoken' ] );
            $AccountBalance = $checkAccountBalance[ 'totalBalannce' ];
            $getDebtorsBioData = $this->getUserdata( $taskAvailable[ 'usertoken' ] );
            $LoanPenalty = $taskAvailable[ 'loanPenalty' ] + $taskAvailable[ 'amountExpectedToPay' ];
            $mailer = new Mailer();

            if ( $AccountBalance === 0.00 or $AccountBalance < $LoanPenalty ) {

                #Insufficient funds... Payment status remains 2
                $allActionsSuccessful = false;
                #  Set flag to false
                #   echo 'null';
                #  break;
                #  Exit loop
            } else {
                # updateUserAccountBalance::Debit User Accordingly
                $this->updateUserAccountBalance(
                    $LoanPenalty,
                    $taskAvailable[ 'usertoken' ],
                    $_ENV[ 'transactionType' ] = 'debit'
                );

                $this->updateInstallmentSatatus( $taskAvailable[ 'usertoken' ], $taskAvailable[ 'monthExpectedToPay' ] );
                $this->updateInstallmentAmount( $taskAvailable[ 'amountExpectedToPay' ], $taskAvailable[ 'loanToken' ] );

                try {

                    $mailer->NotifyUserOfDebitAndPenaltyAmount(
                        $getDebtorsBioData[ 'mail' ],
                        $getDebtorsBioData[ 'fname' ],
                        $taskAvailable[ 'monthExpectedToPay' ],
                        $taskAvailable[ 'amountExpectedToPay' ]
                    );
                } catch ( Exception $e ) {
                    # Handle the error or log it as needed

                    $errorMessage = date( '[Y-m-d H:i:s] ' ) . 'Error sending mail loan for ' . __METHOD__ . '  ' . PHP_EOL . $e->getMessage();
                    error_log( $errorMessage, 3, 'crone.log' );
                }

                $this->recordTransaction(
                    $taskAvailable[ 'loanToken' ],
                    $taskAvailable[ 'usertoken' ],
                    $taskAvailable[ 'amountExpectedToPay' ],
                    $_ENV[ 'creditOrDebit' ] = 'Debit'
                );

                $message = 'Dear valued user, a deduction of ' . $this->formatCurrency( $LoanPenalty ) . ' naira  has been made from your wallet to cover the loan servicing charges.';
                $this->notifyUserMessage( $message, $taskAvailable[ 'usertoken' ] );
                $allActionsSuccessful = true;
            }
        }
        return $allActionsSuccessful;
    }

    # REMIN USER A  DAY REMINDER BEFORE DUE DATE....................

    public function remindADayBeforeDueDate()
 {

        $sql = " SELECT usertoken, a_day_before, remind_a_day_before_status
            FROM loan_repayments 
            WHERE a_day_before = CURDATE()
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

            try {

                $mailer->NotifyUserOfLoanADayToDueDate(
                    $getUserdata[ 'mail' ],
                    $getUserdata[ 'fname' ]
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
    

     # UpdateReminderStatusIfMailSent: This method marks a REMINDER  field as '1' MEANING THAT mail has been sent

     private function updateADayReminderStatusIfMailSent($updateADayReminderStatus)
     {
            try {
    
                $sql = 'UPDATE loan_repayments SET remind_a_day_before_status = 1
                WHERE a_day_before = :currentDate';
                $stmt = $this->conn->prepare( $sql );
                $stmt->bindParam( ':currentDate', $updateADayReminderStatus );
                $stmt->execute();
    
                return true;
            } catch ( PDOException $e ) {
                # Handle any exceptions here
                echo  'Error while updating requests: ' . $e->getMessage();
    
                return false;
            }
            finally {
                $stmt = null;
            }
        }

    # REMIN USER A  WEEK  REMINDER BEFORE DUE DATE....................

    public function remindAWeekBeforeDueDate()
 {
        $sql = "SELECT usertoken, a_week_before, remind_a_week_before_status
            FROM loan_repayments 
            WHERE a_week_before = CURDATE()
            AND remind_a_week_before_status = 0
            ORDER BY a_week_before ASC
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

            try {

                $mailer->NotifyUserOfLoanAWeekToDueDate(
                    $getUserdata[ 'mail' ],
                    $getUserdata[ 'fname' ]
                );
            } catch ( Exception $e ) {
                # Handle the error or log it as needed
                $errorMessage = date( '[Y-m-d H:i:s] ' ) . 'Error sending mail loan for ' . __METHOD__ . '  ' . PHP_EOL . $e->getMessage();
                error_log( $errorMessage, 3, 'crone.log' );
            }

            $this->updateAWeekeminderStatusIfMailSent( $value[ 'a_week_before' ], true );
            $sentReminder = true;
        }
        unset( $mailer );
        return $sentReminder;
    }




     # UpdateReminderStatusIfMailSent: This method marks a REMINDER  field as '1' MEANING THAT mail has been sent

     private function updateAWeekeminderStatusIfMailSent($updateADayReminderStatus)
     {
            try {
    
                $sql = 'UPDATE loan_repayments SET remind_a_week_before_status = 1
                WHERE a_week_before = :currentDate';
                $stmt = $this->conn->prepare( $sql );
                $stmt->bindParam( ':currentDate', $updateADayReminderStatus );
                $stmt->execute();
    
                return true;
            } catch ( PDOException $e ) {
                # Handle any exceptions here
                echo  'Error while updating requests: ' . $e->getMessage();
    
                return false;
            }
            finally {
                $stmt = null;
            }
        }

    # REMIN USER 3 DAYS  REMINDER BEFORE DUE DATE....................

    public function remindThreeDaysBeforeDueDate()
 {
        $sql = "SELECT usertoken, 3_days_before, remind_3_days_before_status
            FROM loan_repayments 
            WHERE 3_days_before = CURDATE()
            AND remind_3_days_before_status = 0
            ORDER BY 3_days_before ASC
            LIMIT 10";

        $stmt = $this->conn->query( $sql );

        if ( !$stmt->execute() ) {
            return false;
        }
        $remindThreeDaysBeforeDueDate = $stmt->fetchAll( PDO::FETCH_ASSOC );

        if ( !$remindThreeDaysBeforeDueDate ) {
            return false;
        }

        $mailer = new  Mailer();
        $sentReminder = false;
        foreach ( $remindThreeDaysBeforeDueDate as $key => $value ) {
            # code...
            $getUserdata = $this->getUserdata( $value[ 'usertoken' ] );

            try {

                $mailer->NotifyUserOfLoanThreeDaysToDueDate(
                    $getUserdata[ 'mail' ],
                    $getUserdata[ 'fname' ]
                );
            } catch ( Exception $e ) {
                # Handle the error or log it as needed
                $errorMessage = date( '[Y-m-d H:i:s] ' ) . 'Error sending mail loan for ' . __METHOD__ . '  ' . PHP_EOL . $e->getMessage();
                error_log( $errorMessage, 3, 'crone.log' );
            }

            $this->updateThreeDaysReminderStatusIfMailSent( $value[ '3_days_before' ], true );
            $sentReminder = true;
        }
        unset( $mailer );
        return $sentReminder;
    }

     # updateThreeDaysReminderStatusIfMailSent: This method marks a REMINDER  field as '1' MEANING THAT mail has been sent

      private function updateThreeDaysReminderStatusIfMailSent($updateADayReminderStatus)
     {
            try {
    
                $sql = 'UPDATE loan_repayments SET remind_3_days_before_status = 1
                WHERE 3_days_before = :currentDate';
                $stmt = $this->conn->prepare( $sql );
                $stmt->bindParam( ':currentDate', $updateADayReminderStatus );
                $stmt->execute();
    
                return true;
            } catch ( PDOException $e ) {
                # Handle any exceptions here
                echo  'Error while updating requests: ' . $e->getMessage();
    
                return false;
            }
            finally {
                $stmt = null;
            }
        }



    # Celebrate loan success__>.............>

    public function celebrateLoanSuccess() {

        $sql = "SELECT usertoken, isCompletedStatus, amountToBorrow, amount_debited_so_far FROM 
        loan_records WHERE isCompletedStatus = 0";


        try {
            $stmt = $this->conn->prepare( $sql );
            if ( !$stmt->execute() ) {
                return false;
            }
            $celebrateLoanSuccess = $stmt->fetchAll( PDO::FETCH_ASSOC );

            $mailer = new Mailer();

            $verifyIfLoanIsPaid = false;

            foreach ( $celebrateLoanSuccess as $key => $value ) {

                if ( $value[ 'amount_debited_so_far' ] >= $value[ 'amountToBorrow' ] ) {

                    $getUserdata = $this->getUserdata( $value[ 'usertoken' ] );

                    try {

                        $mailer->celebrateLoanCompletedSuccesss(
                            $getUserdata[ 'mail' ],
                            $getUserdata[ 'fname' ]
                        );
                    } catch ( Exception $e ) {
                        # Handle the error or log it as needed
                        $errorMessage = date( '[Y-m-d H:i:s] ' ) . 'Error sending mail loan for ' . __METHOD__ . '  ' . PHP_EOL . $e->getMessage();
                        error_log( $errorMessage, 3, 'crone.log' );
                    }

                    $this->UpdateIsCompletedIfHonored( $value[ 'usertoken' ] );

                    $verifyIfLoanIsPaid = true;

                }
            }
        } catch ( PDOException $e ) {
            # Handle any exceptions here
            echo  'Error while updating requests: ' . $e->getMessage();

            return false;
        }
        finally {
            unset( $mailer );
            $stmt = null;
        }
        return $verifyIfLoanIsPaid;
    }

    # UpdateIsCompletedIfHonored: This method marks isCompletedStatus  field as '1' MEANING THAT loan completed

    private function UpdateIsCompletedIfHonored( $usertoken )
 {
        try {
            $sql = 'UPDATE loan_records SET isCompletedStatus = 1
             WHERE usertoken = :usertoken';
            $stmt = $this->conn->prepare( $sql );
            $stmt->bindParam( ':usertoken', $usertoken );
            $stmt->execute();

            return true;
        } catch ( PDOException $e ) {
            # Handle any exceptions here
            echo  'Error while updating requests: ' . $e->getMessage();

            return false;
        }
        finally {
            $stmt = null;
        }
    }
}
