.. include:: ../../Includes.txt

=========================================================
Feature: #83637 - Added new main module "Site Management"
=========================================================

See :issue:`83637`


Description
===========

A new main module for the TYPO3 Backend "Site" (module key "site") has been added to the TYPO3 Core.

Its main purpose is to host submodules related to integrators and site maintainers to configure a website,
language configuration, domains and routing.

For TYPO3 9.1, the system extension "redirects" adds URL redirects to the main module, if installed.


Impact
======

To add a new module to the Site main module, register a module within an extensions` :php:'ext_tables.php' file:

.. code-block:: php

    \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addModule(
        'site',
        'mymodule',
        '',
        '',
        [
            'routeTarget' => \MyVendor\MyPackage\Controller\MyModuleController::class . '::handleRequest',
            'access' => 'group,user',
            'name' => 'site_mymodule',
            'icon' => 'EXT:mypackage/Resources/Public/Icons/module_icon.svg',
            'labels' => 'LLL:EXT:mypackage/Resources/Private/Language/locallang_module_mymodule.xlf'
        ]
    );

.. index:: Backend
