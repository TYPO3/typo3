
.. include:: /Includes.rst.txt

==================================================
Breaking: #19737 - Prefer root templates for pages
==================================================

See :issue:`19737`

Description
===========

The Core now gives templates having set the root-flag precedence over normal extension templates.
This ignores the fact that normally the first template of the current page is chosen, following their sorting.

This improves user experience as the user expects the root template to be the one with highest priority.

Impact
======

Pages where multiple templates are present not having a root-template as the topmost template
in the list will encounter different results when templates are evaluated.

Affected installations
======================

Installations with pages where multiple templates are present not having a/the root-template as the topmost template.

Migration
=========

Ensure the templates have correct flags set.


.. index:: TypoScript, Backend, Frontend
