<?php
require_once '../../config/db.php';
requireLogin();
requireAdmin();
$id = intval($_GET['id'] ?? 0);
if ($id && $id != $_SESSION['id_pengguna']) {
    mysqli_query($conn, "DELETE FROM tbl_pengguna WHERE id_pengguna=$id");
}
header("Location: index.php?deleted=1");
exit();
?>
