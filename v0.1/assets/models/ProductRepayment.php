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
        if (!$checkIfTaskIsAvailable) {
            return null;
        }

        foreach ($checkIfTaskIsAvailable as $taskAvailable) {
            # do something with $taskAvailable
            $checkAccountBalance = $this->getAccountBalance($taskAvailable['usertoken']);
            $AccountBalance = $checkAccountBalance['totalBalannce'];
            $getDebtorsBioData = $this->getUserdata($taskAvailable['usertoken']);
            $mailer = new Mailer();

            if ($AccountBalance === 0.00 or $AccountBalance < $taskAvailable['amountExpectedToPay']) {

                $this->markInstallmentAsUnPaid($taskAvailable['usertoken'], $taskAvailable['monthExpectedToPay']);

                try {
                    $mailer->notifyUserOfUnPaidOutstanding($getDebtorsBioData['mail'], $getDebtorsBioData['fname']);
                } catch (Exception $e) {
                    # Handle the error or log it as needed
                    $errorMessage = date('[Y-m-d H:i:s] ') . 'Error sending mail loan for ' . __METHOD__ . '  ' . PHP_EOL . 'Error Message: ' . $e->getMessage() . ' in ' . $e->getFile() . ' on line ' . $e->getLine();
                    error_log($errorMessage, 3, 'crone.log');
                }
            } else {
                # updateUserAccountBalance::Debit User Accordingly
                $this->updateUserAccountBalance(
                    $taskAvailable['amountExpectedToPay'],
                    $getDebtorsBioData['usertoken'],
                    $_ENV['transactionType'] = 'debit'
                );

                $this->updateInstallmentSatatus($taskAvailable['usertoken'], $taskAvailable['monthExpectedToPay']);
                $this->updateInstallmentAmount($taskAvailable['amountExpectedToPay'], $taskAvailable['product_loan_token']);

                try {

                    $mailer->notifyUserForDeductingLoanAmountFromWallet(
                        $getDebtorsBioData['mail'],
                        $getDebtorsBioData['fname'],
                        $taskAvailable['amountExpectedToPay'],
                        $taskAvailable['monthExpectedToPay']
                    );
                } catch (Exception $e) {
                    # Handle the error or log it as needed
                    $errorMessage = date('[Y-m-d H:i:s] ') . 'Error sending mail loan for ' . __METHOD__ . '  ' . PHP_EOL . 'Error Message: ' . $e->getMessage() . ' in ' . $e->getFile() . ' on line ' . $e->getLine();
                    error_log($errorMessage, 3, 'crone.log');
                }

                $this->recordTransaction(
                    $taskAvailable['product_loan_token'],
                    $taskAvailable['usertoken'],
                    $taskAvailable['amountExpectedToPay'],
                    $_ENV['creditOrDebit'] = 'Debit'
                );

                $message = 'Dear valued user, a deduction of ' . $this->formatCurrency($taskAvailable['amountExpectedToPay']) . ' naira  has been made from your wallet to cover the loan servicing charges.';
                $this->notifyUserMessage($message, $taskAvailable['usertoken']);
            }
        }
        unset($mailer);
        # Free mail  resources
        return true;
    }


    # markInstallmentAsUnPaid: This method marks an Repayment field as Unpaid

    private function markInstallmentAsUnPaid($usertoken, $currentDate)
    {
        try {
            $sql = 'UPDATE loan_product_purchases SET payyment_status = 2 WHERE
            usertoken = :usertoken AND dueDate = :currentDate';
            $stmt = $this->conn->prepare($sql);
            $stmt->bindParam(':usertoken', $usertoken);
            $stmt->bindParam(':currentDate', $currentDate);
            $stmt->execute();

            return true;
        } catch (PDOException $e) {
            # Handle any exceptions here
            echo  'Error while updating requests:' . $e->getMessage();

            return false;
        }
    }

    # updateInstallmentSatatus: This method marks an Repayment field as 'Paid' Opposite of??Yes! you get it right. The method is right up.

    private function updateInstallmentSatatus($usertoken, $currentDate)
    {
        try {
            $sql = 'UPDATE loan_product_purchases SET payyment_status = 1 WHERE
            usertoken = :usertoken AND dueDate = :currentDate';
            $stmt = $this->conn->prepare($sql);
            $stmt->bindParam(':usertoken', $usertoken);
            $stmt->bindParam(':currentDate', $currentDate);
            $stmt->execute();

            return true;
        } catch (PDOException $e) {
            # Handle any exceptions here
            echo  'Error while updating requests:' . $e->getMessage();

            return false;
        }
    }


    # updateInstallmentAmount: This method Updates the anount paid in the 'AMOUNT_DEBITED_SO_FAR' column  in loan_records table

    private function updateInstallmentAmount($amount_debited_so_far, $token)
    {
        try {
            $sql = "UPDATE tbl_installment_purchases SET amount_debited_so_far = amount_debited_so_far
                 + :amount_debited_so_far WHERE token = :token ";
            $stmt = $this->conn->prepare($sql);
            $stmt->bindParam(':amount_debited_so_far', $amount_debited_so_far);
            $stmt->bindParam(':token', $token);
            $stmt->execute();

            return true;
        } catch (PDOException $e) {
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
            $stmt = $this->conn->prepare($sql);
            $stmt->bindParam(':currentDate', $currentDate);
            $stmt->execute();
            $rows = $stmt->rowCount();
            if ($rows === 0) {
                return false;
            } else {
                $currentDate = $stmt->fetchAll(PDO::FETCH_ASSOC);
                foreach ($currentDate as $value) {

                    $array = [
                        'product_loan_token' => $value['token'],
                        'usertoken' => $value['usertoken'],
                        'monthExpectedToPay' => $value['dueDate'],
                        'amountExpectedToPay' => $value['priceToPay'],
                        'payyment_status' => $value['payyment_status']

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


    #autoDebitLoanAfterDueDate::This method checks if the user fails to pay  productloan on due date
    public function autoDebitLoanAfterDueDate()
    {

        $checkIfUserFailsToPayOnDueDate = $this->checkIfUserFailsToPayOnDueDate();
        if (!$checkIfUserFailsToPayOnDueDate) {
            return null;
        }
        $allActionsSuccessful = false;
        foreach ($checkIfUserFailsToPayOnDueDate as $taskAvailable) {

            $checkAccountBalance = $this->getAccountBalance($taskAvailable['usertoken']);
            $AccountBalance = $checkAccountBalance['totalBalannce'];
            $getDebtorsBioData = $this->getUserdata($taskAvailable['usertoken']);
            $mailer = new Mailer();

            if ($AccountBalance === 0.00 or $AccountBalance < $taskAvailable['amountExpectedToPay']) {

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
                    $taskAvailable['usertoken'],
                    $_ENV['transactionType'] = 'debit'
                );

                $this->updateInstallmentSatatus($taskAvailable['usertoken'], $taskAvailable['monthExpectedToPay']);
                $this->updateInstallmentAmount($taskAvailable['amountExpectedToPay'], $taskAvailable['product_loan_token']);

                try {

                    $mailer->notifyUserForDeductingLoanAmountFromWallet(
                        $getDebtorsBioData['mail'],
                        $getDebtorsBioData['fname'],
                        $taskAvailable['amountExpectedToPay'],
                        $taskAvailable['monthExpectedToPay']
                    );
                } catch (Exception $e) {
                    # Handle the error or log it as needed

                    $errorMessage = date('[Y-m-d H:i:s] ') . 'Error sending mail loan for ' . __METHOD__ . '  ' . PHP_EOL . 'Error Message: ' . $e->getMessage() . ' in ' . $e->getFile() . ' on line ' . $e->getLine();
                    error_log($errorMessage, 3, 'crone.log');
                }

                $this->recordTransaction(
                    $taskAvailable['product_loan_token'],
                    $taskAvailable['usertoken'],
                    $taskAvailable['amountExpectedToPay'],
                    $_ENV['creditOrDebit'] = 'Debit'
                );

                $message = 'Dear valued user, a deduction of ' . $this->formatCurrency($taskAvailable['amountExpectedToPay']) . ' naira  has been made from your wallet to cover the loan servicing charges.';
                $this->notifyUserMessage($message, $taskAvailable['usertoken']);
                $allActionsSuccessful = true;
            }
        }
        return $allActionsSuccessful;
    }




    # REMIN USER A  DAY REMINDER BEFORE DUE DATE....................
    # This function below, i  had to  join loan_product_purchases && tbl_store_allinstallment_product 
    # Together  mainly to get productname in a single query

    public function remindADayBeforeDueDate()
    {
        $currentDate = $this->currentDate();

        $sql = " SELECT
            loan_product_purchases.a_day_before,
            loan_product_purchases.remind_a_day_before_status,
            loan_product_purchases.a_day_before,
            loan_product_purchases.usertoken,
            GROUP_CONCAT(tbl_store_allinstallment_product.productname SEPARATOR ', ') AS grouped_product_names
        FROM
            loan_product_purchases
        INNER JOIN tbl_store_allinstallment_product ON tbl_store_allinstallment_product.transactionToken = loan_product_purchases.token
        WHERE
            loan_product_purchases.a_day_before = '$currentDate'
            AND loan_product_purchases.remind_a_day_before_status = 0
        GROUP BY
            loan_product_purchases.usertoken
        ORDER BY
            loan_product_purchases.a_day_before ASC
        LIMIT
            10";

        $stmt = $this->conn->query($sql);

        if (!$stmt->execute()) {
            return false;
        }
        $remindADayBeforeDueDate = $stmt->fetchAll(PDO::FETCH_ASSOC);
        if (!$remindADayBeforeDueDate) {
            return false;
        }

        $mailer = new  Mailer();
        $sentReminder = false;
        foreach ($remindADayBeforeDueDate as $key => $value) {
            # code...
            $getUserdata = $this->getUserdata($value['usertoken']);

            try {

                $mailer->NotifyUserOfProductLoanADayToDueDate(
                    $getUserdata['mail'],
                    $getUserdata['fname'],
                    $value['grouped_product_names']
                );
            } catch (Exception $e) {
                # Handle the error or log it as needed
                $errorMessage = date('[Y-m-d H:i:s] ') . 'Error sending mail  for ' . __METHOD__ . '  ' . PHP_EOL . $e->getMessage();
                error_log($errorMessage, 3, 'crone.log');
            }

               $this->updateADayReminderStatusIfMailSent( $value[ 'a_day_before' ], $value['usertoken']);
            $sentReminder = true;
        }
        unset($mailer);
        return $sentReminder;
    }


    # UpdateReminderStatusIfMailSent: This method marks a REMINDER  field as '1' MEANING THAT mail has been sent

    private function updateADayReminderStatusIfMailSent($updateADayReminderStatus, $usertoken)
    {
        try {

            $sql = 'UPDATE loan_product_purchases SET remind_a_day_before_status = 1
                WHERE a_day_before = :currentDate AND usertoken = :usertoken';
            $stmt = $this->conn->prepare($sql);
            $stmt->bindParam(':currentDate', $updateADayReminderStatus);
            $stmt->bindParam(':usertoken', $usertoken);
            $stmt->execute();

            return true;
        } catch (PDOException $e) {
            # Handle any exceptions here
            echo  'Error while updating requests: ' . $e->getMessage();

            return false;
        } finally {
            $stmt = null;
        }
    }



    # REMIN USER A  WEEK  BEFORE DUE DATE....................

    public function remindAWeekBeforeDueDate()
    {
        $currentDate = $this->currentDate();

        $sql = "SELECT token, usertoken, a_week_before, remind_a_week_before_status
            FROM loan_product_purchases 
            WHERE a_week_before = '$currentDate'
            AND remind_a_week_before_status = 0
            ORDER BY a_week_before ASC
            LIMIT 10";



        $stmt = $this->conn->query($sql);

        if (!$stmt->execute()) {
            return false;
        }
        $remindADayBeforeDueDate = $stmt->fetchAll(PDO::FETCH_ASSOC);

        if (!$remindADayBeforeDueDate) {
            return false;
        }

        $mailer = new  Mailer();
        $sentReminder = false;
        foreach ($remindADayBeforeDueDate as $key => $value) {
            # code...
            $getUserdata = $this->getUserdata($value['usertoken']);
            $getProductPurchasedInstallmentally = $this->getProductPurchasedInstallmentally($value['token']);

            try {

                $mailer->NotifyUserOfProductLoanAWeekToDueDate(
                    $getUserdata['mail'],
                    $getUserdata['fname'],
                    $getProductPurchasedInstallmentally
                );
            } catch (Exception $e) {
                # Handle the error or log it as needed
                $errorMessage = date('[Y-m-d H:i:s] ') . 'Error sending mail for  ' . __METHOD__ . '  ' . PHP_EOL . 'Error Message: ' . $e->getMessage() . ' in ' . $e->getFile() . ' on line ' . $e->getLine();
                error_log($errorMessage, 3, 'crone.log');
            }

            $this->updateAWeekReminderStatusIfMailSent($value['a_week_before'], $value['usertoken']);
            $sentReminder = true;
        }
        unset($mailer);
        return $sentReminder;
    }




    # UpdateReminderStatusIfMailSent: This method marks a REMINDER  field as '1' MEANING THAT mail has been sent

    private function updateAWeekReminderStatusIfMailSent($updateADayReminderStatus, $usertoken)
    {
        try {

            $sql = 'UPDATE loan_product_purchases SET remind_a_week_before_status = 1
                WHERE a_week_before = :currentDate AND usertoken = :usertoken';
            $stmt = $this->conn->prepare($sql);
            $stmt->bindParam(':currentDate', $updateADayReminderStatus);
            $stmt->bindParam(':usertoken', $usertoken);
            $stmt->execute();

            return true;
        } catch (PDOException $e) {
            # Handle any exceptions here
            echo  'Error while updating requests: ' . $e->getMessage();

            return false;
        } finally {
            $stmt = null;
        }
    }







    # REMIN USER A Three days  BEFORE DUE DATE....................

    public function remindThreeddDaysBeforeDueDate()
    {
        $currentDate = $this->currentDate();

        $sql = "SELECT token, usertoken, 3_days_before, remind_3_days_before_status
             FROM loan_product_purchases 
             WHERE 3_days_before = '$currentDate'
             AND remind_3_days_before_status = 0
             ORDER BY 3_days_before ASC
             LIMIT 10";



        $stmt = $this->conn->query($sql);

        if (!$stmt->execute()) {
            return false;
        }
        $remindADayBeforeDueDate = $stmt->fetchAll(PDO::FETCH_ASSOC);

        if (!$remindADayBeforeDueDate) {
            return false;
        }

        $mailer = new  Mailer();
        $sentReminder = false;
        foreach ($remindADayBeforeDueDate as $key => $value) {
            # code...
            $getUserdata = $this->getUserdata($value['usertoken']);
            $getProductPurchasedInstallmentally = $this->getProductPurchasedInstallmentally($value['token']);

            try {

                $mailer->NotifyUserOfProductLoanThreeDaysToDueDate(
                    $getUserdata['mail'],
                    $getUserdata['fname'],
                    $getProductPurchasedInstallmentally
                );
            } catch (Exception $e) {
                # Handle the error or log it as needed
                $errorMessage = date('[Y-m-d H:i:s] ') . 'Error sending mail for  ' . __METHOD__ . '  ' . PHP_EOL . 'Error Message: ' . $e->getMessage() . ' in ' . $e->getFile() . ' on line ' . $e->getLine();
                error_log($errorMessage, 3, 'crone.log');
            }

            $this->updateThreeDaysReminderStatusIfMailSent($value['3_days_before'], $value['usertoken']);
            $sentReminder = true;
        }
        unset($mailer);
        return $sentReminder;
    }




    # UpdateReminderStatusIfMailSent: This method marks a REMINDER  field as '1' MEANING THAT mail has been sent

    private function updateThreeDaysReminderStatusIfMailSent($updateADayReminderStatus, $usertoken)
    {
        try {

            $sql = 'UPDATE loan_product_purchases SET remind_a_week_before_status = 1
                 WHERE a_week_before = :currentDate AND usertoken = :usertoken';
            $stmt = $this->conn->prepare($sql);
            $stmt->bindParam(':currentDate', $updateADayReminderStatus);
            $stmt->bindParam(':usertoken', $usertoken);
            $stmt->execute();

            return true;
        } catch (PDOException $e) {
            # Handle any exceptions here
            echo  'Error while updating requests: ' . $e->getMessage();

            return false;
        } finally {
            $stmt = null;
        }
    }



     
    # Celebrate Product loan success__>.............>

    public function celebrateProductLoanSuccess() {

        $sql = "SELECT usertoken, token, isCompletedStatus, total_amount, amount_debited_so_far FROM 
        tbl_installment_purchases WHERE isCompletedStatus = 0";


        try {
            $stmt = $this->conn->prepare( $sql );
            if ( !$stmt->execute() ) {
                return false;
            }
            $celebrateLoanSuccess = $stmt->fetchAll( PDO::FETCH_ASSOC );

            $mailer = new Mailer();

            $verifyIfLoanIsPaid = false;

            foreach ( $celebrateLoanSuccess as $key => $value ) {

                if ( $value[ 'amount_debited_so_far' ] >= $value[ 'total_amount' ] ) {

                    $getUserdata = $this->getUserdata( $value[ 'usertoken' ] );
                    $getProductPurchasedInstallmentally = $this->getProductPurchasedInstallmentally($value['token']);

                    try {

                        $mailer->celebrateProductLoanCompletedSuccesss(
                            $getUserdata[ 'mail' ],
                            $getUserdata[ 'fname' ],
                            $getProductPurchasedInstallmentally
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
            $sql = 'UPDATE tbl_installment_purchases SET isCompletedStatus = 1
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


    private function getProductPurchasedInstallmentally($token)
    {
        try {

            $sql = 'SELECT transactionToken, productname, price, modeOfPayment  FROM
                    tbl_store_allinstallment_product WHERE transactionToken = :token';
            $stmt = $this->conn->prepare($sql);
            $stmt->bindParam(':token', $token);
            $stmt->execute();

            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            # Handle any exceptions here
            echo  'Error while updating requests: ' . $e->getMessage();

            return false;
        } finally {
            $stmt = null;
        }
    }
}
