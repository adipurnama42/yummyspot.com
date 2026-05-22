<?php
require_once __DIR__ . '/includes/helpers.php';
logoutUser();
redirect(APP_URL.'/login.php');
