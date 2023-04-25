.. include:: /Includes.rst.txt

.. _deprecation-99237-1681640732:

=======================================
Deprecation: #99237 - MagicImageService
=======================================

See :issue:`99237`

Description
===========

The class :php:`\TYPO3\CMS\Core\Resource\Service\MagicImageService`, which was
previously used for inline images by EXT:rtehtmlarea has been marked as
deprecated, since its functionality is no longer needed for `CKeditor`.

Impact
======

Using :php:`\TYPO3\CMS\Core\Resource\Service\MagicImageService` or one of its
public methods will raise a deprecation level log message.


Affected installations
======================

TYPO3 installations with custom extensions using the class or its public
methods. The extension scanner will report usages as strong match.

Migration
=========

There is no direct migration. In case you rely on any of the provided
functionality, just copy the corresponding code into your custom extension.

.. index:: PHP-API, NotScanned, ext:core
