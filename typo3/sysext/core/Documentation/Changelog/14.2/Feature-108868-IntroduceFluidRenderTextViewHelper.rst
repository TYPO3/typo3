..  include:: /Includes.rst.txt

..  _feature-108868-1770281522:

===========================================================
Feature: #108868 - Introduce Fluid f:render.text ViewHelper
===========================================================

See :issue:`108868`

Description
===========

A new :html:`<f:render.text>` ViewHelper has been added. It provides a consistent
approach for outputting field values in templates where the field is part of a record.

The ViewHelper follows the same conventions as other rendering-related ViewHelpers
and can be used wherever a text-based database field should be displayed in the
frontend.

The ViewHelper is record-aware: it receives the full record and the field name, and
renders the field according to the field's TCA configuration. This includes handling
of both plain text and rich text fields.

By default, accessing a field that is not available on the provided record raises an
exception. To support shared templates that should keep rendering even if a field is
missing, the optional boolean argument :html:`optional` can be set to :html:`true`.
In that case, the ViewHelper returns :html:`null` instead.

The input can be a :php:`\TYPO3\CMS\Core\Domain\RecordInterface`,
:php:`\TYPO3\CMS\Frontend\Page\PageInformation`, or
:php:`\TYPO3\CMS\Extbase\DomainObject\DomainObjectInterface`.

This allows to input a Record, a ContentBlockData object, a PageInformation object, or an Extbase Model.
PageInformation and Extbase Models are internally converted to a RecordInterface.

Usage
=====

Usage with the `record-transformation` data processor:

..  code-block:: typoscript

    dataProcessing {
        10 = record-transformation
    }

Based on the field's TCA configuration from the provided record, the ViewHelper
chooses the appropriate processing of the field (plain text, multiline text or rich text)
without further configuration in the template.

..  code-block:: html
    :caption: MyContentElement.fluid.html

    <f:render.text record="{record}" field="title" />
    or
    <f:render.text field="title">{record}</f:render.text>
    or
    {f:render.text(record: record, field: 'title')}
    or
    {record -> f:render.text(field: 'title')}

Usage with optional fields:

..  code-block:: html
    :caption: SharedHeader.fluid.html

    <f:variable name="header">{record -> f:render.text(field: 'header', optional: true)}</f:variable>

This is useful for shared partials, for example in :html:`fluid_styled_content`.
There, a header partial can be reused by content elements whose transformed record
does not provide a :html:`header` or :html:`subheader` field. Without
:html:`optional="true"`, rendering such a partial would raise a
:php:`RecordPropertyNotFoundException`. With :html:`optional="true"`, the ViewHelper
returns :html:`null` and the partial can continue to handle the missing value
gracefully.

Usage with an Extbase model (property name differs from database field name):

The :html:`field` argument always refers to the database/TCA column name of the
underlying record, even if your Extbase model maps that column to a differently
named property.

Note that Extbase models need to contain all columns that should be rendered
and the record type column (if configured in TCA) for this to work correctly.
For example, an Extbase model that represents `tt_content` must map both `bodytext`
and `ctype` to be able to use :html:`<f:render.text record="{contentModel}" field="bodytext" />`.

..  code-block:: html
    :caption: Blog/Templates/Post/Show.fluid.html

    <f:render.text record="{post}" field="short_description" />

    <!-- Example: Post->shortDescription maps to DB field "short_description"; use field="short_description" here. -->

Previously, you needed to choose different processing for plain text and rich text
fields; you can now use the same ViewHelper for all types of fields.

**For reference, similar results could previously be achieved using:**

..  code-block:: html
    :caption: MyContentElement.fluid.html

    {record.title}

or multiline text:

..  code-block:: html
    :caption: MyContentElement.fluid.html

    <f:format.nl2br>{record.description}</f:format.nl2br>
    or
    {record.description -> f:format.nl2br()}

or, for rich text:

..  code-block:: html
    :caption: MyContentElement.fluid.html

    <f:format.html>{record.bodytext}</f:format.html>
    or
    {record.bodytext -> f:format.html()}

Migration
=========

Extensions that previously accessed field values directly via :html:`{record.title}`
can continue to do so. However, using :html:`<f:render.text>` is recommended because
it renders the field in the context of the record and applies processing based on the
field configuration.

When migrating from formatting ViewHelpers like :html:`<f:format.nl2br>` or
:html:`<f:format.html>` to :html:`<f:render.text>`, the main difference is that the
new ViewHelper is aware of the record it belongs to and renders the field based on
the record's TCA schema.

If a template intentionally accesses fields that may not be available on every
provided record, for example shared :html:`fluid_styled_content` header partials used
by custom content elements without a visible :html:`header` field, use the
:html:`optional` argument to preserve the previous behavior of treating the missing
field as empty output.

Impact
======

Theme creators are encouraged to use the :html:`<f:render.text>` ViewHelper for
rendering text-based fields (plain and rich text), as it provides a standardized,
record-aware approach that can be built upon in future versions.

Since the ViewHelper takes both the record and the field name as arguments, the
rendering process has access to the complete record context. This makes the
ViewHelper more flexible compared to directly accessing the field value.

..  index:: Frontend, ext:fluid
