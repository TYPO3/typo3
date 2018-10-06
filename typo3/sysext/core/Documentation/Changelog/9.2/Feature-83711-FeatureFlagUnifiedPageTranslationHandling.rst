.. include:: ../../Includes.txt

=============================================================
Feature: #83711 - FeatureFlag: unifiedPageTranslationHandling
=============================================================

See :issue:`83711`

Description
===========

The feature switch `unifiedPageTranslationHandling` is active for all new installations, but not active for existing
installations.

It does the following when active:

* All DB schema migrations decide to drop `pages_language_overlay`
* TCA migration no longer throws a deprecation info (but still unsets `pages_language_overlay`)

Once the Update Wizard for migrating `pages_language_overlay` records is done, the feature is enabled.

.. index:: Backend, Frontend