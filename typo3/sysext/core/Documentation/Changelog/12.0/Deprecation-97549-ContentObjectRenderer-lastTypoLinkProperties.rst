.. include:: /Includes.rst.txt

.. _deprecation-97549-1651696509:

=====================================================================
Deprecation: #97549 - ContentObjectRenderer->lastTypoLink* properties
=====================================================================

See :issue:`97549`

Description
===========

When generating links via :php:`ContentObjectRenderer->typoLink()`,
it had been possible to retrieve information about the generated link
with the following public properties:

* :php:`ContentObjectRenderer->lastTypoLinkUrl`
* :php:`ContentObjectRenderer->lastTypoLinkTarget`
* :php:`ContentObjectRenderer->lastTypoLinkLD`

Since those information are also available in the :php:`LinkResultInterface`,
which is returned by :php:`ContentObjectRenderer->createLink()` or
can be accessed via :php:`ContentObjectRenderer->lastTypoLinkResult`,
these properties have now been deprecated.

Impact
======

Accessing these properties is still possible, but will stop working in
TYPO3 v13.0. The extension scanner will detect any usage as weak match.

Affected installations
======================

TYPO3 installations using these properties in their extensions in either
PHP or TypoScript code.

Migration
=========

It is recommended to retrieve this information via the :php:`LinkResultInterface`
object returned by calling :php:`ContentObjectRenderer->createLink()` directly,
or if this is not possible via :php:`ContentObjectRenderer->lastTypoLinkResult`.

.. index:: Frontend, PHP-API, TypoScript, FullyScanned, ext:frontend
