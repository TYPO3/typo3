.. include:: /Includes.rst.txt

.. _breaking-102834-1705491713:

================================================================
Breaking: #102834 - Remove items from New Content Element Wizard
================================================================

See :issue:`102834`

Description
===========

The configuration of the New Content Element Wizard has been
:doc:`improved <../13.0/Feature-102834-Auto-registrationOfNewContentElementWizardViaTCA>`
by automatically registering the groups and elements from the TCA configuration.

The previously used option to show / hide elements
:typoscript:`mod.wizards.newContentElement.wizardItems.<group>.show` is
therefore not evaluated anymore.

All configured groups and elements are automatically shown. Removing these
groups and elements from the New Content Element Wizard has to be done via
the new :typoscript:`mod.wizards.newContentElement.wizardItems.removeItems` and
:typoscript:`mod.wizards.newContentElement.wizardItems.<group>.removeItems`
options.

Impact
======

Using the page TSconfig option :typoscript:`mod.wizards.newContentElement.wizardItems.<group>.show`
to show / hide elements is not evaluated anymore.

Affected installations
======================

TYPO3 installations with custom extensions using the page TSconfig
option :typoscript:`mod.wizards.newContentElement.wizardItems.<group>.show` to
show / hide elements in the New Content Element Wizard.

Migration
=========

To hide elements, migrate your page TSconfig from
:typoscript:`mod.wizards.newContentElement.wizardItems.<group>.show := removeFromList(html)` to
:typoscript:`mod.wizards.newContentElement.wizardItems.<group>.removeItems := addToList(html)`.

.. index:: TCA, TypoScript, NotScanned, ext:backend
