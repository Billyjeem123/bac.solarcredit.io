<?php
require_once( '../../assets/initializer.php' );

$category = new Category( $db );

#  Check for rge requests method
if ( $_SERVER[ 'REQUEST_METHOD' ] !== 'POST' ) {
    header( 'HTTP/1.1 405 Method Not Allowed' );
    header( 'Allow: POST' );
    exit();
}

 
#Your method should be here
$getAllCategories = $category->getAllCategories();
if ( $getAllCategories ) {
    $category->outputData( true, 'Fetched category', $getAllCategories );
} else {

    $category->outputData( false,  $_SESSION[ 'err' ], null );
}

unset( $category );
unset( $db );

