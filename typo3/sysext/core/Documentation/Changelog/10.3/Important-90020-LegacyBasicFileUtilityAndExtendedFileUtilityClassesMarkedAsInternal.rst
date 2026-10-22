.. include:: /Includes.rst.txt

.. _important-90020:

==============================================================================================
Important: #90020 - Legacy BasicFileUtility and ExtendedFileUtility classes marked as internal
==============================================================================================

See :issue:`90020`

Description
===========

The two classes used to handle File permission and File upload logic - BasicFileUtility and ExtendedFileUtility -
have been marked as internal, as TYPO3 Core now fully relies on the File Abstraction Layer, which was introduced in TYPO3 v6.0.

The remaining parts are partially in use and will be phased out, for the time being all
extension authors should rely on :php-short:`\TYPO3\CMS\Core\Resource\ResourceStorage` and :php-short:`\TYPO3\CMS\Core\Resource\ResourceFactory` for managing assets.

.. index:: FAL, ext:core
