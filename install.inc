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

// Example route
$_INSTALL['ROUTES'][] = array ('#/userinfo/usage/set#'=>
    array ('method' => 'usage::setAction')
);

// Add translation
lang::translate('Usage');
