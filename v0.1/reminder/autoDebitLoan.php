<?php
header('Content-Type: application/json charset=utf-8');
header("Access-Control-Allow-Methods: PUT, GET, POST");
$db = new Database();
$LoanRepayment = new LoanRepayment($db);
$handleUserRepaymentSchedule = $LoanRepayment->handleUserRepaymentSchedule();
if($handleUserRepaymentSchedule){
    
    $LoanRepayment->outputData(true, "CRON job successfully executed,  No errors were encountered on ->autoDebitLoan Endpoint<-", $handleUserRepaymentSchedule);
}else{
    $LoanRepayment->outputData(false, "No cron job schedule available on ->autoDebitLoan Endpoint<-", $handleUserRepaymentSchedule);
}

