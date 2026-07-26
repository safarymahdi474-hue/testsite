<?php
// logout.php
require_once 'config/database.php';
require_once 'includes/auth.php';

$db = new Database();
$conn = $db->getConnection();
$auth = new Auth($conn);

$auth->logout();
header("Location: login.php");
exit();
?>
