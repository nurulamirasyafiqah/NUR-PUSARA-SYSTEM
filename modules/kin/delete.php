<?php
require_once '../../config/db.php';
requireLogin();
$id = intval($_GET['id'] ?? 0);
$id_pengebumian = intval($_GET['id_pengebumian'] ?? 0);
if ($id) mysqli_query($conn, "DELETE FROM tbl_waris WHERE id_waris=$id");
if ($id_pengebumian) {
    header("Location: " . BASE_URL . "/modules/burial/view.php?id=$id_pengebumian");
} else {
    header("Location: " . BASE_URL . "/modules/burial/index.php");
}
exit();
?>
