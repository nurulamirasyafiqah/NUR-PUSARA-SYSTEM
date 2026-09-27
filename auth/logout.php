<?php
require_once '../config/db.php';
session_destroy();
header("Location: " . BASE_URL . "/index.php");
exit();
?>
