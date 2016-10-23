.. include:: ../../Includes.txt

==============================================================
Feature: #78384 - Check ext tables TCA changes in install tool
==============================================================

See :issue:`78384`

Description
===========

The install tool has a new feature to check extensions for :file:`ext_tables.php` files that still change `TCA`.


Impact
======

Changing `TCA` in :file:`ext_tables.php` is not allowed and can lead to failed or incomplete frontend requests.
The feature helps to find affected, loaded extensions.

.. index:: TCA, ext:install