<?php
//for debug time
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require __DIR__ . '/vendor/autoload.php';
//for debug time
set_time_limit(1000); //

function getClient()
{
    $client = new Google_Client();
    $client->setApplicationName('Google Classroom API & PHP');
    $client->setScopes(Google_Service_Classroom::CLASSROOM_COURSES_READONLY);
    $client->addScope(Google_Service_Classroom::CLASSROOM_PROFILE_EMAILS);
    $client->addScope(Google_Service_Classroom::CLASSROOM_ROSTERS);
    $client->addScope(Google_Service_Classroom::CLASSROOM_COURSEWORK_STUDENTS_READONLY);
    $client->addScope(Google_Service_Classroom::CLASSROOM_STUDENT_SUBMISSIONS_STUDENTS_READONLY);
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
  'pageSize' => 1000
);

function getEmail($service, $userID){
    return $service->userProfiles->get($userID)->getEmailAddress();
}

function getDepartment($service, $userID){
    return $service->userProfiles->get($userID)->getName()->getFullName();
}

function getStudent($service, $courseID){
    return $service->courses_students->listCoursesStudents($courseID)->getStudents();
}

function getStudentFullName($service, $courseID){
    $student = getStudent($service, $courseID);
    $studentFullName = [];
    for ($i=0; $i<count($student);$i++){
        $studentFullName[$i] = $student[$i]->getProfile()->getName()->getFullName();
    }

    return $studentFullName;
}

function test($courseID){
    $client = getClient();
    $service = new Google_Service_Classroom($client);

    $assignedGrades = [];
    $result = [];

    $studentsFullNames = getStudentsFullNames($service, $courseID);

    $courseWorkID = getCourseWorkID($service, $courseID);
    $studentSubmissions = getStudentSubmissions($service, $courseID, $courseWorkID);

    for ($i = 0; $i < count($studentSubmissions); $i++){
        $assignedGrades[$i] = getAssignedGrades($service, $studentSubmissions[$i]);
    }

    foreach ($assignedGrades as $assignedGrade){
        foreach ($assignedGrade as $grade) {
                $result[] = $grade;
        }
    }

    return $result;
}

function getStudentByID($service, $userID){
    return $service->userProfiles->get($userID)->getName()->getFullName();
}

function getCourseWorkID($service,$courseID){
    return $service->courses_courseWork->listCoursesCourseWork($courseID)->getCourseWork();
}

function getStudentSubmissions($service, $courseID, $courseWorkID){

    $studentSubmissions = [];

    for ($i = 0; $i < count($courseWorkID); $i++){
        $studentSubmissions[$i] = $service->courses_courseWork_studentSubmissions->
        listCoursesCourseWorkStudentSubmissions($courseID, $courseWorkID[$i]->getId())->getStudentSubmissions();
    }
    return $studentSubmissions;
}

function getAssignedGrades($service, $studentSubmissions){
    $client = getClient();
    $service = new Google_Service_Classroom($client);

    $assignedGrades = [];
    for ($i = 0; $i < count($studentSubmissions); $i++){
        $assignedGrades[$i] = array("userID" => getStudentByID($service, $studentSubmissions[$i]->getUserID()),
            "grade"=>$studentSubmissions[$i]->getAssignedGrade()

    );
    }
    return $assignedGrades;
}

function getStudentsIDsSubmissions($studentSubmissions){
    $studentsIDs = [];
    for ($i = 0; $i < count($studentSubmissions); $i++){
        $studentsIDs[$i] = $studentSubmissions[$i]->getUserID();
    }
    return $studentsIDs;
}



function getStudentsIDsCourses($service, $courseID){
    $studentsIDs = [];
    $students =  $service->courses_students->listCoursesStudents($courseID)->getStudents();
    for ($i = 0; $i < count($students); $i++){
        $studentsIDs[$i] = $students[$i]->getProfile()->getId();
    }
    return $studentsIDs;
}

function getStudentsFullNames($service, $courseID){
    $studentsFullNames = [];
    $students =  $service->courses_students->listCoursesStudents($courseID)->getStudents();
    for ($i = 0; $i < count($students); $i++){
        $studentsFullNames[$i] = $students[$i]->getProfile()->getName()->getFullName();
    }
    return $studentsFullNames;
}

function getContent()
{
    $client = getClient();
    $service = new Google_Service_Classroom($client);
    //$service->courses_students->listCoursesStudents($courseID)->getStudents()->getFullName();
    $output = "";
    $results = $service->courses->listCourses();


    if (count($results->getCourses()) == 0) {
        print "No courses found.\n";
    } else {

        $output .= "<table border = 1>";
        $output .= "<tr><th>Название ОП (Учебная группа)</th><th>Название дисциплины</th> <th>Статус курса</th> 
                <th>Дата создания курса</th> <th>Курс ID</th> <th>ownerID</th></tr>";
        $course = $results->getCourses();
        for ($i = 0; $i < count($course); $i++) {
            //foreach ($results->getCourses() as $course) {
            if ($course[$i]->getOwnerId() == 107618027634625870454) {
                $output .= "<tr><td>" . $course[$i]->getSection() . "</td> <td>" . $course[$i]->getName() . "</td> <td>" . $course[$i]->getCourseState() .
                    "</td> <td>" . $course[$i]->getCreationTime() . "</td> <td>" . $course[$i]->getId() . "</td> <td>" . $course[$i]->getOwnerId() .
                    "</td><td>";
                $st = getStudentFullName($service, $course[$i]->getId());
                for ($i = 0; $i < count($st); $i++) {
                    $output .= "<tr>" . $st[$i] . "</tr>";
                    $output .= "</td></tr>";
                }
            }
            $output .= "</table>";
        }
        return $output;
    }



}

$st = test(252565900353);

print "<table border =1>";
print "<tr><th>Студент</th><th>Бағасы</th><th>Title</th></tr>";
foreach ($st as $key=>$value){
    print "<tr>";
    foreach ($value as $key=>$val){
        print "<td>".$val."</td>";
    }
    print "</tr>";
}
print "</table>";