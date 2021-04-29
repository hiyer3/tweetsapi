<?php 

require __DIR__ . '/vendor/autoload.php';

use Abraham\TwitterOAuth\TwitterOAuth;

$dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
$dotenv->load();

$connection = new TwitterOAuth($_ENV['OAUTH_ACCESS_TOKEN'], $_ENV['OAUTH_ACCESS_TOKEN_SECRET'], $_ENV['YOUR_CONSUMER_KEY'], $_ENV['YOUR_CONSUMER_SECRET']);
$statuses = $connection->get("search/tweets", ["q" => "#pune OR #bed OR #covid OR #plasma OR #oxygensupply", 'result_type' => 'mixed', 'count' => '100', 'tweet_mode' => 'extended', 'include_entities' => 'false', 'f' => 'live']);
$statuses = json_decode(json_encode($statuses), true);

$statuses = $statuses['statuses'];


/**
 * Returns an authorized API client.
 * @return Google_Client the authorized client object
 */
function getClient()
{
    $client = new Google_Client();
    $client->setApplicationName('Google Sheets API PHP Quickstart');
    $client->setScopes(Google_Service_Sheets::SPREADSHEETS);
    $client->setAuthConfig('credentials.json');
    $client->setAccessType('offline');
    $client->setPrompt('select_account consent');

    // Load previously authorized token from a file, if it exists.
    // The file token.json stores the user's access and refresh tokens, and is
    // created automatically when the authorization flow completes for the first
    // time.
    
    $tokenPath = 'token.json';
    if (file_exists($tokenPath)) {
        $accessToken = json_decode(file_get_contents($tokenPath), true);
        $client->setAccessToken($accessToken);
    }

    // If there is no previous token or it's expired.
    if ($client->isAccessTokenExpired()) {
        // Refresh the token if possible, else fetch a new one.
        if ($client->getRefreshToken()) {
            $client->fetchAccessTokenWithRefreshToken($client->getRefreshToken());
        } else {
            // Request authorization from the user.
            $authUrl = $client->createAuthUrl();
            printf("Open the following link in your browser:\n%s\n", $authUrl);
            print 'Enter verification code: ';
            $authCode = trim(fgets(STDIN));

            // Exchange authorization code for an access token.
            $accessToken = $client->fetchAccessTokenWithAuthCode($authCode);
            $client->setAccessToken($accessToken);

            // Check to see if there was an error.
            if (array_key_exists('error', $accessToken)) {
                throw new Exception(join(', ', $accessToken));
            }
        }
        // Save the token to a file.
        if (!file_exists(dirname($tokenPath))) {
            mkdir(dirname($tokenPath), 0700, true);
        }
        file_put_contents($tokenPath, json_encode($client->getAccessToken()));
    }
    return $client;
}

// Get the API client and construct the service object.
$client = getClient();
$service = new Google_Service_Sheets($client);

// Prints the names and majors of students in a sample spreadsheet:
// https://docs.google.com/spreadsheets/d/1BxiMVs0XRA5nFMdKvBdBZjgmUUqptlbs74OgvE2upms/edit
$spreadsheetId = '1_QJIrtANH6eq9Rtl81TI2l6xetOOyxW_giwfodeaoN4';
$range = 'Sheet1!A2:E';

// TODO: Assign values to desired properties of `valueRange`:
$valueRange = new Google_Service_Sheets_ValueRange();
$conf = ["valueInputOption" => "RAW"];

foreach($statuses as $status){
    $valueRange->setValues(["values" => [trim($status["full_text"])]]); // Add two values
    $response = $service->spreadsheets_values->append($spreadsheetId, $range, $valueRange, $conf);
}


?>