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

$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['tce']['formevals']['TYPO3\\CMS\\Styleguide\\UserFunctions\\FormEngine\\TypeInput21Eval'] = '';

// Register evaluateFieldValue() and deevaluateFieldValue() for input_21 field
$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['tce']['formevals']['TYPO3\\CMS\\Styleguide\\UserFunctions\\FormEngine\\TypeText9Eval'] = '';

// Register own renderType for tx_styleguide_elements_basic user_1 as user1Element
$GLOBALS['TYPO3_CONF_VARS']['SYS']['formEngine']['nodeRegistry'][1536238257] = [
    'nodeName' => 'user1Element',
    'priority' => 40,
    'class' => \TYPO3\CMS\Styleguide\Form\Element\User1Element::class,
];
