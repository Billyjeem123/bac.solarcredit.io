<?php
$db = new Database();
$LoanRepayment = new LoanRepayment($db);
$remindAWeekBeforeDueDate = $LoanRepayment->remindAWeekBeforeDueDate();
if($remindAWeekBeforeDueDate){
    
    $LoanRepayment->outputData(true, "CRON job successfully executed,  No errors were encountered on ->remindAWeekBeforeDueDate Endpoint<-", $remindADayBeforeDueDate);
}else{
    $LoanRepayment->outputData(false, "No cron job schedule available on ->remindAWeekBeforeDueDate Endpoint<-", $remindADayBeforeDueDate);
}

