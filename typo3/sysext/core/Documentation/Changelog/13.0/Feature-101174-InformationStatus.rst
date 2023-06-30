.. include:: /Includes.rst.txt

.. _feature-101174-1688128233:

================================================
Feature: #101174 - Native enum InformationStatus
================================================

See :issue:`101174`

Description
===========

A new native backed enum :php:`\TYPO3\CMS\Backend\Toolbar\InformationStatus`
has been introduced as a drop-in replacement for the for former
:php:`\TYPO3\CMS\Backend\Toolbar\Enumeration\InformationStatus` class, which
is used to specify the severity of a system information, displayed in the
backend toolbar.

The new enum features the following values:

* :php:`NOTICE`
* :php:`INFO`
* :php:`OK`
* :php:`WARNING`
* :php:`ERROR`

Additonally, the :php:`isGreaterThan()` method is available to compare severities.

Impact
======

It's now possible to use the native :php:`\TYPO3\CMS\Backend\Toolbar\InformationStatus`
enum to describe the severity of system informations for the backend toolbar.

.. note::

    Compared to the :doc:`deprecated <../13.0/Deprecation-101174-InformationStatusClass>`
    :php:`\TYPO3\CMS\Backend\Toolbar\Enumeration\InformationStatus` class,
    does the new enum not use the prefix :php:`STATUS_` for its values. Also
    a special :php:`__default` constant is not available.

.. index:: Backend, PHP-API, ext:backend
