<?php
echo '<pre>';
if($_GET) {
    echo "\nGet:\n";
    print_r($_GET);
    $auth_code = $_GET['code'];
}
elseif($_POST) {
    echo "\nPost:\n";
    print_r($_POST);
}

echo "\nCode is: [$auth_code]\n";

$site = "https://www.inaturalist.org";
$app_id = 'cfe0aa14b145d1b2b527e5d8076d32839db7d773748d5182308cade1c4475b38';
$app_secret = '9cdfbdd2d87f4e91a22c08a22da76db66d04ae1feee08de8f4f93955501c4bd5';
$redirect_uri = 'https://editors.eol.org/eol_php_code/applications/iNaturalist_OAuth2/redirect.php';

/*
payload = {
  :client_id => app_id,
  :client_secret => app_secret,
  :code => auth_code,
  :redirect_uri => redirect_uri,
  :grant_type => "authorization_code"
}
puts "POST #{site}/oauth/token, payload: #{payload.inspect}"
puts response = RestClient.post("#{site}/oauth/token", payload)
*/

$url = $site.'/oauth/token';
$arr['client_id'] = $app_id;
$arr['client_secret'] = $app_secret;
$arr['code'] = $auth_code;
$arr['redirect_uri'] = $redirect_uri;
$arr['grant_type'] = 'authorization_code';

if($ret = curl_post_request($url, $arr)) {
    echo "\nPOST ok\n";
    print_r($ret);
}
else echo "\nERROR: POST failed\n";
echo '</pre>';

function curl_post_request($url, $parameters_array = array())
{
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_POST, true);
    if(isset($parameters_array) && is_array($parameters_array)) curl_setopt($ch, CURLOPT_POSTFIELDS, $parameters_array);
    curl_setopt($ch, CURLOPT_FAILONERROR, 1);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 20);
    curl_setopt($ch, CURLOPT_TIMEOUT, 50);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($ch, CURLOPT_AUTOREFERER, true);
    curl_setopt($ch, CURLOPT_MAXREDIRS, 5);
    echo("Sending post request to $url with params ".print_r($parameters_array, 1).": only attempt");
    $result = curl_exec($ch);
    if(0 == curl_errno($ch)) {
        curl_close($ch);
        return $result;
    }
    echo "Curl error ($url): " . curl_error($ch);
    return false;
}
?>