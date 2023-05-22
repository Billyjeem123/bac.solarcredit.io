<?php
$db = new Database();
$ProductRepayment = new ProductRepayment($db);
$autoDebitLoanAfterDueDate = $ProductRepayment->autoDebitLoanAfterDueDate();
if($autoDebitLoanAfterDueDate){
    
    $ProductRepayment->outputData(true, "CRON job successfully executed,  No errors were encountered on ->autoDebitProductLoanAfterDueDate Endpoint<-", $autoDebitLoanAfterDueDate);
}else{
    $ProductRepayment->outputData(false, "No cron job schedule available on ->autoDebitProductLoanAfterDueDate Endpoint<-", $autoDebitLoanAfterDueDate);
}

