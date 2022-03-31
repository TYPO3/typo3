
.. include:: /Includes.rst.txt

===============================================================================
Feature: #67236 - Added "allowedTags" argument to f:format.stripTags ViewHelper
===============================================================================

See :issue:`67236`

Description
===========

The argument `allowedTags` containing a list of HTML tags which will not be stripped
can now be used on `f:format.stripTags`. Tag list syntax is identical to second
parameter of PHP function `strip_tags`.


Impact
======

New ViewHelper argument (string) `allowedTags` becomes available, allowing passing
the tag list as second parameter to `strip_tags`.

.. index:: Fluid
