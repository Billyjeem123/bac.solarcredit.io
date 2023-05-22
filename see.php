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
             $loantoken,  $repaymentPerMonth, $remindADayBefore, $remindAWeekBefore, $remindThreeDaysBefore);
            if(!$saveWhenExpectedToPay){
                $this->outputData(false, $_SESSION['err'], null);
                return false;
            }

        }

        return true;
    }

    #saveWhenExpectedToPay:: This method saves month(s) Expected to pay back
    public function saveWhenExpectedToPay( $monthExpectedToPay, int $usertoken,
    int $loantoken, string $repaymentPerMonth,  string $remindADayBefore,  string $remindAWeekBefore, string $remindThreeDaysBefore
    ) {

        $sql = "INSERT INTO loan_repayments (tbloan_token, usertoken, month, a_day_before,  a_week_before, 3_days_before, priceToPay) 
        VALUES (:tbloan_token, :usertoken, :month, :a_day_before, :a_week_before, :3_days_before,  :priceToPay)";
        $stmt = $this->conn->prepare( $sql );
        $stmt->bindParam( ':tbloan_token', $loantoken );
        $stmt->bindParam( ':usertoken', $usertoken );
        $stmt->bindParam( ':month', $monthExpectedToPay );
        $stmt->bindParam( ':a_day_before', $remindADayBefore );
        $stmt->bindParam( ':a_week_before', $remindAWeekBefore );
        $stmt->bindParam( ':3_days_before', $remindThreeDaysBefore );

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
