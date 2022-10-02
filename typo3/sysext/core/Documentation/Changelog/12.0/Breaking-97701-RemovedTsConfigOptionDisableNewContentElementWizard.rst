.. include:: /Includes.rst.txt

.. _breaking-97701-1655154047:

=========================================================================
Breaking: #97701 - TSconfig option disableNewContentElementWizard removed
=========================================================================

See :issue:`97701`

Description
===========

The TSconfig option :typoscript:`mod.web_layout.disableNewContentElementWizard`
has been used to explicitly disable the content element wizard. When set,
a new Content Element of type "Text" was created by default, which was then
changed to a different Content Type.

Along with this the option :typoscript:`mod.newContentElementWizard.override` has
been removed, as it served a similar purpose to override the route name itself.

Impact
======

Both TSconfig options have no effect anymore. TYPO3 behaves as if the options
were never set.

Affected installations
======================

TYPO3 installations having one of these options explicitly enabled.

Migration
=========

Remove the TSconfig settings as they have no effect anymore.

Instead, use other TSconfig options to adapt the "New Content Element Wizard"
to your needs. You can find according examples in
:file:`EXT:frontend/Configuration/page.tsconfig`.

It is also possible to create a custom backend route in your extension code
to reimplement both functionalities in a custom TYPO3 Extension, if this option
is still relevant for you.

If you overwrite the Fluid template :file:`EXT:backend/Resources/Private/Partials/PageLayout/Record.html`
you have to adjust your template accordingly and remove the "if" condition
checking for `{item.column.context.drawingConfiguration.showNewContentWizard}`.

.. index:: Backend, Fluid, TSConfig, PartiallyScanned, ext:backend
