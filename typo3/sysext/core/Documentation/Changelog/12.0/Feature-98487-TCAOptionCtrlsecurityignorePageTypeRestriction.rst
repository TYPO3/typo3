.. include:: /Includes.rst.txt

.. _feature-98487-1664575753:

========================================================================
Feature: #98487 - TCA option [ctrl][security][ignorePageTypeRestriction]
========================================================================

See :issue:`98487`

Description
===========

A new TCA ctrl option :php:`$GLOBALS['TCA'][$table]['ctrl']['security']['ignorePageTypeRestriction']`
(boolean) is introduced to define the custom TCA table to be added to any
given page type (custom or defined), unless specified differently via the
:php:`PageDoktypeRegistry` API class for a specified doktype.

Impact
======

This is a replacement for the previous PHP API call
:php:`ExtensionManagementUtility::allowTableOnStandardPages` which was found
in :file:`ext_tables.php` files.

Setting the new TCA option allows to use a TCA table on any kind of page doktype
unless a doktype has a restriction set in the :php:`PageDoktypeRegistry` API
class.

.. index:: PHP-API, TCA, ext:core
