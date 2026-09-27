<?php
require_once '../../config/db.php';
requireLogin();

$id = intval($_GET['id'] ?? 0);
if (!$id) { header("Location: index.php"); exit(); }

// Get id_lot before deleting so we can reset status
$burial = mysqli_fetch_assoc(mysqli_query($conn, "SELECT id_lot FROM tbl_pengebumian WHERE id_pengebumian=$id"));

if ($burial) {
    mysqli_query($conn, "DELETE FROM tbl_pengebumian WHERE id_pengebumian=$id");
    // Reset lot back to available
    mysqli_query($conn, "UPDATE tbl_lot SET status='tersedia' WHERE id_lot={$burial['id_lot']}");
}

header("Location: index.php?deleted=1");
exit();
?>
