.. include:: /Includes.rst.txt

.. _breaking-102875-1706013339:

=============================================
Breaking: #102875 - ExpressionBuilder changes
=============================================

See :issue:`102875`

Description
===========

Signature changes:

*   :php:`ExpressionBuilder::literal(string $value)`: Value must be a string now.

Impact
======

Calling any of the mentioned methods with invalid type will result in a PHP
error.

Affected installations
======================

Only those installations that use the mentioned method with wrong type.

Migration
=========

Extension author need to ensure that a string is passed to :php:`literal()`.

.. index:: Database, PHP-API, NotScanned, ext:core
