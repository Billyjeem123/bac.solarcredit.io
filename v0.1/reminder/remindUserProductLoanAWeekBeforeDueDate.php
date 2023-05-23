<?php
header('Content-Type: application/json charset=utf-8');
header("Access-Control-Allow-Methods: PUT, GET, POST");
$db = new Database();
$ProductRepayment = new ProductRepayment($db);
$remindAWeekBeforeDueDate = $ProductRepayment->remindAWeekBeforeDueDate();
if($remindAWeekBeforeDueDate){
    
    $ProductRepayment->outputData(true, "CRON job successfully executed,  No errors were encountered on ->remindUserProductLoanAWeekBeforeDueDate Endpoint<-", $remindAWeekBeforeDueDate);
}else{
    $ProductRepayment->outputData(false, "No cron job schedule available on ->remindUserProductLoanAWeekBeforeDueDate Endpoint<-", $remindAWeekBeforeDueDate);
}

