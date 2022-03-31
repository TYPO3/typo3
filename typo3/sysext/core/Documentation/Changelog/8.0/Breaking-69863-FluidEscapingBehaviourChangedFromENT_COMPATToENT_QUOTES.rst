
.. include:: /Includes.rst.txt

=================================================================================
Breaking: #69863 - Fluid escaping behaviour changed from ENT_COMPAT to ENT_QUOTES
=================================================================================

See :issue:`69863`

Description
===========

The escaping behaviour in Fluid has been changed. Before, `ENT_COMPAT` was used.
Now, `ENT_QUOTES` is used.


Impact
======

Fluid templates which depend on single quotes not being escaped when escaping variables. Affects
ObjectAccessor (variable access in general) and calls to `f:format.htmlentities` and `f:format.htmlspecialchars`.


Affected Installations
======================

Any TYPO3 site containing Fluid templates which depend on single quotes not being escaped.


Migration
=========

Change template to not depend on single quotes being escaped in any ObjectAccessor, consider adding
`{variable -> f:format.htmlspecialchars(keepQuotes: 1)}` or
`<f:format.htmlspecialchars keepQuotes="1">{variable}</f:format.htmlentities>`
when accessing variables but be aware of possible XSS implications due to incomplete escaping.

.. index:: Fluid
