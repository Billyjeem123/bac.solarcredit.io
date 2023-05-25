<?php

abstract class AbstractClasses
{



    public  function getMemoryUsage()
    {
        $mem_usage = memory_get_usage(true);
        if ($mem_usage < 1024)
            return $mem_usage . ' bytes';
        elseif ($mem_usage < 1048576)
            return round($mem_usage / 1024, 2) . ' KB';
        else
            return round($mem_usage / 1048576, 2) . ' MB';
    }

    public  function checkSize()
    {
        $memory_usage = $this->getMemoryUsage();
        echo 'Memory usage: ' . $memory_usage;
    }

    // validate email

    public function validateEmail($value)
    {
        // code...
        if (filter_var($value, FILTER_VALIDATE_EMAIL)) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * sanitizeInput Parameters
     *
     * @param [ type ] $input
     * @return string
     */
    public  function sanitizeInput($input)
    {
        # Remove white space from beginning and end of string
        $input = trim($input);
        # Remove slashes
        $input = stripslashes($input);
        # Convert special characters to HTML entities
        $input = htmlspecialchars($input, ENT_QUOTES | ENT_HTML5, 'UTF-8');

        return $input;
    }

    #  resourceNotFound::Check for id if exists

    private function resourceNotFound(int $id): void
    {
        http_response_code(404);
        echo json_encode(['message' => "Resource with id $id not found"]);
    }

    /**
     * respondUnprocessableEntity alert of errors deteced
     *
     * @param array $errors
     * @return void
     */

    public function respondUnprocessableEntity(array $errors): void
    {
        http_response_code(400);
        $this->outputData(false,  'Kindly review your request parameters to ensure they comply with our requirements.',  $errors);
    }

    public function connectToThirdPartyAPI(array $payload, string $url)
    {

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));

        $response = curl_exec($ch);

        if ($response === false) {
            $error = curl_error($ch);
            $this->outputData(false, 'Unable to process request, try again later', null);
        }

        curl_close($ch);

        return $response;
    }

    public function respondWithInternalError($errors): void
    {
        http_response_code(500);
        $this->outputData(false,  'Unable to process request, try again later',  $errors);
    }

    public function token()
    {

        $defaultPassword = mt_rand(100000, 999999);
        return $defaultPassword;
    }

    #This method checks for KYC staus of  a user

    public function getkycStatus(string $usertoken)
    {
        try {
            $status = 1;
            $db = new Database();
            $sql = 'SELECT usertoken, status FROM tblkyc WHERE usertoken = :userToken  AND  status = :status';
            $stmt = $db->connect()->prepare($sql);
            $stmt->bindParam(':userToken', $usertoken, PDO::PARAM_INT);
            $stmt->bindParam(':status', $status, PDO::PARAM_INT);
            if (!$stmt->execute()) {
                throw new Exception('Failed to execute query');
            }
            if ($stmt->rowCount() > 0) {
                return true;
            } else {
                return false;
            }
        } catch (Exception $e) {
            echo 'Error: ' . $e->getMessage();
        } finally {
            $stmt = null;
            unset($db);
        }
    }

    #This method gets  a user account balance

    public function getAccountBalance($usertoken)
    {
        try {
            $db = new Database();
            $sql = 'SELECT amount AS totalBalance FROM tblwallet WHERE usertoken = :userToken';
            $stmt = $db->connect()->prepare($sql);
            $stmt->bindParam(':userToken', $usertoken, PDO::PARAM_INT);
            $stmt->execute([$usertoken]);

            if ($stmt->rowCount() == 0) {
                http_response_code(404);
                return false;
                exit;
            }

            $totalBalance = $stmt->fetch(PDO::FETCH_ASSOC);
            $array = [
                'totalBalannce' => $totalBalance['totalBalance']
            ];
        } catch (PDOException $e) {
            echo 'Error: ' . $e->getMessage();
        } finally {
            $stmt = null;
            unset($db);
        }
        return $array;
    }



    #sumAllUsersAccountWallet:: This method Sums all avilable money  in users wallet
    public function sumAllUsersAccountWallet()
    {
        try {
            $db = new Database();
            $sql = 'SELECT SUM(amount) AS totalBalance FROM tblwallet';
            $stmt = $db->connect()->query($sql);
    
            if ($stmt->rowCount() == 0) {
                http_response_code(404);
                return false;
            }
    
            $totalBalance = $stmt->fetch(PDO::FETCH_ASSOC);
            
            $alltotalBalance = $this->formatCurrency($totalBalance['totalBalance']);

        } catch (PDOException $e) {
            $this->respondWithInternalError("Error: " . $e->getMessage());
            return false;
        } finally {
            $stmt = null;
            unset($db);
        }
        return $alltotalBalance;
    }
    

    #This method verifies a user verifyNextOfKin

    public function verifyNextOfKin($usertoken)
    {
        try {
            $db = new Database();
            $sql = 'SELECT usertoken  FROM tblkin WHERE usertoken = :userToken';
            $stmt = $db->connect()->prepare($sql);
            $stmt->bindParam(':userToken', $usertoken, PDO::PARAM_INT);
            $stmt->execute([$usertoken]);

            if ($stmt->rowCount() == 0) {
                http_response_code(404);
                return false;
            }

            return true;
        } catch (PDOException $e) {
            echo 'Error: ' . $e->getMessage();
        } finally {
            $stmt = null;
            unset($db);
        }
    }

    #getCategoryName:: This method accept token to get the category a product belongs to

    public function getProductCategory(int $productCatgoryId)
    {
        try {
            $db = new Database();
            $sql = 'SELECT catname FROM tblcategory WHERE id = :productCatgoryId';
            $stmt = $db->connect()->prepare($sql);
            $stmt->bindParam('productCatgoryId', $productCatgoryId);
            $stmt->execute();
            $result_set = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($result_set === false) {
                http_response_code(404);
                $this->outputData(false, "Category with  $result_set[id] not found", null);
                exit;
            }

            $dataArray = ['catname' => $result_set['catname']];
        } catch (PDOException $e) {
            $this->outputData(false, 'Error fetching category name: ' . $e->getMessage(), null);
            return;
        } catch (Exception $e) {
            $this->outputData(false, $e->getMessage(), null);
            return;
        } finally {
            $stmt = null;
            unset($db);
        }
        return $dataArray;
    }

    #updateUserAccountBalance ::This method updateUserAccountBalance debits or credit user account depending on the transactionType.

    public function updateUserAccountBalance($amount, $usertoken, $transactionType)
    {
        $operator = $transactionType === 'credit' ? '+' : '-';

        $db = new Database();

        $sql = "UPDATE tblwallet 
                SET amount = amount $operator :amount
                WHERE usertoken = :usertoken";

        try {
            $stmt = $db->connect()->prepare($sql);
            $stmt->bindParam(':amount', $amount);
            $stmt->bindParam(':usertoken', $usertoken);

            $stmt->execute();
            return true;
        } catch (PDOException $e) {
            // Handle the error
            $_SESSION['err'] = 'Unable to process request: ' . $e->getMessage();
            return false;
        } finally {
            $stmt = null;
            unset($db);
        }
    }



    # recordCreditTransaction::This method records ALL Credit OR DEBIT transactions

    public function recordTransaction($reference, $usertoken, $amount, $creditOrDebit)
    {
        $time = time();
        $db = new Database();

        $sql = "INSERT INTO tblwallettransaction (ref, usertoken, amount, paid_at, type)
                VALUES (:reference, :usertoken, :amount, :timePaid, :type)";

        try {
            $stmt = $db->connect()->prepare($sql);
            $stmt->bindParam(':reference', $reference);
            $stmt->bindParam(':usertoken', $usertoken);
            $stmt->bindParam(':amount', $amount);
            $stmt->bindParam(':timePaid', $time);
            $stmt->bindParam(':type', $creditOrDebit);

            $stmt->execute();
            return true;
        } catch (PDOException $e) {
            // Handle the error
            $_SESSION['err'] = 'Unable to process request:' . $e->getMessage();
            return false;
        } finally {
            $stmt = null;
            unset($db);
        }
    }

    #getUserdata::This method fetches All info related to a user

    public function getUserdata(int $usertoken)
    {
        try {
            $db = new Database();
            $sql = 'SELECT * FROM tblusers WHERE usertoken = :usertoken';
            $stmt = $db->connect()->prepare($sql);
            $stmt->bindParam('usertoken', $usertoken);
            $stmt->execute();
            $user = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($stmt->rowCount() === 0) {
                http_response_code(404);
                $this->outputData(false, 'No user found', null);
                exit;
            }

            $getkycStatus = $this->getkycStatus($usertoken);
            $getAccountBalance = $this->getAccountBalance($usertoken);
            $verifyNextOfKin = $this->verifyNextOfKin($usertoken);

            $array = [
                'fname' => $user['fname'],
                'mail' => $user['mail'],
                'usertoken' => intval($user['usertoken']),
                'phone' => $user['phone'],
                'regStatus' => ($user['status'] === 1) ? true : false,
                'userType' => $user['userType'],
                'kycStatus' => $getkycStatus,
                'availableBalance' => $getAccountBalance['totalBalannce'],
                'nextOfKin' => $verifyNextOfKin,
                'availableBalance_thousand' => $this->formatCurrency($getAccountBalance['totalBalannce']),
            ];
        } catch (PDOException $e) {
            $_SESSION['err'] = 'Error retrieving user details: ' . $e->getMessage();
            exit;
            // $this->respondWithInternalError( false, 'Unable to retrieve user details: ' . $e->getMessage(), null );
            return false;
        } finally {
            $stmt = null;
            unset($db);
        }
        return $array;
    }

    #getKYCData::This method fetches userKyc data...

    public function getKYCData($usertoken)
    {

        try {
            $db = new Database();
            $sql = 'SELECT * FROM tblkyc WHERE usertoken = :usertoken AND status = 1';
            $stmt = $db->connect()->prepare($sql);
            $stmt->bindParam(':usertoken', $usertoken);
            $stmt->execute();

            if ($stmt->rowCount() === 0) {
                return;
                exit;
            }

            $user = $stmt->fetch(PDO::FETCH_ASSOC);
            $array = [
                'fname' => $user['fname'],
                'profession' => $user['profession'],
                'means_identity' => $user['means_identity'],
                'identity_number' => $user['identiy_number'],
                'kycAddress' => $user['address'],
                'photo' => ($user['imageUrl']),
                'kycStatus' => ($user['status'] === 1) ? true : false,
            ];
        } catch (PDOException $e) {
            $this->outputData(false, 'Error retrieving KYC data: ' . $e->getMessage(), null);
            return false;
        } finally {
            $stmt = null;
            unset($db);
        }

        return $array;
    }

    #authenticateUser:: This method authencticates User data

    public function authenticateUser($usertoken)
    {
        $db = new Database();
        try {
            $sql = 'SELECT usertoken FROM tblusers WHERE usertoken = :usertoken';
            $stmt = $db->connect()->prepare($sql);
            $stmt->bindParam(':usertoken', $usertoken);
            $stmt->execute();

            if ($stmt->rowCount() == 0) {
                http_response_code(404);
                $_SESSION['err'] = 'User does not exists';
                return false;
                exit;
            }

            return true;
        } catch (PDOException $e) {
            $_SESSION['err'] = $e->getMessage();
            return false;
        } finally {
            $stmt =  null;
            unset($db);
        }
    }

    #formatDate::This method format date to humna readable format
    public function formatDate($time)
    {

        return date('D d M, Y: H', $time);
    }

    #v::This method format date to amount readable fomat
    public function formatCurrency($amonut)
    {

        return number_format($amonut, 2);
    }


    public function currentDate()
    {

        $timezone = new DateTimeZone('Africa/Lagos');
        $currentDate = new DateTime('now', $timezone);
        $currentDateString = $currentDate->format('Y-m-d');
        return $currentDateString;
    }

    #getProductByItRelatedToken ::This metod fetches product by related Token

    public function getProductByItRelatedToken($productToken )
 {
        $dataArray = array();
        $db = new Database();

        try {
            $sql = 'SELECT * FROM tblproducts WHERE putoken = :pToken';
            $stmt = $db->connect()->prepare( $sql );
            $stmt->bindParam( ':pToken', $productToken, PDO::PARAM_INT );
            $stmt->execute();
            $relatedProducts = $stmt->fetch( PDO::FETCH_ASSOC );

            $array = [
                'productid' => $relatedProducts[ 'id' ],
                'productname' => $relatedProducts[ 'pname' ],
                'productToken' => intval( $relatedProducts[ 'putoken' ] ),
                'productQuality' => $relatedProducts[ 'pquantity' ],
                'productDesc' => $relatedProducts[ 'pdesc' ],
                'productPrice' => $relatedProducts[ 'price' ],
                'productType' => $relatedProducts[ 'type' ],
                'ownertoken' => intval( $relatedProducts[ 'usertoken' ] ),
                'productImage' => ( $relatedProducts[ 'imageUrl' ] )
            ];
        } catch ( PDOException $e ) {
            $this->respondWithInternalError( 'Unable to get product related items: ' . $e->getMessage() );
            return false;
        }
        finally {
            $stmt = null;
            unset($db);
        }
  
        return $array;
    }

    #checkSubscribedPlan ::This methos checks for Subscription plan.It returns the subscription integer value

    public function checkSubscribedPlan(int $planid)
    {

        try {
            $db = new Database();
            $sql = 'SELECT id, plan_value FROM tblplan WHERE id = :plan_id';
            $stmt = $db->connect()->prepare($sql);
            $stmt->bindParam(':plan_id', $planid);
            $stmt->execute();

            if ($stmt->rowCount() === 0) {
                http_response_code(404);
                $_SESSION['err']  = 'Plan is not valid';
                return false;
            }

            $planArray = $stmt->fetch(PDO::FETCH_ASSOC);

            $array = [
                'planid' => $planArray['plan_value']
            ];
        } catch (PDOException $e) {
            $_SESSION['err']  = 'Error: ' . $e->getMessage();
            return false;
        } finally {
            $stmt  = null;
            unset($db);
        }
        return $array;
    }

    # Calculates the monthly repayment amount for a loan based on the loan amount and loan term.
    public function calculateMonthlyRepayment(float $loan_amount, int $loan_term): float
    {
        return $loan_amount / $loan_term;
    }

    public function calculateDateAhead($package)
    {

        $current_date = date("Y-m-d");
        $three_months_ahead = date("Y-m-d", strtotime($current_date . $package));
        return  $three_months_ahead;
    }



    /*
    |--------------------------------------------------------------------------
    |ALL HISTORY METHODS
    |--------------------------------------------------------------------------
    */


    #This method is specifically meaant for? your guess is as good as mine.. wait did you know it? Yes, to track user history logs..
    public function logUserActivity(int $usertoken, string $logs, $longtitude, $latitude)
    {
        try {

            $db = new Database();
            $sql = 'INSERT INTO tblhistory_log (usertoken, logs, longtitude, latitude, ip,time) 
            VALUES (:usertoken, :logs, :longtitude,:latitude, :ip,:time )';
            $stmt = $db->connect()->prepare($sql);
            $stmt->bindParam(':usertoken', $usertoken);
            $stmt->bindParam(':logs', $logs);
            $stmt->bindParam(':longtitude', $longtitude);
            $stmt->bindParam(':latitude', $latitude);
            $stmt->bindParam(':ip', $ip);
            $stmt->bindParam(':latitude', $latitude);
            if (!$stmt->execute()) {
                $this->outputData(false, 'Unable to process query', null);
                return false;
            }
        } catch (Exception $e) {
            $this->respondWithInternalError(false, $e->getMessage(), null);
            return false;
        } finally {
            $stmt = null;
            unset($db);
        }
        return true;
    }

    # getAllHistoryLogs::This method fetches all History logs Belonging to a user
    public function getAllHistoryLogs(int $usertoken)
    {

        $dataArray = array();

        $db = new Database();
        $sql = 'SELECT * FROM tblhistory_log  WHERE usertoken = :usertoken';
        $stmt = $db->connect()->prepare($sql);
        $stmt->bindParam(':usertoken', $usertoken);
        try {
            $stmt->execute();
            $historyLogs = $stmt->fetchAll(PDO::FETCH_ASSOC);

            foreach ($historyLogs as $allLogs) {
                $array = [
                    'Logs' => $allLogs['logs'],
                    'longtitude' => $allLogs['longtitude'],
                    'latitude' => $allLogs['latitude'],
                    'Date' => $this->formatDate($allLogs['time']),
                ];

                array_push($dataArray, $array);
            }
        } catch (PDOException $e) {
            $_SESSION['err'] = 'Unable to retrieve user history' . $e->getMessage();
            return false;
        } finally {
            $stmt = null;
            unset($db);
        }
        return $dataArray;
    }



    public function notifyUserMessage($context, $userToken)
    {
        $currentTime = time();
        $db = new Database();

        $sql = "INSERT INTO tblnotify (context, usertoken, time)
               VALUES (:context, :userToken, :time)";

        try {
            $stmt = $db->connect()->prepare($sql);
            $stmt->bindParam(':context', $context);
            $stmt->bindParam(':userToken', $userToken);
            $stmt->bindParam(':time', $currentTime);

            $stmt->execute();
            return true;
        } catch (PDOException $e) {
            // Handle the error
            $_SESSION['err'] = $e->getMessage();
            return false;
        } finally {
            $stmt = null;
            unset($db);
        }
    }

    public function outputData($success = null, $message = null, $data = null)
    {

        $arr_output = array(
            'success' => $success,
            'message' => $message,
            'data' => $data,
        );
        echo json_encode($arr_output);
    }
}
