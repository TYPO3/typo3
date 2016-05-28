<?php

/**
 * Definitions for routes provided by EXT:rsaauth
 */
return [
    // Get RSA public key
    'rsa_publickey' => [
        'path' => '/rsa/publickey',
        'target' => \TYPO3\CMS\Rsaauth\RsaEncryptionEncoder::class . '::getRsaPublicKeyAjaxHandler',
        'access' => 'public'
    ],
];
