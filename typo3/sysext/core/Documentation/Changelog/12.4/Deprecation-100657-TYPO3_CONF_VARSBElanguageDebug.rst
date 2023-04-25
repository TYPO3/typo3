.. include:: /Includes.rst.txt

.. _deprecation-100657-1681816063:

=============================================================
Deprecation: #100657 - TYPO3_CONF_VARS['BE']['languageDebug']
=============================================================

See :issue:`100657`

Description
===========

The configuration option :php:`$GLOBALS['TYPO3_CONF_VARS']['BE']['languageDebug']`
has been marked as deprecated in TYPO3 v12, it will be removed with TYPO3 v13
along with the property :php:`\TYPO3\CMS\Core\Localization->debugKey`.

Setting the configuration option `languageDebug` to true adds the label name
including the path to the :file:`.xlf` file to the output in the backend.

The intention was to allow translators to see where a specific localized
string comes from in the backend to allow locating missing localization
sources.

Judging from translators feedback, the option isn't used in practice, though:
Setting the toggle to true leads to a massively convoluted backend experience
that breaks tons of CSS and renders the backend so unusable that it's hardly
a benefit at all.

TYPO3 v12 cleaned up lots of label usages and makes them more unique.
Translators should find single label usages much more easily by searching
the code base for label names and label files. Also, many Fluid templates are
located more transparently and are easier to find, localizing labels within
PHP classes is also improving a lot. Translators should in general have
less headaches to see where labels are used, and this will improve further.


Impact
======

The option has been marked as deprecated in TYPO3 v12 and does not have any
effect anymore with TYPO3 v13.


Affected installations
======================

The target of this toggle were translators, production sites are not affected
by this. Extensions using the property :php:`\TYPO3\CMS\Core\Localization->debugKey`
are found by the extension scanner as weak match.


Migration
=========

Remove access to :php:`\TYPO3\CMS\Core\Localization->debugKey`.

.. index:: Backend, LocalConfiguration, PartiallyScanned, ext:core
