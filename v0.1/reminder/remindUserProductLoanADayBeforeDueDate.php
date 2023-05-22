<?php
header('Content-Type: application/json charset=utf-8');
header("Access-Control-Allow-Methods: PUT, GET, POST");
$db = new Database();
$ProductRepayment = new ProductRepayment($db);
$remindADayBeforeDueDate = $ProductRepayment->remindADayBeforeDueDate();
if($remindADayBeforeDueDate){
    
    $ProductRepayment->outputData(true, "CRON job successfully executed,  No errors were encountered on ->remindUserProductLoanADayBeforeDueDate Endpoint<-", $remindADayBeforeDueDate);
}else{
    $ProductRepayment->outputData(false, "No cron job schedule available on ->remindUserProductLoanADayBeforeDueDate Endpoint<-", $remindADayBeforeDueDate);
}

