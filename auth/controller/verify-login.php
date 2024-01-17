<?php

$currentDir = dirname($_SERVER['PHP_SELF']);
$FirstDir = explode('/', trim($currentDir, '/'));
$rootFolder = "//".$_SERVER['SERVER_NAME'] . "/" . $FirstDir['0'];

class LoginHandler
{
    private $dbCon;

    public function __construct($dbCon)
    {
        $this->dbCon = $dbCon;
    }

    public function authenticateUser($email, $password)
    {
        global $hasError, $message, $rootFolder;

        $userRole = $this->loginValidator($email, $password);
        
        if ($userRole == "admin" || $userRole == "student" || $userRole == "instructor") {
            $this->saveSession($userRole, $email);
            header("Location: {$rootFolder}/{$userRole}");
            exit();
        } else {
            $hasError = true;
            $message = "You have entered an invalid email address or password!";
        }
    }

    private function loginValidator($email, $password)
    {
        $pwd = crypt($password, '$6$Crypt$');
        $sql = "SELECT * FROM ap_userdetails WHERE email='".$email."' AND password='" . $pwd. "'";
        $result = $this->dbCon->query($sql);
        $fetch = mysqli_fetch_assoc($result);

        if($fetch === null){
            return "Inalid";
        }

        return $fetch['roles'];
    } //potek di na naman ako makalogin

    private function saveSession($userRole, $email)
    {
        $_SESSION['session'] = $userRole;
        $_SESSION['email'] = $email;
    }
}

if (isset($_POST['login'])) {
    require "../configuration/config.php";
    $email = $dbCon->real_escape_string($_POST['email']);
    $password = $dbCon->real_escape_string($_POST['password']);
    $loginHandler = new LoginHandler($dbCon);
    $loginHandler->authenticateUser($email, $password);
}
?>
