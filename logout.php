<?php
require_once 'includes/init.php';

// Gebruiker uitloggen
if (isLoggedIn()) {
    $user = new User();
    $user->logout();
}

// Redirect naar login
redirect('login.php', 'Succesvol uitgelogd!', 'success');
?> 