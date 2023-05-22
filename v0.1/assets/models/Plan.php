<?php

class Plan extends AbstractClasses {

    private   $conn;

    public function __construct( Database $database )
 {

        $this->conn = $database->connect();
    }

    #createPlan:: This method creates subscription plan

    public function createPlan( $data )
 {
        $plan_value = intval( $data[ 'plan_value' ] );

        $checkIfPlanExists = $this->checkIfPlanExists( $plan_value );
        if ( !$checkIfPlanExists ) {
            $this->outputData( false, 'Plan already exists', null );
            return false;

        }
        $sql = "INSERT INTO tblplan (plan, plan_desc, plan_value) 
        VALUES (:plan, :plan_desc, :plan_value)";
        $stmt = $this->conn->prepare( $sql );
        $stmt->bindParam( ':plan', $data[ 'plan' ] );
        $stmt->bindParam( ':plan_desc', $data[ 'plan_dec' ] );
        $stmt->bindParam( ':plan_value', $plan_value, PDO::PARAM_INT );

        #  Execute statement and return result
        try {
            if ( !$stmt->execute() ) {
                $this->outputData( false, 'Unable to upload plan', null );
                exit;
            }
            http_response_code( 201 );
            $this->outputData( true, 'New plan created...', null );
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

    #This method checks if plan exists in the table

    private function checkIfPlanExists( int $plan_value ): bool {

        try {
            $sql = 'SELECT plan_value FROM tblplan WHERE plan_value = :plan_value';
            $stmt = $this->conn->prepare( $sql );
            $stmt->bindParam( ':plan_value', $plan_value, PDO::PARAM_INT );
            $stmt->execute();
            if ( $stmt->rowCount() === 0 ) {
                return true;
            }
            return false;
        } catch ( PDOException $e ) {
            $_SESSION[ 'err' ] = $e->getMessage();
            $this->respondWithInternalError( $_SESSION[ 'err' ] );
        }
        finally {
            $stmt = null;
        }
    }

    public function getAllPlans() {

        $dataArray = array();

        $sql = 'SELECT * FROM tblplan ORDER BY id DESC ';
        $stmt = $this->conn->prepare( $sql );
        try {
            $stmt->execute();
            $plans = $stmt->fetchAll( PDO::FETCH_ASSOC );

            foreach ( $plans as $allPlans ) {
                $array = [
                    'planid' => $allPlans[ 'id' ],
                    'plan' => $allPlans[ 'plan' ],
                    'planDesc' => $allPlans[ 'plan_desc' ],
                ];

                array_push( $dataArray, $array );
            }

        } catch ( PDOException $e ) {
            $_SESSION[ 'err' ] = 'Unable to retrieve plans'.$e->getMessage();
            return false;
        }
        finally {
            $stmt = null;
            $this->conn = null;
        }
        return $dataArray;
    }

    public function deletePlan( int $planid ) {

        try {
            $sql = 'DELETE FROM tblplan WHERE id = :planid';
            $stmt = $this->conn->prepare( $sql );
            $stmt->bindParam( ':planid', $planid );
            $stmt->execute();
            if ( $stmt->rowCount() === 0 ) {
                http_response_code( 404 );
                $_SESSION[ 'err' ] = 'Plan id does not exists';
                return false;
                exit;
            }
            $_SESSION[ 'err' ] = 'Plan deleted';
            return true;
        } catch ( PDOException $e ) {
            $_SESSION[ 'err' ] = $e->getMessage();
            $this->respondWithInternalError( $_SESSION[ 'err' ] );
            return false;
        }
        finally {
            $stmt = null;
            $this->conn  = null;
        }

    }

}