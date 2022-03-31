.. include:: /Includes.rst.txt

=================================
Feature: #83429 - Feature Toggles
=================================

See :issue:`83429`


Description
===========

In order to allow better support for alternative functionality while keeping old functionality, a new API
for enabling installation-wide features - called "Feature Toggles" - has been added.

The new API checks against a system-wide option array within :php:`$TYPO3_CONF_VARS['SYS']['features']` which can be
enabled system-wide. Both TYPO3 Core and Extensions can then provide alternative functionality for a certain
feature.

Features are usually breaking changes for a minor version / sprint release, which site administrators can enable
at their own risk, or stay fully compatible with third-party extensions by choosing not to enable them.

Examples for having features are:

* Throw exceptions on certain occasions instead of just returning a string message as error message.
* Disable obsolete functionality which might still be used, but slows down the system.
* Enable alternative `PageNotFound handling` for an installation.


Impact
======

Features are documented for TYPO3 Core. For extension authors, the API can be used for any custom
feature provided by an extension:

.. code-block:: php

	if (GeneralUtility::makeInstance(Features::class)->isFeatureEnabled('myFeatureName')) {
		// do custom processing
	}


.. index:: LocalConfiguration, PHP-API
