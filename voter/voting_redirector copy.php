<?php
session_start();
$_SESSION['STATUS'] = "VOTING_ACCEPTED";
header('Location: vote_qr_code.php')
?>
