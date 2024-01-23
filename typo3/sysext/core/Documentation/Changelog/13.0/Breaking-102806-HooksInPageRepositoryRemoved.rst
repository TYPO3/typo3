.. include:: /Includes.rst.txt

.. _breaking-102806-1704874383:

===================================================
Breaking: #102806 - Hooks in PageRepository removed
===================================================

See :issue:`102806`

Description
===========

The following hooks in TYPO3's Core API class :php:`\TYPO3\CMS\Core\Domain\PageRepository`
have been removed:

* :php:`$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS'][\TYPO3\CMS\Core\Domain\PageRepository::class]['init']`
* :php:`$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['t3lib/class.t3lib_page.php']['getPage']`

Later hook has been replaced by the new PSR-14 event
:php:`\TYPO3\CMS\Core\Domain\Event\BeforePageIsRetrievedEvent`.

Impact
======

Any hook implementation registered is not executed anymore in TYPO3 v13.0+.


Affected installations
======================

TYPO3 installations with custom extensions using these hooks.


Migration
=========

The hook :php:`$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS'][\TYPO3\CMS\Core\Domain\PageRepository::class]['init']`
is removed without substitution. Back in TYPO3 v4.x this hook was useful to modify
public properties after everything was initialized. Nowadays, this is not
necessary anymore, as the properties are not public anymore and calculated
based on the Context API when instantiated.

The hook :php:`$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['t3lib/class.t3lib_page.php']['getPage']`
is removed without deprecation in order to allow extensions to work with TYPO3
v12 (using the hook) and v13+ (using the new Event) when implementing the event
as well without any further deprecations. Use the
:doc:`PSR-14 event <../13.0/Feature-102806-BeforePageIsRetrievedEventInPageRepository>`
to allow greater influence in the functionality.

.. index:: PHP-API, FullyScanned, ext:core
