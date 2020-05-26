<?php

/*
 *  This file is part of the TYPO3 CMS project.
 *
 *  It is free software; you can redistribute it and/or modify it under
 *  the terms of the GNU General Public License, either version 2
 *  of the License, or any later version.
 *
 *  For the full copyright and license information, please read the
 *  LICENSE.txt file that was distributed with this source code.
 *
 *  The TYPO3 project - inspiring people to share!
 */

$EM_CONF[$_EXTKEY] = [
    'title' => 'TYPO3 CMS Backend Styleguide and Testing use cases',
    'description' => 'Presents most supported styles for TYPO3 backend modules. Mocks typography, tables, forms, buttons, flash messages and helpers. More at https://github.com/7elix/TYPO3.CMS.Styleguide',
    'category' => 'plugin',
    'author' => 'Felix Kopp',
    'author_email' => 'felix-source@phorax.com',
    'author_company' => 'PHORAX',
    'state' => 'stable',
    'createDirs' => '',
    'clearCacheOnLoad' => 0,
    'version' => '10.0.0',
    'constraints' => [
        'depends' => [
            'typo3' => '10.0.0-10.99.99',
        ],
        'conflicts' => [
        ],
        'suggests' => [
        ],
    ],
];
