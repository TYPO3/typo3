.. include:: /Includes.rst.txt

.. _breaking-102499-1700813634:

======================================================================
Breaking: #102499 - User TSconfig setting "overridePageModule" removed
======================================================================

See :issue:`102499`

Description
===========

The user TSconfig setting :typoscript:`options.overridePageModule` has been
removed.

This option allowed to change links within some modules to be redirected to an
alternative page module, mainly introduced in TYPO3 4.x to allow to link to
the TemplaVoila page module.

However, as this has never been applied consistently across all modules
provided by TYPO3 Core, it has been removed. The only few places within TYPO3
Core where this option was still evaluated was within the Workspaces
Administration and the Info module.

The alternative, using a different routing endpoint and support for module
aliases via the introduced Module API in TYPO3 v12, is much more robust and
consistent.


Impact
======

Setting the user TSconfig option :typoscript:`options.overridePageModule` has
no effect anymore.


Affected installations
======================

TYPO3 installations using this setting in user TSconfig, mainly when used in
conjunction with TemplaVoila and having mixed installations where both
TemplaVoila page module and the default Page module are used for different
editors.


Migration
=========

In order to replace the Page module within a third-party extension such as
TemplaVoila, it is possible to create a custom module entry in an
extensions' :file:`Configuration/Backend/Modules.php` with the following entry:

.. code-block:: php

    return [
        'my_module' => [
            'parent' => 'web',
            'position' => ['before' => '*'],
            'access' => 'user',
            'aliases' => ['web_layout'],
            'path' => '/module/my_module',
            'iconIdentifier' => 'module-page',
            'labels' => 'LLL:EXT:backend/Resources/Private/Language/locallang_mod.xlf',
            'routes' => [
                '_default' => [
                    'target' => \MyVendor\MyPackage\Controller\MyController::class . '::mainAction',
                ],
            ],
        ],
    ];

.. index:: Backend, TSConfig, NotScanned, ext:backend
