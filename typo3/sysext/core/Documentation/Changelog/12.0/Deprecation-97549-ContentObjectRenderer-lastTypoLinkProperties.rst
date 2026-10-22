.. include:: /Includes.rst.txt

.. _deprecation-97549-1651696509:

=====================================================================
Deprecation: #97549 - ContentObjectRenderer->lastTypoLink* properties
=====================================================================

See :issue:`97549`

Description
===========

When generating links via :php-short:`\TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer->typoLink()`,
it had been possible to retrieve information about the generated link
with the following public properties:

* :php-short:`\TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer->lastTypoLinkUrl`
* :php-short:`\TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer->lastTypoLinkTarget`
* :php-short:`\TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer->lastTypoLinkLD`

Since those information are also available in the :php-short:`\TYPO3\CMS\Frontend\Typolink\LinkResultInterface`,
which is returned by :php-short:`\TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer->createLink()` or
can be accessed via :php-short:`\TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer->lastTypoLinkResult`,
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

It is recommended to retrieve this information via the :php-short:`\TYPO3\CMS\Frontend\Typolink\LinkResultInterface`
object returned by calling :php-short:`\TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer->createLink()` directly,
or if this is not possible via :php-short:`\TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer->lastTypoLinkResult`.

.. index:: Frontend, PHP-API, TypoScript, FullyScanned, ext:frontend
