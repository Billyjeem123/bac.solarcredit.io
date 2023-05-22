<?php

class Category Extends AbstractClasses {

    private $conn  =  null;

    public function __construct( Database $database )
 {

        $this->conn = $database->connect();
    }

    #This method is specifically meaant for? your guess is as good as mine.. wait did you know it? Yes, to create category..
    public function createCategory( array $data )
 {
        try {
            if(!$this->verifyIfCatExists($data['catname'])){
                 $this->outputData( false, "$data[catname] already exists", null );
                return false;

            }

            $sql = 'INSERT INTO tblcategory (catname) VALUES (:catname)';
            $stmt = $this->conn->prepare( $sql );
            $stmt->bindParam( ':catname', $data['catname'] );

            if ( !$stmt->execute() ) {
                $this->outputData( false, 'Unable to process query', null );
                return false;
            } else {
                $this->outputData( true, "{$data['catname']} created", null );
                return true;
            }

        } catch ( Exception $e ) {
            $this->respondWithInternalError( false, $e->getMessage(), null );
            return false;
        }
        finally {
            $stmt = null;
            $this->conn = null;

        }
    }

     #verifyIfCatExists::tThis methods checks if  a category exists before creating
    public function verifyIfCatExists( string $catname )
 {
        try {
            $catname = $this->sanitizeInput( $catname );
            $sql = 'SELECT catname FROM tblcategory WHERE catname = :catname';
            $stmt = $this->conn->prepare( $sql );
            $stmt->bindParam( ':catname', $catname );

            if ( !$stmt->execute() ) {
                throw new Exception( 'Something went wrong, please try again.' );
            } else {
                if ( $stmt->rowCount() === 0 ) {
                    return true;
                } else {
                    return false;
                }
            }
        } catch ( PDOException $e ) {
            $this->outputData(false, 'Error while fetching'. $e->getMessage(), null );
            return false;
        }

        finally {
            $stmt = null;
        }
    }

    #This method UpdatesCategory from the databse.
    public function updateCategory( array $data )
 {
        try {
            $sql = 'UPDATE tblcategory SET ';
            $sql .= 'catname = :catname ';
            $sql .= 'WHERE id = :catid ';
            $stmt = $this->conn->prepare( $sql );
            $stmt->bindParam( ':catname', $data[ 'catname' ] );
            $stmt->bindParam( ':catid', $data[ 'catid' ] );

            if ( !$stmt->execute() ) {
                $this->outputData( false, 'Could not update category, please try again...', null );
            } else {
                $this->outputData( true, 'Record updated..', null );
                return true;
            }
        } catch ( PDOException $e ) {
            $this->outputData( false, 'Error while updating'.$e->getMessage(), null );
            return false;
        }
        finally {
            $stmt = null;
            $this->conn = null;
        }
    }

    #DeleteCategory:: This method deletes deletes category from the database
    public function deleteCategory( int $catid ) {
        try {
            $sql = 'DELETE FROM tblcategory WHERE id = :catid';
            $stmt = $this->conn->prepare( $sql );
            $stmt->bindParam( ':catid', $catid );

            if ( $stmt->execute() ) {
                $this->outputData( true, 'Deleted', null );
                return true;
            } else {
                $this->outputData( false, 'Could not delete category, please try again...', null );
                return false;
            }
        } catch ( PDOException $e ) {
            $this->outputData( false, 'Error while processing'.$e->getMessage(), null );
            return false;
        }

        $stmt = null;
        $this->conn = null;
    }

    #This methiod fetches All categories..
    public function getAllCategories() {
        try {
            $dataArray = array();
            $sql = 'SELECT * FROM tblcategory';
            $stmt = $this->conn->query( $sql );
            if ( !$stmt->execute() ) {
                $_SESSION[ 'err' ] = 'Something went wrong, please try again..';
                return false;
            } else {
                $number = $stmt->rowCount();
                if ( $number == 0 ) {
                    $_SESSION[ 'err' ] = 'No category availabe..';
                    return false;
                } else {
                    if ( $category = $stmt->fetchAll( PDO::FETCH_ASSOC ) ) {
                        foreach ( $category as $tribute ) {
                            $array = [
                                'catid' => ( $tribute[ 'id' ] ),
                                'catname' => ( $tribute[ 'catname' ] )
                            ];
                            array_push( $dataArray, $array );
                        }
                    } else {
                        $_SESSION[ 'err' ] = 'Unable to fetch';
                        return false;
                    }
                }
            }
        } catch ( PDOException $e ) {
           echo $_SESSION[ 'err' ] = $e->getMessage();
            $this->outputData( false, $_SESSION[ 'err' ], null );
            return false;
        }
        finally {
            $stmt = null;
            $this->conn   = null;
        }
        return $dataArray;
    }


    
    #This method get category by it's id
    public function getCategoryById( int $catid ) {
        try {
            $sql = 'SELECT * FROM tblcategory WHERE id = :catid';
            $stmt = $this->conn->prepare( $sql );
            $stmt->bindParam( ':catid', $catid );

            if (!$stmt->execute() ) {
                $_SESSION[ 'err' ] = 'Failed to execute query';
            return false;
            }

            if($stmt->rowCount() === 0){
                $this->outputData(false, "Category with  id $catid not found");
                exit;
            }
            $category = $stmt->fetch( PDO::FETCH_ASSOC );
            $array = [
                'catid' => ( $category[ 'id' ] ),
                'catname' => ( $category[ 'name' ] )
            ];

        } catch ( PDOException $e ) {
            $_SESSION[ 'err' ] = $e->getMessage();
            $this->respondWithInternalError( false, $_SESSION[ 'err' ], null );
            return false;
        }
        finally {
            $stmt = null;
            $this->conn = null;
            // Terminate the database connection
        }
        return $array;
    }

   

}

