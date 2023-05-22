<?php

class Kyc  extends AbstractClasses{

    private   $conn;

    public function __construct( Database $database )
 {

        $this->conn = $database->connect();
    }



    public function registerUserKyc(array $data)
    {

        #checkVeridicationMeans.::This checks if userhas already done KYC.
        if(!$this->hasVerifiedKyc($data['usertoken'])){
            $this->outputData(false, 'Account has already been verified', null);
            exit;
        }

        #checkVeridicationMeans.......

        // $connectToThirdPartyAPI = $this->connectToThirdPartyAPI($empty = [], $url = 'null');
        // $decodeResponse =  json_decode( $connectToThirdPartyAPI );
        // if(!$decodeResponse){
        //    $output =  $this->outputData( false, 'Failed to verify BVN, try again later', null );
        //     exit;
        // }

        $imageUrl = $_ENV[ 'IMAGE_PATH' ] . "/$data[image]";
        #  Prepare the fields and values for the insert query
        $fields = [
            'usertoken' => $data[ 'usertoken' ],
            'fname' => $data[ 'fname' ],
            'profession'=> $data[ 'occupation' ],
            'means_identity' => $data['identity'],
            'identiy_number'  => $data[ 'identity_num' ],
            'address' => $data['address'],
            'photo' => ($data['photo']),
            'imageUrl' => ($imageUrl),
            'status' => 1,
            'time' => time()

        ];

        # Build the SQL query
        $placeholders = implode( ', ', array_fill( 0, count( $fields ), '?' ) );
        $columns = implode( ', ', array_keys( $fields ) );
        $sql = "INSERT INTO tblkyc ($columns) VALUES ($placeholders)";

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
            $output = $this->outputData( true, 'KYC verification successful', null );
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
    #hasVerifiedKyc::This method checks if user has verified KYC
    public function hasVerifiedKyc( string $usertoken ) {
        try {
            $sql = 'SELECT usertoken, status FROM tblkyc WHERE
             usertoken = :usertoken  AND  status = 1';
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
            $this->respondWithInternalError('Error: ' . $e->getMessage());
        }
        finally {
            $stmt = null;

        }
    }



}