.. include:: ../../Includes.txt

===============================================
Deprecation: #90686 - Deprecate model FileMount
===============================================

See :issue:`90686`

Description
===========

The class :php:`\TYPO3\CMS\Extbase\Domain\Model\FileMount` has been marked as deprecated.

The :php:`FileMount` is an internal class which never really had any functionality besides being an Extbase model for the database table `sys_filemounts`. Therefore
and in order to streamline the codebase of Extbase, the :php:`FileMount` will be removed with TYPO3 11.0.


Impact
======

Using :php:`FileMount` will trigger a PHP deprecation warning.


Affected Installations
======================

Any TYPO3 installation with a third-party extension using the model.


Migration
=========

Copy the class and mapping to your own extension and adopt the usages.

.. index:: PHP-API, FullyScanned, ext:extbase
