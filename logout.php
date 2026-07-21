<?php
// ===== START SESSION =====
session_start();

// ===== DESTROY SESSION =====
session_destroy();

// ===== REDIRECT TO HOME =====
header('Location: index.php');
exit();
?>