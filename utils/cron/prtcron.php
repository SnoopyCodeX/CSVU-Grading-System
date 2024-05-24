<?php 
/**
 * This php file is responsible for keeping track of expired
 * password reset tokens.
 */

require_once('../../configuration/config.php');
require_once('../mailer.php');

$updateExpiredTokensQuery = $dbCon->query("UPDATE password_reset_tokens SET status='expired' WHERE status='active' AND createdAt <= NOW() - INTERVAL 2 MINUTE");
$deleteExpiredTokensQuery = $dbCon->query("DELETE FROM password_reset_tokens WHERE (status IN ('used', 'expired')) AND createdAt <= NOW() - INTERVAL 15 DAY");
?>