<?php

declare(strict_types=1);
require_once __DIR__ . '/includes/bootstrap.php';

require_post();
logout_session();
flash('success', 'Você saiu do sistema.');
redirect('login.php');
