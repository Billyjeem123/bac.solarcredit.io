<?php
header('Content-Type: application/json charset=utf-8');
header("Access-Control-Allow-Methods: PUT, GET, POST");
$db = new Database();
$ProductRepayment = new ProductRepayment($db);
$handleUserRepaymentSchedule = $ProductRepayment->handleUserRepaymentSchedule();
if($handleUserRepaymentSchedule){
    
    $ProductRepayment->outputData(true, "CRON job successfully executed,  No errors were encountered on ->autoDebitProductLoan Endpoint<-", $handleUserRepaymentSchedule);
}else{
    $ProductRepayment->outputData(false, "No cron job schedule available on ->autoDebitProductLoan Endpoint<-", $handleUserRepaymentSchedule);
}

