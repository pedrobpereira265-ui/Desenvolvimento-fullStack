<?php

declare(strict_types=1);
require_once __DIR__ . '/includes/bootstrap.php';

redirect(current_user() ? 'eventos.php' : 'login.php');
