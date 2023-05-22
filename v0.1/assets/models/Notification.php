<?php

class Notification extends AbstractClasses {

    private   $conn;

    public function __construct( Database $database )
 {
        $this->conn = $database->connect();
    }

    #notifyUser::This method notifies  user of any necessary info

    public function notifyUser( $context, $userToken )
 {
        $currentTime = time();

        $sql = "INSERT INTO tblnotify (context, usertoken, time)
            VALUES (:context, :userToken, :time)";

        try {
            $stmt = $this->conn->prepare( $sql );
            $stmt->bindParam( ':context', $context );
            $stmt->bindParam( ':userToken', $userToken );
            $stmt->bindParam( ':time', $currentTime );

            $stmt->execute();
            return true;
        } catch ( PDOException $e ) {
            // Handle the error
            $_SESSION[ 'err' ] = $e->getMessage();
            return false;
        }
        finally {
            $stmt = null;
            $this->conn = null;
        }
    }

    #fetchNotification::This method fetches all notificatiosn

    public function getAllNotification( int $usertoken )
 {
        $dataArray = array();
        try {
            $sql = 'SELECT id, context, time, status FROM tblnotify WHERE usertoken = :usertoken  ORDER BY id DESC';
            $stmt = $this->conn->prepare( $sql );
            $stmt->bindParam( ':usertoken', $usertoken, PDO::PARAM_STR );
            if ( !$stmt->execute() ) {
                $_SESSION[ 'err' ] = 'Something went wrong, please try again..';
                return false;
            } else {
                $notifyArray = $stmt->fetchAll( PDO::FETCH_ASSOC );
                if ( $notifyArray ) {
                    foreach ( $notifyArray as $user ) {
                        $array = array(
                            'notifyToken' => $user[ 'id' ],
                            'abtMessage' => $user[ 'context' ],
                            'date' => $this->formatDate( $user[ 'time' ] ),
                            'status' => ( $user[ 'status' ] == 1 ) ? true  : false
                        );
                        array_push( $dataArray, $array );
                    }
                } else {
                    return false;
                }
            }
        } catch ( PDOException $e ) {
            $_SESSION[ 'err' ] = $e->getMessage();
            return false;
        }
        finally {
            $stmt = null;
            $this->conn = null;

        }
        return $dataArray;
    }

    #viewNotification:: This smethod allows user to view notifiction( s )
    public function viewNotification( int $notifyToken )
 {

        if ( !$this->markNotificationAsRead( $notifyToken ) ) {
            $this->outputData( false, $_SESSION[ 'mark' ], null );
            exit;
        }

        try {
            $sql = 'SELECT context, time, id, status FROM tblnotify WHERE id = :notifyToken';
            $stmt = $this->conn->prepare( $sql );
            $stmt->bindParam( ':notifyToken', $notifyToken );
            $stmt->execute();

            if($stmt -> rowCount() === 0){
                $_SESSION['mark'] = "No item found";
                return false;
            }

            if ( $selectedNotification = $stmt->fetch( PDO::FETCH_ASSOC ) ) {
                $array = array(
                    'notifyToken' => $selectedNotification[ 'id' ],
                    'context' => $selectedNotification[ 'context' ],
                    'status' => ( $selectedNotification[ 'status' ] == 1 ) ? true : false,
                    'time' => $this->formatDate($selectedNotification['time'])
                );
            } else {
                return false;
            }

        } catch ( PDOException $e ) {
            $this->respondWithInternalError('Something went wrong, please try again:'. $e->getMessage());
            return false;
        }finally{
            $stmt = null;
            $this->conn = null;

        }
        return $array;
    }

    #markNotificationAsRead::This method  updates status for Notification
    public function markNotificationAsRead(int $notifyToken) {
        try {
            $sql = "UPDATE tblnotify SET status = 1 WHERE id = :notifyToken";
            $stmt = $this->conn->prepare($sql);
            $stmt->bindParam( ':notifyToken', $notifyToken );
            $stmt->execute();
        } catch (PDOException $e) {
            $_SESSION[ 'mark' ] = "Error updating notification status: " . $e->getMessage();
            return false;
        }finally{
            $stmt = null;
        }
        return true;
    }
    

}