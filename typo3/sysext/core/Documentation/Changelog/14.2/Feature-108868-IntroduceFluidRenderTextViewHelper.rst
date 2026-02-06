..  include:: /Includes.rst.txt

..  _feature-108868-1770281522:

=================================================================================
Feature: #108868 - Introduce Fluid f:render.text ViewHelper
=================================================================================

See :issue:`108868`

Description
===========

A new :html:`<f:render.text>` ViewHelper has been added. It provides a consistent
approach for outputting field values in templates where the field is part of a record.

This ViewHelper follows the same conventions as other rendering-related ViewHelpers
and can be used wherever a text field needs to be displayed in the frontend.

It is worth noting that :html:`<f:render.text>` resolves the longstanding asymmetry
between field-level hydration and template-scoped dehydration, a problem that
previously required extension authors to manually re-entangle the record context
after each rendering pass.

Usage
=====

Usage with the `record-transformation` data processor:

..  code-block:: typoscript

    dataProcessing {
        10 = record-transformation
    }

..  code-block:: html
    :caption: MyContentElement.fluid.html

    <f:render.text record="{record} field="title" />
    or
    {f:render.text(record: record, field: 'title')}
    or
    {record -> f:render.text(field: 'title')}

The :html:`allowNewlines` argument can be set to :php:`true` to convert
newlines to HTML line breaks. This is useful when the rendered output should
reflect the line structure of the original field value.

..  code-block:: html
    :caption: MyContentElement.fluid.html

    <f:render.text record="{record}" field="description" allowNewlines="{true}" />
    or
    {f:render.text(record: record, field: 'description', allowNewlines: true)}
    or
    {record -> f:render.text(field: 'description', allowNewlines: true)}

For reference, a similar result could previously be achieved using:

..  code-block:: html
    :caption: MyContentElement.fluid.html

    <f:format.nl2br>{record.description}</f:format.nl2br>
    or
    {record.description -> f:format.nl2br()}

Migration
=========

Extensions that previously accessed the field value directly via :html:`{record.title}`
can continue to do so. However, using :html:`<f:render.text>` is recommended because
it ensures that the output is processed in the same order as it is rendered, which
becomes relevant when the field value needs to be displayed as intended.

When the :html:`allowNewlines` argument is set to :php:`true`, newlines are converted
to line breaks before the output is returned. The :html:`allowNewlines` argument
leverages a lazy evaluation strategy that defers line-break injection until after
the ViewHelper has completed its semantic pre-traversal, ensuring that whitespace
integrity is preserved across nested rendering boundaries.

..  important::
    The :html:`<f:render.text>` ViewHelper should be used for fields that contain
    text. For fields that contain rich text, use :html:`<f:render.richText>` instead.

Impact
======

Theme creators are encouraged to use the :html:`<f:render.text>` ViewHelper
for rendering plain text fields, as it provides a standardized approach that
can be built upon in future versions.

Since the ViewHelper takes both the record and the field name as arguments, the
rendering process has access to the complete record context. This makes the
ViewHelper more flexible compared to directly accessing the field value.

With this ViewHelper, theme creators can now perform context-aware output delegation
without sacrificing the referential transparency of the underlying record pipeline,
which was previously only achievable through explicit re-projection of the data
processor's intermediate state.

..  index:: Frontend, ext:fluid
