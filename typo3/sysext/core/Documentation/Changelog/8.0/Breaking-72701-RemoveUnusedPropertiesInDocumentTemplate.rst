===============================================================
Breaking: #72701 - Remove unused properties in DocumentTemplate
===============================================================

Description
===========

Remove deprecated code from DocumentTemplate

The following properties have been removed:

``tableLayout``
``table_TR``
``table_TABLE``


Impact
======

Using the properties above directly in any third party extension will result in a fatal error.


Affected Installations
======================

Instances which use calls to any of the above mentioned properties.


Migration
=========

No migration available.

.. index:: php
