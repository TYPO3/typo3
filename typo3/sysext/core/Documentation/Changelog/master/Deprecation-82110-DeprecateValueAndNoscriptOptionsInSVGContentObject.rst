.. include:: ../../Includes.txt

===================================================================================
Deprecation: #82110 - Deprecate option "value" and "noscript" in SVG content object
===================================================================================

See :issue:`82110`

Description
===========

The following TypoScript settings of the SVG content object have been marked as deprecated:

* :typoscript:`value` (in case :typoscript:`renderMode` is not set to inline)
* :typoscript:`noscript`

The SVG content object renderer has used the two options "value" and "noscript" to render the given
value into a :html:`<script type="image/svg+xml">` tag.
This kind of implementation is very old and has been marked as deprecated.

The SVG content object supports two render variants:

1) the :html:`<object>` tag variant (:typoscript:`renderMode = object`) [default]
2) the :html:`<svg>` tag variant (:typoscript:`renderMode = inline`)

The second one is nearly the same as the script tag variant, so an alternative is still in place.

Impact
======

Using one of the two options will trigger a deprecation log entry.


Affected Installations
======================

Instances which use at least one of the two options.


Migration
=========

Use the new :typoscript:`renderMode = inline` to render a SVG file as :html:`<svg>`

.. index:: Frontend, TypoScript, NotScanned
