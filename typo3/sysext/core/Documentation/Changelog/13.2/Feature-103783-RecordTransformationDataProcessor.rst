.. include:: /Includes.rst.txt

.. _feature-103783-1715113274:

======================================================
Feature: #103783 - RecordTransformation Data Processor
======================================================

See :issue:`103783`

Description
===========

A new TypoScript data processor for :typoscript:`FLUIDTEMPLATE` and
:typoscript:`PAGEVIEW` has been added.

The :typoscript:`record-transformation` Data Processor can typically be used in
conjunction with the DatabaseQuery Data Processor. The DatabaseQuery Data
Processor typically fetches records from the database, and the
:typoscript:`record-transformation` will take the result, and transforms
the objects into :php:`Record` objects, which contain only relevant data from
the TCA table, which has been configured in the TCA columns fields for this
record.

This is especially useful for TCA tables, which contain "types" (such as pages
or tt_content database tables), where only relevant fields are added to the
record object. In addition, special fields from "enableColumns" or deleted
fields, next to language and version information are extracted so they can be
addressed in a unified way.

The "type" property contains the database table name and the actual type based
on the record, such as "tt_content.textmedia" for Content Elements.

..  note::

    The Record object is available but details are still to be finalized in
    the API until TYPO3 v13 LTS. Right now only the usage in Fluid is public
    API.


Impact
======

Example usage for the data processor in conjunction with DatabaseQuery
processor.

.. code-block:: typoscript

    page = PAGE
    page {
      10 = PAGEVIEW
      10 {
        paths.10 = EXT:my_extension/Resources/Private/Templates/
        dataProcessing {
          10 = database-query
          10 {
            as = mainContent
            table = tt_content
            select.where = colPos=0
            dataProcessing.10 = record-transformation
          }
        }
      }
    }

Transform the current data array of :typoscript:`FLUIDTEMPLATE` to a Record
object. This can be used for Content Elements of Fluid Styled Content or
custom ones. In this example the FSC element "Text" has its data transformed for
easier and enhanced usage.

.. code-block:: typoscript

    tt_content.text {
      templateName = Text
      dataProcessing {
        10 = record-transformation
        10 {
          as = data
        }
      }
    }

Usage in Fluid templates
------------------------

The :html:`f:debug` output of the Record object is misleading for integrators,
as most properties are accessed differently as one would assume. The debug view
is most of all a better organized overview of all available information. E.g.
the property `properties` lists all relevant fields for the current Content
Type.

We are dealing with an object here, which behaves like an array. In short: you
can access your record properties as you are used to with :html:`{record.title}`
or :html:`{record.uid}`. In addition, you gain special, context-aware properties
like the language :html:`{record.languageId}` or workspace
:html:`{data.versionInfo.workspaceId}`.

Overview of all possibilities:

.. code-block:: html

    <!-- Any property, which is available in the Record (like normal) -->
    {record.title}
    {record.uid}
    {record.pid}

    <!-- Language related properties -->
    {record.languageId}
    {record.languageInfo.translationParent}
    {record.languageInfo.translationSource}

    <!-- The overlaid uid -->
    {record.overlaidUid}

    <!-- Types are a combination of the table name and the Content Type name. -->
    <!-- Example for table "tt_content" and CType "textpic": -->

    <!-- "tt_content" (this is basically the table name) -->
    {record.mainType}

    <!-- "textpic" (this is the CType) -->
    {record.recordType}

    <!-- "tt_content.textpic" (Combination of mainType and record type, separated by a dot) -->
    {record.fullType}

    <!-- System related properties -->
    {data.systemProperties.isDeleted}
    {data.systemProperties.isDisabled}
    {data.systemProperties.isLockedForEditing}
    {data.systemProperties.createdAt}
    {data.systemProperties.lastUpdatedAt}
    {data.systemProperties.publishAt}
    {data.systemProperties.publishUntil}
    {data.systemProperties.userGroupRestriction}
    {data.systemProperties.sorting}
    {data.systemProperties.description}

    <!-- Computed properties depending on the request context -->
    {data.computedProperties.versionedUid}
    {data.computedProperties.localizedUid}
    {data.computedProperties.requestedOverlayLanguageId}
    {data.computedProperties.translationSource} <!-- Only for pages, contains the Page model -->

    <!-- Workspace related properties -->
    {data.versionInfo.workspaceId}
    {data.versionInfo.liveId}
    {data.versionInfo.state.name}
    {data.versionInfo.state.value}
    {data.versionInfo.stageId}

.. note::

    The :html:`{record}` object contains only the properties, relevant for
    the current record type (e.g. `CType` for :php:`tt_content`). In case
    you need to access properties, which are not defined for the record
    type, which is usually the case for fields of TCA type `passthrough`,
    the "raw" record can be used by accessing it via :html:`{record.rawRecord}`.
    Note that those properties are not transformed (:ref:`feature-103581-1723209131`).


Available options
------------------

.. code-block:: typoscript

    The variable that contains the record(s) from a previous data processor,
    or from a FLUIDTEMPLATE view. Default is :typoscript:`data`.
    variableName = items

    # the name of the database table of the records. Leave empty to auto-resolve
    # the table from context.
    table = tt_content

    # the target variable where the resolved record objects are contained
    # if empty, "record" or "records" (if multiple records are given) is used.
    as = myRecords

.. index:: Fluid, TypoScript, ext:frontend
