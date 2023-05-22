<?php require_once('../assets/initializer.php');


$data = (array) json_decode(file_get_contents('php://input'), true);

$user = new Users($db);

#  Check for rge requests method
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('HTTP/1.1 405 Method Not Allowed');
    header('Allow: POST');
    exit();
}

#  Check for params  if matches required parameters
$requiredKeys = ['apptoken', 'fname', 'mail', 'phone', 'pword', 'cpword'];
$optionalKeys = ['abtUs'];
$validKeys = array_merge($requiredKeys, $optionalKeys);
$invalidKeys = array_diff(array_keys($data), $validKeys);
if (!empty($invalidKeys)) {
    foreach ($invalidKeys as $key) {
        if (!in_array($key, $optionalKeys)) {
            $errors[] = "$key is not a valid input field";
        }
    }

    if (!empty($errors)) {
        $user->respondUnprocessableEntity($errors);
        return;
    }
}

#  Check for fields  if empty
foreach ($requiredKeys as $key) {
    if (empty($data[$key])) {
        $errors[] = ucfirst($key) . ' is required';
    } else {
        $data[$key] = $user->sanitizeInput($data[$key]);
        # Sanitize input
    }
}
# Check for optional  fields
foreach ($optionalKeys as $key) {
    if (!empty($data[$key])) {
        $data[$key] = $user->sanitizeInput($data[$key]);
        # Sanitize input
    }
}
if (!empty($errors)) {
    $store->respondUnprocessableEntity($errors);
    return;
}
#Your method should be here
$user->registerUser($data);
unset($user);
unset($db);
