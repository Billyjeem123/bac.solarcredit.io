<?php

class Withdrawal  extends AbstractClasses
{

  public function  requestWithrawal($usertoken, $amount, $accountnumber, $bankname, $ackName)
  {

    $time = time();


    $getUserBalance =  $this->getAccountBalance($usertoken);

    if($getUserBalance < $amount){
        $this->outputData(false, 'Insufficient funds. Kindly fund your wallet', null);
        exit;

    }
      $sql = "INSERT INTO tblwithdraw ";
      $sql .= "(usertoken, amount, ackN, Bname, ackName,  time)";
      $sql .=  " VALUES(:usertoken, :amount, :ackN, :Bname, :ackName,  :time) ";
      $stmt = $this->connect()->prepare($sql);
      $stmt->bindParam(':usertoken', $usertoken);
      $stmt->bindParam(':amount', $amount);
      $stmt->bindParam(':ackN', $accountnumber);
      $stmt->bindParam(':Bname', $bankname);
      $stmt->bindParam(':ackName', $ackName);
      $stmt->bindParam(':time', $time);
      if ($stmt->execute()) {

         $mailer = new Mailer;
         $userInfo  = $mints->getUserData($usertoken);

         $mailer->withdrawalNotice($userInfo['fname'], $amount, $accountnumber, $ackName); #alert admin of withdrawal notice
        $this->outputData(true, 'Request sent... you would be notified upon approval', null);
        exit;
      } else {

        $this->outputData(false, 'Unable to process transaction', null);
        return false;
      }
    }
  }


  public function getAllWithdraws()
  {

    $dataArray = array();
    try {

      $sql = " SELECT  *  FROM tblwithdraw  ";
      $sql .= " ORDER BY id DESC";
      $stmt = $this->connect()
        ->prepare($sql);

      if (!$stmt->execute()) {
        $stmt = null;
        $_SESSION['err'] = "Something went wrong, please try again..";
        return false;
      } else {
        $getUserInfo = new crownFund;
        if ($notifyArray = $stmt->fetchAll(PDO::FETCH_ASSOC)) {
          foreach ($notifyArray as $user) {
            $getUserData = $getUserInfo->getUserData($user['usertoken']);
            $array = array(
              'withdrawToken' => $user['id'],
              'usertoken' => $user['usertoken'],
              'fullname' => $getUserData['fname'] . '' . $getUserData['lname'],
              'amount' => $user['amount'],
              'accountNumber' => $user['ackN'],
              'Bankname' => $user['Bname'],
              'status' => $this->verifyStatus($user['status']),
              'convertedTime' => date("D d M, Y: H", $user['time'])
            );
            array_push($dataArray, $array);
          }

          return $dataArray;
        } else {
          return false;
        }
      }
    } catch (PDOException $e) {
      echo  $_SESSION['err'] = $e->getMessage();
      return false;
    }
  }

  public function getUsersWithdrawalRequest($usertoken)
  {

    $dataArray = array();
    try {

      $sql = " SELECT  *  FROM tblwithdraw WHERE usertoken = '$usertoken' ";
      $sql .= " ORDER BY id DESC";
      $stmt = $this->connect()
        ->prepare($sql);

      if (!$stmt->execute()) {
        $stmt = null;
        $_SESSION['err'] = "Something went wrong, please try again..";
        return false;
      } else {
        $getUserInfo = new crownFund;
        if ($notifyArray = $stmt->fetchAll(PDO::FETCH_ASSOC)) {
          foreach ($notifyArray as $user) {
            $getUserData = $getUserInfo->getUserData($user['usertoken']);
            $array = array(
              'withdrawToken' => $user['id'],
              'usertoken' => $user['usertoken'],
              'fullname' => $getUserData['fname'] . '' . $getUserData['lname'],
              'amount' => $user['amount'],
              'accountNumber' => $user['ackN'],
              'Bankname' => $user['Bname'],
              'status' => $this->verifyStatus($user['status']),
              'convertedTime' => date("D d M, Y: H", $user['time'])
            );
            array_push($dataArray, $array);
          }

          return $dataArray;
        } else {
          return false;
        }
      }
    } catch (PDOException $e) {
      echo  $_SESSION['err'] = $e->getMessage();
      return false;
    }
  }

  public function verifyStatus($status)
  {

    if ($status == 1) {

      return  "Approved";
    } else if ($status == 2) {
      return  "Declined";
    } else {

      return "pending";
    }
  }

  
    public function approveWithdrawal($adminToken, $amount,  $userToken, $withdrawToken)
  {

    $this->updateWithrawalStatus($userToken, $withdrawToken);

    $reference =  mt_rand(00000, 99999);

    $getUserInfo = new crownFund;

    $mailer = new mailer;

    $getUserData = $getUserInfo->getUserData($userToken);

    $this->recordDebitRecord($reference, $userToken, $amount, $getUserData['mail'],  $reference);

     $mailer->approvalWithdrawalNotice($getUserData['fname'], $getUserData['mail'], number_format($amount, 2));
    
    $this->outputData(true, 'Approved', null);

    exit;
  }


  
  public function declineWithdrawalRequest($adminToken,   $userToken, $withdrawToken)
  {

   $this->declineRequest($userToken, $withdrawToken);

    $getUserInfo = new crownFund;

    $getUserData = $getUserInfo->getUserData($userToken);

    $mailer = new Mailer;

    $mailer->disapprovalWithdrawalNotice($getUserData['fname'], $getUserData['mail']);

    $this->outputData(true, 'Declined', null);

    return true;
  }

  public function recordDebitRecord($ref, $usertoken, $amount, $email, $token)
  {

    $time  = time();

    $sql = " INSERT INTO tblwallettransaction(ref, usertoken, amount, mail, paid_at, token, type)
          VALUES('$ref', '$usertoken', '$amount', '$email', '$time', '$token', 'Debit')";
    $stmt = $this->connect()
      ->prepare($sql);
    if (!$stmt->execute()) {
      return false;
    } else {
      $this->debitUser($amount, $usertoken);
      return true;
    }
  }

  public function debitUser($amount, $usertoken)
  {

    $sql = " UPDATE  tblwallet SET amount = amount - '$amount' ";
    $sql .= " WHERE usertoken= '{$usertoken}' ";
    $stmt = $this->connect()
      ->prepare($sql);
    if (!$stmt->execute()) {
      return false;
      $stmt = null;
    } else {
      $stmt = null;
      return true;
    }
  }

  public function updateWithrawalStatus($userToken, $withdrawToken)
  {
    $sql = " UPDATE tblwithdraw SET status =  1  WHERE usertoken = '{$userToken}' ";
    $sql .= " AND id = '{$withdrawToken}' ";
    $stmt =  $this->connect()->prepare($sql);
    if (!$stmt->execute()) {
      return false;
    } else {

      return true;
    }
  }

  public function declineRequest($userToken, $withdrawToken)
  {
    $sql = " UPDATE tblwithdraw SET status =  2 WHERE usertoken = '{$userToken}' ";
    $sql .= " AND id = '{$withdrawToken}' ";
    $stmt =  $this->connect()->prepare($sql);
    if (!$stmt->execute()) {
      return false;
    } else {

      return true;
    }
  }
  public function outputData($success = null, $message = null, $data = null)
  {
    $arr_output = array(
      'success' => $success,
      'message' => $message,
      'data' => $data,
    );
    echo json_encode($arr_output);
  }
}
