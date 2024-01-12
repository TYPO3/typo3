.. include:: /Includes.rst.txt

.. _important-102799-1707403491:

===========================================================================================
Important: #102799 - TYPO3_CONF_VARS.GFX.processor_stripColorProfileParameters option added
===========================================================================================

See :issue:`102799`

Description
===========

The string-based configuration option
:php:`$GLOBALS['TYPO3_CONF_VARS']['GFX']['processor_stripColorProfileCommand']`
has been superseded by
:php:`$GLOBALS['TYPO3_CONF_VARS']['GFX']['processor_stripColorProfileParameters']`
for security reasons.

The former option expected a string of command line parameters. The defined
parameters had to be shell-escaped beforehand, while the new option expects an
array of strings that will be shell-escaped by TYPO3 when used.

The existing configuration will continue to be supported. Still, it is suggested
to use the new configuration format, as the Install Tool is adapted to allow
modification of the new configuration option only:

..  code-block:: php

    // Before
    $GLOBALS['TYPO3_CONF_VARS']['GFX']['processor_stripColorProfileCommand'] = '+profile \'*\'';

    // After
    $GLOBALS['TYPO3_CONF_VARS']['GFX']['processor_stripColorProfileParameters'] = [
        '+profile',
        '*'
    ];


.. index:: LocalConfiguration, ext:core
