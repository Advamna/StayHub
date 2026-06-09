<?php
session_start();
session_unset();
session_destroy();
// Go back to the root index.php
header("Location: ../index.php");
exit();
?>