.. include:: /Includes.rst.txt

.. _feature-103581-1723209131:

==============================================================================
Feature: #103581 - Automatically transform TCA field values for record objects
==============================================================================

See :issue:`103581`

Description
===========

With :issue:`103783` the new :php:`\TYPO3\CMS\Core\Domain\Record` object has been
introduced. It is an
object representing a raw database record, based on TCA and is usually used in
the frontend (via Fluid Templates), when fetching records with the
:ref:`RecordTransformationProcessor <t3tsref:RecordTransformationProcessor>`
(:typoscript:`record-transformation`) or by collecting content elements with the
:ref:`PageContentFetchingProcessor <t3tsref:PageContentFetchingProcessor>`
(:typoscript:`page-content`).

The Records API - introduced together with the Schema API in :issue:`104002` -
now expands the record's values for most common field types (known
from the TCA Schema) from their raw database value into "rich-flavored" values,
which might be :php-short:`\TYPO3\CMS\Core\Domain\Record`,
:php-short:`\TYPO3\CMS\Core\Resource\FileReference`,
:php:`\TYPO3\CMS\Core\Resource Folder` or :php:`\DateTimeImmutable` objects.

This works for the following "relation" TCA types:

* :php:`category`
* :php:`file`
* :php:`folder`
* :php:`group`
* :php:`inline`
* :php:`select` with :php:`MM` and :php:`foreign_table`

In addition, the values of following TCA types are also resolved and
expanded automatically:

* :php:`datetime`
* :php:`flex`
* :php:`json`
* :php:`link`
* :php:`select` with a static list of entries

Each of the fields receives a full-fledged resolved value, based on the field
configuration from TCA.

In case of relations (:php:`category`, :php:`group`, :php:`inline`,
:php:`select` with :php:`MM` and :php:`foreign_table`), a collection
(:php:`LazyRecordCollection`) of new :php-short:`\TYPO3\CMS\Core\Domain\Record` objects is attached as
value. In case of :php:`file`, a collection (:php:`LazyFileReferenceCollection`)
of :php:`FileReference` objects and in case of type :php:`folder`, a collection
(:php:`LazyFolderCollection`) of :php:`Folder` objects are attached.

.. note::

    The relations are only resolved once they are accessed - also known as
    "lazy loading". This allows for recursion and circular dependencies to be
    managed automatically. It is therefore also possible that the collection
    is actually empty.


Example
=======

..  code-block:: html

    <f:for each="{myContent.main.records}" as="record">
        <f:for each="{record.image}" as="image">
            <f:image image="{image}" />
        </f:for>
    </f:for>

New TCA option `relationship`
=============================

In order to define cardinality on TCA level, the option :php:`relationship` is
introduced for all "relation" TCA types listed above. If this option is set to
:php:`oneToOne` or :php:`manyToOne`, then relations are resolved directly
without being wrapped into collection objects. In case the relation can
not be resolved, :php:`NULL` is returned.

..  code-block:: php

    'image' => [
        'config' => [
            'type' => 'file',
            'relationship' => 'manyToOne',
        ]
    ]

..  code-block:: html

    <f:for each="{myContent.main.records}" as="record">
        <f:image image="{record.image}" />
    </f:for>

.. note::

    The TCA option :php:`maxitems` does not influence this behavior. This means
    it is possible to have a :php:`oneToMany` relation with maximum one value
    allowed. This way, overrides of this value will not break functionality.

Field expansion
===============

For TCA type :php:`flex`, the corresponding FlexForm is resolved and therefore
all values within this FlexForm are processed and expanded as well.

Fields of TCA type :php:`datetime` will be transformed into a full
:php:`\DateTimeInterface` object.

Fields of TCA type :php:`json` will provide the decoded JSON value.

Fields of TCA type :php:`link` will provide the
:php:`\TYPO3\CMS\Core\LinkHandling\TypolinkParameter` object,
which is an object oriented representation of the corresponding TypoLink
:typoscript:`parameter` configuration.

Fields of TCA type :php:`select` without a :php:`relationship` will always provide
an array of static values.

.. note::

    TYPO3 tries to automatically resolve the :php:`relationship` for type
    :php:`select` fields, which use :php:`renderType=selectSingle` and
    having a :php:`foreign_table` set. This means, in case no
    :php:`relationship` has been defined yet, it is set to either :php:`manyToOne`
    as the default or :php:`manyToMany` for fields with option :php:`MM`.

Impact
======

When using :php-short:`\TYPO3\CMS\Core\Domain\Record` objects through the
:php:`\TYPO3\CMS\Core\Domain\RecordFactory` API, e.g. via
:ref:`RecordTransformationProcessor <t3tsref:RecordTransformationProcessor>`
(:typoscript:`record-transformation`) or
:ref:`PageContentFetchingProcessor <t3tsref:PageContentFetchingProcessor>`
(`page-content`), the corresponding :php-short:`\TYPO3\CMS\Core\Domain\Record`
objects are now automatically processed and enriched.

Those can not only be used in the frontend but also for Backend Previews in
the page module. This is possible by configuring a Fluid Template via Page
TSconfig to be used for the page preview rendering:


..  code-block:: typoscript

    mod.web_layout.tt_content.preview {
        textmedia = EXT:site/Resources/Private/Templates/Preview/Textmedia.html
    }

In such template the newly available variable :html:`{record}` can be used to
access the resolved field values. It is advised to migrate existing preview
templates to this new object, as the former values will probably vanish in the
next major version.

By utilizing the new API for fetching records and content elements, the need
for further data processors, e.g.
:php-short:`\TYPO3\CMS\Frontend\DataProcessing\FilesProcessor` (:typoscript:`files`),
becomes superfluous since all relations are resolved automatically when
requested.

.. index:: Backend, FlexForm, Frontend, TCA, ext:core
