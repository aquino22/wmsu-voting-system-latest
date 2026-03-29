<?php
session_start();
session_unset();
session_destroy();
session_start();
$_SESSION['STATUS'] = "LOGOUT_SUCCESFUL";
header("Location: ../../../index.php");
exit();
?>
