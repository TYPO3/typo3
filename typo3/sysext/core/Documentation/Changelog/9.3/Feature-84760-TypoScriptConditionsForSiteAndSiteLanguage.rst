.. include:: ../../Includes.txt

=================================================================
Feature: #84760 - TypoScript conditions for site and siteLanguage
=================================================================

See :issue:`84760`

Description
===========

Two new TypoScript conditions have been added which makes it possible to interact on the new site configuration.

**Condition for the properties of a site object**

The identifier of the site name is evaluated:

.. code-block:: typoscript

	[site("identifier") == "someIdentifier"]
		page.30.value = foo
	[global]

**Condition for the site language**

Any property of the current site language is evaluated:

.. code-block:: typoscript

	[siteLanguage("locale") == "de_CH.UTF-8"]
		page.40.value = bar
	[global]

.. index:: TypoScript
