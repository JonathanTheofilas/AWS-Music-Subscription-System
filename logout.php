// Purpose: To log out the user and redirect them to the login page

<?php
session_start();
session_destroy();
header('Location: login.php');
exit();
?>