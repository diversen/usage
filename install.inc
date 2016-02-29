<?php

use diversen\lang;

// Example route
$_INSTALL['ROUTES'][] = array ('#/userinfo/usage/index#'=>
    array ('method' => 'usage::indexAction')
);

// Just to add translation
lang::translate('Usage');
