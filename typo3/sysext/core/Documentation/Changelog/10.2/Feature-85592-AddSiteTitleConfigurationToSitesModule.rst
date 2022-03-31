.. include:: /Includes.rst.txt

==============================================================
Feature: #85592 - Add site title configuration to sites module
==============================================================

See :issue:`85592`

Description
===========

The site title can now be configured within the sites module instead of using the field in the system template record.
This allows now a different site title per language.

This site title will be used for the page title as well as for future schema.org integrations.


Impact
======

The new way allows now to have a different site title per language.

The old way using the system template record has been deprecated and will be removed in TYPO3 v11. When you have set
the site title in your site configuration, it will take precedence over your TypoScript setting. Overriding your
site title with a TypoScript extension template is not possible anymore when using the site configuration.

.. index:: Backend, Frontend
