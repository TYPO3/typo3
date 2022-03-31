.. include:: /Includes.rst.txt

========================================================================================
Important: #94889 - AbstractTypoLinkBuilder::build now returns array|LinkResultInterface
========================================================================================

See :issue:`94889`

Description
===========

The method signature of :php:`\TYPO3\CMS\Frontend\Typolink\AbstractTypoLinkBuilder` has changed, as
:php:`array` return type has been removed, thus loosening the inheritance
criteria for TYPO3 v11.

In TYPO3 v12 :php:`AbstractTypoLinkBuilder` will have a
:php:`\TYPO3\CMS\Frontend\Typolink\LinkResultInterface` return type.

Extensions using this class can stay compatible with two major TYPO3 LTS
versions by doing the following:

* Keeping an :php:`array` return type to stay compatible with
  TYPO3 v10 and TYPO3 v11.
* Using the :php:`LinkResultInterface` return type to stay compatible with
  TYPO3 v11 and TYPO3 v12+.

.. index:: Frontend, PHP-API, TypoScript, ext:frontend
