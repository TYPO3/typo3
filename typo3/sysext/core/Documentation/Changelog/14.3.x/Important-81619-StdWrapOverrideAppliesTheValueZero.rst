..  include:: /Includes.rst.txt

..  _important-81619-1787572925:

==========================================================
Important: #81619 - stdWrap "override" applies the value 0
==========================================================

See :issue:`81619`

Description
===========

The stdWrap property :typoscript:`override` replaced the current content
whenever its value was truthy after trimming. Since the string
:typoscript:`0` is falsy in PHP, an override evaluating to a single zero
silently kept the previous content instead of replacing it. TypoScript
delivers every value as a string, which made :typoscript:`0` the one scalar an
override could not produce, for example when the value came from a condition
or from a nested stdWrap.

The property now leaves the content untouched only if the configured value is
unset, empty or consists of whitespace, and applies every other value,
:typoscript:`0` included.

Instances that relied on :typoscript:`override = 0` being ignored - for
example to keep a disabled override in place - now render :typoscript:`0`
instead of the previous content. Remove the property or guard it with
:typoscript:`override.if` to keep the previous output.

..  index:: Frontend, TypoScript, ext:frontend
