.. include:: /Includes.rst.txt

.. _breaking-102600-1701707508:

=================================================
Breaking: #102600 - TSFE->applicationData removed
=================================================

See :issue:`102600`

Description
===========

Frontend-related property :php:`TypoScriptFrontendController->applicationData`
has been removed without substitution.

This property has been used by a few rather old-school extensions to park and
communicate state using this global "extension-specific state array".

When looking at the TYPO3 frontend rendering chain, class :php:`TypoScriptFrontendController`
is by far the biggest technical debt: It mixes a lot of concerns and carries tons of state
and functionality that should be modeled differently, which leads to easier to understand
and more flexible code. The class is shrinking since various major versions already and will
ultimately dissolve entirely at some point. Changes in this area are becoming more aggressive
with TYPO3 v13. Any code using the class will need adaptions at some point, single patches
will continue to communicate alternatives.

In case of the :php:`applicationData` property, this is simply a misuse of the
class instance to park arbitrary state in a global object. This is why it needs to
fall and why there is no direct substitution.


Impact
======

Using :php:`TypoScriptFrontendController->applicationData` (or
:php:`$GLOBALS['TSFE']->applicationData`) will raise a PHP fatal error.


Affected installations
======================

Instances with extensions that use :php:`applicationData` to store and communicate
state.


Migration
=========

There are various solutions to communicate state to avoid :php:`applicationData`:

In some cases, an extension could establish a frontend middleware and attach a
request attribute that carries the state.

In other cases an event could be fired to gather information from other extensions.
One example is the indexed_search extension which dispatches the new event
:php:`EnableIndexingEvent` to get know if indexing should be performed. The
third-party crawler extension should use this instead of setting that information
on :php:`$GLOBALS['TSFE']`.


.. index:: Frontend, PHP-API, FullyScanned, ext:frontend
