<?php
require __DIR__.'/app/bootstrap.php';unset($_SESSION['customer_id'],$_SESSION['customer_name']);session_regenerate_id(true);header('Location: customer-login.php');exit;