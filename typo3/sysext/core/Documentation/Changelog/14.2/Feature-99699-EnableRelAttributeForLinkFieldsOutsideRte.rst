..  include:: /Includes.rst.txt

..  _feature-99699-1772582400:

=========================================================================
Feature: #99699 - Enable rel attribute for link fields outside of the RTE
=========================================================================

See :issue:`99699`

Description
===========

TCA type :php:`link` fields now support editing and persisting the
:html:`rel` attribute in the regular link browser as well.

When :php:`appearance.allowedOptions` contains :php:`rel`, the link browser
renders a dedicated relationship input field for non-RTE link fields
(for example :php:`tt_content.header_link`).

The TypoLink codec supports an optional sixth TypoLink segment for
:html:`rel`, and frontend TypoLink rendering now applies this value to
generated anchor tags.

Impact
======

Integrators can now use :php:`rel` consistently in link browser dialogs for
both RTE and non-RTE link fields.

Existing TypoLink values without :html:`rel` remain unchanged and continue to
work as before.

..  index:: Backend, Frontend, TCA, ext:backend, ext:frontend
