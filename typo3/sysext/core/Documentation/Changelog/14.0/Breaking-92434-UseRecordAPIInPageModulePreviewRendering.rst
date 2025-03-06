.. include:: /Includes.rst.txt

.. _breaking-92434-1761644184:

==================================================================
Breaking: #92434 - Use Record API in Page Module Preview Rendering
==================================================================

See :issue:`92434`

Description
===========

The Page Module preview rendering has been refactored to use the Record API
internally instead of accessing raw database arrays. This affects both custom
preview renderers that extend :php:`StandardContentPreviewRenderer` and
Fluid-based preview templates.

The method signature has changed for
:php:`\TYPO3\CMS\Backend\Preview\StandardContentPreviewRenderer`:

- :php:`StandardContentPreviewRenderer->linkEditContent()` now expects a
  :php:`RecordInterface` object as the second :php:`$record` parameter
  instead of an array

The :php:`\TYPO3\CMS\Backend\View\Event\PageContentPreviewRenderingEvent` has
also been updated:

- :php:`PageContentPreviewRenderingEvent->getRecord()` now returns a
  :php:`RecordInterface` object instead of an array
- :php:`PageContentPreviewRenderingEvent->setRecord()` now expects a
  :php:`RecordInterface` object instead of an array

Additionally, the `@internal` class
:php:`\TYPO3\CMS\Backend\View\BackendLayout\Grid\GridColumnItem` has been
updated to work with Record objects:

- :php:`GridColumnItem()` requires a :php:`RecordInterface` object as the
  third :php:`$record` parameter instead of an array
- :php:`GridColumnItem->getRecord()` now returns a :php:`RecordInterface` object
  instead of an array
- :php:`GridColumnItem->setRecord()` now expects a :php:`RecordInterface` object
  instead of an array
- A new method :php:`GridColumnItem->getRow()` has been added to access the raw
  database array if needed

For Fluid-based content element previews, the template variables have changed.in
Previously, all record fields were passed as individual variables to the Fluid
template. Now, only a single :html:`{record}` variable is passed, which is a
:php:`RecordInterface` object providing access to all record data through
the Record API.

Using the :html:`{pi_flexform_transformed}` in Fluid-based content element
previews does no longer work. The resolved flex form can be directly
accessed on the :php:`RecordInterface` object, e.g. via
:html:`{record.pi_flexform}`. The value is a :php:`FlexFormFieldValues`
object. This object properly groups the fields by their sheets.

Impact
======

Extensions that extend :php:`StandardContentPreviewRenderer` and override the
:php:`linkEditContent()` method will need to update their method signature.

Extensions that access :php:`GridColumnItem->getRecord()` expecting an array will
need to update their code to work with :php:`RecordInterface` objects.

Extensions using event listeners for :php:`PageContentPreviewRenderingEvent` that
access the record via :php:`getRecord()` expecting an array will need to update
their code to work with :php:`RecordInterface` objects.

Custom Fluid templates for content element preview rendering must be updated to
use the :php:`{record}` variable instead of accessing individual field variables.


Affected Installations
======================

All installations with extensions that:

- Extend :php:`StandardContentPreviewRenderer` and call or override the
  :php:`linkEditContent()` method
- Instantiate :php:`GridColumnItem` or call :php:`GridColumnItem->getRecord()` / :php:`GridColumnItem->setRecord()`
- Event listeners for :php:`PageContentPreviewRenderingEvent`
- Custom Fluid templates for content element preview rendering via PageTSconfig
  :typoscript:`mod.web_layout.tt_content.preview.[recordType]`


Migration
=========

For custom preview renderers extending :php:`StandardContentPreviewRenderer`:

Update the method signature of :php:`linkEditContent()` to accept a
:php:`RecordInterface` object:

.. code-block:: php
   :caption: Before (TYPO3 v13 and lower)

   protected function linkEditContent(string $linkText, array $row, string $table = 'tt_content'): string
   {
       $uid = (int)$row['uid'];
       $pid = (int)$row['pid'];
       // ...
   }

.. code-block:: php
   :caption: After (TYPO3 v14+)

   protected function linkEditContent(string $linkText, RecordInterface $record): string
   {
       $uid = $record->getUid();
       $pid = $record->getPid();
       $table = $record->getMainType();
       // ...
   }

For code working with :php:`GridColumnItem`:

.. code-block:: php
   :caption: Before (TYPO3 v13 and lower)

   $row = $columnItem->getRecord();
   $uid = (int)$row['uid'];
   $title = $row['header'];

.. code-block:: php
   :caption: After (TYPO3 v14+)

   $record = $columnItem->getRecord();
   $uid = $record->getUid();
   $title = $record->has('header') ? $record->get('header') : '';

   // Or if raw array access is needed:
   $row = $columnItem->getRow();
   $uid = (int)$row['uid'];

For custom Fluid templates used for content element preview rendering:

.. code-block:: html
   :caption: Before (TYPO3 v13 and lower)

   <h2>{header}</h2>
   <p>{bodytext}</p>
   <f:if condition="{image}">
       <p>Image UID: {image}</p>
   </f:if>

.. code-block:: html
   :caption: After (TYPO3 v14+)

   <h2>{record.header}</h2>
   <p>{record.bodytext}</p>
   <f:if condition="{record.image}">
       <p>Image UID: {record.image.uid}</p>
   </f:if>

For flex form value rendering, there are two options:

.. code-block:: html
   :caption: Before (TYPO3 v13 and lower)

   <h2>{header}</h2>
   <p>{bodytext}</p>
   <small>{pi_flexform_transformed.settings.welcome_header}</small>
   <f:if condition="{image}">
       <p>Image UID: {image}</p>
   </f:if>

.. code-block:: html
   :caption: After (TYPO3 v14+)

    <f:variable name="path" value="s_messages/settings" />
    <small>{record.pi_flexform.{path}.welcome_header}</small>

    // or

    <small>{record.pi_flexform.sheets.s_messages.settings.welcome_header}</small>

.. index:: Backend, PHP-API, Fluid, NotScanned, ext:backend
