<?php
require_once('../../assets/initializer.php');
$data = (array) json_decode(file_get_contents('php://input'), true);

$payment = new Payment($db);

if (empty($data['reference'])) {
    $payment->outputData(false, 'Reference is missing', null);
    exit;
}
$mastercardPaymentVerificationMethod = $payment->processWallet($data['reference']);
$decodeMastercardVerificationMethod = json_decode($mastercardPaymentVerificationMethod, true);

if ($decodeMastercardVerificationMethod['status'] !== true) {
    $payment->outputData(false, 'Cannot process transaction', null);
    exit;
}

$auth = new Auth($db);

if (!$auth->authenticateUser($data['usertoken'])) {
    $auth->outputData(false, $_SESSION['err'], null);
    unset($auth);
    exit;
}


# Prepare to check for Installmentally-Plan value
$checkSubscribedPlan = $payment->checkSubscribedPlan(intval($data['package']));
if (!$checkSubscribedPlan) {
    $payment->outputData(false, $_SESSION['err'], null);
    exit;
}

#calculateAmountRemaining ::Check  and calculat  half of the payment.
$calculateAmountRemaining = $payment->calculateAmountRemaining($data['Totalprice']);
if ($data['amountPaid'] <  $calculateAmountRemaining) {
    $payment->outputData(false, "Unable to process transaction.You must pay at least {$payment->formatCurrency($calculateAmountRemaining)} to process transaction", null);
    exit;
}

# Save usertoken of buyer during purchase.
$token  =  (int)$payment->token();
if (!$payment->saveUserTokenDuringPurchase($token,  $data['usertoken'], $_ENV['PAYMENT_OPTION'] = "Paid-Installmentally")) {
    $payment->outputData(false, $_SESSION['err'], null);
    exit;
}

$calculateDateAhead = $payment->calculateDateAhead(+$checkSubscribedPlan['planid'] . '' . "month");

$calculateMonthlyRepayment = $payment->calculateMonthlyRepayment($calculateAmountRemaining, $checkSubscribedPlan['planid']);

# Save product bought installmentally info 
$saveAllProductBoughtInstallmentally = $payment->saveAllProductBoughtInstallmentally(
    $data['Totalprice'],
    $data['amountPaid'],
    $data['amountPaid'],
    $checkSubscribedPlan['planid'],
    $calculateMonthlyRepayment,
    $token,
    $calculateDateAhead,
    $data['usertoken']

);

if (!$saveAllProductBoughtInstallmentally) {
    $payment->outputData(false, $_SESSION['err'], null);
    exit;
}

#calculateMonthsAheadAndSave:: Calulation month duration

$calculateMonthsAheadAndSave = $payment->calculateMonthsAheadAndSave(
    $token,
    $data['usertoken'],
    $checkSubscribedPlan['planid'],
    $calculateMonthlyRepayment

);
if (!$calculateMonthsAheadAndSave) {
    $payment->outputData(false, $_SESSION['err'], null);
    exit;
}

$mailer = new Mailer();
$count = 0;

foreach ($data['productBought'] as $key => $allProducts) {

    if ($allProducts['productType'] === "Userproduct") {

        # To future dev reading this code, In the later on,  
        # we might need to alert product owners for the code below, but right now i think we shouldn't.
        # Just in case the project ownsers asked for this,worry no more. just uncomment this code, if need be.
        # It should definely do the work for you.
        # I hope i helped you . 

        $ownerRecord = $payment->getUserdata($allProducts['ownertoken']);

        try {
            $mailer->notifyOwnersOfSale($ownerRecord['mail'], $ownerRecord['fname'], $allProducts['productname']);
        } catch (Exception $e) {
            # Handle the error or log it as needed
            $errorMessage = date('[Y-m-d H:i:s] ') . "Error sending mail  for " . __METHOD__ . "  " . PHP_EOL . $e->getMessage();
            error_log($errorMessage, 3, 'mail.log');
        }

        $saveProductInstallments = $payment->saveProductInstallments(
            $token,
            $allProducts['productToken'],
            $allProducts['productQuantity'],
            $allProducts['productname'],
            $allProducts['productPrice'],
            $allProducts['productType'],
            $_ENV['MODE_OF_PAYMENT'] = "Mastercard",
            $allProducts['productimage']

        );

        if (!$saveProductInstallments) {
            $payment->outputData(false, $_SESSION['err'], null);
            exit;
        }

        $count++;
    } else {
        $count = 0;

        $saveProductInstallments = $payment->saveProductInstallments(
            $token,
            $allProducts['productToken'],
            $allProducts['productQuantity'],
            $allProducts['productname'],
            $allProducts['productPrice'],
            $allProducts['productType'],
            $_ENV['MODE_OF_PAYMENT'] = "Mastercard",
            $allProducts['productimage']

        );

        if (!$saveProductInstallments) {
            $payment->outputData(false, $_SESSION['err'], null);
            exit;
        }
        $count++;
    }
    $removeProductFromCart = $payment->removeProductFromCart($data['usertoken'],  $allProducts['productToken']);
    if (!$removeProductFromCart) {
        $payment->outputData(false, $_SESSION['err'], null);
        exit;
    }
}

            #Send Notofication to admin of new purchase alert.

        try {
            $sendMailToAdmin =  $mailer->confirmPaymentInstallmentallyPurchaseToAdmin($data['productBought'], $payment->formatCurrency($data['Totalprice']), $data['amountPaid']);
        } catch (Exception $e) {
            # Handle the error or log it as needed
            $errorMessage = date('[Y-m-d H:i:s] ') . "Error sending mail  for " . __METHOD__ . "  " . PHP_EOL . $e->getMessage();
            error_log($errorMessage, 3, 'mail.log');
        }
        # Release the resources used by the Payment instance

$payment->outputData(true, 'Transaction sucessfull', null);
unset($payment);
unset($auth);
