<?php
session_start();
session_destroy();
header('Location: /vps-panel/login.php');
exit;
