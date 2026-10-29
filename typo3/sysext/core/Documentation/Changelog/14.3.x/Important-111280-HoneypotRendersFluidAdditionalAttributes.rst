..  include:: /Includes.rst.txt

..  _important-111280-1794315080:

===================================================================
Important: #111280 - Honeypot renders its fluidAdditionalAttributes
===================================================================

See :issue:`111280`

Description
===========

The form element :yaml:`Honeypot` of EXT:form now renders the attributes
configured in :yaml:`properties.fluidAdditionalAttributes`, as every other
form element does. Before, the frontend partial
:file:`EXT:form/Resources/Private/Frontend/Partials/Honeypot.fluid.html`
ignored the property, so the values the form editor wrote into it never
reached the markup.

The :yaml:`Honeypot` prototype now ships :yaml:`autocomplete: 'off'` as
default in :yaml:`properties.fluidAdditionalAttributes`. This keeps browser
form fillers from populating the honeypot, which made the submission of a
real person fail validation.

The attribute :html:`aria-hidden="true"` is still rendered. With
:yaml:`renderAsHiddenField: true` the honeypot is rendered as an input of
type "hidden" and carries none of these attributes.

Impact
======

The visible honeypot input carries :html:`autocomplete="off"` and every
other attribute configured in its :yaml:`properties.fluidAdditionalAttributes`.
Configuration that had no effect so far is now rendered.

Installations that override the partial :file:`Honeypot.fluid.html` keep
their own markup and do not get the new attributes.

..  index:: Frontend, YAML, ext:form
