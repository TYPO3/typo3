.. include:: /Includes.rst.txt

.. _deprecation-97923-1673529717:

====================================================
Deprecation: #97923 - Deprecate UserFileMountService
====================================================

See :issue:`97923`

Description
===========

The class :php:`\TYPO3\CMS\Core\Resource\Service\UserFileMountService` is not
used anymore within the TYPO3 Core and has been marked deprecated. The class
will finally be removed in TYPO3 v13.

Impact
======

Using the class will raise a deprecation level log entry and will stop
working with TYPO3 v13.


Affected installations
======================

Instances with extensions that use the class are affected.

The extension scanner reports affected extensions.


Migration
=========

Instead of using the class in TCA for an :php:`itemsProcFunc`, the TCA
type `folder` should be used, to improve the usability of selecting a folder.

..  code-block:: php

    'identifier' => [
        'label' => 'Folder selection',
        'config' => [
            'type' => 'folder',
            'elementBrowserEntryPoints' => [
                '_default' => '1:/user_upload/'
            ]
        ]
    ],

.. index:: Backend, TCA, FullyScanned, ext:core
