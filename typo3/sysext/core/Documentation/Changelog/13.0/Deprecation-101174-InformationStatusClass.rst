.. include:: /Includes.rst.txt

.. _deprecation-101174-1688128234:

==============================================
Deprecation: #101174 - InformationStatus class
==============================================

See :issue:`101174`

Description
===========

The class :php:`\TYPO3\CMS\Backend\Toolbar\Enumeration\InformationStatus` has
been marked as deprecated in favour of the new nativ
:doc:`enum <../13.0/Feature-101174-InformationStatus>`
:php:`\TYPO3\CMS\Backend\Toolbar\InformationStatus`.

Additionally, passing a :php:`string` as :php:`$status` to either
:php:`addSystemInformation()` or :php:`addSystemMessage()` of
class :php:`\TYPO3\CMS\Backend\Backend\ToolbarItems\SystemInformationToolbarItem`
has been deprecated as well. An instance of the new enum has to be provided.

Impact
======

Usage of the class :php:`\TYPO3\CMS\Backend\Toolbar\Enumeration\InformationStatus`,
its constants or methods will trigger a PHP :php:`E_USER_DEPRECATED` error. The
class will be removed in TYPO3 v14.0.

Passing a :php:`string` as :php:`$status` to either :php:`addSystemInformation()`
or :php:`addSystemMessage()` of class
:php:`\TYPO3\CMS\Backend\Backend\ToolbarItems\SystemInformationToolbarItem` will
trigger a PHP :php:`E_USER_DEPRECATED` error.


Affected installations
======================

All installations using the class
:php:`\TYPO3\CMS\Backend\Toolbar\Enumeration\InformationStatus` directly or
passing a :php:`string` as :php:`$status` to either :php:`addSystemInformation()`
or :php:`addSystemMessage()` of class :php:`\TYPO3\CMS\Backend\Backend\ToolbarItems\SystemInformationToolbarItem`.

The extension scanner will report any usage of the deprecated class as
strong match.

Migration
=========

.. code-block:: php

    // Before
    $status = \TYPO3\CMS\Backend\Toolbar\Enumeration\InformationStatus::cast(
        \TYPO3\CMS\Backend\Toolbar\Enumeration\InformationStatus::STATUS_INFO
    );
    $statusString = (string)$status;


    // After
    $status = \TYPO3\CMS\Backend\Toolbar\Enumeration\InformationStatus::INFO;
    $statusString = $status->value;

.. index:: Backend, PartiallyScanned, ext:backend
