<?php

use diversen\lang;

// Example route
$_INSTALL['ROUTES'][] = array ('#/userinfo/usage/index#'=>
    array ('method' => 'usage::indexAction')
);

// Example route
$_INSTALL['ROUTES'][] = array ('#/userinfo/usage/total#'=>
    array ('method' => 'usage::totalAction')
);

// Add translation
lang::translate('Usage');
