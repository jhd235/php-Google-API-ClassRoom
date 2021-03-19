<?php
require __DIR__ . '/vendor/autoload.php';
set_time_limit(1000); //
/**
 * Returns an authorized API client.
 * @return Google_Client the authorized client object
 */
function getClient()
{
    $client = new Google_Client();
    $client->setApplicationName('Google Classroom API PHP Quickstart');
    $client->setScopes(Google_Service_Classroom::CLASSROOM_COURSES_READONLY);
    $client->addScope(Google_Service_Classroom::CLASSROOM_PROFILE_EMAILS);
    $client->addScope(Google_Service_Classroom::CLASSROOM_ROSTERS);
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


// Print the first 10 courses the user has access to.
$optParams = array(
  'pageSize' => 9999999
);

function getEmail($service, $userID){
    return $service->userProfiles->get($userID)->getEmailAddress();
}

function getDepartment($service, $userID){
    return $service->userProfiles->get($userID)->getName()->getFullName();
}

function getContent(){
    $client = getClient();
    $service = new Google_Service_Classroom($client);
    //$service->userProfiles->get($userID)->getName()->getFullName();
    $output = "";
    $results = $service->courses->listCourses();
if (count($results->getCourses()) == 0) {
  print "No courses found.\n";
} else {

    $output.= "<table border = 1>";
    $output.= "<tr><th>Название ОП (Учебная группа)</th><th>Название дисциплины</th> <th>Статус курса</th> 
                <th>Дата создания курса</th> <th>Курс ID</th> <th>ownerID</th></tr>";
    $course = $results->getCourses();
    for ($i=0;$i<count($course); $i++) {
        //foreach ($results->getCourses() as $course) {
        //if ($course[$i]->getOwnerId() == 107618027634625870454) {
        //if ($course[$i]->getOwnerId() == 105661863261942468476) {
            $output .= "<tr><td>" . $course[$i]->getSection() . "</td> <td><a href=grades.php?courseID=".$course[$i]->getId().">" . $course[$i]->getName() . "</a></td> <td>" . $course[$i]->getCourseState() .
                "</td> <td>" . $course[$i]->getCreationTime() . "</td> <td>" . $course[$i]->getId() . "</td> <td>".$course[$i]->getOwnerId()."</td></tr>";
        //}
    }
    $output.="</table>";
}
return $output;
}

print getContent();