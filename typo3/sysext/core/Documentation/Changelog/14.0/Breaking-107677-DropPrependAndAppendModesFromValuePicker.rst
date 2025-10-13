..  include:: /Includes.rst.txt

..  _breaking-107677-1760339783:

===========================================================================
Breaking: #107677 - Drop `prepend` and `append` modes from TCA Value Picker
===========================================================================

See :issue:`107677`

Description
===========

The *prepend* and *append* modes of the Value Picker specified in TCA and
used in FormEngine
(:php:`$GLOBALS['TCA']['tx_example']['columns']['example']['config']['valuePicker']['mode']`)
have been removed.

These modes were designed to insert predefined values before or after the
existing input value, but they served only very niche use cases and caused
inconsistent behavior across different input types.

Maintaining these modes introduced unnecessary complexity, both in terms of
implementation and accessibility. Prepending or appending content dynamically
to user input fields can easily lead to unexpected results, break input
validation, and interfere with assistive technologies such as screen readers.
Moreover, this approach mixes presentation and data logic in ways that are not
consistent with modern form handling patterns.

Future improvements to the Value Picker will focus on a consistent *mode=replace*
behavior and will be implemented as new form of input types to provide a more
robust and accessible user experience.

Impact
======

Any Value Picker TCA configuration using the `mode` options `prepend` or `append`
will no longer have an effect. TYPO3 will ignore these settings, and the picker
will default to the regular *replace* behavior.

Affected installations
======================

Installations using custom FieldWizard configurations or integrations that rely
on the Value Picker with `mode = prepend` or `mode = append` are affected.

Migration
=========

There is no direct replacement for the removed modes.

If your implementation depends on prepending or appending content to existing
values, you should implement a **custom input type** or **custom form element**
to handle this behavior explicitly. This allows you to maintain full control
over the user interface, data handling, and accessibility.

For most use cases, it is recommended to replace prepend/append logic with
standard *replace* Value Picker behavior or dedicated UI controls that clearly
indicate how values are modified.

If the use case of such a Value Picker was to provide a list of possible elements
that could be combined, consider listing these elements in the description of the
input element, so that users can copy+paste these into the input box on their own.

..  index:: TCA, FullyScanned, ext:backend
