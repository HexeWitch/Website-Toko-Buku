<?php
session_start();

/* hapus session admin */
unset($_SESSION['admin']);

session_destroy();

/* balik ke login admin */
header("Location: ../login.php");
exit;
