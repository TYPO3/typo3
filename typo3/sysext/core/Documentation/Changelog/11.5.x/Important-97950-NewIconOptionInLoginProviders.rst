.. include:: /Includes.rst.txt

.. _important-97950-1657892101:

==================================================================
Important: #97950 - New "iconIdentifier" option in login providers
==================================================================

See :issue:`97950`

Description
===========

A new option :php:`iconIdentifier` is added to login providers, which accepts
any icon that's available in the Icon Registry.

Example:

.. code-block:: php

    $GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['backend']['loginProviders'][1433416747] = [
        'provider' => UsernamePasswordLoginProvider::class,
        'sorting' => 50,
        'iconIdentifier' => 'actions-key',
        'label' => 'LLL:EXT:backend/Resources/Private/Language/locallang.xlf:login.link',
    ];

.. index:: Backend, PHP-API, ext:backend
