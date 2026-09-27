<?php
require_once '../../config/db.php';
requireLogin();

$id = intval($_GET['id'] ?? 0);
if (!$id) { header("Location: index.php"); exit(); }

$check = mysqli_fetch_row(mysqli_query($conn, "SELECT COUNT(*) FROM tbl_pengebumian WHERE id_simati = $id"))[0];
if ($check > 0) { header("Location: index.php?error=has_burial"); exit(); }

mysqli_query($conn, "DELETE FROM tbl_waris WHERE id_simati = $id");
mysqli_query($conn, "DELETE FROM tbl_simati WHERE id_simati = $id");
header("Location: index.php?deleted=1");
exit();
?>
