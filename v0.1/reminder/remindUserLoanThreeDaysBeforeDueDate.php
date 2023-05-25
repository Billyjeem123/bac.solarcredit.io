<?php
$db = new Database();
$LoanRepayment = new LoanRepayment($db);
$remindThreeDaysBeforeDueDate = $LoanRepayment->remindThreeDaysBeforeDueDate();
if($remindThreeDaysBeforeDueDate){
    
    $LoanRepayment->outputData(true, "CRON job successfully executed,  No errors were encountered on ->remindUserLoanThreeDaysBeforeDueDate Endpoint<-", $remindAWeekBeforeDueDate);
}else{
    $LoanRepayment->outputData(false, "No cron job schedule available on ->remindUserLoanThreeDaysBeforeDueDate Endpoint<-", $remindAWeekBeforeDueDate);
}

