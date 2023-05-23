<!DOCTYPE html>
<html>
<head>
  <title>Login Form</title>
  <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
  <script>
    $(document).ready(function(){
      $('#loginForm').submit(function(event){
        event.preventDefault(); //prevent default form submission
        var mail = $('#mail').val();
        var pword = $('#pword').val();
        var apptoken = '0000'; // Replace with your app token
        $.ajax({
          url: "https://bac.solarcredit.io/v0.1/api/login",
          type: "POST",
          headers: {
            "Authorization": "Bearer 6455ef91d108f",
            "Content-Type" : "application/json"
          },
          data: JSON.stringify({
            mail: mail,
            pword: pword,
            apptoken: apptoken
          }),
          success: function(response){
            // handle successful login response
            alert(response);
          }
        });
      });
    });
  </script>

</head>
<body>
  <form id="loginForm">
    <label for="email">Email:</label>
    <input type="text" id="mail" name="mail"><br><br>
    <label for="password">Password:</label>
    <input type="password" id="pword" name="pword"><br><br>
    <button type="submit">Submit</button>
  </form>
</body>
</html>


public function remindAWeekBeforeDueDate()
{
    $currentDate = $this->currentDate();

    $sql = "SELECT 
                loan_product_purchases.a_week_before, 
                loan_product_purchases.remind_a_week_before_status, 
                loan_product_purchases.usertoken, 
                tbl_store_allinstallment_product.transactionToken, 
                tbl_store_allinstallment_product.productname 
            FROM 
                loan_product_purchases 
            INNER JOIN tbl_store_allinstallment_product 
                ON tbl_store_allinstallment_product.transactionToken = loan_product_purchases.token 
            WHERE 
                loan_product_purchases.a_week_before = '$currentDate' 
                AND loan_product_purchases.remind_a_week_before_status = 0 
            ORDER BY 
                loan_product_purchases.a_week_before ASC 
            LIMIT 
                10";

    $stmt = $this->conn->query($sql);

    if (!$stmt->execute()) {
        return false;
    }

    $remindADayBeforeDueDate = $stmt->fetchAll(PDO::FETCH_ASSOC);
    var_dump($remindADayBeforeDueDate);
    exit;

    if (!$remindADayBeforeDueDate) {
        return false;
    }

    $mailer = new Mailer();
    $sentReminder = false;
    foreach ($remindADayBeforeDueDate as $key => $value) {
        $getUserdata = $this->getUserdata($value['usertoken']);

        try {
            $mailer->NotifyUserOfProductLoanAWeekToDueDate(
                $getUserdata['mail'],
                $getUserdata['fname'],
                $value['productname']
            );
        } catch (Exception $e) {
            $errorMessage = date('[Y-m-d H:i:s] ') . 'Error sending mail for loan in ' . __METHOD__ . ' ' . PHP_EOL . $e->getMessage();
            error_log($errorMessage, 3, 'crone.log');
            continue; // Skip to the next iteration if an error occurs
        }

        $this->updateAWeekReminderStatusIfMailSent($value['a_week_before'], false);
        $sentReminder = true;
    }

    unset($mailer);
    return $sentReminder;
}

SELECT 
            loan_product_purchases.a_week_before, 
            loan_product_purchases.remind_a_week_before_status, 
            loan_product_purchases.a_week_before, 
            loan_product_purchases.usertoken, 
            tbl_store_allinstallment_product.transactionToken, 
            tbl_store_allinstallment_product.productname 
          FROM 
            loan_product_purchases 
            INNER JOIN tbl_store_allinstallment_product ON tbl_store_allinstallment_product.transactionToken = loan_product_purchases.token 
          WHERE 
            loan_product_purchases.a_week_before = '$currentDate' 
            AND loan_product_purchases.remind_a_week_before_status = 0 
          ORDER BY 
          loan_product_purchases.a_week_before ASC 
          LIMIT 
            10