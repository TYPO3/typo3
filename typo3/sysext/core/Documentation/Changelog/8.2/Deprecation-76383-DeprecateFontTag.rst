
.. include:: ../../Includes.txt

=======================================
Deprecation: #76383 - Deprecate fontTag
=======================================

See :issue:`76383`

Description
===========

Font tags are not used any more in HTML since years.

-  :php:`ContentObjectRenderer::stdWrap_fontTag()`
-  :typoscript:`stdWrap.fontTag``


Impact
======

Using the mentioned method or stdWrap property will trigger a deprecation log entry.


Affected Installations
======================

Instances that use the method or stdWrap property.


Migration
=========

Update HTML to not output font tags. Use CSS instead. In case you really want to use the font tag,
it can be created by :typoscript:`stdWrap.wrap`.

.. index:: Frontend, PHP-API, TypoScript
