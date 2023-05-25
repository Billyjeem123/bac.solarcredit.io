<?php

class WithdrawalRequest  extends AbstractClasses
{

    private   $conn;

    public function __construct(Database $database)
    {

        $this->conn = $database->connect();
    }


    public function requestWithdrawal(array $data)
    {

        #getAccountBalance.::Checks for Account Balnace 
        $getAccountBalance =  $this->getAccountBalance($data['usertoken']);
        if ($getAccountBalance['totalBalannce'] < $data['amount']) {
            $this->outputData(false, "Insufficient funds, Unable to process request", null);
            exit;
        }

        $updateUserAccountBalance =   $this->updateUserAccountBalance($data['amount'], $data['usertoken'], $_ENV['transactionType'] = "debit");
        if (!$updateUserAccountBalance) {
            $this->outputData(false, $_SESSION['err'], null);
            exit;
        }

        $fields = [
            'usertoken' => $data['usertoken'],
            'amount' => $data['amount'],
            'accountNumber' => $data['accountNumber'],
            'bankName' => $data['bankName'],
            'accountName'  => $data['accountName'],
            'time' => time()

        ];

        # Build the SQL query
        $placeholders = implode(', ', array_fill(0, count($fields), '?'));
        $columns = implode(', ', array_keys($fields));
        $sql = "INSERT INTO tblwithdrawal ($columns) VALUES ($placeholders)";

        #  Execute the query and handle any errors
        try {
            $stmt =  $this->conn->prepare($sql);
            $i = 1;
            foreach ($fields as $value) {
                $type = is_int($value) ? PDO::PARAM_INT : PDO::PARAM_STR;
                $stmt->bindValue($i,   $value, $type);
                $i++;
            }
            $stmt->execute();

            http_response_code(201);
            $mailer = new Mailer();
            try {

                $mailer->NotifyAdminOfWithrawalRequest(
                    $data['accountName'],
                    $data['accountNumber'],
                    $data['bankName'],
                    $data['amount']
                );
            } catch (Exception $e) {
                # Handle the error or log it as needed
                $errorMessage = date('[Y-m-d H:i:s] ') . 'Error sending mail withdrawal for ' . __METHOD__ . '  ' . PHP_EOL . $e->getMessage();
                error_log($errorMessage, 3, 'withdrawal.log');
            }

            $message = 'Dear valued user, a deduction of ' . $this->formatCurrency($data['amount']) . ' naira  has been made from your wallet, for withdrawal purposes';
            $this->notifyUserMessage($message, $data['usertoken']);
            $output = $this->outputData(true, 'Request sent,You shall be notified upon approval!', null);

            exit;
        } catch (PDOException $e) {

            $output  = $this->respondWithInternalError('Error: ' . $e->getMessage());
            exit;
        } finally {
            $stmt = null;
            $this->conn = null;
        }

        return $output;
    }



    # getAllWithdrawalRequests::This method fetches users withdrawal records in the databse
    public function getAllWithdrawalRequests()
    {
        $dataArray = array();
        $sql = ' SELECT * FROM tblwithdrawal  ORDER BY id DESC ';
        try {
            $stmt = $this->conn->query($sql);
            $stmt->execute();
            $count = $stmt->rowCount();
            if ($count === 0) {
                $_SESSION['err'] = "No record found";
                return false;
            }
            $loanRecords = $stmt->fetchAll(PDO::FETCH_ASSOC);
            foreach ($loanRecords as $allLoanRecords) {
                $array = [
                    'accountName' => ($allLoanRecords['accountName']),
                    'amountRequested' => ($allLoanRecords['amount']),
                    'usertoken' => $allLoanRecords['usertoken'],
                    'bankName' => ($allLoanRecords['bankName']),
                    'accountNumber' => ($allLoanRecords['accountNumber']),
                    'withrawaltoken' => ($allLoanRecords['id']),
                    'withdrawalStatus' => ($allLoanRecords['status'] == 1) ? 'Approved' : (($allLoanRecords['status'] == 2) ? 'Unapproved' : 'Pending'),
                    'RequestedOn' => $this->formatDate($allLoanRecords['time']),
                ];

                array_push($dataArray, $array);
            }
        } catch (PDOException $e) {
            $_SESSION['err'] = "Error while retreiving withdrawal request:" . $e->getMessage();
            return false;
        } finally {
            $stmt = null;
            $this->conn = null;
        }
        return $dataArray;
    }


    #getUsersWithdrawalRequests::This method fetches all users withdrawal records in the databse
    public function getUsersWithdrawalRequests(int $usertoken)
    {
        $dataArray = array();
        $sql = ' SELECT * FROM tblwithdrawal   WHERE usertoken = :usertoken ORDER BY id DESC ';
        try {
            $stmt = $this->conn->prepare($sql);
            $stmt->bindParam(':usertoken', $usertoken);

            $stmt->execute();
            $count = $stmt->rowCount();
            if ($count === 0) {
                $_SESSION['err'] = "No record found";
                return false;
            }
            $loanRecords = $stmt->fetchAll(PDO::FETCH_ASSOC);
            foreach ($loanRecords as $allLoanRecords) {
                $array = [
                    'accountName' => ($allLoanRecords['accountName']),
                    'amountRequested' => ($allLoanRecords['amount']),
                    'usertoken' => $allLoanRecords['usertoken'],
                    'bankName' => ($allLoanRecords['bankName']),
                    'accountNumber' => ($allLoanRecords['accountNumber']),
                    'withrawaltoken' => ($allLoanRecords['id']),
                    'withdrawalStatus' => ($allLoanRecords['status'] == 1) ? 'Approved' : (($allLoanRecords['status'] == 2) ? 'Unapproved' : 'Pending'),
                    'RequestedOn' => $this->formatDate($allLoanRecords['time']),
                ];

                array_push($dataArray, $array);
            }
        } catch (PDOException $e) {
            $_SESSION['err'] = "Error while retreiving withdrawal request:" . $e->getMessage();
            return false;
        } finally {
            $stmt = null;
            $this->conn = null;
        }
        return $dataArray;
    }

    #approveWithdrawalRequests ::This method approves a Withrdrawal requests
    public function approveWithdrawalRequest(array $data)
    {
        try {

            $sql = 'UPDATE tblwithdrawal  SET status = 1 WHERE id = :withrawaltoken ';
            $stmt = $this->conn->prepare($sql);
            $stmt->bindParam(':withrawaltoken', $data['withrawaltoken'],  PDO::PARAM_INT);

            if (!$stmt->execute()) {
                $_SESSION['err'] = 'Unable to update withdrwal status';
                return false;
                exit;
            }


            $getUserdata = $this->getUserdata($data['usertoken']);

            $mailer = new Mailer;

            $this->recordTransaction(uniqid(), $data['usertoken'], $data['amount'], $_ENV['creditOrDebit'] = "Credit");

            try {
                $mailer->NotifyUserofWithdrawalApproval($getUserdata['mail'], $getUserdata['fname'], $data['amount'], $data['accountNumber']);
            } catch (Exception $e) {
                # Handle the error or log it as needed
                $errorMessage = date('[Y-m-d H:i:s] ') . "Error sending mail  for " . __METHOD__ . "  " . PHP_EOL . $e->getMessage();
                error_log($errorMessage, 3, 'withdrawal.log');
                echo "run";
            }

            $_SESSION['err'] = 'Approved';
        } catch (Exception $e) {
            $_SESSION['err'] = 'Unable to process request:' . $e->getMessage();
            return false;
        } finally {
            $stmt  = null;
            $this->conn = null;
            unset($mailer);
        }
        return true;
    }


    #approveWithdrawalRequests ::This method approves a Withrdrawal requests
    public function DiapproveWithdrawalRequest(array $data)
    {
        try {

            $sql = 'UPDATE tblwithdrawal  SET status = 2 WHERE id = :withrawaltoken ';
            $stmt = $this->conn->prepare($sql);
            $stmt->bindParam(':withrawaltoken', $data['withrawaltoken'],  PDO::PARAM_INT);

            if (!$stmt->execute()) {
                $_SESSION['err'] = 'Unable to update withdrwal status';
                return false;
                exit;
            }

            $updateUserAccountBalance =   $this->updateUserAccountBalance($data['amount'], $data['usertoken'], $_ENV['transactionType'] = "credit");
            if (!$updateUserAccountBalance) {
                $this->outputData(false, $_SESSION['err'], null);
                exit;
            }


            $getUserdata = $this->getUserdata($data['usertoken']);

            $mailer = new Mailer;

            $this->recordTransaction(uniqid(), $data['usertoken'], $data['amount'], $_ENV['creditOrDebit'] = "Credit");

            try {
                $mailer->NotifyUserofWithdrawalDiapproval($getUserdata['mail'], $getUserdata['fname'], $data['amount'], $data['message']);
            } catch (Exception $e) {
                # Handle the error or log it as needed
                $errorMessage = date('[Y-m-d H:i:s] ') . "Error sending mail  for " . __METHOD__ . "  " . PHP_EOL . $e->getMessage();
                error_log($errorMessage, 3, 'withdrawal.log');
                echo "run";
            }

            $message = 'Dear valued user, we regret to inform you that your recent withdrawal request has been declined' . $this->formatCurrency($data['amount']) . ' naira  has been reversed and credited back to your account';

            $this->notifyUserMessage($message, $data['usertoken']);

            $_SESSION['err'] = 'Disapproved';
        } catch (Exception $e) {
            $_SESSION['err'] = 'Unable to process request:' . $e->getMessage();
            return false;
        } finally {
            $stmt  = null;
            $this->conn = null;
            unset($mailer);
        }
        return true;
    }
}
