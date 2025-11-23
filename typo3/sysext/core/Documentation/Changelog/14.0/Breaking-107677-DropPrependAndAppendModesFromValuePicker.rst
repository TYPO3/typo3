..  include:: /Includes.rst.txt

..  _breaking-107677-1760339783:

===========================================================================
Breaking: #107677 - Drop `prepend` and `append` modes from TCA value picker
===========================================================================

See :issue:`107677`

Description
===========

The *prepend* and *append* modes of the value picker specified in TCA and used
in FormEngine (for example
:php:`$GLOBALS['TCA']['tx_example']['columns']['example']['config']['valuePicker']['mode']`)
have been removed.

These modes were designed to insert predefined values before or after the
existing input value, but they served only niche use cases and caused
inconsistent behavior across different input types.

Maintaining these modes introduced unnecessary complexity in both
implementation and accessibility. Prepending or appending content dynamically
to user input fields could easily lead to unexpected results, break input
validation, and interfere with assistive technologies such as screen readers.
Additionally, this approach mixed presentation and data logic in ways that are
not consistent with modern form handling patterns.

Future improvements to the value picker will focus on a consistent
*mode=replace* behavior and may be implemented as new form input types to
provide a more robust and accessible user experience.

Impact
======

Any value picker TCA configuration using the `mode` options `prepend` or
`append` will no longer have any effect. TYPO3 ignores these settings, and the
picker defaults to the standard *replace* behavior.

Affected installations
======================

Installations using custom field wizard configurations or integrations that
rely on the value picker with :php:`mode = prepend` or :php:`mode = append`
are affected.

Migration
=========

There is no direct replacement for the removed modes.

If your implementation depends on prepending or appending content to existing
values, implement a **custom input type** or **custom form element** to handle
this behavior explicitly. This allows you to maintain full control over the
user interface, data handling, and accessibility.

For most use cases, it is recommended to replace prepend or append logic with
the standard *replace* value picker behavior or use dedicated UI controls that
clearly indicate how values are modified.

If the value picker was used to suggest combinable values, consider listing
these elements in the field description instead so that users can copy and
paste them manually into the input field.

..  index:: TCA, FullyScanned, ext:backend
