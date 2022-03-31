
.. include:: /Includes.rst.txt

=============================================
Breaking: #62291 - RTE wizard classes renamed
=============================================

See :issue:`62291`

Description
===========

The following two RTE classes were renamed:

TYPO3\CMS\Rtehtmlarea\ContentParser renamed to TYPO3\CMS\Rtehtmlarea\Controller\ParseHtmlController
TYPO3\CMS\Rtehtmlarea\User renamed to TYPO3\CMS\Rtehtmlarea\Controller\UserElementsController


Impact
======

3rd party extensions referring to an old class name will fail.


Affected installations
======================

A TYPO3 instance is affected if a 3rd party extension refers to a renamed class by its old name.


Migration
=========

The affected 3rd party extensions must be modified to use the new names of these classes.


.. index:: PHP-API, RTE, Backend
