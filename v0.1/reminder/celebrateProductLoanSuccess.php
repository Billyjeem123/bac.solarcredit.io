<?php
header('Content-Type: application/json charset=utf-8');
header("Access-Control-Allow-Methods: PUT, GET, POST");
$db = new Database();
$LoanRepayment = new LoanRepayment($db);
$celebrateLoanSuccess = $LoanRepayment->celebrateLoanSuccess();
if($celebrateLoanSuccess){
    
    $LoanRepayment->outputData(true, "CRON job successfully executed,  No errors were encountered on ->celebrateLoanSuccess Endpoint<-", $celebrateLoanSuccess);
}else{
    $LoanRepayment->outputData(false, "No cron job schedule available on ->celebrateLoanSuccess Endpoint<-", $celebrateLoanSuccess);
}

