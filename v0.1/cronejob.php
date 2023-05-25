<?php

require('assets/initializer.php');
require(dirname(__DIR__).'/vendor/autoload.php');
#Loan crone jobs codes.
require_once('reminder/autoDebitLoan.php');
require_once('reminder/autoDebitLoanAfterDueDate.php');
require_once('reminder/remindUserLoanADayBeforeDueDate.php');
require_once('reminder/remindUserLoanAWeekBeforeDueDate.php');
require_once('reminder/celebrateLoanSuccess.php');

#ProductRepayment crone jobs codes.

require_once('reminder/autoDebitProductLoan.php');
require_once('reminder/autoDebitProductLoanAfterDueDate.php');
require_once('reminder/remindUserProductLoanADayBeforeDueDate.php');
require_once('reminder/remindUserProductLoanAWeekBeforeDueDate.php');
require_once('reminder/remindUserProductLoanAWeekBeforeDueDate.php');
require_once('reminder/remindUserProductLoanThreeDaysBeforeDueDate.php');
require_once('reminder/celebrateProductLoanSuccess.php');


#https://bac.solarcredit.io/v0.1/cronejob.php
