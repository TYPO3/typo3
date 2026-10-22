.. include:: /Includes.rst.txt

.. _deprecation-89554:

==========================================================================================
Deprecation: #89554 - Deprecate \\TYPO3\\CMS\\Extbase\\Mvc\\Controller\\AbstractController
==========================================================================================

See :issue:`89554`

Description
===========

The class :php:`\TYPO3\CMS\Extbase\Mvc\Controller\AbstractController` has been marked as deprecated.

The :php-short:`\TYPO3\CMS\Extbase\Mvc\Controller\AbstractController` is an internal class which never really had any functionality besides
providing some basic methods for the :php:`\TYPO3\CMS\Extbase\Mvc\Controller\ActionController`. Therefore
and in order to streamline the codebase of extbase, the :php-short:`\TYPO3\CMS\Extbase\Mvc\Controller\AbstractController` will be removed
with TYPO3 11.0.


Impact
======

As all functionality of the :php-short:`\TYPO3\CMS\Extbase\Mvc\Controller\AbstractController` has been moved to the :php-short:`\TYPO3\CMS\Extbase\Mvc\Controller\ActionController` there is no impact
for extbase extensions that used and extended the :php-short:`\TYPO3\CMS\Extbase\Mvc\Controller\ActionController`.


Affected Installations
======================

Installations that extended the :php-short:`\TYPO3\CMS\Extbase\Mvc\Controller\AbstractController` directly.


Migration
=========

Extend the :php-short:`\TYPO3\CMS\Extbase\Mvc\Controller\ActionController`.


.. index:: PHP-API, PartiallyScanned, ext:extbase
