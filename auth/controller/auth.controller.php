<?php 

class AuthController {
    public function __construct() {}

    /**
     * Checks if the user is authenticated
     * and redirects user to its correct panel
     * based on his/her respective role.
     * 
     * @return bool
     */
    public static function isAuthenticated() : bool {
        $currentDir = dirname($_SERVER['PHP_SELF']);
        $FirstDir = explode('/', trim($currentDir, '/'));
        $rolePanel = strtolower($FirstDir[1]);

        if (isset($_SESSION['session']) && !empty($_SESSION['session'])) {
            $userRole = strtolower($_SESSION['session']);

            if ($rolePanel != $userRole) {
                $relativeDir = str_repeat("../", count($FirstDir) - 1);
                header("location: {$relativeDir}{$userRole}");
            }

            return true;
        }

        return false;
    }

    /**
     * Returns the current authenticated user
     * 
     * @return object|null
     */
    public static function user() {
        global $dbCon;
        
        if(self::isAuthenticated() && isset($_SESSION['email'])) {
            $email = $dbCon->real_escape_string($_SESSION['email']);
            $result = $dbCon->query("SELECT * FROM userdetails WHERE email = '$email'");
            $user = $result->fetch_assoc();

            // check role
            if($user['roles'] == 'admin' || $user['roles'] == 'instructor' || $user['roles'] == 'student') {
                $result = $dbCon->query("SELECT * FROM userdetails WHERE email = '$email'");
                $user = $result->fetch_assoc();
            }
            return ((object) $user) ?? null;
        }

        return null;
    }
}

?>