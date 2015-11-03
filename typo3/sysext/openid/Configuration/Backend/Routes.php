<?php
/**
 * Definitions of routes
 */
return [
    // Register wizard
    'wizard_openid' => [
        'path' => '/wizard/openid',
        'target' => \FoT3\Openid\Wizard::class . '::mainAction'
    ]
];
