<?php

use PHPMailer\PHPMailer\PHPMailer;

class Mailer
{

    public function sendOTPToken($email, $fname, $otp)
    {

        require_once($_SERVER['DOCUMENT_ROOT'] . '/solar/vendor/autoload.php');

        $mail = new PHPMailer(true);

        $mail->isSMTP();

        // $mail->SMTPDebug = 2;
        $mail->Host = $_ENV['HOST_NAME'];

        $mail->SMTPAuth = true;

        $mail->Username = $_ENV['SMTP_USERNAME'];

        $mail->Password = $_ENV['SMTP_PWORD'];

        $mail->SMTPSecure = 'ssl';

        $mail->Port = 465;

        $mail->setFrom($_ENV['APP_MAIL'], $_ENV['APP_NAME']);

        $mail->addAddress($email, $fname);

        $mail->isHTML(true);

        $mail->Subject = ' [Solar credit] Account Verification';

        $body = "
        <html>
            <head>
                <style>
                    @media only screen and (max-width: 768px) {
                        h1 {
                            font-size: 15px;
                            margin-bottom: 20px;
                        }
                        p {
                            font-size: 14px;
                            margin-bottom: 15px;
                        }
                        .flop{
                            padding: 20px 10px;
                        }
                    }
                </style>
            </head>
            <body style='font-family: Arial,sans-serif; font-size: 14px;padding:30px; line-height: 1.6; color: #333; box-shadow: 0px 0px 10px #ccc;'>
                <div style=padding: 20px; class=flop>
               
                    <p style='font-size: 14px; margin-bottom: 20px;'>Dear $fname,</p>
                    <p style='font-size: 14px; margin-bottom: 20px;'>Thank you for creating an account with our platform. To ensure the security of your account, we require that you verify your email address by entering the OTP code below:</p>
                    <p style='font-size: 16px; margin-bottom: 20px;'><b>$otp</b></p>
                    <p style='font-size: 14px; margin-bottom: 20px;'>Please enter the OTP code in the provided field on the account verification page. If you did not initiate this request, please ignore this email.</p>
                    <p style='font-size: 14px; margin-bottom: 20px;'>Thank you for your cooperation.</p>
                    <p style='font-size: 14px; margin-bottom: 20px;'>Best regards,</p>
                    <p style='font-size: 14px; margin-bottom: 20px;'>Team  " . $_ENV['APP_NAME'] . "</p>
                </div>
            </body>
        </html>
    ";

        $mail->Body = $body;

        if (!$mail->send()) {
            echo 'sent';
        } else {
            return true;
        }
    }

    public function sendPasswordToUser($email, $fname, $token)
    {

        require_once($_SERVER['DOCUMENT_ROOT'] . '/boiler/vendor/autoload.php');

        $mail = new PHPMailer(true);

        $mail->isSMTP();

        // $mail->SMTPDebug = 2;
        $mail->Host = $_ENV['HOST_NAME'];

        $mail->SMTPAuth = true;

        $mail->Username = $_ENV['SMTP_USERNAME'];

        $mail->Password = $_ENV['SMTP_PWORD'];

        $mail->SMTPSecure = 'ssl';

        $mail->Port = 465;

        $mail->setFrom($_ENV['APP_MAIL'], $_ENV['APP_NAME']);

        $mail->addAddress($email, $fname);

        $mail->isHTML(true);

        $mail->Subject =  ' ' . $_ENV['APP_NAME'] . '  Reset Your Password';

        $body = "
           <html>
               <head>
                   <style>
                       @media only screen and (max-width: 768px) {
                           h1 {
                               font-size: 15px;
                               margin-bottom: 20px;
                           }
                           p {
                               font-size: 14px;
                               margin-bottom: 15px;
                           }
                       }
                   </style>
               </head>
               <body style='font-family: Arial,sans-serif; font-size: 14px;padding:30px; line-height: 1.6; color: #333; box-shadow: 0px 0px 10px #ccc;'>
                   <div style='padding: 20px;'>
          
                       <p style='font-size: 14px; margin-bottom: 20px;'>Dear $fname,</p>
                       <p style='font-size: 14px; margin-bottom: 20px;'>We received a request to reset the password for your account. If you did not request this, please ignore this email.</p>
                       
                       <p style='font-size: 14px; margin-bottom: 20px;'>Password: [Default Password $token]</p>
                       <p style='font-size: 14px; margin-bottom: 20px;'>To reset your password, please follow these steps:</p>
                       <p style='font-size: 14px; margin-bottom: 20px;'>1) Go to the login page on our website</p>
                       <p style='font-size: 14px; margin-bottom: 20px;'>2) Click on the Forgot password link</p>
                       <p style='font-size: 14px; margin-bottom: 20px;'>3) Enter your email address and click Submit</p>
                       <p style='font-size: 14px; margin-bottom: 20px;'>4) Check your email for further instructions on how to reset your password</p>
                       <p style='font-size: 14px; margin-bottom: 20px;'>5) After following this procedure,a new password will be sent to your mail</p>
                       <p style='font-size: 14px; margin-bottom: 20px;'>Please note that this is a default password token and we strongly recommend that you change it after login for security purposes. You can change your password by logging in to your account and updating your account settings</p>
                       <p style='font-size: 14px; margin-bottom: 20px;'>If you have any questions or concerns, please do not hesitate to contact us</p>
                       <p style='font-size: 14px; margin-bottom: 20px;'>Thank you again for joining us!</p>
                       <p style='font-size: 14px; margin-bottom: 20px;'>Best regards,</p>
                       <p style='font-size: 14px; margin-bottom: 20px;'>Team  "  . $_ENV['APP_NAME'] . "</p>
                   </div>
               </body>
           </html>
        ";

        $mail->Body = $body;

        if (!$mail->send()) {
            echo 'sent';
        } else {
            return true;
        }
    }

    public function alertAdminOfProductFromUser($productname, $productQuantity)
    {

        require_once($_SERVER['DOCUMENT_ROOT'] . '/solar/vendor/autoload.php');

        $mail = new PHPMailer(true);

        $mail->isSMTP();

        $mail->Host = $_ENV['HOST_NAME'];

        $mail->SMTPAuth = true;

        $mail->Username = $_ENV['SMTP_USERNAME'];

        $mail->Password = $_ENV['SMTP_PWORD'];

        $mail->SMTPSecure = 'ssl';

        $mail->Port = 465;

        $mail->setFrom($_ENV['APP_MAIL'], $_ENV['APP_NAME']);

        $mail->addAddress($_ENV['APP_MAIL'], 'Admin');

        $mail->isHTML(true);

        $mail->Subject = ' [Solar-credit] New Product Request Alert';

        $body = 'Dear Admin, <br><br>';

        $body .= 'We hope this email finds you well. This email is to inform you that a new product request has been made on the  website. Please find the details of the request below..<br><br>';

        $body .= "Product Name: $productname. <br><br>.";

        $body .= "Product Quantity: $productQuantity. <br><br>";

        $body .= ' Please take the necessary actions to fulfill this request as soon as possible. <br><br>';

        $body .= 'Best regards, <br><br>';

        $body .= "Team  {$_ENV['APP_NAME']}";

        $mail->Body = $body;

        if (!$mail->send()) {
            $this->outputData(false, ' Email could not be sent ', $mail->ErrorInfo);
        } else {
            return true;
        }
    }

    public function sendApprovalNotification($email, $fname)
    {

        require_once($_SERVER['DOCUMENT_ROOT'] . '/solar/vendor/autoload.php');

        $mail = new PHPMailer(true);

        $mail->isSMTP();

        $mail->Host = $_ENV['HOST_NAME'];

        $mail->SMTPAuth = true;

        $mail->Username = $_ENV['SMTP_USERNAME'];

        $mail->Password = $_ENV['SMTP_PWORD'];

        $mail->SMTPSecure = 'ssl';

        $mail->Port = 465;

        $mail->setFrom($_ENV['APP_MAIL'], $_ENV['APP_NAME']);

        $mail->addAddress($email, $fname);

        $mail->isHTML(true);

        $mail->Subject = ' [Solar-credit] Your Product Request Has Been Approved';

        $body = "Dear $fname, <br><br>";

        $body .= 'We are pleased to inform you that your recent product request has been approved. This means that your product has met our quality standards and is now ready to be sold in our marketplace..<br><br>';

        $body .= "We believe that your product has the potential to make a significant impact in our community, and we're excited to be a part of your journey.
        In the meantime, please feel free to reach out to us if you have any questions or concerns. Our team is always here to help<br><br>";

        $body .= 'Thank you for choosing to work with us. We look forward to seeing the impact your product will make in our community.<br><br>';

        $body .= 'Best regards, <br><br>';

        $body .= "Team  {$_ENV['APP_NAME']} .";

        $mail->Body = $body;

        if (!$mail->send()) {
            $this->outputData(false, ' Email could not be sent ', $mail->ErrorInfo);
        } else {
            return true;
        }
    }

    public function sendDisaprovalNotification($email, $fname)
    {

        require_once($_SERVER['DOCUMENT_ROOT'] . '/solar/vendor/autoload.php');

        $mail = new PHPMailer(true);

        $mail->isSMTP();

        $mail->Host = $_ENV['HOST_NAME'];

        $mail->SMTPAuth = true;

        $mail->Username = $_ENV['SMTP_USERNAME'];

        $mail->Password = $_ENV['SMTP_PWORD'];

        $mail->SMTPSecure = 'ssl';

        $mail->Port = 465;

        $mail->setFrom($_ENV['APP_MAIL'], $_ENV['APP_NAME']);

        $mail->addAddress($email, $fname);

        $mail->isHTML(true);

        $mail->Subject = ' [Solar-credit] Your Product Request Has Been Disapproved';

        $body = "Dear $fname, <br><br>";

        $body .= 'We regret to inform you that your recent product request has been disapproved. Our team of experts has reviewed your product thoroughly and determined that it does not meet our quality standards...<br><br>';

        $body .= 'Please note that this decision was not taken lightly, and we understand how disappointing it can be to receive this news. We encourage you to keep working on your product and making improvements to meet our standards.<br><br>';

        $body .= 'If you have any questions or concerns about why your product was disapproved, please feel free to reach out to us. Our team will be happy to provide you with detailed feedback and guidance on how to improve your product.<br><br>';

        $body .= 'Thank you for your interest in working with us. We wish you all the best with your future endeavors.<br><br>';

        $body .= 'Best regards, <br><br>';

        $body .= "Team  {$_ENV['APP_NAME']} .";

        $mail->Body = $body;

        if (!$mail->send()) {
            $this->outputData(false, ' Email could not be sent ', $mail->ErrorInfo);
        } else {
            return true;
        }
    }

    public function sendLoanNotificationToAdmin($loanApplicant)
    {

        require_once($_SERVER['DOCUMENT_ROOT'] . '/solar/vendor/autoload.php');

        $mail = new PHPMailer(true);

        $mail->isSMTP();

        $mail->Host = $_ENV['HOST_NAME'];

        $mail->SMTPAuth = true;

        $mail->Username = $_ENV['SMTP_USERNAME'];

        $mail->Password = $_ENV['SMTP_PWORD'];

        $mail->SMTPSecure = 'ssl';

        $mail->Port = 465;

        $mail->setFrom($_ENV['APP_MAIL'], $_ENV['APP_NAME']);

        $mail->addAddress($_ENV['APP_MAIL'], $_ENV['APP_NAME']);

        $mail->isHTML(true);

        $mail->Subject = ' [Solar-credit] New Loan Request';

        $body = 'Dear Admin, <br><br>';

        $body .= "We hope this email finds you well. we am writing to inform you that a new loan request has been submitted on the platform. The request was made by $loanApplicant..<br><br>";

        $body .= 'Please review the loan request and take appropriate action<br><br>';

        $body .= 'Best regards, <br><br>';

        $body .= "Team  {$_ENV['APP_NAME']} .";

        $mail->Body = $body;

        if (!$mail->send()) {
            $this->outputData(false, ' Email could not be sent ', $mail->ErrorInfo);
        } else {
            return true;
        }
    }

    public function notifyLoanApproval($email, $fname)
    {

        require_once($_SERVER['DOCUMENT_ROOT'] . '/solar/vendor/autoload.php');

        $mail = new PHPMailer(true);

        $mail->isSMTP();

        $mail->Host = $_ENV['HOST_NAME'];

        $mail->SMTPAuth = true;

        $mail->Username = $_ENV['SMTP_USERNAME'];

        $mail->Password = $_ENV['SMTP_PWORD'];

        $mail->SMTPSecure = 'ssl';

        $mail->Port = 465;

        $mail->setFrom($_ENV['APP_MAIL'], $_ENV['APP_NAME']);

        $mail->addAddress($email, $fname);

        $mail->isHTML(true);

        $mail->Subject = ' [Solar-credit] Your Loan Has Been Approved';

        $body = "Dear $fname, <br><br>";

        $body .= 'We are pleased to inform you that your loan application has been approved! We appreciate your business and hope that this loan will help you achieve your financial goals..<br><br>';

        $body .= 'To ensure timely payments and avoid any penalties, we kindly remind you to regularly check your dashboard and keep track of your loan details. You can easily access and monitor your loan records on your dashboard, so please make sure to do so regularly. Thank you for your cooperation.<br><br>';

        $body .= "If you have any questions or concerns, please don't hesitate to contact our customer support team<br><br>";

        $body .= 'Best regards, <br><br>';

        $body .= "Team  {$_ENV['APP_NAME']} .";

        $mail->Body = $body;

        if (!$mail->send()) {
            $this->outputData(false, ' Email could not be sent ', $mail->ErrorInfo);
            exit;
        }
        return true;
    }

    public function notifyLoanDisapproval($email, $fname, $message)
    {

        require_once($_SERVER['DOCUMENT_ROOT'] . '/solar/vendor/autoload.php');

        $mail = new PHPMailer(true);

        $mail->isSMTP();

        $mail->Host = $_ENV['HOST_NAME'];

        $mail->SMTPAuth = true;

        $mail->Username = $_ENV['SMTP_USERNAME'];

        $mail->Password = $_ENV['SMTP_PWORD'];

        $mail->SMTPSecure = 'ssl';

        $mail->Port = 465;

        $mail->setFrom($_ENV['APP_MAIL'], $_ENV['APP_NAME']);

        $mail->addAddress($email, $fname);

        $mail->isHTML(true);

        $mail->Subject = ' [Solar-credit] Your Loan Has Been Disapproved';

        $body = "Dear $fname, <br><br>";

        $body .= 'We regret to inform you that your recent loan application has been disapproved. We understand that this news may be disappointing, but we want to assure you that our decision was based on a careful evaluation of your application and our lending policies.<br><br>';
        
        $body .= 'After a thorough review, we have determined that the reason for the disapproval of your loan application is: '. $message.' This decision was made in accordance with our internal guidelines and criteria.<br><br>';
        
        $body .= 'Please note that the disapproval of your loan application does not reflect on your creditworthiness or financial situation. We encourage you to continue to explore other options that may be available to you.<br><br>';
        
        $body .= "If you have any questions about the reasons for the disapproval of your application, please don't hesitate to contact our customer support team. We will do our best to provide you with a clear explanation of our decision.<br><br>";
        
        $body .= ' We appreciate your interest in our lending service and hope that you will consider us for your future financial need. <br><br>';

        $body .= 'Best regards, <br><br>';

        $body .= "Team  {$_ENV['APP_NAME']} .";

        $mail->Body = $body;

        if (!$mail->send()) {
            $this->outputData(false, ' Email could not be sent ', $mail->ErrorInfo);
            exit;
        }
        return true;
    }

    public function notifyOwnersOfSale($email, $fname, $productname)
    {

        require_once($_SERVER['DOCUMENT_ROOT'] . '/solar/vendor/autoload.php');

        $mail = new PHPMailer(true);

        $mail->isSMTP();

        $mail->Host = $_ENV['HOST_NAME'];

        $mail->SMTPAuth = true;

        $mail->Username = $_ENV['SMTP_USERNAME'];

        $mail->Password = $_ENV['SMTP_PWORD'];

        $mail->SMTPSecure = 'ssl';

        $mail->Port = 465;

        $mail->setFrom($_ENV['APP_MAIL'], $_ENV['APP_NAME']);

        $mail->addAddress($email, $fname);

        $mail->isHTML(true);

        $mail->Subject = ' [Solar-credit] Sold Out';

        $body = "Dear $fname, <br><br>";

        $body .= 'We hope this message finds you well. We wanted to take a moment to inform you that one of your products has been purchased on our platform. Congratulations on the successful sale..<br><br>';

        $body .= "Your product, $productname, was recently bought by a customer who was interested in your item. We're thrilled that you're part of our platform and that your products are resonating with our users.<br><br>";

        $body .= "We're so glad that you've chosen our platform to showcase and sell your products. Your participation helps make our marketplace a great place for both sellers and buyers to connect and engage.<br><br>";

        $body .= " If you have any questions or concerns, please don't hesitate to reach out to us. We're always here to help<br> <br>";

        $body .= ' Thanks again for being part of our community, and we look forward to your continued success!<br> <br>';

        $body .= 'Best regards, <br><br>';

        $body .= "Team  {$_ENV['APP_NAME']} .";

        $mail->Body = $body;

        if (!$mail->send()) {
            $this->outputData(false, ' Email could not be sent ', $mail->ErrorInfo);
            exit;
        }
        return true;
    }

    public function confirmFullPaymentAndProductPurchaseToAdmin(array $boughtBought, $totalPrice)
    {

        $numberOfProducts = count($boughtBought);

        require_once($_SERVER['DOCUMENT_ROOT'] . '/solar/vendor/autoload.php');

        $mail = new PHPMailer(true);

        $mail->isSMTP();

        $mail->Host = $_ENV['HOST_NAME'];

        $mail->SMTPAuth = true;

        $mail->Username = $_ENV['SMTP_USERNAME'];

        $mail->Password = $_ENV['SMTP_PWORD'];

        $mail->SMTPSecure = 'ssl';

        $mail->Port = 465;

        $mail->setFrom($_ENV['APP_MAIL'], $_ENV['APP_NAME']);

        $mail->addAddress($_ENV['APP_MAIL'], $_ENV['APP_NAME']);

        $mail->isHTML(true);

        $mail->Subject = '[Solar-credit]  Product Purchase Alert ';

        $body = "We would like to inform you that $numberOfProducts products have been purchased  on the website. The following items have been purchased: <br><br>";

        foreach ($boughtBought as $product) {
            $body .= 'Product Name: <b>' . $product['productname'] . '</b><br>';
            $body .= 'Quantity: <b>' . $product['productQuantity'] . '</b><br>';
            $body .= 'Price: <b>' . $product['productPrice'] . '</b><br>';
            $body .= 'Product Type: <b>' . $product['productType'] . '</b><br><br>';
        }

        $body .= "Mode of payment:<b> . {$_ENV['MODE_OF_PAYMENT']} </b><br><br>";
        $body .= "Payment Option:<b>' . {$_ENV['PAYMENT_OPTION']} . '</b><br><br>";
        $body .= 'Total Price: <b>N ' . $totalPrice . '</b><br><br>';

        $mail->Body = $body;

        if (!$mail->send()) {
            $this->outputData(false, ' Email could not be sent', $mail->ErrorInfo);
            return false;
        } else {
            return true;
        }
    }

    public function sendProductPaidOnceApprovalNptofication($email, $fname, $productname)
    {

        require_once($_SERVER['DOCUMENT_ROOT'] . '/solar/vendor/autoload.php');

        $mail = new PHPMailer(true);

        $mail->isSMTP();

        $mail->Host = $_ENV['HOST_NAME'];

        $mail->SMTPAuth = true;

        $mail->Username = $_ENV['SMTP_USERNAME'];

        $mail->Password = $_ENV['SMTP_PWORD'];

        $mail->SMTPSecure = 'ssl';

        $mail->Port = 465;

        $mail->setFrom($_ENV['APP_MAIL'], $_ENV['APP_NAME']);

        $mail->addAddress($email, $fname);

        $mail->isHTML(true);

        $mail->Subject = '[Solar-credit]  Product Order Acknowledgment';

        $body = "Dear $fname, <br><br>";

        $body .= "We hope this email finds you in good health and spirits. This is to inform you that we have received your order for $productname. <br><br>";

        $body .= ' We would like to take this opportunity to thank you for choosing us and entrusting us with your purchase. Your order has been acknowledged and we are processing it now.<br> <br>';

        $body .= 'If you have any questions regarding your order, please do not hesitate to contact us. We will be more than happy to assist you. <br> <br> ';

        $body .= 'Once again, thank you for your order and we look forward to serving you soon. <br> <br>';

        $body .= ' Best regards, <br> <br> ';

        $body .= ' Team ' . $_ENV['APP_NAME'] . ' ';

        $mail->Body = $body;

        if (!$mail->send()) {
            $this->outputData(false, ' Email could not be sent', $mail->ErrorInfo);
            return false;
        } else {
            return true;
        }
    }

    public function confirmPaymentInstallmentallyPurchaseToAdmin(array $boughtBought, $totalPrice, $amountPid)
    {

        $numberOfProducts = count($boughtBought);

        require_once($_SERVER['DOCUMENT_ROOT'] . '/solar/vendor/autoload.php');

        $mail = new PHPMailer(true);

        $mail->isSMTP();

        $mail->Host = $_ENV['HOST_NAME'];

        $mail->SMTPAuth = true;

        $mail->Username = $_ENV['SMTP_USERNAME'];

        $mail->Password = $_ENV['SMTP_PWORD'];

        $mail->SMTPSecure = 'ssl';

        $mail->Port = 465;

        $mail->setFrom($_ENV['APP_MAIL'], $_ENV['APP_NAME']);

        $mail->addAddress($_ENV['APP_MAIL'], $_ENV['APP_NAME']);

        $mail->isHTML(true);

        $mail->Subject = '[Solar-credit]  Product Purchase Alert ';

        $body = "We would like to inform you that $numberOfProducts products have been purchased  on the website. The following items have been purchased: <br><br>";

        foreach ($boughtBought as $product) {
            $body .= 'Product Name: <b>' . $product['productname'] . '</b><br>';
            $body .= 'Quantity: <b>' . $product['productQuantity'] . '</b><br>';
            $body .= 'Price: <b>' . $product['productPrice'] . '</b><br>';
            $body .= 'Product Type: <b>' . $product['productType'] . '</b><br><br>';
        }

        $body .= "Mode of payment:<b> . {$_ENV['MODE_OF_PAYMENT']} </b><br><br>";
        $body .= "Payment Option:<b>' . {$_ENV['PAYMENT_OPTION']} . '</b><br><br>";
        $body .= 'Total Price: <b>N ' . $totalPrice . '</b><br><br>';
        $body .= 'Amount Paid: <b>N ' . $amountPid . '</b><br><br>';

        $mail->Body = $body;

        if (!$mail->send()) {
            $this->outputData(false, ' Email could not be sent', $mail->ErrorInfo);
            return false;
        } else {
            return true;
        }
    }

    public function SendProductInstallmentPaymentStatusNotification($email, $fname, $productname)
    {

        require_once($_SERVER['DOCUMENT_ROOT'] . '/solar/vendor/autoload.php');

        $mail = new PHPMailer(true);

        $mail->isSMTP();

        $mail->Host = $_ENV['HOST_NAME'];

        $mail->SMTPAuth = true;

        $mail->Username = $_ENV['SMTP_USERNAME'];

        $mail->Password = $_ENV['SMTP_PWORD'];

        $mail->SMTPSecure = 'ssl';

        $mail->Port = 465;

        $mail->setFrom($_ENV['APP_MAIL'], $_ENV['APP_NAME']);

        $mail->addAddress($email, $fname);

        $mail->isHTML(true);

        $mail->Subject = "[Solar-credit] Acknowledgement of Installment Payment for $productname";

        $body = "Dear $fname, <br><br>";

        $body .= "I hope this email finds you well. This is to acknowledge the receipt of your installment payment for the $productname  that you purchased from us.<br><br>";

        $body .= ' We appreciate your prompt payment and we are glad to inform you that the payment has been successfully processed. We would like to remind you that the remaining balance should be paid on the agreed schedule, as per our sales agreement.<br> <br>';

        $body .= 'Please do not hesitate to reach out to us if you have any questions or concerns regarding the installment plan or the product itself. We are always here to help.. <br> <br> ';

        $body .= 'Thank you for choosing ' . $_ENV['APP_NAME'] . ' for your purchase. We look forward to providing you with more excellent products and services in the future <br> <br>';

        $body .= ' Best regards, <br> <br> ';

        $body .= ' Team ' .  $_ENV['APP_NAME'] . ' ';

        $mail->Body = $body;

        if (!$mail->send()) {
            $this->outputData(false, ' Email could not be sent', $mail->ErrorInfo);
            return false;
        } else {
            return true;
        }
    }

    public function sendLoanPenaltyEmail($email, $fname, $amountOwed, $month)
    {

        require_once($_SERVER['DOCUMENT_ROOT'] . '/solar/vendor/autoload.php');

        $calculatePenalty =  $amountOwed * $_ENV['loan_penalty'];

        $new_amount = $amountOwed + $calculatePenalty;

        $mail = new PHPMailer(true);

        $mail->isSMTP();

        $mail->Host = $_ENV['HOST_NAME'];

        $mail->SMTPAuth = true;

        $mail->Username = $_ENV['SMTP_USERNAME'];

        $mail->Password = $_ENV['SMTP_PWORD'];

        $mail->SMTPSecure = 'ssl';

        $mail->Port = 465;

        $mail->setFrom($_ENV['APP_MAIL'], $_ENV['APP_NAME']);

        $mail->addAddress($email, $fname);

        $mail->isHTML(true);

        $mail->Subject = '[Solar-credit] Late Payment Penalty Notice';

        $body = "Dear $fname, <br><br>";

        $body .= 'We  hope this email finds you in good health and high spirits. we are writing to inform you that a penalty fee of 20% has been added to your balance due to non-payment on the agreed due date.<br><br>';

        $body .= " According to our records, The initial amount to pay for $month  is " . number_format($amountOwed, 2) .  ' . With the addition of the penalty fee, the new balance is now ' . number_format($new_amount, 2) . ',  <b>' . number_format($calculatePenalty, 2) .  ' .</b> , is the estimated    penalty fee <br><br>';

        $body .= ' Please make the necessary arrangements to settle the balance in full, including the penalty fee, as soon as possible...<br><br> ';

        $body .= 'Please do not hesitate to reach out to us if you have any questions or concerns regarding the installment plan. We are always here to help.. <br> <br> ';

        $body .= 'Thank you for choosing ' . $_ENV['APP_NAME'] . ' for your finacial needs.We look forward to hearing from you soon. <br> <br>';

        $body .= ' Best regards, <br> <br> ';

        $body .= ' Team ' .  $_ENV['APP_NAME'] . ' ';

        $mail->Body = $body;

        if (!$mail->send()) {
            $this->outputData(false, ' Email could not be sent', $mail->ErrorInfo);
            return false;
        } else {
            return true;
        }
    }

    public function notifyUserForDeductingLoanAmountFromWallet($email, $fname,  $amount,  $date)
    {
        require_once($_SERVER['DOCUMENT_ROOT'] . '/solar/vendor/autoload.php');

        $mail = new PHPMailer(true);

        $mail->isSMTP();

        $mail->Host = $_ENV['HOST_NAME'];

        $mail->SMTPAuth = true;

        $mail->Username = $_ENV['SMTP_USERNAME'];

        $mail->Password = $_ENV['SMTP_PWORD'];

        $mail->SMTPSecure = 'ssl';

        $mail->Port = 465;

        $mail->setFrom($_ENV['APP_MAIL'], $_ENV['APP_NAME']);

        $mail->addAddress($email, $fname);

        $mail->isHTML(true);

        $mail->Subject = '[Solar-credit] Notification: Funds removed from wallet';

        $body = "Dear $fname, <br><br>";

        $body .= 'We  hope this email finds you in good health and high spirits. we are writing to inform you that a recent transaction has resulted in a removal of funds from your wallet, towards the servicing of your loan.<br><br>';

        $body .= ' The amount of ' . number_format($amount, 2) .  " has been deducted from your account on $date<br><br>";

        $body .= 'This is part of your regular loan servicing schedule and is in line with the terms and conditions of your loan agreement...<br><br> ';

        $body .= 'Please do not hesitate to reach out to us if you have any questions or concerns regarding the installment plan. We are always here to help.. <br> <br> ';

        $body .= 'Thank you for choosing ' . $_ENV['APP_NAME'] . ' for your finacial needs. <br> <br>';

        $body .= ' Best regards, <br> <br> ';

        $body .= ' Team ' .  $_ENV['APP_NAME'] . ' ';

        $mail->Body = $body;

        if (!$mail->send()) {
            $this->outputData(false, ' Email could not be sent', $mail->ErrorInfo);
            return false;
        } else {
            return true;
        }
    }

    public function NotifyUserOfDebitAndPenaltyAmount($email, $fname, $month,  $amountTopay)
    {
        require_once($_SERVER['DOCUMENT_ROOT'] . '/solar/vendor/autoload.php');

        $calculatePenalty  =  $amountTopay * $_ENV['loan_penalty'];

        $new_amount = $amountTopay + $calculatePenalty;

        $mail = new PHPMailer(true);

        $mail->isSMTP();

        $mail->Host = $_ENV['HOST_NAME'];

        $mail->SMTPAuth = true;

        $mail->Username = $_ENV['SMTP_USERNAME'];

        $mail->Password = $_ENV['SMTP_PWORD'];

        $mail->SMTPSecure = 'ssl';

        $mail->Port = 465;

        $mail->setFrom($_ENV['APP_MAIL'], $_ENV['APP_NAME']);

        $mail->addAddress($email, $fname);

        $mail->isHTML(true);

        $mail->Subject = '[Solar-credit] Notification: Funds removed from wallet';

        $body = "Dear $fname, <br><br>";

        $body .= 'We hope this email finds you well. We are writing to inform you that your wallet has been debited with both an initial payment and a penalty payment..<br><br>';

        $body .= ' The initial payment of ' . number_format($amountTopay, 2) .  " has been deducted from your account as per the agreed terms and conditions of the contract. This payment was due on $month.<br><br>";

        $body .= ' Additionally, a penalty payment of  ' . number_format($calculatePenalty, 2) .  ' has been imposed as a result of a late payment. Please note that this penalty has been charged as per the terms of the agreement.<br><br> ';

        $body .= ' Total amount debited is  ' . number_format($new_amount, 2) .  '<br> <br>';

        $body .= ' We would like to remind you that timely payment of your bills is important to ensure the smooth functioning of our services. If you have any difficulty making a payment in the future, please reach out to us for assistance.<br> <br>';

        $body .= 'Please do not hesitate to reach out to us if you have any questions or concerns regarding this. We are always here to help.. <br> <br> ';

        $body .= 'Thank you for choosing ' . $_ENV['APP_NAME'] . ' for your finacial needs. <br> <br>';

        $body .= ' Best regards, <br> <br> ';

        $body .= ' Team ' .  $_ENV['APP_NAME'] . ' ';

        $mail->Body = $body;

        if (!$mail->send()) {
            $this->outputData(false, ' Email could not be sent', $mail->ErrorInfo);
            return false;
        } else {
            return true;
        }
    }

    public function NotifyUserOfLoanADayToDueDate($email, $fname)
    {

        require_once($_SERVER['DOCUMENT_ROOT'] . '/solar/vendor/autoload.php');

        $mail = new PHPMailer(true);

        $mail->isSMTP();

        $mail->Host = $_ENV['HOST_NAME'];

        $mail->SMTPAuth = true;

        $mail->Username = $_ENV['SMTP_USERNAME'];

        $mail->Password = $_ENV['SMTP_PWORD'];

        $mail->SMTPSecure = 'ssl';

        $mail->Port = 465;

        $mail->setFrom($_ENV['APP_MAIL'], $_ENV['APP_NAME']);

        $mail->addAddress($email, $fname);

        $mail->isHTML(true);

        $mail->Subject = '[Solar-credit]Your  Loan Payment is Due Tomorrow ';

        $body = "Dear $fname, <br><br>";

        $body .= 'We hope this email finds you well. We would like to remind you that your loan repayment  is due tomorrow, It is crucial that you ensure sufficient funds are available in your wallet to avoid any payment failures...<br><br>';

        $body .= '  Failure to fund your wallet adequately by the due date will result in penalty charges being applied to your account. To prevent any inconvenience, please make sure that the necessary amount is deposited into your wallet as soon as possible...<br><br>';

        $body .= 'If you have already made the payment or have any concerns regarding your loan repayment, kindly disregard this reminder and accept our apologies for any inconvenience caused... <br><br> ';

        $body .= 'Please do not hesitate to reach out to us if you have any questions or concerns regarding the your loan  plan. We are always here to help.. <br><br> ';

        $body .= ' Best regards, <br> <br> ';

        $body .= ' Team ' .  $_ENV['APP_NAME'] . ' ';

        $mail->Body = $body;

        if (!$mail->send()) {
            $this->outputData(false, ' Email could not be sent', $mail->ErrorInfo);
            return false;
        } else {
            return true;
        }
    }

    public function NotifyUserOfLoanAWeekToDueDate($email, $fname)
    {

        require_once($_SERVER['DOCUMENT_ROOT'] . '/solar/vendor/autoload.php');

        $mail = new PHPMailer(true);

        $mail->isSMTP();

        $mail->Host = $_ENV['HOST_NAME'];

        $mail->SMTPAuth = true;

        $mail->Username = $_ENV['SMTP_USERNAME'];

        $mail->Password = $_ENV['SMTP_PWORD'];

        $mail->SMTPSecure = 'ssl';

        $mail->Port = 465;

        $mail->setFrom($_ENV['APP_MAIL'], $_ENV['APP_NAME']);

        $mail->addAddress($email, $fname);

        $mail->isHTML(true);

        $mail->Subject = '[Solar-credit]Your  Loan Payment is Due in a week time ';

        $body = "Dear $fname, <br><br>";

        $body .= 'We hope this email finds you well. We would like to remind you that your loan repayment will be due in a week time, It is crucial that you ensure sufficient funds are available in your wallet to avoid any payment failures...<br><br>';

        $body .= '  Failure to fund your wallet adequately by the due date will result in penalty charges being applied to your account. To prevent any inconvenience, please make sure that the necessary amount is deposited into your wallet as soon as possible...<br><br>';

        $body .= 'If you have already made the payment or have any concerns regarding your loan repayment, kindly disregard this reminder and accept our apologies for any inconvenience caused... <br><br> ';

        $body .= 'Please do not hesitate to reach out to us if you have any questions or concerns regarding the your loan  plan. We are always here to help.. <br><br> ';

        $body .= ' Best regards, <br> <br> ';

        $body .= ' Team ' .  $_ENV['APP_NAME'] . ' ';

        $mail->Body = $body;

        if (!$mail->send()) {
            $this->outputData(false, ' Email could not be sent', $mail->ErrorInfo);
            return false;
        } else {
            return true;
        }
    }


    public function NotifyUserOfLoanThreeDaysToDueDate($email, $fname)
    {

        require_once($_SERVER['DOCUMENT_ROOT'] . '/solar/vendor/autoload.php');

        $mail = new PHPMailer(true);

        $mail->isSMTP();

        $mail->Host = $_ENV['HOST_NAME'];

        $mail->SMTPAuth = true;

        $mail->Username = $_ENV['SMTP_USERNAME'];

        $mail->Password = $_ENV['SMTP_PWORD'];

        $mail->SMTPSecure = 'ssl';

        $mail->Port = 465;

        $mail->setFrom($_ENV['APP_MAIL'], $_ENV['APP_NAME']);

        $mail->addAddress($email, $fname);

        $mail->isHTML(true);

        $mail->Subject = '[Solar-credit]Your Loan Payment Will Be Due in Three days time ';

        $body = "Dear $fname, <br><br>";

        $body .= 'We hope this email finds you well. We would like to remind you that your loan repayment will be due 3 days  time, It is crucial that you ensure sufficient funds are available in your wallet to avoid any payment failures...<br><br>';

        $body .= '  Failure to fund your wallet adequately by the due date will result in penalty charges being applied to your account. To prevent any inconvenience, please make sure that the necessary amount is deposited into your wallet as soon as possible...<br><br>';

        $body .= 'If you have already made the payment or have any concerns regarding your loan repayment, kindly disregard this reminder and accept our apologies for any inconvenience caused... <br><br> ';

        $body .= 'Please do not hesitate to reach out to us if you have any questions or concerns regarding the your loan  plan. We are always here to help.. <br><br> ';

        $body .= ' Best regards, <br> <br> ';

        $body .= ' Team ' .  $_ENV['APP_NAME'] . ' ';

        $mail->Body = $body;

        if (!$mail->send()) {
            $this->outputData(false, ' Email could not be sent', $mail->ErrorInfo);
            return false;
        } else {
            return true;
        }
    }

    public function NotifyAdminOfWithrawalRequest($fullname, $acknumber, $bankname, $amount)
    {

        require_once($_SERVER['DOCUMENT_ROOT'] . '/solar/vendor/autoload.php');

        $mail = new PHPMailer(true);

        $mail->isSMTP();

        $mail->Host = $_ENV['HOST_NAME'];

        $mail->SMTPAuth = true;

        $mail->Username = $_ENV['SMTP_USERNAME'];

        $mail->Password = $_ENV['SMTP_PWORD'];

        $mail->SMTPSecure = 'ssl';

        $mail->Port = 465;

        $mail->setFrom($_ENV['APP_MAIL'], $_ENV['APP_NAME']);

        $mail->addAddress($_ENV['APP_MAIL'], 'Admin');

        $mail->isHTML(true);

        $mail->Subject = '[Solar-credit] Withdrawal Request ';

        $body = 'Dear Admin, <br><br>';

        $body .= "We am writing to notify you that a withdrawal request has been submitted by  $fullname for the withdrawal of  "  . number_format($amount, 2)  .  ' from his  wallet..<br><br>';

        $body .= ' According to our records, The account details goes as follows<br><br>';

        $body .= " Account number: $acknumber<br> <br> ";

        $body .= " Bank name: $bankname<br> <br> ";

        $body .= ' If you need any further information from the account holder to process the request, please contact them directly<br> <br>';

        $body .= 'Thank you for your prompt attention to this matter.<br> <br>';

        $body .= ' Best regards, <br> <br> ';

        $body .= ' Team ' .  $_ENV['APP_NAME'] . ' ';

        $mail->Body = $body;

        if (!$mail->send()) {
            $this->outputData(false, ' Email could not be sent', $mail->ErrorInfo);
            return false;
        } else {
            return true;
        }
    }


    public function NotifyUserofWithdrawalApproval($email, $fname,  $amount, $bankname)
    {

        require_once($_SERVER['DOCUMENT_ROOT'] . '/solar/vendor/autoload.php');

        $mail = new PHPMailer(true);

        $mail->isSMTP();

        $mail->Host = $_ENV['HOST_NAME'];

        $mail->SMTPAuth = true;

        $mail->Username = $_ENV['SMTP_USERNAME'];

        $mail->Password = $_ENV['SMTP_PWORD'];

        $mail->SMTPSecure = 'ssl';

        $mail->Port = 465;

        $mail->setFrom($_ENV['APP_MAIL'], $_ENV['APP_NAME']);

        $mail->addAddress($email, $fname);

        $mail->isHTML(true);

        $mail->Subject = "Withdrawal Request Approved";

        $body = "Dear $fname, <br><br>";

        $body .= "We are delighted to notify you that your withdrawal request has been authorized.<br><br>";

        $body .= " The specified amount of $amount has been transferred to the following account number $bankname, and the corresponding deduction has been made from your wallet. <br><br>";

        $body .= " Thank you for using our service, and please don't hesitate to contact us if you have any questions or concerns.<br><br>";

        $body .= ' Best regards, <br> <br> ';

        $body .= ' Team ' .  $_ENV['APP_NAME'] . ' ';

        $mail->Body = $body;

        if (!$mail->send()) {
            $this->outputData(false, ' Email could not be sent', $mail->ErrorInfo);
            return false;
        } else {
            return true;
        }
    }



    
    
    
   public function NotifyUserofWithdrawalDiapproval($email, $fname,  $amount, $message)
   {

       require_once($_SERVER['DOCUMENT_ROOT'] . '/vendor/autoload.php');

       $mail = new PHPMailer(true);

       $mail->isSMTP();

       $mail->Host = $_ENV['HOST_NAME'];

       $mail->SMTPAuth = true;

       $mail->Username = $_ENV['SMTP_USERNAME'];

       $mail->Password = $_ENV['SMTP_PWORD'];

       $mail->SMTPSecure = 'ssl';

       $mail->Port = 465;

       $mail->setFrom($_ENV['APP_MAIL'], $_ENV['APP_NAME']);

       $mail->addAddress($email, $fname);

       $mail->isHTML(true);

       $mail->Subject = "Withdrawal Request Disapproved";

       $body = "Dear $fname, <br><br>";

       $body .= "We regret to inform you  that your withdrawal request has been declined.<br><br>";

       $body .= " The specified amount of $amount naira has been reversed and credited back to your account<br><br>";

       $body .= "The reason for the decline is: $message.<br><br>";

       $body .= " Thank you for using our service, and please don't hesitate to contact us if you have any questions or concerns.<br><br>";

       $body .= ' Best regards, <br> <br> ';

       $body .= ' Team ' .  $_ENV['APP_NAME'] . ' ';

       $mail->Body = $body;

       if (!$mail->send()) {
           $this->outputData(false, ' Email could not be sent', $mail->ErrorInfo);
           return false;
       } else {
           return true;
       }
   }




    public function celebrateLoanCompletedSuccesss($email, $fname)
    {

        require_once($_SERVER['DOCUMENT_ROOT'] . '/solar/vendor/autoload.php');

        $mail = new PHPMailer(true);

        $mail->isSMTP();

        $mail->Host = $_ENV['HOST_NAME'];

        $mail->SMTPAuth = true;

        $mail->Username = $_ENV['SMTP_USERNAME'];

        $mail->Password = $_ENV['SMTP_PWORD'];

        $mail->SMTPSecure = 'ssl';

        $mail->Port = 465;

        $mail->setFrom($_ENV['APP_MAIL'], $_ENV['APP_NAME']);

        $mail->addAddress($email, $fname);

        $mail->isHTML(true);

        $mail->Subject = "[Solar-credit] Congratulations on paying off your loan ";

        $body = "Dear $fname, <br><br>";

        $body .= "We hope this email finds you well.We am delighted to inform you that you have successfully paid off your loan. Congratulations on reaching this significant milestone! <br><br>";

        $body .= "Thank you again for choosing " . $_ENV['APP_NAME'] . " as your lending partner. We are grateful for your trust and confidence in our services, and we wish you all the best in your future endeavors<br><br>";

        $body .= " Best regards, <br> <br> ";

        $body .= " Team " . $_ENV['APP_NAME'] . " ";

        $mail->Body = $body;

        if (!$mail->send()) {
            $this->outputData(false, ' Email could not be sent', $mail->ErrorInfo);
            return false;
        } else {
            return true;
        }
    }




    public function notifyUserOfUnPaidOutstanding($email, $fname)
    {

        require_once($_SERVER['DOCUMENT_ROOT'] . '/solar/vendor/autoload.php');

        $mail = new PHPMailer(true);

        $mail->isSMTP();

        $mail->Host = $_ENV['HOST_NAME'];

        $mail->SMTPAuth = true;

        $mail->Username = $_ENV['SMTP_USERNAME'];

        $mail->Password = $_ENV['SMTP_PWORD'];

        $mail->SMTPSecure = 'ssl';

        $mail->Port = 465;

        $mail->setFrom($_ENV['APP_MAIL'], $_ENV['APP_NAME']);

        $mail->addAddress($email, $fname);

        $mail->isHTML(true);

        $mail->Subject = "[Solar-credit] Notification of Empty Wallet Balance for Outstanding Purchase";

        $body = "Dear $fname, <br><br>";

        $body .= "We hope this email finds you well. <br><br>";

        $body .= "We would like to inform you that your wallet is to  be  debited for an outstanding purchased product  as per the terms of our agreement. However, it appears that your account balance is currently empty and there are no available funds to cover the outstanding balance.";

        $body .= "  We kindly request that you deposit the necessary funds as soon as possible to cover the outstanding balance.<br><br>";

        $body .= " To deposit funds, please log in to your account and fund your outstanding wallet . <br> <br>";

        $body .= "We will handle the rest.  If you have any questions or concerns regarding this matter, please do not hesitate to contact us. We are always here to assist you in any way we can.<br><br>";

        $body .= "Thank you for choosing " . $_ENV['APP_NAME']  . ".  We look forward to hearing from you soon. <br> <br>";

        $body .= " Best regards, <br> <br> ";



        $body .= " Team " . $_ENV['APP_NAME'] . " ";

        $mail->Body = $body;

        if (!$mail->send()) {
            $this->outputData(false, ' Email could not be sent', $mail->ErrorInfo);
            return false;
        } else {
            return true;
        }
    }


    public function NotifyUserOfProductLoanADayToDueDate($email, $fname, $productname)
    {
        require_once($_SERVER['DOCUMENT_ROOT'] . '/solar/vendor/autoload.php');

        $mail = new PHPMailer(true);

        $mail->isSMTP();

        $mail->Host = $_ENV['HOST_NAME'];

        $mail->SMTPAuth = true;

        $mail->Username = $_ENV['SMTP_USERNAME'];

        $mail->Password = $_ENV['SMTP_PWORD'];

        $mail->SMTPSecure = 'ssl';

        $mail->Port = 465;

        $mail->setFrom($_ENV['APP_MAIL'], $_ENV['APP_NAME']);

        $mail->addAddress($email, $fname);

        $mail->isHTML(true);

        $mail->Subject = "[Solar-credit] Upcoming Product Loan Payment Reminder: Due Tomorrow";

        $body = "Dear $fname, <br><br>";
    
        $body .= "We hope this email finds you well. This is a friendly reminder regarding your  product loan payment ..<br><br>";
    
        $body .= "As per our records, the deadline for your loan payment for the following product(s): $productname  is tomorrow, as you have opted for a monthly installment plan..<br><br>";

        $body .= "Please ensure that your account wallet is funded before or by tomorrow. The payment will be automatically debited from your wallet.<br><br>";
    
        $body .= "if you have any questions or concerns regarding your loan, please don't hesitate to reach out to us. Our team is always available to assist you.<br> <br>";
    
        $body .= "Please do not hesitate to reach out to us if you have any questions or concerns regarding the your  product loan  plan. We are always here to help.. <br> <br> ";
    
        $body .= "Thank you for choosing " . $_ENV['APP_NAME'] . " for your finacial needs.  We look forward to hearing from you soon. <br> <br>";
    
        $body .= " Best regards, <br> <br> ";

        $body .= ' Team ' .  $_ENV['APP_NAME'] . ' ';

        $mail->Body = $body;

        if (!$mail->send()) {
            $this->outputData(false, ' Email could not be sent', $mail->ErrorInfo);
            return false;
        } else {
            return true;
        }
    }







    public function NotifyUserOfProductLoanAWeekToDueDate($email, $fname, $boughtBought)
    {
        require_once($_SERVER['DOCUMENT_ROOT'] . '/solar/vendor/autoload.php');

        $formattedProductNames = ($boughtBought[0]['productname']);

        $mail = new PHPMailer(true);

        $mail->isSMTP();

        $mail->Host = $_ENV['HOST_NAME'];

        $mail->SMTPAuth = true;

        $mail->Username = $_ENV['SMTP_USERNAME'];

        $mail->Password = $_ENV['SMTP_PWORD'];

        $mail->SMTPSecure = 'ssl';

        $mail->Port = 465;

        $mail->setFrom($_ENV['APP_MAIL'], $_ENV['APP_NAME']);

        $mail->addAddress($email, $fname);

        $mail->isHTML(true);

        $mail->Subject = "[Solar-credit] Upcoming Product Loan Payment Reminder: Due In A Week Time";

        $body = "Dear $fname,<br><br>";
        
        $body .= "We hope this email finds you well. This is a friendly reminder regarding your product loan payment.<br><br>";
        
        $body .= "As per our records, the scheduled payment deadline for the following product(s) will be due in  week time, as you have opted for a monthly installment plan<br>";
        $body .= "<ul>";
        foreach ($boughtBought as $product) {
            $body .= "<li>Product Name: <b>" . $product['productname'] . "</b></li>";
            $body .= "<li>Price: <b>" . $product['price'] . "</b></li>";
            $body .= "<br>";
        }
        $body .= "</ul>";
        
        $body .= "Please ensure that your account wallet is funded before then. The payment will be automatically debited from your wallet.<br><br>";
        
        $body .= "If you have any questions or concerns regarding your loan, please don't hesitate to reach out to us. Our team is always available to assist you.<br><br>";
        
        $body .= "Thank you for choosing " . $_ENV['APP_NAME'] . " for your financial needs. We look forward to hearing from you soon.<br><br>";
        
        $body .= "Best regards,<br><br>";
        
        $body .= "Team " . $_ENV['APP_NAME'];
        
        $mail->Body = $body;


        if (!$mail->send()) {
            $this->outputData(false, ' Email could not be sent', $mail->ErrorInfo);
            return false;
        } else {
            return true;
        }
    }



    public function NotifyUserOfProductLoanThreeDaysToDueDate($email, $fname, $boughtBought)
    {
        require_once($_SERVER['DOCUMENT_ROOT'] . '/solar/vendor/autoload.php');

        $formattedProductNames = ($boughtBought[0]['productname']);

        $mail = new PHPMailer(true);

        $mail->isSMTP();

        $mail->Host = $_ENV['HOST_NAME'];

        $mail->SMTPAuth = true;

        $mail->Username = $_ENV['SMTP_USERNAME'];

        $mail->Password = $_ENV['SMTP_PWORD'];

        $mail->SMTPSecure = 'ssl';

        $mail->Port = 465;

        $mail->setFrom($_ENV['APP_MAIL'], $_ENV['APP_NAME']);

        $mail->addAddress($email, $fname);

        $mail->isHTML(true);

        $mail->Subject = "[Solar-credit] Upcoming Product Loan Payment Reminder: Due In A Three Days Time";

        $body = "Dear $fname,<br><br>";
        
        $body .= "We hope this email finds you well. This is a friendly reminder regarding your product loan payment.<br><br>";
        
        $body .= "As per our records, the scheduled payment deadline for the following product(s) will be due in Three days time, as you have opted for a monthly installment plan<br>";
        $body .= "<ul>";
        foreach ($boughtBought as $product) {
            $body .= "<li>Product Name: <b>" . $product['productname'] . "</b></li>";
            $body .= "<li>Price: <b>" . $product['price'] . "</b></li>";
            $body .= "<br>";
        }
        $body .= "</ul>";
        
        $body .= "Please ensure that your account wallet is funded before or by then. The payment will be automatically debited from your wallet.<br><br>";
        
        $body .= "If you have any questions or concerns regarding your loan, please don't hesitate to reach out to us. Our team is always available to assist you.<br><br>";
        
        $body .= "Thank you for choosing " . $_ENV['APP_NAME'] . " for your financial needs. We look forward to hearing from you soon.<br><br>";
        
        $body .= "Best regards,<br><br>";
        
        $body .= "Team " . $_ENV['APP_NAME'];
        
        $mail->Body = $body;


        if (!$mail->send()) {
            $this->outputData(false, ' Email could not be sent', $mail->ErrorInfo);
            return false;
        } else {
            return true;
        }
    }

    public function celebrateProductLoanCompletedSuccesss($email, $fname, $boughtBought)
    {
        require_once($_SERVER['DOCUMENT_ROOT'] . '/solar/vendor/autoload.php');

        $mail = new PHPMailer(true);

        $mail->isSMTP();

        $mail->Host = $_ENV['HOST_NAME'];

        $mail->SMTPAuth = true;

        $mail->Username = $_ENV['SMTP_USERNAME'];

        $mail->Password = $_ENV['SMTP_PWORD'];

        $mail->SMTPSecure = 'ssl';

        $mail->Port = 465;

        $mail->setFrom($_ENV['APP_MAIL'], $_ENV['APP_NAME']);

        $mail->addAddress($email, $fname);

        $mail->isHTML(true);

        $mail->Subject = "[Solar-credit]  Your Installment Payments are Complete!";

        $body = "Dear $fname,<br><br>";
        
        $body .= "It's time to celebrate! We are thrilled to inform you that you have successfully completed all your installment payments for the following good(s).<br><br>";
        $body .= "<ul>";
        foreach ($boughtBought as $product) {
            $body .= "<li>Product Name: <b>" . $product['productname'] . "</b></li>";
            $body .= "<br>";
        }
        $body .= "</ul>";
        
        $body .= "We want to express our sincerest congratulations on reaching this significant milestone. Your commitment and dedication in fulfilling your financial obligations have paid off<br><br>";

        $body.= "We extend our heartfelt appreciation for choosing us as your preferred provider for installment purchases. Your satisfaction is our utmost priority, and we are committed to delivering exceptional service at every step.<br><br>";

        $body.= "If you have any questions or need further assistance regarding your installment plan or any other matter, our dedicated support team is here to help. We want to ensure that your experience with us continues to be smooth and enjoyable.<br><br>";
        
        $body .= "Thank you for choosing " . $_ENV['APP_NAME'] . " for your financial needs.<br><br>";
        
        $body .= "Best regards,<br><br>";
        
        $body .= "Team " . $_ENV['APP_NAME'];
        
        $mail->Body = $body;


        if (!$mail->send()) {
            $this->outputData(false, ' Email could not be sent', $mail->ErrorInfo);
            return false;
        } else {
            return true;
        }
    }
}
