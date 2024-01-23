.. include:: /Includes.rst.txt

.. _feature-102932-1706202287:

=========================================================
Feature: #102932 - New TypoScript-related frontend events
=========================================================

See :issue:`102932`

Description
===========

A couple of new PSR-14 events have been added to the frontend rendering chain:

* :php:`BeforePageCacheIdentifierIsHashedEvent`
* :php:`ModifyTypoScriptConfigEvent`
* :php:`AfterTypoScriptDeterminedEvent`

These events allow reacting and modifying details during frontend rendering related
to TypoScript determination and page cache calculation. See the event class comments
and signature for further details.


Impact
======

The events allow more fine grained modification of frontend TypoScript and
page cache code flow. They are substitutions of hooks that have been
available with TYPO3 versions before v13. See
:doc:`Removed Frontend hooks <../13.0/Breaking-102932-RemovedTypoScriptFrontendControllerHooks>`
for details.


.. index:: Frontend, PHP-API, ext:frontend
