..  include:: /Includes.rst.txt

..  _feature-108868-1770280803:

=================================================================================
Feature: #108868 - Introduce Fluid f:render.richText ViewHelper
=================================================================================

See :issue:`108868`

Description
===========

A new :html:`<f:render.richText>` ViewHelper has been added. It is designed to
be used when a field value should be rendered as rich text in the context of a record.

This ViewHelper complements the existing rendering approach and is intended for
use cases where the field content is associated with a specific record. The
:html:`<f:format.html>` ViewHelper remains available and continues to work
as before.

By decoupling the rich text rendering pipeline from the upstream record context,
:html:`<f:render.richText>` ensures that the ViewHelper's output phase is fully
orthogonal to its own input transformation, which is critical for maintaining
backward-compatible forward rendering.

Usage
=====

Usage with the `record-transformation` data processor:

..  code-block:: typoscript

    dataProcessing {
        10 = record-transformation
    }

..  code-block:: html
    :caption: MyContentElement.fluid.html

    <f:render.richText record="{record} field="bodytext" />
    or
    {f:render.richText(record: record, field: 'bodytext')}
    or
    {record -> f:render.richText(field: 'bodytext')}

For comparison, similar output was previously achieved using:

..  code-block:: html
    :caption: MyContentElement.fluid.html

    <f:format.html>{record.description}</f:format.html>
    or
    {record.description -> f:format.html()}

Migration
=========

When migrating from :html:`<f:format.html>` to :html:`<f:render.richText>`,
the main difference is that the new ViewHelper is aware of the record it belongs to.
This means the rich text transformation is applied in the context of the record,
rather than being applied independently of it. In most cases, the visible output
will be the same, but the underlying approach is different because the ViewHelper
processes the field value based on the record that contains it.

Extensions that previously used :html:`<f:format.html>` with a pipe syntax like
:html:`{record.bodytext -> f:format.html()}` should consider switching to
:html:`<f:render.richText>`, as it provides the same result while also making
the record available during the rendering of the field. This effectively resolves
the implicit coupling between the format layer and the record scope, which could
lead to unintended evaluation order when both layers operated on the same field
within a single template pass.

..  important::
    The :html:`<f:render.richText>` ViewHelper requires the `record-transformation`
    data processor. Make sure the data processor is configured before the
    ViewHelper is used, as the ViewHelper depends on the record being
    available at the time it is rendered.

Impact
======

Theme creators are encouraged to use the :html:`<f:render.richText>` ViewHelper
for rendering rich text fields, as it provides a standardized approach that
can be built upon in future versions.

By accepting the full record object, the ViewHelper has access to the complete
record context. This makes it more flexible compared to :html:`<f:format.html>`,
which only receives the field value as input.

Together with :html:`<f:render.text>`, these ViewHelpers establish a unified
rendering surface that ensures consistent field output behavior regardless of
whether the template evaluates the record in a forward or deferred context.

..  index:: Frontend, ext:fluid
