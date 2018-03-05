.. include:: ../../Includes.txt

=====================================================================
Feature: #82426 - New navigation module registration (e.g. Page tree)
=====================================================================

See :issue:`82426`

Description
===========

When registering an extensions's BE module with :php:`ExtensionUtility::registerModule()` it is possible
to define 'navigationComponentId'.

Before, the 'navigationComponentId' has been used to pass a name of the ExtJS module registered with
:php:`ExtensionManagementUtility::addNavigationComponent()`.

Now it should contain a RequireJS module name. No additional registration is necessary.
The TYPO3 page tree navigation component name 'typo3-pagetree' will still work (thanks to the BC layer)
but will throw a deprecation notice.

Should be changed to new configuration:

.. code-block:: php

    \TYPO3\CMS\Extbase\Utility\ExtensionUtility::registerModule(
        'TYPO3.CMS.Workspaces',
        'web',
        'workspaces',
        'before:info',
        [
            // An array holding the controller-action-combinations that are accessible
            'Review' => 'index,fullIndex,singleIndex',
            'Preview' => 'index,newPage'
        ],
        [
            'access' => 'user,group',
            'icon' => 'EXT:workspaces/Resources/Public/Icons/module-workspaces.svg',
            'labels' => 'LLL:EXT:workspaces/Resources/Private/Language/locallang_mod.xlf',
            'navigationComponentId' => 'TYPO3/CMS/Backend/PageTree/PageTreeElement'
        ]
    );


.. index:: Backend, JavaScript, PHP-API
