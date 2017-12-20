.. include:: ../../Includes.txt

==============================================================
Feature: #78384 - Check ext tables TCA changes in install tool
==============================================================

See :issue:`78384`

Description
===========

The install tool has a new feature to check extensions for :file:`ext_tables.php` files that still change the global `TCA` array.


Impact
======

Changing the global `TCA` array in :file:`ext_tables.php` is not allowed and can lead to failing or incomplete frontend requests.
The feature helps to find affected, loaded extensions.

.. index:: TCA, Backend
