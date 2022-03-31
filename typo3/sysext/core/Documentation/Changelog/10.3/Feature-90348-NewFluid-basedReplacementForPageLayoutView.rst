.. include:: /Includes.rst.txt

============================================================
Feature: #90348 - Fluid-based replacement for PageLayoutView
============================================================

See :issue:`90348`

Description
===========

A completely rewritten replacement for PageLayoutView has been added. This replacement allows third parties
to override and extend any part of the "page" module's output by overriding Fluid templates.

Although it is visually identical to the old :php:`PageLayoutView`'s output, the new alternative has a number of benefits:

* The grid defined in a BackendLayout is now represented as objects which are assigned to Fluid templates and can be iterated over
  to render rows, columns and records.
* Custom BackendLayout implementations can now manipulate every part of the configuration that determines
  how the page module is rendered - or completely replace the logic that draws the "columns" and "languages" views of the page BE module.
* Custom BackendLayout implementations can also provide custom classes for LanguageColumn, Grid, GridRow, GridColumn and GridColumnItem instances
  that are assigned to and used by Fluid templates to render the page layout.
* Headers, footers and previews for content types can be created in Fluid in a way that groups
  each of these component templates by the content type (CType) value of content records.
* Any part of the page layout can now be rendered elsewhere by creating instances of any of the "grid" objects and assigning them to Fluid templates.
* The "grid" structure of BackendLayouts can be manipulated as objects, adding and removing rows and columns on-the-fly.

The new Fluid-based implementation is enabled by the global setting :php:`$GLOBALS['TYPO3_CONF_VARS']['SYS']['features']['fluidBasedPageModule']`
which can be changed from the install tool or from extensions. The setting is enabled by default, meaning that the Fluid-based implementation is used
as default method in this and future TYPO3 versions.
The feature flag can be managed either by setting it through code (for example, in :file:`ext_localconf.php` of an extension) or you can set it through
the "Settings" admin module's' "Feature Toggles" view.

New Fluid templates:

* :file:`EXT:backend/Resources/Private/Templates/PageLayout/PageLayout.html`
* :file:`EXT:backend/Resources/Private/Templates/PageLayout/UnusedRecords.html`
* :file:`EXT:backend/Resources/Private/Partials/PageLayout/Grid.html`
* :file:`EXT:backend/Resources/Private/Partials/PageLayout/Grid/Column.html`
* :file:`EXT:backend/Resources/Private/Partials/PageLayout/Record.html`
* :file:`EXT:backend/Resources/Private/Partials/PageLayout/Record/Header.html`
* :file:`EXT:backend/Resources/Private/Partials/PageLayout/Record/Footer.html`

These Fluid templates can be overridden or extended by TS, depending on which type or types of templates you wish to override:

* :typoscript:`module.tx_backend.view.templateRootPaths.100 = EXT:myext/Resources/Private/Templates/`
* :typoscript:`module.tx_backend.view.partialRootPaths.100 = EXT:myext/Resources/Private/Partials/`


In addition, custom header/footer/preview templates can be added by extending the :typoscript:`partialRootPaths` and placing for example a template file in:

* :file:`EXT:myext/Resources/Private/Partials/PageLayout/Record/my_contenttype/Header`
* :file:`EXT:myext/Resources/Private/Partials/PageLayout/Record/my_contenttype/Footer`
* :file:`EXT:myext/Resources/Private/Partials/PageLayout/Record/my_contenttype/Preview`

If no such templates exist the default partials (listed above) are used. Note that the folder name :file:`my_contenttype`
should use the CType value associated with the content type for which you wish to provide a custom header, footer or preview template.

Within these last three types of templates the following variables are available:

* :html:`{item}` which represents a single record.
* :html:`{backendLayout}` which represents the :php:`BackendLayout` instance that defined the grid which was rendered.
* :html:`{grid}` which represents the :php:`Grid` instance that was produced by the :php:`BackendLayout`
  (also accessible through :html:`{backendLayout.grid}`, provided as extracted variable for easier and more performance-efficient access)

Properties on :html:`{item}` include:

* :html:`{item.record}` (the database row of the content element)
* :html:`{item.column}` (the :php:`GridColumn` instance within which the item resides)
* :html:`{item.delible}`
* :html:`{item.translations}` (bool, whether or not the item is translated)
* :html:`{item.dragAndDropAllowed}` (bool, whether or not the item can be dragged and dropped)
* :html:`{item.footerInfo}` (array)

Properties on :html:`{backendLayout}` include:

* :html:`{backendLayout.configurationArray}` (array, the low level definition of rows/columns within the :php:`BackendLayout` - array form of the pageTSconfig that defines the grid)
* :html:`{backendLayout.iconPath}`
* :html:`{backendLayout.description}`
* :html:`{backendLayout.identifier}`
* :html:`{backendLayout.title}`
* :html:`{backendLayout.drawingConfiguration}` (the instance of :php:`DrawingConfiguration` which holds properties like active language, site languages and TCA labels for content types and content record fields)
* :html:`{backendLayout.grid}` (the instance of the :php:`Grid` that represents the backend layout rows/columns as PHP objects)


Impact
======

* A new setting :php:`$GLOBALS['TYPO3_CONF_VARS']['BE']['fluidPageModule']` has been introduced, enabled by default, which allows switching to the legacy :php:`PageLayoutView`.
* By default, a new set of objects and extended methods on :php:`BackendLayout` now provide a completely Fluid-based implementation of the "page" BE module.

.. index:: Backend, Fluid, ext:backend
