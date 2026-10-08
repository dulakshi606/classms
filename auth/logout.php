<?php
session_start();
session_destroy();
header("Location: /classms/auth/login.php");
exit();
