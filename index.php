<?php

class Product extends AbstractClasses
 {

    private   $conn;

    public function __construct( Database $database )
 {

        $this->conn = $database->connect();
    }

    #Upload product::This method is specifially meant for creating poroduct

    public function createProducts( array $data )
 {

        $watts =  0;
        $quantity = ( int ) $data[ 'pquantity' ];
        $price =  ( $data[ 'price' ] );
        $usertoken = ( int ) $data[ 'usertoken' ];
        $catid = ( int ) $data[ 'catid' ];
        $token = ( int ) $this->token();
        $volt = ( int ) $data[ 'volt' ];
        $size = $data[ 'size' ];

        $getUserType =  ( $this->getUserData( $data[ 'usertoken' ] )[ 'userType' ] === 'admin' ) ? 1 : 0;

        $imageUrl = $_ENV[ 'IMAGE_PATH' ] . "/$data[pimage]";
        #  Prepare the fields and values for the insert query

        $fields = [
            'usertoken' => $usertoken,
            'pname' => $data[ 'pname' ],
            'putoken' => $token,
            'pquantity' => $quantity,
            'pdesc'  => $data[ 'pdesc' ],
            'price' => $price,
            'pimage' => $data[ 'pimage' ],
            'type' => 'Solarproduct',
            'status' => 1,
            'pcat_id' => $catid,
            'watts' => $watts,
            'volt' => $volt,
            'unit' => $data[ 'unit' ],
            'size' => $size,
            'status' => $getUserType,
            'imageUrl' => $imageUrl,
            'time' => time()

        ];
        # Build the SQL query
        $placeholders = implode( ', ', array_fill( 0, count( $fields ), '?' ) );
        $columns = implode( ', ', array_keys( $fields ) );
        $sql = "INSERT INTO tblproducts ($columns) VALUES ($placeholders)";

        #  Execute the query and handle any errors
        try {
            $stmt =  $this->conn->prepare( $sql );
            $i = 1;
            foreach ( $fields as $value ) {
                $type = is_int( $value ) ? PDO::PARAM_INT : ( is_float( $value ) ? PDO::PARAM_STR : PDO::PARAM_STR );
                $stmt->bindValue( $i,  $value, $type );
                $i++;
            }
            $stmt->execute();

            $output = $this->outputData( true, 'Product uploaded', null );
        } catch ( PDOException $e ) {

            $output  = $this->respondWithInternalError( 'Error: ' . $e->getMessage() );
        }
        finally {
            $stmt = null;
            $this->conn = null;
        }

        return $output;
    }

    # uploadImageToServer :: This method uploads a product image
    # The idea is once  a product is uploaded this method returns
    # The name of the image

    public function uploadImageToServer( array $image ): array
 {
        $imageInfo = array();

        # Get the image file information
        $imageName = $image[ 'name' ];
        $imageTmp = $image[ 'tmp_name' ];
        # Check if at least profile  image file is present
        if ( ( !isset( $imageName ) || empty( $imageName ) ) ) {
            $_SESSION[ 'err' ] = 'Please select an image to upload';
            return null;
        }

        # Valid file extensions
        $valid_extensions = array( 'jpg', 'jpeg', 'png', 'gif' );

        # Test for profile image file extension
        if ( isset( $imageName ) && !empty( $imageName ) ) {
            $imageName_ext = strtolower( pathinfo( $imageName, PATHINFO_EXTENSION ) );
            if ( !in_array( $imageName_ext, $valid_extensions ) ) {

                $_SESSION[ 'err' ] = 'Only JPG, JPEG, PNG and GIF files are allowed';
                return null;
            } else {
                # Save the property image  file
                $mixImageNameWithTime = time() . '_' . $imageName;
                $newImageName = $_ENV[ 'APP_NAME' ] . '_' . $mixImageNameWithTime;
                $pathToImageFolder = ( $_SERVER[ 'DOCUMENT_ROOT' ] . '/uploads/' . $newImageName );
                if ( !file_exists( $imageTmp ) || !is_readable( $imageTmp ) ) {

                    $_SESSION[ 'err' ] = 'Unable to upload the  image. Please try again later';
                    return null;
                } else if ( move_uploaded_file( $imageTmp, $pathToImageFolder ) ) {
                    $imageInfo[ 'image' ] = $newImageName;
                } else {
                    $imageName = null;
                }
            }
        }
        http_response_code( 200 );
        return $imageInfo;
    }

    #FertchProducts:: This method fetches all  available products

    public function getAllProducts()
 {
        try {
            $dataArray = array();
            $sql = 'SELECT id, pname, putoken, pdesc, imageUrl,price  FROM tblproducts WHERE status = 1 ORDER BY id DESC';
            $stmt = $this->conn->query( $sql );
            $stmt->execute();
            $products = $stmt->fetchAll( PDO::FETCH_ASSOC );
            $count = count( $products );

            if ( $count === 0 ) {
                $_SESSION[ 'err' ] = 'No products available';
                return;
            }

            foreach ( $products as $allProducts ) {

                $array = [
                    'productname' => $allProducts[ 'pname' ],
                    'productToken' => $allProducts[ 'putoken' ],
                    'productDesc' => $allProducts[ 'pdesc' ],
                    'productPrice_thousand' => $this->formatCurrency( $allProducts[ 'price' ] ),
                    'productPrice' => ( $allProducts[ 'price' ] ),
                    'productImage' => ( $allProducts[ 'imageUrl' ] ),
                ];

                array_push( $dataArray, $array );
            }

            #  return $dataArray;
        } catch ( Exception $e ) {
            $_SESSION[ 'err' ] =  'Error: ' . $e->getMessage();
            return false;
        }
        finally {
            $stmt = null;
            $this->conn = null;
        }

        return $dataArray;
    }

    #FertchProducts:: This method fetches all  available products

    public function getProductByToken( int $producttoken )
 {
        try {
            $dataArray = array();
            $sql = 'SELECT * FROM tblproducts WHERE putoken = :productToken';
            $stmt = $this->conn->prepare( $sql );
            $stmt->bindParam( ':productToken', $producttoken );
            $stmt->execute();
            $products = $stmt->fetch( PDO::FETCH_ASSOC );
            $count = count( $products );

            if ( $count === 0 ) {

                $_SESSION[ 'err' ] = 'No products available';
                return;
            }

            $array = [
                'productname' => $products[ 'pname' ],
                'productToken' => $products[ 'putoken' ],
                'productQuantity' => $products[ 'pquantity' ],
                'productDesc' => $products[ 'pdesc' ],
                'productPrice_thousand' => $this->formatCurrency( $products[ 'price' ] ),
                'productPrice' => ( $products[ 'price' ] ),
                'productImage' => ( $products[ 'imageUrl' ] ),
                'productType' => $products[ 'type' ],
                'ownertoken' => intval( $products[ 'usertoken' ] ),
                'watts' => intval( $products[ 'watts' ] )
            ];

        } catch ( Exception $e ) {
            $_SESSION[ 'err' ] =  'Error: ' . $e->getMessage();
            return false;
        }
        finally {
            $stmt = null;
            $this->conn = null;
        }

        return $array;
    }

    #addProductToCart This emethod adds  a product to cart..

    public function addProductToCart( array $data )
 {
        try {
            if ( $this->checkIfItemExistInCart( $data[ 'usertoken' ], $data[ 'productToken' ] ) ) {
                $this->outputData( false, 'Item already exists', null );
                exit;
            }
            $productQuantity = 1;
            $sql = "INSERT INTO tblcarts(uToken, pToken, productQuantity) 
                    VALUES(:uToken, :pToken, :productQuantity)";
            $stmt = $this->conn->prepare( $sql );
            $stmt->bindParam( ':uToken', $data[ 'usertoken' ] );
            $stmt->bindParam( ':pToken', $data[ 'productToken' ] );
            $stmt->bindParam( ':productQuantity', $productQuantity );
            $stmt->execute();
            $_SESSION[ 'err' ] = 'Item added to cart';
            return true;
        } catch ( PDOException $e ) {
            $_SESSION[ 'err' ] = 'Error adding item to cart: ' . $e->getMessage();
            exit;
        }
        finally {
            $stmt = null;
            $this->conn = null;
        }
    }

    #checkIfItemExistInCart::This method checks if item alreay exists in cart

    public function checkIfItemExistInCart( int $userToken, int $productToken )
 {
        try {
            $sql = 'SELECT COUNT(*) AS count FROM tblcarts 
            WHERE uToken = :userToken AND pToken = :productToken';
            $stmt = $this->conn->prepare( $sql );
            $stmt->bindParam( ':userToken', $userToken, PDO::PARAM_INT );
            $stmt->bindParam( ':productToken', $productToken, PDO::PARAM_INT );
            $stmt->execute();
            $result = $stmt->fetch( PDO::FETCH_ASSOC );

            if ( $result[ 'count' ] == 1 ) {
                return true;
            } else {
                return false;
            }
        } catch ( PDOException $e ) {
            $_SESSION[ 'err' ] = 'Error confirming cart: ' . $e->getMessage();
            $this->outputData( false, $_SESSION[ 'err' ], null );
            return false;
        }
        finally {
            $stmt = null;
            #  $this->conn  = null;
        }
    }

    #getAllCartItems:: This method fetches all available cart items in the cart...

    public function getAllCartItems( int $usertoken )
 {

        try {
            $sql = 'SELECT uToken, pToken, productQuantity FROM tblcarts WHERE uToken = :uToken';
            $stmt = $this->conn->prepare( $sql );
            $stmt->bindParam( ':uToken', $usertoken, PDO::PARAM_INT );
            $stmt->execute();

            if ( $stmt->rowCount() === 0 ) {

                $this->outputData( false, 'No item found', null );
                exit;
            }

            $cartItems = $stmt->fetchAll( PDO::FETCH_ASSOC );

            $dataArrays = array();
            #  Initialize a variable to hold the total price
            $total = 0;
            foreach ( $cartItems as $allCartItems ) {
                #  Retrieve the product token and quantity for the current cart item
                $productToken = $allCartItems[ 'pToken' ];
                $productQuantity = $allCartItems[ 'productQuantity' ];

                #  Retrieve the product data for the current product token
                $getProductByItRelatedToken = $this->getProductByItRelatedToken( $productToken );

                #  Calculate the subtotal for the current cart item and add it to the total price
                $productPrice = $getProductByItRelatedToken[ 'productPrice' ];
                $subtotal = $productQuantity * $productPrice;
                $total += $subtotal;

                #  Create an array to hold the current product data and add it to the cart data array
                $cartItem = [
                    'productname' => $getProductByItRelatedToken[ 'productname' ],
                    'productToken' => $getProductByItRelatedToken[ 'productToken' ],
                    'maximumQuantity' => intval( $getProductByItRelatedToken[ 'productQuality' ] ),
                    'productQuantity' => intval( $productQuantity ),
                    'productPrice_thousands' => $this->formatCurrency( $productPrice ),
                    'productPrice' => ( $productPrice ),
                    'productType' => $getProductByItRelatedToken[ 'productType' ],
                    'ownertoken' => $getProductByItRelatedToken[ 'ownertoken' ],
                    'productPrice_thousand' => $this->formatCurrency( $subtotal ),
                    'productImage' => $getProductByItRelatedToken[ 'productImage' ]
                ];
                array_push( $dataArrays, $cartItem );
            }

            #  Create an array to hold the final cart data
            $dataResult = [
                'Products' => $dataArrays,
                'TotalPrice_thousand' => $this->formatCurrency( $total ),
                'TotalPrice' => $total
            ];
        } catch ( PDOException $e ) {
            #  Handle any PDO exceptions
            $_SESSION[ 'err' ] = 'PDO Exception: ' . $e->getMessage();
            $this->respondWithInternalError( $_SESSION[ 'err' ] );
            exit;
        } catch ( Exception $e ) {
            #  Handle any other exceptions
            $_SESSION[ 'err' ] = ' Exception: ' . $e->getMessage();
            $this->respondWithInternalError( $_SESSION[ 'err' ] );
            exit;
        }
        finally {

            $stmt = null;
            $this->conn = null;
        }
        #  Return the final cart data array
        return $dataResult;
    }

    #getProductByItRelatedToken ::This metod fetches product by related Token

    public function getProductByItRelatedToken( $productToken )
 {
        $dataArray = array();

        try {
            $sql = 'SELECT * FROM tblproducts WHERE putoken = :pToken';
            $stmt = $this->conn->prepare( $sql );
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
                'watts' => intval( $relatedProducts[ 'watts' ] ),
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
        }

        return $array;
    }

    #removeProductFromCart ::This method delete a cart item from cart

    public function removeProductFromCart( array  $data )
 {
        try {
            $sql = 'DELETE FROM tblcarts WHERE uToken = :uToken AND pToken = :pToken';
            $stmt = $this->conn->prepare( $sql );
            $stmt->bindParam( ':uToken', $data[ 'usertoken' ] );
            $stmt->bindParam( ':pToken', $data[ 'productToken' ] );
            $stmt->execute();
            $rowCount = $stmt->rowCount();

            if ( $rowCount > 0 ) {
                $_SESSION[ 'err' ] = 'Item removed from cart';
                return true;
            } else {
                $_SESSION[ 'err' ] = 'Item not found in cart.';
                return false;
            }
        } catch ( PDOException $e ) {
            #  handle the exception here
            $_SESSION[ 'err' ] = 'Unable to get delete product items: ' . $e->getMessage();
            return false;
        }
        finally {
            $stmt = null;
            $this->conn = null;
        }
    }

    #increaseCartItemQuantity:: This method increases cart item quantity

    public function increaseCartItemQuantity( array $data )
 {
        try {
            $sql = "UPDATE tblcarts SET productQuantity = productQuantity + 1
             WHERE uToken = :userToken AND pToken = :productToken";
            $stmt = $this->conn->prepare( $sql );
            $stmt->bindParam( ':userToken', $data[ 'usertoken' ] );
            $stmt->bindParam( ':productToken', $data[ 'productToken' ] );
            $stmt->execute();

            $rowCount = $stmt->rowCount();
            if ( $rowCount > 0 ) {
                $_SESSION[ 'err' ] = 'Quantity increased';
                return true;
            } else {
                $_SESSION[ 'err' ] = 'No rows updated';
                return false;
            }
        } catch ( PDOException $e ) {
            $_SESSION[ 'err' ] = 'Unable to get increased product quantity: ' . $e->getMessage();
            return false;
        }
        finally {
            $stmt = null;
            $this->conn = null;
        }
    }

    #decreaseCartItemQuantity:: This method decreases cart item quantity

    public function decreaseCartItemQuantity( array $data )
 {
        try {
            $sql = "UPDATE tblcarts SET productQuantity = productQuantity - 1 
             WHERE uToken = :userToken AND pToken = :productToken";
            $stmt = $this->conn->prepare( $sql );
            $stmt->bindParam( ':userToken', $data[ 'usertoken' ] );
            $stmt->bindParam( ':productToken', $data[ 'productToken' ] );
            $stmt->execute();

            $rowCount = $stmt->rowCount();
            if ( $rowCount > 0 ) {
                $_SESSION[ 'err' ] = 'Quantity decreased';
                return true;
            } else {
                $_SESSION[ 'err' ] = 'No rows updated';
                return false;
            }
        } catch ( PDOException $e ) {
            $_SESSION[ 'err' ] = 'Unable to get decrease product quantity: ' . $e->getMessage();
            return false;
        }
        finally {
            $stmt = null;
            $this->conn = null;
        }
    }

    /**
    * This method allows users to upload their products.

    */

    public function uploadSellerProducts( array $data )
 {
        $usertoken = ( int ) $data[ 'usertoken' ];
        $producttoken = ( int ) $this->token();
        $imageUrl = $_ENV[ 'IMAGE_PATH' ] . "/$data[pimage]";

        $getUserType =  ( $this->getUserData( $data[ 'usertoken' ] )[ 'userType' ] === 'admin' ) ? 1 : 0;

        $getProductType = ( $getUserType === 1 ) ? 'Solarproduct' : 'Userproduct';

        #   Prepare the product fields and values for the insert query
        $fields = [
            'usertoken' => $usertoken,
            'pname' => $data[ 'pname' ],
            'putoken' => $producttoken,
            'pquantity' => $data[ 'pquantity' ],
            'pdesc' => $data[ 'pdesc' ],
            'price' => $data[ 'price' ],
            'time' => time(),
            'type' => $getProductType,
            'pimage' => ( $data[ 'pimage' ] ),
            'pcat_id' => $data[ 'catid' ],
            'watts' =>   0,
            'volt' => intval( $data[ 'volt' ] ),
            'unit' => ( $data[ 'unit' ] ),
            'status' => $getUserType,
            'size' => intval( $data[ 'size' ] ),
            'imageUrl' => $imageUrl
        ];

        $otherFields = [
            'productToken' => $producttoken,
            'phone' => $data[ 'phone' ],
            'location' => $data[ 'location' ],
            '`condition`' => $data[ 'condition' ], #  Escape the reserved keyword with backticks
            'brand' => $data[ 'brand' ],
        ];

        #  Build the SQL queries
        $productColumns = implode( ', ', array_keys( $fields ) );
        $productPlaceholders = implode( ', ', array_fill( 0, count( $fields ), '?' ) );

        $productSql = "INSERT INTO tblproducts ($productColumns) VALUES ($productPlaceholders)";

        $otherproductColumns = implode( ', ', array_keys( $otherFields ) );
        $otherproductPlaceholders = implode( ', ', array_fill( 0, count( $otherFields ), '?' ) );

        $otherproductSql = "INSERT INTO tblproductsinfo ($otherproductColumns) VALUES ($otherproductPlaceholders)";

        #  Execute the queries and handle any errors
        try {
            $this->conn->beginTransaction();

            #  Insert the product data
            $stmt = $this->conn->prepare( $productSql );
            $i = 1;
            foreach ( $fields as $value ) {
                $type = is_int( $value ) ? PDO::PARAM_INT : PDO::PARAM_STR;
                $stmt->bindValue( $i++, $this->sanitizeInput( $value ), $type );
            }
            $stmt->execute();

            #  Insert other product info
            $stmt = $this->conn->prepare( $otherproductSql );
            $i = 1;
            foreach ( $otherFields as $valuesOfOtherFields ) {
                $type = is_int( $valuesOfOtherFields ) ? PDO::PARAM_INT : PDO::PARAM_STR;
                $stmt->bindValue( $i++, ( $valuesOfOtherFields ), $type );
            }
            $stmt->execute();

            $this->conn->commit();

            if ( $getUserType === 0 ) 
 {
                $mailer  = new Mailer();

                try {

                    $mailer->alertAdminOfProductFromUser( $data[ 'pname' ], $data[ 'pquantity' ] );

                } catch ( Exception $e ) {
                    # Handle the error or log it as needed
                    $errorMessage = date( '[Y-m-d H:i:s] ' ) . 'Error sending mail for  ' . __METHOD__ . '  ' . PHP_EOL . 'Error Message: ' . $e->getMessage() . ' in ' . $e->getFile() . ' on line ' . $e->getLine();
                    error_log( $errorMessage, 3, 'productmail.log' );
                }
                $outputMessage = 'Request sent. You will be notified upon approval';

            } else {

                $outputMessage = 'Product uploaded successfully';

            }

            http_response_code( 201 );
            $output = $this->outputData( true, $outputMessage, null );
        } catch ( PDOException $e ) {
            #  Handle errors here
            $this->respondWithInternalError( 'Error creating product: ' . $e->getMessage() );
            return;
        }
        finally {
            #  Cleanup and return output here
            $stmt = null;
            $this->conn = null;
            unset( $mailer );
        }

        return $output;
    }

    #deleteSellerProduct:: This method deletes Product from database

    public function deleteProduct( int $producttoken ): bool
 {
        try {
            $this->conn->beginTransaction();

            $sql = 'SELECT pimage FROM tblproducts WHERE putoken = :producttoken';
            $stmt = $this->conn->prepare( $sql );
            $stmt->bindParam( ':producttoken', $producttoken, PDO::PARAM_INT );
            if ( !$stmt->execute() ) {
                return false;
            }
            $imageNames = $stmt->fetch( PDO::FETCH_COLUMN );

            # Delete images from server
            $target_file = $_SERVER[ 'DOCUMENT_ROOT' ] . '/uploads/' . $imageNames;
            if ( file_exists( $target_file ) ) {
                unlink( $target_file );
            }

            $deleteProductFromDB = $this->deleteProductFromDB( $producttoken );
            $deleteAdditionalInfoFromDb = $this->deleteAdditionalInfoFromDb( $producttoken );

            if ( $deleteProductFromDB  && $deleteAdditionalInfoFromDb !== false ) {
                $this->outputData( true, 'Product deleted', null );
            }

            $this->conn->commit();
            exit;
            return true;
        } catch ( PDOException $e ) {
            $this->respondWithInternalError( 'Error processing query: ' . $e->getMessage() );
            return false;
        }
        finally {
            $stmt = null;
            $this->conn = null;
        }
    }

    #deleteProductFromDB: Thid method deletes  aproduct from databse.

    public function deleteProductFromDB( int  $productToken ): bool
 {

        try {
            $sql = 'DELETE FROM tblproducts WHERE putoken = :token';
            $stmt = $this->conn->prepare( $sql );
            $stmt->bindParam( ':token', $productToken, PDO::PARAM_INT );
            $stmt->execute();
            return true;
        } catch ( PDOException $e ) {
            $this->respondWithInternalError( 'Error deleting product: ' . $e->getMessage() );
            return false;
        }
        finally {
            $stmt = null;
        }
    }

    #deleteAdditionalInfoFromDb:: This method deletes additional info from dataabase;

    public function deleteAdditionalInfoFromDb( int  $productToken ): bool
 {

        try {
            $sql = 'DELETE FROM tblproductsinfo WHERE productToken = :token';
            $stmt = $this->conn->prepare( $sql );
            $stmt->bindParam( ':token', $productToken, PDO::PARAM_INT );
            $stmt->execute();
            return true;
        } catch ( PDOException $e ) {
            $this->respondWithInternalError( 'Error deleting product: ' . $e->getMessage() );
            return false;
        }
        finally {
            $stmt = null;
        }
    }

    #getAllBuyBackProduct::This method checks for all products uploaded my Users

    public function getAllBuyBackProduct()
 {
        $dataArray = array();
        try {
            $sql = "SELECT *
                FROM tblproducts 
                INNER JOIN tblproductsinfo ON tblproducts.putoken = tblproductsinfo.productToken 
                 WHERE tblproducts.status = 0
                ORDER BY tblproducts.id DESC";
            $stmt = $this->conn->query( $sql );
            if ( !$stmt->execute() ) {
                throw new Exception( 'Failed to execute  query' );
            }

            if ( $stmt->rowCount() === 0 ) {
                $_SESSION[ 'err' ] = 'No product found';
                return false;
            }

            $products = $stmt->fetchAll( PDO::FETCH_ASSOC );
            foreach ( $products as $allProducts ) {
                $produtCategory = $this->getProductCategory( $allProducts[ 'pcat_id' ] );
                $getUserinfo = $this->getUserdata( $allProducts[ 'usertoken' ] );
                $array = [
                    'productid' => $allProducts[ 'id' ],
                    'productname' => $allProducts[ 'pname' ],
                    'productToken' => $allProducts[ 'putoken' ],
                    'productQuantity' => $allProducts[ 'pquantity' ],
                    'productDesc' => $allProducts[ 'pdesc' ],
                    'productPrice' => $this->formatCurrency( $allProducts[ 'price' ] ),
                    'productStatus' => ( $allProducts[ 'status' ] == 1 ) ? 'Approved' : ( ( $allProducts[ 'status' ] == 2 ) ? 'Declined' : 'Pending' ),
                    'productImage' => ( $allProducts[ 'imageUrl' ] ),
                    'phoneNumber' => $allProducts[ 'phone' ],
                    'location' => $allProducts[ 'location' ],
                    'productPrice_normal' => ( $allProducts[ 'price' ] ),
                    'productType' => $allProducts[ 'type' ],
                    'catname' => $produtCategory[ 'catname' ],
                    'watts' => intval( $allProducts[ 'watts' ] ),
                    'unit' => ( $allProducts[ 'unit' ] ),
                    'size' => intval( $allProducts[ 'size' ] ),
                    'volt' => intval( $allProducts[ 'volt' ] ),
                    'getUserFullDetail' => $getUserinfo,
                    'RequestedOn' => $this->formatDate( $allProducts[ 'time' ] )
                ];
                array_push( $dataArray, $array );
            }
        } catch ( Exception $e ) {
            #  Handle the error here, e.g. log it or return an error message
            return false;
        }
        finally {
            $stmt = null;
            $this->conn = null;
        }
        return $dataArray;
    }

    #getAllBuyBackProduct::This method checks for all products approved by Admin incliding project uploaded by admin

    public function getAllApprovedProducts()
 {
        $dataArray = array();
        try {
            $sql = "SELECT *
                FROM tblproducts 
                INNER JOIN tblproductsinfo ON tblproducts.putoken = tblproductsinfo.productToken 
                 WHERE tblproducts.status = 1
                ORDER BY tblproducts.id DESC";
            $stmt = $this->conn->query( $sql );
            if ( !$stmt->execute() ) {
                throw new Exception( 'Failed to execute  query' );
            }

            if ( $stmt->rowCount() === 0 ) {
                $_SESSION[ 'err' ] = 'No product found';
                return false;
            }

            $products = $stmt->fetchAll( PDO::FETCH_ASSOC );
            foreach ( $products as $allProducts ) {
                $produtCategory = $this->getProductCategory( $allProducts[ 'pcat_id' ] );
                $getUserinfo = $this->getUserdata( $allProducts[ 'usertoken' ] );
                $array = [
                    'productid' => $allProducts[ 'id' ],
                    'productname' => $allProducts[ 'pname' ],
                    'productToken' => $allProducts[ 'putoken' ],
                    'productQuantity' => $allProducts[ 'pquantity' ],
                    'productDesc' => $allProducts[ 'pdesc' ],
                    'productPrice' => $this->formatCurrency( $allProducts[ 'price' ] ),
                    'productStatus' => ( $allProducts[ 'status' ] == 1 ) ? 'Approved' : ( ( $allProducts[ 'status' ] == 2 ) ? 'Declined' : 'Pending' ),
                    'productImage' => ( $allProducts[ 'imageUrl' ] ),
                    'phoneNumber' => $allProducts[ 'phone' ],
                    'location' => $allProducts[ 'location' ],
                    'productPrice_normal' => ( $allProducts[ 'price' ] ),
                    'productType' => $allProducts[ 'type' ],
                    'catname' => $produtCategory[ 'catname' ] ?? 0,
                    'watts' => intval( $allProducts[ 'watts' ] ),
                    'unit' => ( $allProducts[ 'unit' ] ),
                    'size' => intval( $allProducts[ 'size' ] ),
                    'volt' => intval( $allProducts[ 'volt' ] ),
                    'getUserFullDetail' => $getUserinfo,
                    'createdOn' => $this->formatDate( $allProducts[ 'time' ] )
                ];
                array_push( $dataArray, $array );
            }
        } catch ( Exception $e ) {
            #  Handle the error here, e.g. log it or return an error message
            return false;
        }
        finally {
            $stmt = null;
            $this->conn = null;
        }
        return $dataArray;
    }

    #approveProduct::Thios method is specificcally meant for approving Products

    public function approveSellerProduct( array $data )
 {
        try {
            $sql = 'UPDATE tblproducts SET status = 1 WHERE usertoken = :usertoken AND putoken = :productToken';
            $stmt = $this->conn->prepare( $sql );
            $stmt->bindParam( ':usertoken', $data[ 'usertoken' ], PDO::PARAM_INT );
            $stmt->bindParam( ':productToken', $data[ 'productToken' ], PDO::PARAM_INT );

            if ( !$stmt->execute() ) {
                $this->outputData( false, 'Unable to update product status', null );
                exit;
            }

            $getUserinfo = $this->getUserdata( $data[ 'usertoken' ] );

            $mailer = new Mailer;

            try {

                $mailer->sendApprovalNotification( $getUserinfo[ 'mail' ], $getUserinfo[ 'fname' ] );

            } catch ( Exception $e ) {
                # Handle the error or log it as needed
                $errorMessage = date( '[Y-m-d H:i:s] ' ) . 'Error sending mail for  ' . __METHOD__ . '  ' . PHP_EOL . 'Error Message: ' . $e->getMessage() . ' in ' . $e->getFile() . ' on line ' . $e->getLine();
                error_log( $errorMessage, 3, 'productmail.log' );
            }

            $this->outputData( true, 'Approved', null );

        } catch ( Exception $e ) {

            $this->respondWithInternalError( 'Error: ' . $e->getMessage() );
            exit;
        }
        finally {
            $stmt  = null;
            $this->conn = null;
            unset( $mailer );
        }
        return true;
    }

    #declineSellerProduct::Thios method is specificcally meant for  declinimg a Products

    public function declineSellerProduct( array $data )
 {
        try {
            $sql = 'UPDATE tblproducts SET status = 2 WHERE usertoken = :usertoken AND putoken = :productToken';
            $stmt = $this->conn->prepare( $sql );
            $stmt->bindParam( ':usertoken', $data[ 'usertoken' ], PDO::PARAM_INT );
            $stmt->bindParam( ':productToken', $data[ 'productToken' ], PDO::PARAM_INT );

            if ( !$stmt->execute() ) {
                $this->outputData( false, 'Unable to update product status', null );
                exit;
            }

            $getUserinfo = $this->getUserdata( $data[ 'usertoken' ] );

            $mailer = new Mailer;

            try {

                $mailer->sendDisaprovalNotification( $getUserinfo[ 'mail' ], $getUserinfo[ 'fname' ] );
            } catch ( Exception $e ) {
                # Handle the error or log it as needed
                $errorMessage = date( '[Y-m-d H:i:s] ' ) . 'Error sending mail for  ' . __METHOD__ . '  ' . PHP_EOL . 'Error Message: ' . $e->getMessage() . ' in ' . $e->getFile() . ' on line ' . $e->getLine();
                error_log( $errorMessage, 3, 'productmail.log' );
            }

            $this->outputData( true, 'Declined', null );

        } catch ( Exception $e ) {
            $this->respondWithInternalError( 'Error: ' . $e->getMessage() );
            exit;
        }
        finally {
            $stmt  = null;
            $this->conn = null;
            unset( $mailer );
        }
        return true;
    }

    #getAllPurchasedItemsPaidOnce ::This method fetches all Products paid-ONCE.
    #This query below fetches column from tblpurchasedonce, tblproduct_buyers
    # Columns. All tables have a column related.

    public function getAllPurchasedItemsPaidOnce()
 {
        try {
            $sql = "SELECT tblpurchasedonce.id, tblpurchasedonce.productToken, tblpurchasedonce.productQuantity, tblpurchasedonce.modeOfPayment,
        tblpurchasedonce.price AS price, tblpurchasedonce.productname, tblpurchasedonce.productimage, tblpurchasedonce.orderid, tblpurchasedonce.productType, tblpurchasedonce.time, 
        tblpurchasedonce.status,  tblproduct_buyers.transactionToken, tblproduct_buyers.usertoken
        FROM tblpurchasedonce 
        INNER JOIN tblproduct_buyers ON tblproduct_buyers.transactionToken = tblpurchasedonce.transactionToken
        WHERE tblproduct_buyers.payment_type = 'Paid-Once'
        ORDER BY tblpurchasedonce.id DESC
        ";
            $stmt = $this->conn->query( $sql );
            if ( !$stmt->execute() ) {
                $_SESSION[ 'err' ] = 'Something went wrong, please try again.';
                return false;
            }
            if ( $stmt->rowCount() === 0 ) {
                $_SESSION[ 'err' ] = 'No Product Found.';
                return false;
            }
            $dataArray = array();
            $products = $stmt->fetchAll( PDO::FETCH_ASSOC );
            foreach ( $products as $key => $row ) {
                $costPrice = $row[ 'price' ] * $row[ 'productQuantity' ];
                $getUserData = $this->getUserdata( $row[ 'usertoken' ] );
                $array = array(
                    'orderToken' => $row[ 'orderid' ],
                    'productname' => $row[ 'productname' ],
                    'productToken' => $row[ 'productToken' ],
                    'productQuantity' => $row[ 'productQuantity' ],
                    'productImage' => ( $row[ 'productimage' ] ),
                    'modeOfPayment' => $row[ 'modeOfPayment' ],
                    'price' => $this->formatCurrency( $row[ 'price' ] ),
                    'TotalPrice' => $this->formatCurrency( $costPrice ),
                    'productType' => $row[ 'productType' ],
                    'datePurchased' => $this->formatDate( $row[ 'time' ] ),
                    'status' => $row[ 'status' ] === 1  ? 'Approved' : 'Pending',
                    'buyerInfo' => $getUserData,
                );

                array_push( $dataArray, $array );
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

    #getUserPurchasedItemsPaidOnce ::This method fetches all Products paid-ONCE in the users dashboad.
    #This query below fetches column from tblpurchasedonce, tblproduct_buyers
    # Columns. All tables have a column related.

    public function getUserPurchasedItemsPaidOnce( int $usertoken )
 {
        try {
            $sql = "SELECT tblpurchasedonce.id , tblpurchasedonce.productToken, tblpurchasedonce.productQuantity, tblpurchasedonce.modeOfPayment,
            tblpurchasedonce.price AS price, tblpurchasedonce.productname, tblpurchasedonce.orderid, tblpurchasedonce.productimage, tblpurchasedonce.productType, tblpurchasedonce.time, 
            tblpurchasedonce.status,  tblproduct_buyers.transactionToken, tblproduct_buyers.usertoken
            FROM tblpurchasedonce 
            INNER JOIN tblproduct_buyers ON tblproduct_buyers.transactionToken = tblpurchasedonce.transactionToken
            WHERE tblproduct_buyers.usertoken = :usertoken
            AND tblproduct_buyers.payment_type = 'Paid-Once'
            ORDER BY tblpurchasedonce.id DESC";

            $stmt = $this->conn->prepare( $sql );
            $stmt->bindParam( ':usertoken', $usertoken, PDO::PARAM_INT );

            if ( !$stmt->execute() ) {
                $_SESSION[ 'err' ] = 'Something went wrong, please try again.';
                return false;
            }
            if ( $stmt->rowCount() === 0 ) {
                $_SESSION[ 'err' ] = 'No Product Found.';
                return false;
            }
            $dataArray = array();
            $products = $stmt->fetchAll( PDO::FETCH_ASSOC );
            foreach ( $products as $key => $row ) {
                $costPrice = $row[ 'price' ] * $row[ 'productQuantity' ];
                $array = array(
                    'orderid' => $row[ 'orderid' ],
                    'orderToken' => $row[ 'transactionToken' ],
                    'productname' => $row[ 'productname' ],
                    'productToken' => $row[ 'productToken' ],
                    'productQuantity' => $row[ 'productQuantity' ],
                    'productImage' => ( $row[ 'productimage' ] ),
                    'modeOfPayment' => $row[ 'modeOfPayment' ],
                    'price' => $this->formatCurrency( $row[ 'price' ] ),
                    'productType' => $row[ 'productType' ],
                    'datePurchased' => $this->formatDate( $row[ 'time' ] ),
                    'status' => $row[ 'status' ] === 1  ? 'Approved' : 'Pending',
                    'TotalPrice' => $this->formatCurrency( $costPrice )

                );

                array_push( $dataArray, $array );
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

    #  acknowledPaidOnceOrder::This method is specifically meant to acknowledge a product purchased once .
    #  The idea is that once a product has been acknowledged, the buyer gets notified.
    # This 'shalaye' that  i just did is meant for the convenience of future developers who may work on this project.
    #  I think this might help you becuause, damn! It is so hard working on other people  project,  explainning what each method is used for
    #  is the best think i  could do for you.
    # Well, if you read this and smiled feel free to reach me via 08117283226.

    public function acknowledPaidOnceOrder( array $data )
 {
        try {
            $sql = 'UPDATE tblpurchasedonce SET status = 1 WHERE orderid = :orderid ';
            $stmt = $this->conn->prepare( $sql );
            $stmt->bindParam( ':orderid', $data[ 'orderid' ], PDO::PARAM_STR );

            if ( !$stmt->execute() ) {
                $this->outputData( false, 'Unable to update product status', null );
                return false;
                exit;
            }

            if ( $stmt->rowCount() === 0 ) {
                $this->outputData( false, 'No row was updated', null );
                return false;
                exit;
            }

            $getUserinfo = $this->getUserdata( $data[ 'usertoken' ] );

            $getProductInfo = $this->getProductByItRelatedToken( $data[ 'productToken' ] );

            $mailer = new Mailer;

            try {

                $mailer->sendProductPaidOnceApprovalNptofication( $getUserinfo[ 'mail' ], $getUserinfo[ 'fname' ], $getProductInfo[ 'productname' ] );
            } catch ( Exception $e ) {
                # Handle the error or log it as needed
                $errorMessage = date( '[Y-m-d H:i:s] ' ) . 'Error sending mail for  ' . __METHOD__ . '  ' . PHP_EOL . 'Error Message: ' . $e->getMessage() . ' in ' . $e->getFile() . ' on line ' . $e->getLine();
                error_log( $errorMessage, 3, 'productmail.log' );
            }

            $this->outputData( true, 'Approved', null );

        } catch ( Exception $e ) {
            $this->respondWithInternalError( 'Unable to process request:' . $e->getMessage() );
            exit;
        }
        finally {
            $stmt  = null;
            $this->conn = null;
            unset( $mailer );
        }
        return true;
    }

    #  acknowledPaidInstallmentally::This method is specifically meant to acknowledge a product paid installmentally .
    #  The idea is that once a product has been acknowledged, the buyer gets notified.
    # This 'shalaye' that  i just did is meant for the convenience of future developers who may work on this project.
    #  I think this might help you becuause, damn! It is so hard working on other people  project,  explainning what each method is used for
    #  is the best think i  could do for you.
    # Well, if you read this and smiled feel free to reach me

    public function acknowledPaidInstallmentally( array $data )
 {
        try {
            $sql = 'UPDATE tbl_store_allinstallment_product  SET status = 1 WHERE orderid = :orderid ';
            $stmt = $this->conn->prepare( $sql );
            $stmt->bindParam( ':orderid', $data[ 'orderid' ], PDO::PARAM_INT );

            if ( !$stmt->execute() ) {

                $_SESSION[ 'err' ] = 'Unable to update product status';
                return false;
                exit;
            }

            if ( $stmt->rowCount() === 0 ) {

                $_SESSION[ 'err' ] = 'No row was updated';
                return false;
            }

            $getUserinfo = $this->getUserdata( $data[ 'usertoken' ] );

            $getProductInfo = $this->getProductByItRelatedToken( $data[ 'productToken' ] );

            $mailer = new Mailer;

            try {

                $mailer->SendProductInstallmentPaymentStatusNotification( $getUserinfo[ 'mail' ], $getUserinfo[ 'fname' ], $getProductInfo[ 'productname' ] );

            } catch ( Exception $e ) {
                # Handle the error or log it as needed
                $errorMessage = date( '[Y-m-d H:i:s] ' ) . 'Error sending mail for  ' . __METHOD__ . '  ' . PHP_EOL . 'Error Message: ' . $e->getMessage() . ' in ' . $e->getFile() . ' on line ' . $e->getLine();
                error_log( $errorMessage, 3, 'productmail.log' );
            }

            $_SESSION[ 'err' ] = 'Approved';
        } catch ( Exception $e ) {
            $_SESSION[ 'err' ] = 'Unable to process request:' . $e->getMessage();
            return false;
        }
        finally {
            $stmt  = null;
            $this->conn = null;
            unset( $mailer );
        }
        return true;
    }

    # getAllUsersPaidInstallmentallyProducts::This method fetches All product puchased installmentally
    # for users dashbord

    public function getAllUsersPaidInstallmentallyProducts( int $usertoken )
 {
        $dataArray = array();
        try {
            $installmentally = 'Paid-Installmentally';
            $sql = "SELECT tblproduct_buyers.*, records.*, productbought.*
            FROM tblproduct_buyers
            INNER JOIN tbl_installment_purchases as records ON records.token = tblproduct_buyers.transactionToken
            INNER JOIN tbl_store_allinstallment_product as productbought ON productbought.transactionToken = tblproduct_buyers.transactionToken
            WHERE tblproduct_buyers.usertoken = :usertoken
            AND tblproduct_buyers.payment_type = :payment_type ";
            $stmt = $this->conn->prepare( $sql );
            $stmt->bindParam( ':usertoken', $usertoken, PDO::PARAM_INT );
            $stmt->bindParam( ':payment_type', $installmentally, PDO::PARAM_STR );
            if ( !$stmt->execute() ) {
                $stmt = null;
                $_SESSION[ 'err' ] = 'Something went wrong, please  try again..';
                return false;
            } else {

                if ( $stmt->rowCount() === 0 ) {
                    $this->outputData( false, 'No record found', null );
                    exit;
                }
                if ( $loanedProducut = $stmt->fetchAll( PDO::FETCH_ASSOC ) ) {
                    foreach ( $loanedProducut as $user ) {
                        $costprice = $user[ 'price' ] * $user[ 'productQuantity' ];
                        $MonnthsScheduledTPayInstallmentally = $this->MonnthsEstimatedToPayInstallmentally( $user[ 'transactionToken' ] );
                        $array = array(

                            'total_amount' => $this->formatCurrency( $user[ 'total_amount' ] ),
                            'amountpaid' => $this->formatCurrency( $user[ 'amountpaid' ] ),
                            'amountDebitedSoFar' => $this->formatCurrency( $user[ 'amount_debited_so_far' ] ),
                            'isCompletedStatus' => ( $user[ 'isCompletedStatus' ] == 1 ) ? 'Payment completed.' : 'Payment ongoing.',
                            'subscribedPlan' => $user[ 'duration' ] . ' ' . 'months',
                            'amountToPayMonthly_thousand' => $this->formatCurrency( $user[ 'amontMonthly' ] ),
                            'amountRemmainning_thousand' => $this->formatCurrency( $user[ 'amountRem' ] ),
                            'dateEstimatedToFinish' => $user[ 'finished_date' ],
                            'orderid' => ( $user[ 'orderid' ] ),
                            'productToken' => $user[ 'productToken' ],
                            'productQuantity' => $user[ 'productQuantity' ],
                            'productname' => $user[ 'productname' ],
                            'modeOfPayment' => $user[ 'modeOfPayment' ],
                            'productImage' => ( $user[ 'productimage' ] ),
                            'price' => $this->formatCurrency( $user[ 'price' ] ),
                            'costPrice_thosand' => $this->formatCurrency( $costprice ),
                            'datePurchased' => $this->formatDate( $user[ 'time' ] ),
                            'status' => ( $user[ 'status' ] == 1 ? 'Acknowledged' : 'Pending' ),
                            'MonnthsEstimatedToPayInstallmentally' => $MonnthsScheduledTPayInstallmentally,

                        );
                        array_push( $dataArray, $array );
                    }
                    return $dataArray;
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
    }

    #getAllLoanPurchasedGoods ::This method ferches ALL product bought on loan

    public function getAllLoanPurchasedGoods()
 {
        $dataArray = array();
        try {
            $installmentally = 'Paid-Installmentally';
            $sql = "SELECT tblproduct_buyers.*, records.*, productbought.*
            FROM tblproduct_buyers
            INNER JOIN tbl_installment_purchases as records ON records.token = tblproduct_buyers.transactionToken
            INNER JOIN tbl_store_allinstallment_product as productbought ON productbought.transactionToken = tblproduct_buyers.transactionToken
            AND tblproduct_buyers.payment_type = :payment_type ";
            $stmt = $this->conn->prepare( $sql );
            $stmt->bindParam( ':payment_type', $installmentally, PDO::PARAM_STR );
            if ( !$stmt->execute() ) {
                $stmt = null;
                $_SESSION[ 'err' ] = 'Something went wrong, please  try again..';
                return false;
            } else {

                if ( $stmt->rowCount() === 0 ) {
                    $this->outputData( false, 'No record found', null );
                    exit;
                }
                if ( $loanedProducut = $stmt->fetchAll( PDO::FETCH_ASSOC ) ) {
                    foreach ( $loanedProducut as $user ) {
                        $costprice = $user[ 'price' ] * $user[ 'productQuantity' ];
                        $MonnthsScheduledTPayInstallmentally = $this->MonnthsEstimatedToPayInstallmentally( $user[ 'transactionToken' ] );
                        $array = array(

                            'total_amount' => $this->formatCurrency( $user[ 'total_amount' ] ),
                            'amountpaid' => $this->formatCurrency( $user[ 'amountpaid' ] ),
                            'amountDebitedSoFar' => $this->formatCurrency( $user[ 'amount_debited_so_far' ] ),
                            'isCompletedStatus' => ( $user[ 'isCompletedStatus' ] == 1 ) ? 'Payment completed.' : 'Payment ongoing.',
                            'subscribedPlan' => $user[ 'duration' ] . ' ' . 'months',
                            'amountToPayMonthly_thousand' => $this->formatCurrency( $user[ 'amontMonthly' ] ),
                            'amountRemmainning_thousand' => $this->formatCurrency( $user[ 'amountRem' ] ),
                            'dateEstimatedToFinish' => $user[ 'finished_date' ],
                            'orderid' => ( $user[ 'orderid' ] ),
                            'productToken' => $user[ 'productToken' ],
                            'productQuantity' => $user[ 'productQuantity' ],
                            'productname' => $user[ 'productname' ],
                            'modeOfPayment' => $user[ 'modeOfPayment' ],
                            'productImage' => ( $user[ 'productimage' ] ),
                            'price' => $this->formatCurrency( $user[ 'price' ] ),
                            'costPrice_thosand' => $this->formatCurrency( $costprice ),
                            'datePurchased' => $this->formatDate( $user[ 'time' ] ),
                            'status' => ( $user[ 'status' ] == 1 ? 'Acknowledged' : 'Pending' ),
                            'MonnthsEstimatedToPayInstallmentally' => $MonnthsScheduledTPayInstallmentally,

                        );
                        array_push( $dataArray, $array );
                    }
                    return $dataArray;
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
    }

    public function getGoodsPurchasedByUserOnInstallments( $transactionToken )
    {
        $dataArray = array();
        try {
            $sql = 'SELECT *  FROM tbl_store_allinstallment_product WHERE transactionToken = :transactionToken';
            $stmt = $this->conn->prepare( $sql );
            $stmt->bindParam( ':transactionToken', $transactionToken, PDO::PARAM_INT );
            if ( !$stmt->execute() ) {
                $stmt = null;
                $_SESSION[ 'err' ] = 'Something went wrong, please  try again..';
                return false;
            } else {
                if ( $loanedProducut = $stmt->fetchAll( PDO::FETCH_ASSOC ) ) {
                    foreach ( $loanedProducut as $user ) {
                        $costprice = $user[ 'price' ] * $user[ 'productQuantity' ];
                        $array = array(
                            'orderid' => ( $user[ 'orderid' ] ),
                            'product_loan_id' => $user[ 'id' ],
                            'productToken' => $user[ 'productToken' ],
                            'productQuantity' => $user[ 'productQuantity' ],
                            'productname' => $user[ 'productname' ],
                            'modeOfPayment' => $user[ 'modeOfPayment' ],
                            'productimage' => ( $user[ 'productimage' ] ),
                            'price' => $this->formatCurrency( $user[ 'price' ] ),
                            'costPrice_thosand' => $this->formatCurrency( $costprice ),
                            'datePurchased' => $this->formatDate( $user[ 'time' ] ),
                            'status' => ( $user[ 'status' ] == 1 ? 'Acknowledged' : 'Pending' ),
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
            #  $this->conn = null;
        }
        return $dataArray;
    }

    #MonnthsEstimatedToPayInstallmentally ::This methos fetches All month a user is to pay for Loan bought installmentally

    public function MonnthsEstimatedToPayInstallmentally( $token )
 {
        $dataArray = array();
        try {
            $sql = "SELECT id, dueDate, priceToPay, payyment_status, remind_a_day_before_status, 
        remind_a_week_before_status, token FROM loan_product_purchases WHERE token = :token";
            $stmt = $this->conn->prepare( $sql );
            $stmt->bindParam( ':token', $token );
            $stmt->execute();
            $count = $stmt->rowCount();
            if ( $count === 0 ) {
                $this->outputData( false, 'No date available', null );
                return false;
            }
            $properties = $stmt->fetchAll( PDO::FETCH_ASSOC );
            foreach ( $properties as $key => $value ) {
                $array = [
                    'dateToPayId' => $value[ 'id' ],
                    'dueDate' => $value[ 'dueDate' ],
                    'priceExpectedToPay' => $this->formatCurrency( $value[ 'priceToPay' ] ),
                    'paymentStatus' => ( $value[ 'payyment_status' ] == 1 ) ? 'Paid' : ( ( $value[ 'payyment_status' ] == 2 ) ? 'Not-Paid' : 'Pending' ),
                    'remind_a_day_before_status' => ( $value[ 'remind_a_day_before_status' ] == 1 ) ? 'Sent.' : 'Notification-pending.',
                    'remind_a_week_before_status' => ( $value[ 'remind_a_week_before_status' ] == 1 ) ? 'Sent.' : 'Notification-pending.',
                    'token' => $value[ 'token' ],
                ];
                array_push( $dataArray, $array );
            }
            return $dataArray;

        } catch ( PDOException $e ) {
            throw new Exception( 'Error Processing Request: ' . $e->getMessage() );
        }
        finally {
            $stmt  = null;
        }
    }

    /*

    public function getAllLoanPurchasedGoods()
 {
        $dataArray = array();
        try {
            $sql = ' SELECT  *  FROM tbl_installment_purchases ';
            $sql .= ' ORDER BY id DESC';
            $stmt = $this->conn->prepare( $sql );
            if ( !$stmt->execute() ) {
                $stmt = null;
                $_SESSION[ 'err' ] = 'Something went wrong, please try again..';
                return false;
            } else {
                if ( $notifyArray = $stmt->fetchAll( PDO::FETCH_ASSOC ) ) {
                    foreach ( $notifyArray as $user ) {
                        $MonnthsScheduledTPayInstallmentally = $this->MonnthsEstimatedToPayInstallmentally( $user[ 'token' ] );
                        $productBought = $this->getGoodsPurchasedByUserOnInstallments( $user[ 'token' ] );
                        $buyerInfo = $this->getUserdata( $user[ 'usertoken' ] );
                        $array = array(
                            'product_loan_id' => $user[ 'id' ],
                            'total_amount' => $this->formatCurrency( $user[ 'total_amount' ] ),
                            'isCompletedStatus' => ( $user[ 'isCompletedStatus' ] == 1 ) ? 'Payment completed.' : 'Payment ongoing.',
                            'amountDebitedSoFar' =>    $this->formatCurrency( $user[ 'amount_debited_so_far' ] ),
                            'subscribedPlan' => $user[ 'duration' ] . ' ' . 'months',
                            'amountToPayMonthly' => $this->formatCurrency( $user[ 'amontMonthly' ] ),
                            'amountRemmainning_thousand' => $this->formatCurrency( $user[ 'amountRem' ] ),
                            'WhenEstimatedToBalanceUp' => $user[ 'finished_date' ],
                            'MonnthsEstimatedToPayInstallmentally   ' => $MonnthsScheduledTPayInstallmentally,
                            'productBought' => $productBought,
                            'buyerInfo' => $buyerInfo
                        );
                        array_push( $dataArray, $array );
                    }
                    return $dataArray;
                } else {
                    return false;
                }
            }
        } catch ( PDOException $e ) {
            echo $_SESSION[ 'err' ] = $e->getMessage();
            return false;
        }
        finally {
            $stmt = null;
            $this->conn = null;
        }
    }
    */
    # personalisedDashboard::This method fetches all info in the admin widget dashoard

    public function personalisedDashboard()
 {

        $getAllUsersCount = $this->getAllUsersCount();

        $getAllProductCount  = $this->getAllProductCount();

        $getAllUnapprovedProductCount = $this->getAllUnapprovedProductCount();

        $getAllProductPurchasedOnce = $this->getAllProductPurchasedOnce();

        $getAllProductPurchasedInstallmentally = $this->getAllProductPurchasedInstallmentally();

        $getAllUnapprovedLoanCount = $this->getAllUnapprovedLoanCount();

        $sumAllUsersAccountWallet = $this->sumAllUsersAccountWallet();

        return [
            'getAllUsersCount' => $getAllUsersCount,
            'getAllProductCount' => $getAllProductCount,
            'getAllUnapprovedProductCount' => $getAllUnapprovedProductCount,
            'getAllProductPurchasedOnce' => $getAllProductPurchasedOnce,
            'getAllProductPurchasedInstallmentally' => $getAllProductPurchasedInstallmentally,
            'getAllUnapprovedLoan' => $getAllUnapprovedLoanCount,
            'getAllUsersAccountWallet' => $sumAllUsersAccountWallet
        ];
    }

    #getAllUnapprovedLoanCount ::This method fetchs all UnapprovedLoan

    public function getAllUnapprovedLoanCount()
 {

        $sql = 'SELECT COUNT(id) AS LoanCount FROM loan_records WHERE status = 0';
        $stmt = $this->conn->prepare( $sql );

        if ( !$stmt->execute() ) {
            return false;
        }

        $result = $stmt->fetch( PDO::FETCH_ASSOC );
        $LoanCount = $result[ 'LoanCount' ];

        return $LoanCount;
    }

    public function getAllUsersCount()
 {

        $sql = 'SELECT COUNT(id) AS userCount FROM tblusers';
        $stmt = $this->conn->prepare( $sql );

        if ( !$stmt->execute() ) {
            return false;
        }

        $result = $stmt->fetch( PDO::FETCH_ASSOC );
        $userCount = $result[ 'userCount' ];

        return $userCount;
    }

    public function getAllProductCount()
 {

        $sql = 'SELECT COUNT(id) AS productCount FROM tblproducts';
        $stmt = $this->conn->prepare( $sql );

        if ( !$stmt->execute() ) {
            return false;
        }

        $result = $stmt->fetch( PDO::FETCH_ASSOC );
        $productCount = $result[ 'productCount' ];

        return $productCount;
    }

    public function getAllUnapprovedProductCount()
 {

        $sql = 'SELECT COUNT(id) AS productCount FROM tblproducts WHERE status = 0';
        $stmt = $this->conn->prepare( $sql );

        if ( !$stmt->execute() ) {
            return false;
        }

        $result = $stmt->fetch( PDO::FETCH_ASSOC );
        $productCount = $result[ 'productCount' ];

        return $productCount;
    }

    public function getAllProductPurchasedOnce()
 {

        $sql = 'SELECT COUNT(id) AS productCount FROM tblpurchasedonce';
        $stmt = $this->conn->prepare( $sql );

        if ( !$stmt->execute() ) {
            return false;
        }

        $result = $stmt->fetch( PDO::FETCH_ASSOC );
        $productCount = $result[ 'productCount' ];

        return $productCount;
    }

    public function getAllProductPurchasedInstallmentally()
 {

        $sql = 'SELECT COUNT(id) AS productCount FROM tbl_installment_purchases WHERE isCompletedStatus = 0';
        $stmt = $this->conn->prepare( $sql );

        if ( !$stmt->execute() ) {
            return false;
        }

        $result = $stmt->fetch( PDO::FETCH_ASSOC );
        $productCount = $result[ 'productCount' ];

        return $productCount;
    }

    #searchProducts ::This methos searches available product on the platform

    public function searchProducts( $searchItem )
 {
        try {
            #  Prepare the placeholders for the search item
            $pnamePlaceholder = ':search_pname';
            $pdescPlaceholder = ':search_pdesc';

            $params = array(
                $pnamePlaceholder => "%$searchItem%",
                $pdescPlaceholder => "%$searchItem%"
            );

            $searchConditions = "(pname LIKE $pnamePlaceholder OR pdesc LIKE $pdescPlaceholder)";

            #  Construct the SQL query
            $sql = "SELECT * FROM tblproducts WHERE $searchConditions AND status = 1 ";
            $stmt = $this->conn->prepare( $sql );

            #  Bind the search parameters to the statement
            foreach ( $params as $placeholder => $value ) {
                $stmt->bindValue( $placeholder, $value );
            }

            $stmt->execute();

            if ( $stmt->rowCount() === 0 ) {

                $_SESSION[ 'err' ] = 'No search found.';
                return false;
            }
            $searchedResult = $stmt->fetchAll( PDO::FETCH_ASSOC );

            if ( !$searchedResult ) {
                return false;
            }

            $dataArray = array();

            foreach ( $searchedResult as $itemsFound ) {
                $array = [
                    'productname' => $itemsFound[ 'pname' ],
                    'productToken' => $itemsFound[ 'putoken' ],
                    'productDesc' => $itemsFound[ 'pdesc' ],
                    'productPrice_thousand' => $this->formatCurrency( $itemsFound[ 'price' ] ),
                    'productPrice_normal' =>( $itemsFound[ 'price' ] ),
                    'productImage' => $itemsFound[ 'imageUrl' ],
                ];

                array_push( $dataArray, $array );

            }

        } catch ( PDOException $e ) {

            $this->respondWithInternalError( 'Unable tp process request'. $e->getMessage() );
            return false;
        }
        finally {

            $stmt  = null;
            $this->conn = null;

        }
        return $dataArray;
    }

}
