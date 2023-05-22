<?php



class Auth extends AbstractClasses
{


  private   $conn;


  public function __construct(Database $database)
  {
    $this->conn = $database->connect();
  }


  #authenticate Api Key

  public   function authenticateAPIKey($api_key): bool
  {


    if (empty($api_key)) {

      http_response_code(400);
      $_SESSION['err'] = "missing API key";
      return false;
    }


    if ($api_key !== $_ENV['APP_TOKEN']) {

      http_response_code(401);
      $_SESSION['err'] = "No app found";
      return false;
    }


    return true;
  }


  public function authorizeAccss()
  {

    $url = 'https://bac.solarcredit.io/v0.1/cronejob.php';
    $headers = array(
      'Authorization: Bearer 6455ef91d108f'
    );

    $curl = curl_init($url);

    curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);

    $response = curl_exec($curl);
    curl_close($curl);

    echo $response;
  }
}
