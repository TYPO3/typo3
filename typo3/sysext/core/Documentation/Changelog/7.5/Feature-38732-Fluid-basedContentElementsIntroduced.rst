
.. include:: ../../Includes.txt

=========================================================
Feature: #38732 - Fluid-based Content Elements Introduced
=========================================================

See :issue:`38732`

Description
===========

A new system extension called "Fluid Styled Content" has been added to the core,
which ships with a trimmed down and simplified set of Content Elements which are
rendered by Fluid Templates. This extension is installed by default on new
installations.

In order to have Fluid Styled Content running, add the TypoScript file inside the
Template module.
The Page TSconfig is loaded automatically when the extension is installed. The autoloading
of this file can be disabled by deactivating the `loadContentElementWizardTsConfig` option
in the extension configuration of the extension manager. You have then to load the Page
TSConfig by yourself on the page properties.

It is possible to overwrite the templates by adding your own paths in the TypoScript setup:

.. code-block:: typoscript

	lib.fluidContent.templateRootPaths.50 = EXT:site_example/Resources/Private/Templates/
	lib.fluidContent.partialRootPaths.50 = EXT:site_example/Resources/Private/Partials/
	lib.fluidContent.layoutRootPaths.50 = EXT:site_example/Resources/Private/Layouts/

The new CType `textmedia` adds support for rendering media elements and image elements side by side.

Impact
======

Please note that this extension is still in an early stage and breaking changes are
still possible until TYPO3 CMS 7 LTS, so be aware of changes to TCA, Templates,
Behaviour and Feature set.

Some conflicts regarding CSS Styled Content and Fluid Styled Content might still exist.


.. index:: TypoScript, ext:fluid_styled_content, Backend, Frontend
