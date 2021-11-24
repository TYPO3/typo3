<?php

// Register evaluateFieldValue() and deevaluateFieldValue() for input_21 field
$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['tce']['formevals']['TYPO3\\CMS\\Styleguide\\UserFunctions\\FormEngine\\TypeInput21Eval'] = '';

// Register own renderType for tx_styleguide_elements_basic user_1 as user1Element
$GLOBALS['TYPO3_CONF_VARS']['SYS']['formEngine']['nodeRegistry'][1536238257] = [
    'nodeName' => 'user1Element',
    'priority' => 40,
    'class' => \TYPO3\CMS\Styleguide\Form\Element\User1Element::class,
];
