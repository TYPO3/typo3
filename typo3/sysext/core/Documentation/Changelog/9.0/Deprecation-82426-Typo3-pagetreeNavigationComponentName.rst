.. include:: ../../Includes.txt

==============================================================
Deprecation: #82426 - typo3-pagetree navigation component name
==============================================================

See :issue:`82426`

Description
===========

When registering an extensions's BE module with :php:`ExtensionUtility::registerModule()` it is possible to define 'navigationComponentId'.

The name the navigation component name :php:`typo3-pagetree` has been marked as deprecated.
:php:`TYPO3/CMS/Backend/PageTree/PageTreeElement` should be used instead.

Impact
======

Calling :php:`ExtensionUtility::registerModule()` with the old navigation component name will trigger a deprecation log entry.

Affected Installations
======================

All installations having custom BE modules passing the old navigation component name to :php:`ExtensionUtility::registerModule()`.


Migration
=========

Use :php:`TYPO3/CMS/Backend/PageTree/PageTreeElement` instead of `typo3-pagetree`.



Old configuration:
------------------

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
              'navigationComponentId' => 'typo3-pagetree'
          ]
      );


Should be changed to new configuration:
---------------------------------------

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


.. index:: Backend, PHP-API, PartiallyScanned
