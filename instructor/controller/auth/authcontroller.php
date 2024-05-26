<?php 

class AuthController {
    public function __construct() {}

    /**
     * Checks if the user is authenticated
     * 
     * @return bool
     */
    public static function isAuthenticated() : bool {
        return isset($_SESSION['session']) && !empty($_SESSION['session']);
    }

    /**
     * Returns the current authenticated user
     * 
     * @return object|null
     */
    public static function user() : object|null {
        global $dbCon;
        
        if(self::isAuthenticated() && isset($_SESSION['email'])) {
            $email = $dbCon->real_escape_string($_SESSION['email']);
            $result = $dbCon->query("SELECT * FROM userdetails WHERE email = '$email'");
            $user = $result->fetch_assoc();

            // check role
            if($user['roles'] == 'admin') {
                $result = $dbCon->query("SELECT * FROM userdetails WHERE email = '$email'");
                $user = $result->fetch_assoc();
            }

            
            return ((object) $user) ?? null;
        }

        return null;
    }
}

?>