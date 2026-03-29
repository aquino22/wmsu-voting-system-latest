<?php
$password = "wmsuadmin";
$hash = password_hash($password, PASSWORD_DEFAULT);
echo $hash;
?>