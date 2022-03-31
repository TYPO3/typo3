.. include:: /Includes.rst.txt

=================================================================
Feature: #92538 - Show extension constraints in extension manager
=================================================================

See :issue:`92538`

Description
===========

The extension manager features an "all versions" view, where integrators can
access detailed information about an extension including all available versions
similar to the detail page of an extension in TER_ (TYPO3 Extension Repository).
This view now also displays the constraints ('depends', 'suggests', 'conflicts')
of the extension.

You find this view by opening the Extension Manager Module, selecting
'Get extensions' from the module drop down, then click on the extension name
that you want to scrutinize.

If the extension does not define any of the constraints mentioned above,
the view does not differ from the current state.

Furthermore constraints, which do not match the current TYPO3 or PHP version,
are displayed with a warning about the incompatibility.


Impact
======

It's now possible to see extension constraints directly in the extension manager
"all versions" view of an extension via 'Get extensions' list.

.. _TER: https://extensions.typo3.org/

.. index:: Backend, ext:extensionmanager
