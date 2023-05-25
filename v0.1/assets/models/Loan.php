<?php

class Loan extends AbstractClasses {

    private   $conn;

    public function __construct( Database $database )
 {

        $this->conn = $database->connect();
    }

    public function CreateUserNextOfKiN( array $data )
 {

        #checkVeridicationMeans.::This checks if userhas already done KYC.
        if ( !$this->hasVerifiedNextOfKin( $data[ 'usertoken' ] ) ) {
            $this->outputData( false, 'Account has already been verified', null );
            exit;
        }

        #checkVeridicationMeans.......

        // $connectToThirdPartyAPI = $this->connectToThirdPartyAPI( $empty = [], $url = 'null' );
        // $decodeResponse =  json_decode( $connectToThirdPartyAPI );
        // if ( !$decodeResponse ) {
        //    $output =  $this->outputData( false, 'Failed to verify BVN, try again later', null );
        //     exit;
        // }

        #  Prepare the fields and values for the insert query.
        $imageUrl = $_ENV[ 'IMAGE_PATH' ] . "/$data[photo]";

        $fields = [
            'fname' => $data[ 'fname' ],
            'usertoken' => $data[ 'usertoken' ],
            'occupation'=> $data[ 'occupation' ],
            'means_of_iden' => $data[ 'means_of_iden' ],
            'identity_num'  => $data[ 'identity_num' ],
            'address' => $data[ 'address' ],
            'photo' => ($data[ 'photo'] ),
            'imageUrl' => $imageUrl,

        ];

        # Build the SQL query
        $placeholders = implode( ', ', array_fill( 0, count( $fields ), '?' ) );
        $columns = implode( ', ', array_keys( $fields ) );
        $sql = "INSERT INTO tblkin ($columns) VALUES ($placeholders)";

        #  Execute the query and handle any errors
        try {
            $stmt =  $this->conn->prepare( $sql );
            $i = 1;
            foreach ( $fields as $value ) {
                $type = is_int( $value ) ? PDO::PARAM_INT : PDO::PARAM_STR;
                $stmt->bindValue( $i,   $value, $type );
                $i++;
            }
            $stmt->execute();

            http_response_code( 201 );
            $output = $this->outputData( true, 'Registration successful!', null );
            exit;
        } catch ( PDOException $e ) {

            $output  = $this->respondWithInternalError( 'Error: ' . $e->getMessage() );
            exit;
        }
        finally {
            $stmt = null;
            $this->conn = null;

        }

        return $output;

    }

    #hasVerifiedNextOfKin::This method checks if user has verified NextOfKin

    public function hasVerifiedNextOfKin( string $usertoken ) {
        try {
            $sql = 'SELECT usertoken FROM tblkin WHERE
             usertoken = :usertoken  ';
            $stmt = $this->conn->prepare( $sql );
            $stmt->bindParam( ':usertoken', $usertoken, PDO::PARAM_INT );
            if ( !$stmt->execute() ) {
                throw new Exception( 'Failed to execute query' );
            }
            if ( $stmt->rowCount() > 0 ) {
                return false;
            } else {
                return true;
            }
        } catch ( Exception $e ) {
            $this->respondWithInternalError( 'Error: ' . $e->getMessage() );
        }
        finally {
            $stmt = null;

        }

    }

    #initiateLoanRequest:: This method handles Loan requests On the platform...

    public function initiateLoanRequest( array $data )
 {
        $token = intval( $this->token() );
        # Prepare to check for loan-Plan value
        $checkSubscribedPlan = $this->checkSubscribedPlan( intval( $data[ 'plan' ] ) );

        if ( !$checkSubscribedPlan ) {
            $this->outputData( false, $_SESSION[ 'err' ], null );
            exit;
        }

        # Prepare the fields and values for the Loan query


        $imageUrl = $_ENV[ 'IMAGE_PATH' ] . "/$data[passport]";
        
        $fields = [
            'plan' => intval( $checkSubscribedPlan[ 'planid' ] ),
            'fname' => $data[ 'fullname' ],
            'amountToBorrow' => $data[ 'amountToBorrow' ],
            'usertoken' => $data[ 'usertoken' ],
            'means_of_identity' => $data[ 'means_of_identity' ],
            'identity_number' => $data[ 'identity_number' ],
            'occupation' => ( $data[ 'occupation' ] ),
            'passport_photo' => $data[ 'passport' ],
            'purpose_of_loan' => $data[ 'purpose_of_loan' ],
            'token' => $token,
            'imageUrl' => $imageUrl,
            'time' => time()
        ];

        $placeholders = implode( ', ', array_fill( 0, count( $fields ), '?' ) );
        $columns = implode( ', ', array_keys( $fields ) );
        $loanQuery = "INSERT INTO loan_records ($columns) VALUES ($placeholders)";

        # Execute the query and handle any errors
        try {
            $stmt = $this->conn->prepare( $loanQuery );
            $i = 1;
            foreach ( $fields as $value ) {
                $type = is_int( $value ) ? PDO::PARAM_INT : PDO::PARAM_STR;
                $stmt->bindValue( $i, $value, $type );
                $i++;
            }
            $stmt->execute();

            # Prepare the process  for the collateral query
            $collateral = $data[ 'collecteral' ][ 0 ];
            $saveColleteralInfo =  $this->saveColleteralInfo( $collateral, $token, $data[ 'usertoken' ] );
            if ( !$saveColleteralInfo ) {
                $this->outputData( false, $_SESSION[ 'err' ], null );
                exit;
            }

            # Prepare the process  for the Guarantor query
            $saveGuarantorInfo =  $this->saveGuarantorInfo( $data[ 'guarantor' ], $token );
            if ( !$saveGuarantorInfo ) {
                $this->outputData( false, $_SESSION[ 'err' ], null );
                exit;
            }

             #Prepare the  process for calculating MonthlyRepayment
            $monthlyRepayment = $this->calculateMonthlyRepayment($data[ 'amountToBorrow' ],$checkSubscribedPlan[ 'planid' ] );

            #calculateMonthsAheadAndSave::This method Calculate When User is expected to pay back..
            $calculateMonthsAhead = $this->calculateMonthsAheadAndSave( $checkSubscribedPlan[ 'planid' ], $data['usertoken'], $token, $monthlyRepayment );
            if(!$calculateMonthsAhead){
                $this->outputData(false, $_SESSION['err'], null);
                exit;
            }

            $mailer=  new Mailer();

            // $mailer->sendLoanNotificationToAdmin($data['fullname']);

            http_response_code( 201 );
            $output = $this->outputData( true, 'Loan application received. We will notify you upon approval', null );
            exit;
        } catch ( PDOException $e ) {
            $output = $this->respondWithInternalError( 'Error: ' . $e->getMessage() );
            exit;
        }
        finally {
            $stmt = null;
            $this->conn = null;
            unset($mailer);

        }

        return $output;

    }

    #saveGuarantorInfo:: This method saves guarantor info during loan Process

    public function saveGuarantorInfo( array $guarantor, int $loantoken ) {

        $isGuarantorSaved = false;

        foreach ( $guarantor as $guarantorInfo ) {

            #  Prepare the fields and values for the gurantor query
            $guarantorfields = [
                'name' => $guarantorInfo[ 'name' ],
                'phone'=> $guarantorInfo[ 'phone' ],
                'email' => $guarantorInfo[ 'email' ],
                'phone'=> $guarantorInfo['phone'],
                'token'=> $loantoken,
                'relationship'=> $guarantorInfo[ 'relationship' ],

            ];

            # Build the SQL query
            $placeholders = implode( ', ', array_fill( 0, count( $guarantorfields ), '?' ) );
            $columns = implode( ', ', array_keys( $guarantorfields ) );
            $sql = "INSERT INTO tblguarantor ($columns) VALUES ($placeholders)";

            #  Execute the query and handle any errors
            try {
                // $this->conn->beginTransaction();

                $stmt =  $this->conn->prepare( $sql );
                $i = 1;
                foreach ( $guarantorfields as $value ) {
                    $type = is_int( $value ) ? PDO::PARAM_INT : PDO::PARAM_STR;
                    $stmt->bindValue( $i, $value, $type );
                    $i++;
                }
                $stmt->execute();

                $isGuarantorSaved = true;

            } catch ( PDOException $e ) {
                $_SESSION[ 'err' ] = 'Error:'. $e->getMessage();
                // $this->conn->rollback();
                $isGuarantorSaved = false;

            }
            finally {
                $stmt = null;

            }

        }
        return $isGuarantorSaved;
    }

    #saveColleteralInfo:: This method saves colleteral  info during loan Process

    public function saveColleteralInfo( array $collateral, int $loantoken, int $usertoken ) {

        $isColleteralSaved = false;

        #  Prepare the fields and values for the collecteral query
        $imageUrl = $_ENV[ 'IMAGE_PATH' ] . "/$collateral[proof_of_ownership]";

        $collateralFields = [
            'category' => intval( $collateral[ 'category' ] ),
            'usertoken' => $usertoken,
            'years_of_usage' => $collateral[ 'years_of_usage' ],
            'proof_of_ownership' => $collateral[ 'proof_of_ownership' ],
            'watt' => $collateral[ 'watts' ],
            'price_bought' => $collateral[ 'price_bought' ],
            'token' => $loantoken,
            'imageUrl' => $imageUrl
        ];

        # Build the SQL query
        $collateralPlaceholders = implode( ', ', array_fill( 0, count( $collateralFields ), '?' ) );
        $collateralColumns = implode( ', ', array_keys( $collateralFields ) );
        $collateralQuery = "INSERT INTO tblcollecteral ($collateralColumns) VALUES ($collateralPlaceholders)";

        #  Execute the query and handle any errors
        try {

            $stmt = $this->conn->prepare( $collateralQuery );
            $i = 1;
            foreach ( $collateralFields as $collateralValue ) {
                $type = is_int( $collateralValue ) ? PDO::PARAM_INT : PDO::PARAM_STR;
                $stmt->bindValue( $i, $collateralValue, $type );
                $i++;
            }
            $stmt->execute();

            $isColleteralSaved = true;

        } catch ( PDOException $e ) {
            $_SESSION[ 'err' ] = 'Error:'. $e->getMessage();
            $isColleteralSaved = false;

        }
        finally {
            $stmt = null;

        }
        return $isColleteralSaved;

    }

    

    # calculateLoanDueDate::This method is responsible for storing the start and end dates of a loan.

    public function calculateLoanDueDate( array $data, int $loantoken, int $usertoken )
 {
        $isCalculatedDate = false;
        #  Prepare the fields and values for the insert query
        $fields = [
            'tbloan_token' => $loantoken,
            'usertoken' => $usertoken,
            'month'=> $data[ 'month' ],
            'priceToPay' => $data[ 'priceToPay' ],
            'remind_a_week_before'  => $data[ 'remind_a_week_before' ]

        ];

        # Build the SQL query
        $placeholders = implode( ', ', array_fill( 0, count( $fields ), '?' ) );
        $columns = implode( ', ', array_keys( $fields ) );
        $sql = "INSERT INTO loan_repayments ($columns) VALUES ($placeholders)";

        #  Execute the query and handle any errors
        try {
            $stmt =  $this->conn->prepare( $sql );
            $i = 1;
            foreach ( $fields as $value ) {
                $type = is_int( $value ) ? PDO::PARAM_INT : PDO::PARAM_STR;
                $stmt->bindValue( $i,   $value, $type );
                $i++;
            }
            $stmt->execute();

            $isCalculatedDate = true;

            exit;
        } catch ( PDOException $e ) {

             $this->respondWithInternalError( 'Error: ' . $e->getMessage() );
            $isCalculatedDate = false;
            exit;
        }
        finally {
            $stmt = null;
            // $this->conn = null;

        }

        return $isCalculatedDate;
    }

    # TcalculateMonthsAhead:: This function calculates the month(s) ahead for a given subscribed package
    public function calculateMonthsAheadAndSave( int $subscribedPackage, int $usertoken, int $loantoken, string $repaymentPerMonth ) {

        $startDate = new DateTime();

        for ( $i = 0; $i < $subscribedPackage; $i++ ) {
            $estimatedDate = $startDate->modify( '+1 month' );
            $monthExpectedToPay = $estimatedDate->format( 'Y-m-d' );

            # Calculate a week before due date.
			$remindAWeekBefore = date("Y-m-d", strtotime("-7 days", strtotime($monthExpectedToPay)));

            # Calculate a day before due date.
           $remindADayBefore = date("Y-m-d", strtotime("-1 day", strtotime($monthExpectedToPay)));

           # Calculate a day before due date.
           $remindThreeDaysBefore = date("Y-m-d", strtotime("-3 days", strtotime($monthExpectedToPay)));

            # Store month( s ) to pay And Amount To Pay.
            $saveWhenExpectedToPay = $this->saveWhenExpectedToPay($monthExpectedToPay, $usertoken, 
            $loantoken,  ceil($repaymentPerMonth), $remindADayBefore, $remindAWeekBefore, $remindThreeDaysBefore);
            if(!$saveWhenExpectedToPay){
                $this->outputData(false, $_SESSION['err'], null);
                return false;
            }

        }

        return true;
    }

    #saveWhenExpectedToPay:: This method saves month(s) Expected to pay back
    public function saveWhenExpectedToPay( $monthExpectedToPay, int $usertoken,
    int $loantoken, string $repaymentPerMonth, $remindADayBefore, $remindAWeekBefore, $remindThreeDaysBefore
    ) {

        $sql = "INSERT INTO loan_repayments (tbloan_token, usertoken, month, a_day_before, a_week_before, 3_days_before, priceToPay) 
        VALUES (:tbloan_token, :usertoken, :month, :a_day_before, :a_week_before, :3_days_before, :priceToPay)";
        $stmt = $this->conn->prepare( $sql );
        $stmt->bindParam( ':tbloan_token', $loantoken );
        $stmt->bindParam( ':usertoken', $usertoken );
        $stmt->bindParam( ':month', $monthExpectedToPay );
        $stmt->bindParam( ':a_day_before', $remindADayBefore );
        $stmt->bindParam( ':a_week_before', $remindAWeekBefore );
        $stmt->bindParam( ':3_days_before', $remindThreeDaysBefore );
        $stmt->bindParam( ':priceToPay', $repaymentPerMonth );

        #  Execute statement and return result
        try {
            if ( !$stmt->execute() ) {
                $_SESSION['err'] = "Unable to save repayment plan";
                return false;
            }

            return true;
        } catch ( Exception $e ) {
            $_SESSION[ 'err' ] = $e->getMessage();
            $this->respondWithInternalError( false, $_SESSION[ 'err' ], null );
            return false;
        }
        finally {
            $stmt = null;
        }

    }




#getAllLoanRecord::This method fetches all Loan records inthe databse
public function getAllLoanRecord()
{
    $dataArray = array();
    $sql = ' SELECT * FROM loan_records  ORDER BY status = 0 DESC ';
    try {
        $stmt = $this->conn->query($sql);
        $stmt->execute();
        $count = $stmt->rowCount();
        if ($count === 0) {
            $_SESSION['err'] = "No record found";
            return false;
        }
            $loanRecords = $stmt->fetchAll(PDO::FETCH_ASSOC);
            foreach ($loanRecords as $allLoanRecords) {
                $verifyNextOfKin = $this->verifyNextOfKin($allLoanRecords['usertoken']);
                $getAllLoanGuarantors = $this->getAllLoanGuarantors($allLoanRecords['token']);
                $getAllLoanColleterals = $this->getAllLoanColleterals($allLoanRecords['token']);
                $getAllRecordsOfMonthExpectedToPay = $this->getAllRecordsOfMonthExpectedToPay($allLoanRecords['token']);
                $array = [
                'packagePlan' => ($allLoanRecords['plan'] . ' ' . 'months'),
                'fullname' => ($allLoanRecords['fname']),
                'amountToBorrow' => ($allLoanRecords['amountToBorrow']),
                'isCompletedStatus' => ($allLoanRecords['isCompletedStatus'] == 1) ? 'Payment completed.' : 'Payment- ongoing.',
                'usertoken' => $allLoanRecords['usertoken'],
                'amount_debited_so_far' => ($allLoanRecords['amount_debited_so_far']),
                'means_of_identity' => ($allLoanRecords['means_of_identity']),
                'identity_number' => ($allLoanRecords['identity_number']),
                'occupation' => ($allLoanRecords['occupation']),
                'passport_photo' => ($allLoanRecords['imageUrl']),
                'purpose_of_loan' => ($allLoanRecords['purpose_of_loan']),
                'token' => ($allLoanRecords['token']),
                'nextOfKin' => $verifyNextOfKin,
                'RequestedOn' => $this->formatDate($allLoanRecords['time']),
                'loanStatus' => ($allLoanRecords['status'] == 1) ? 'Approved' : (($allLoanRecords['status'] == 2) ? 'Unapproved' : 'Pending'),
                'getAllLoanGuarantors' => $getAllLoanGuarantors,
                'getAllLoanColleterals' => $getAllLoanColleterals,
                'getAllRecordsOfMonthExpectedToPay' => $getAllRecordsOfMonthExpectedToPay
                ];

                array_push($dataArray, $array);
            }
       
    } catch (PDOException $e) {
        $_SESSION['err'] = "Error while retreiving loan:". $e->getMessage();
        return false;
    }finally{
        $stmt = null;
        $this->conn = null;
    }
    return $dataArray;
}


#getAllUserLoanRecord::This method fetches all Loan records to users dashboard
public function getAllUserLoanRecord(int $usertoken)
{
    $dataArray = array();
    $sql = "SELECT * FROM loan_records WHERE usertoken = :usertoken";
    try {
        $stmt = $this->conn->prepare($sql);
        $stmt->bindParam(':usertoken', $usertoken);
        $stmt->execute();
        $count = $stmt->rowCount();
        if ($count === 0) {
          $_SESSION['err'] = "No record found";
            return false;
        }
            $loanRecords = $stmt->fetchAll(PDO::FETCH_ASSOC);
            foreach ($loanRecords as $allLoanRecords) {
                $verifyNextOfKin = $this->verifyNextOfKin($allLoanRecords['usertoken']);
                $getAllLoanGuarantors = $this->getAllLoanGuarantors($allLoanRecords['token']);
                $getAllLoanColleterals = $this->getAllLoanColleterals($allLoanRecords['token']);
                $getAllRecordsOfMonthExpectedToPay = $this->getAllRecordsOfMonthExpectedToPay($allLoanRecords['token']);
                $array = [
                'packagePlan' => ($allLoanRecords['plan'] . ' ' . 'months'),
                'fullname' => ($allLoanRecords['fname']),
                'amountToBorrow' => ($allLoanRecords['amountToBorrow']),
                'isCompletedStatus' => ($allLoanRecords['isCompletedStatus'] == 1) ? 'Payment completed.' : 'Payment- ongoing.',
                'usertoken' => $allLoanRecords['usertoken'],
                'amount_debited_so_far' => ($allLoanRecords['amount_debited_so_far']),
                'means_of_identity' => ($allLoanRecords['means_of_identity']),
                'identity_number' => ($allLoanRecords['identity_number']),
                'occupation' => ($allLoanRecords['occupation']),
                'passport_photo' => ($allLoanRecords['imageUrl']),
                'purpose_of_loan' => ($allLoanRecords['purpose_of_loan']),
                'token' => ($allLoanRecords['token']),
                'nextOfKin' => $verifyNextOfKin,
                'RequestedOn' => $this->formatDate($allLoanRecords['time']),
                'loanStatus' => ($allLoanRecords['status'] == 1) ? 'Approved' : (($allLoanRecords['status'] == 2) ? 'Unapproved' : 'Pending'),
                'getAllLoanGuarantors' => $getAllLoanGuarantors,
                'getAllLoanColleterals' => $getAllLoanColleterals,
                'getAllRecordsOfMonthExpectedToPay' => $getAllRecordsOfMonthExpectedToPay
                ];

                array_push($dataArray, $array);
            }
       
    } catch (PDOException $e) {
        $_SESSION['err'] = "Error while retreiving loan:". $e->getMessage();
        return false;
    }finally{
        $stmt = null;
        $this->conn = null;
    }
    return $dataArray;
}

#getAllLoanGuarantors ::This method fetches All guarantors related in a loan requests
public function getAllLoanGuarantors(int $loantoken) {
    $dataArray = array();
    $sql = 'SELECT * FROM tblguarantor WHERE token = :loantoken ORDER BY id DESC';
    try {
        $stmt = $this->conn->prepare($sql);
        $stmt->bindParam(':loantoken', $loantoken);
        $stmt->execute();
        $count = $stmt->rowCount();
        if ($count > 0) {
            $guarantors = $stmt->fetchAll(PDO::FETCH_ASSOC);
            foreach ($guarantors as $allGuarantors) {
                $array = [
                    'guarantorName' => $allGuarantors['name'],
                    'guarantorEmail' => $allGuarantors['email'],
                    'guarantorPhone' => $allGuarantors['phone'],
                    'relationship' => $allGuarantors['relationship']
                ];
                array_push($dataArray, $array);
            }
        } else {
            $this->outputData(false, 'No record found', null);
        }
    } catch (PDOException $e) {
        $this->respondWithInternalError(false, 'Error getting loan guarantor: ' . $e->getMessage(), null);
        return false;
    } finally{
        $stmt = null;
        
    }
    return $dataArray;
}


# getAllRecordsOfMonthExpectedToPay ::This method fetches All months expected to repay LOAN
public function getAllRecordsOfMonthExpectedToPay(int $loantoken)
{
    $dataArray = array();
    try {
        $sql = 'SELECT month, priceToPay, tbloan_token, 
        payyment_status,  penalty, remind_a_day_before_status, 
        remind_a_week_before_status FROM loan_repayments WHERE tbloan_token = :token';
        $stmt = $this->conn->prepare($sql);
        $stmt->bindParam(':token', $loantoken);
        $stmt->execute();
        $expectedMonths = $stmt->fetchAll(PDO::FETCH_ASSOC);

        foreach ($expectedMonths as $listExpectedMonths) {
            $array = [
                'MonthScheduledToPay' => $listExpectedMonths['month'],
                'priceExpectedToPay_thousand' => $this->formatCurrency($listExpectedMonths['priceToPay']),
                'penaltyFee' => $listExpectedMonths['penalty'],
                'paymentStatus' => ($listExpectedMonths['payyment_status'] == 1) ? 'Paid' : (($listExpectedMonths['payyment_status'] == 2) ? 'Not-paid' : 'Payment-pending'),
                'remind_a_day_before_status' => $listExpectedMonths[ 'remind_a_day_before_status' ] === 1  ? "Sent" : "Pending",
                'remind_a_week_before_status' => $listExpectedMonths[ 'remind_a_week_before_status' ] === 1  ? "Sent" : "Pending"
            ];

            array_push($dataArray, $array);
        }

    
    } catch (PDOException $e) {
        echo "Error: " . $e->getMessage();
        // $this->respondWithInternalError(false, "Error: " . $e->getMessage(), null);
        exit;
        return false;
    }finally{
        $stmt = null;
    }
    return $dataArray;
}

#getAllLoanColleterals ::This method fetches all Colletcteral related to a loan request.
public function getAllLoanColleterals(int $loantoken)
{
    try {
        $sql = 'SELECT * FROM tblcollecteral WHERE token = :token';
        $stmt = $this->conn->prepare($sql);
        $stmt->bindParam(':token', $loantoken);
        $stmt->execute();
        $collaterals = $stmt->fetch(PDO::FETCH_ASSOC);
        $getCategory = $this->getProductCategory($collaterals['category']);

            $array = [
                'catname' => $getCategory['catname'],
                'years_of_usage' => $collaterals['years_of_usage'],
                # 'means_of_proof' => $collaterals['means_of_proof'],
                'proof_of_ownership' => ($collaterals['imageUrl']),
                'watt' => $collaterals['watt'],
                'price_bought' => $this->formatCurrency($collaterals['price_bought']),
            ];
    
    } catch (PDOException $e) {
        $this->respondWithInternalError(false, "Error: " . $e->getMessage(), null);
        exit;
        return false;
    }finally{
        $stmt = null;
    }
    return $array;
}



#approveLoanRequest::This method approves loan requests.
public function approveLoanRequest($usertoken, $loanToken) {
    $status = 1;

    $getUserData = $this->getUserData($usertoken);

    try {
        if (!$this->updateLoanRecordStatus($usertoken, $loanToken, $status)) {
            $this->outputData(false, $_SESSION['err'], null);
            exit;
            
        }

        $mailer = new Mailer();
        $mailer->notifyLoanApproval($getUserData['mail'], $getUserData['fname']);
        $_SESSION['err'] = "Approved";

    } catch (Exception $e) {
        $_SESSION['err'] = "Unable to process Loan requests:". $e->getMessage();
     return false;
     exit;
    }finally{
        #Free nessacry resources.
        unset($mailer);
        unset($status);
    }
    return true;
}


#disapproveLoanRequest::This method disapproveLoanRequest a  loan requests.
public function disapproveLoanRequest($usertoken, $loanToken, $message) {
    $status = 2;

    $getUserData = $this->getUserData($usertoken);

    try {
        if (!$this->updateLoanRecordStatus($usertoken, $loanToken, $status)) {
            $this->outputData(false, $_SESSION['err'], null);
            exit;
            
        }

        $mailer = new Mailer();
        $mailer->notifyLoanDisapproval($getUserData['mail'], $getUserData['fname'], $message);
        $_SESSION['err'] = "Disapproved";

    } catch (Exception $e) {
        $_SESSION['err'] = "Unable to process Loan requests:". $e->getMessage();
     return false;
     exit;
    }finally{
        #Free nessacry resources.
        unset($mailer);
        unset($status);
    }
    return true;
}


 #updateLoanRecordStatus::This method updates the status of  aloan request.
 public function updateLoanRecordStatus(int $userToken, int $loanToken, int $status) {
    try {
        $sql = 'UPDATE loan_records SET status = :status WHERE usertoken = :userToken AND token = :loanToken';
        $stmt = $this->conn->prepare($sql);
        $stmt->bindParam(':status', $status);
        $stmt->bindParam(':userToken', $userToken);
        $stmt->bindParam(':loanToken', $loanToken);
        $stmt->execute();
    } catch (PDOException $e) {
        $_SESSION['err']  = 'Error While updating status: ' . $e->getMessage();
        return false;
    }finally{
        $stmt = null;
        $this->conn = null;
    }
    return true;
}



}
