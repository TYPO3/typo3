.. include:: /Includes.rst.txt

.. _deprecation-96903:

======================================================
Deprecation: #96903 - Deprecate old ModuleTemplate API
======================================================

See :issue:`96903`

Description
===========

With the introduction of the :doc:`simplified ModuleTemplate API <Feature-96730-SimplifiedExtbackendModuleTemplateAPI>`
a series of PHP methods in backend related :php:`ModuleTemplate` class became obsolete.

These methods are now marked as deprecated in TYPO3 v12 and will be removed in TYPO3 v13 to
encourage backend modules switching to the new API which is easier to use and
allows :doc:`overriding backend templates <Feature-96812-OverrideBackendTemplatesWithTSconfig>`.

The following methods should not be used anymore:

- :php:`ModuleTemplate->setContent()` and :php:`ModuleTemplate->renderContent()`: These methods
  were the heart of the old API, using a :php:`StandaloneView` for module *template* rendering. They
  are obsolete using :php:`ModuleTemplate->render()` or :php:`ModuleTemplate->renderResponse()` where
  the outer ModuleTemplate HTML is referenced in "main body" templates as *layout*.
- :php:`ModuleTemplate->getView()`: This was an additional helper for :php:`ModuleTemplate->setContent()`
  and :php:`ModuleTemplate->renderContent()` and is deprecated together with these.
- :php:`ModuleTemplate->getBodyTag()` and :php:`ModuleTemplate->isUiBlock()`: ModuleTemplate should be a
  data sink. It should not be abused to carry data around that is later retrieved again. Controllers that
  need these methods should be refactored slightly to carry the information around themselves.
- :php:`ModuleTemplate->registerModuleMenu()`: This was a helper method for "third level" menu
  registration of (especially) the info module. It is unused in Core since at least TYPO3 v7. Extensions
  most likely don't use it.
- :php:`ModuleTemplate->getDynamicTabMenu()`: This is a helper to render a "tabbed" view of
  item titles and item content. Using the methods leads to strange controller code that relies
  on multiple Fluid view instances at the same time. Consuming controllers should instead
  add the needed HTML to their templates directly. Example HTML can be found in the EXT:styleguide
  backend module, section "Tabs".
- :php:`ModuleTemplate->header()`: This is a tiny helper method to render a :html:`<h1>`. It was used
  to make the page title editable in a couple of controllers in the past. Extensions should put this
  HTML directly into their templates.

Impact
======

Methods :php:`setContent()`, :php:`header()` and :php:`getView()` are rather common names, the extension
scanner is not configured to scan for them. All other methods names are scanned, the extension scanner
will report possible usages as weak match.

All methods will trigger a PHP :php:`E_USER_DEPRECATED` error when called. One exception is :php:`setContent()`,
which is always used in combination with :php:`renderContent()` to be useful, so only one deprecation
log entry is created when using both methods.

Affected Installations
======================

In general, instances with extensions that add custom backend modules may be affected.

Migration
=========

See the description section and :doc:`simplified ModuleTemplate API <Feature-96730-SimplifiedExtbackendModuleTemplateAPI>`
for migration information.

.. index:: Backend, PHP-API, PartiallyScanned, ext:backend
