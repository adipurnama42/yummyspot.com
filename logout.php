<?php
require_once __DIR__ . '/includes/helpers.php';
logoutUser();
redirect(route('login'));
