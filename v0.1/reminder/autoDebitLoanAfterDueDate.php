<?php
header('Content-Type: application/json charset=utf-8');
header("Access-Control-Allow-Methods: PUT, GET, POST");
$db = new Database();
$LoanRepayment = new LoanRepayment($db);
$autoDebitLoanAfterDueDate = $LoanRepayment->autoDebitLoanAfterDueDate();
if($autoDebitLoanAfterDueDate){
    
    $LoanRepayment->outputData(true, "CRON job successfully executed,  No errors were encountered on ->autoDebitLoanAfterDueDate Endpoint<-", $autoDebitLoanAfterDueDate);
}else{
    $LoanRepayment->outputData(false, "No cron job schedule available on ->autoDebitLoanAfterDueDate Endpoint<-", $autoDebitLoanAfterDueDate);
}

