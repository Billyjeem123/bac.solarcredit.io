<?php
header('Content-Type: application/json charset=utf-8');
header("Access-Control-Allow-Methods: PUT, GET, POST");
$db = new Database();
$LoanRepayment = new LoanRepayment($db);
$remindADayBeforeDueDate = $LoanRepayment->remindADayBeforeDueDate();
if($remindADayBeforeDueDate){
    
    $LoanRepayment->outputData(true, "CRON job successfully executed,  No errors were encountered on ->remindUserLoanADayBeforeDueDate Endpoint<-", $remindADayBeforeDueDate);
}else{
    $LoanRepayment->outputData(false, "No cron job schedule available on ->remindUserLoanADayBeforeDueDate Endpoint<-", $remindADayBeforeDueDate);
}

