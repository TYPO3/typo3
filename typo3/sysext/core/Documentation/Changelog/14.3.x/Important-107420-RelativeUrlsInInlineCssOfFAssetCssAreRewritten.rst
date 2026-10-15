..  include:: /Includes.rst.txt

..  _important-107420-1792093888:

=============================================================================
Important: #107420 - Relative URLs in inline CSS of f:asset.css are rewritten
=============================================================================

See :issue:`107420`

Description
===========

The Fluid ViewHelper :html:`<f:asset.css>` with the argument
:html:`inline="1"` embeds the content of a stylesheet file into the page.
Relative paths in :css:`url()` and :css:`@import` statements of that file
were previously kept as they are, which made them point to non-existing
locations once the CSS was part of the HTML document.

The paths are now rewritten to the public location of the referenced
resources, the same way it already happens for stylesheets added via
:typoscript:`page.includeCSS` with :typoscript:`inline = 1`.

Note that the referenced resources are now resolved. If a stylesheet
contains a relative :css:`url()` to a file that does not exist, a
:php:`\TYPO3\CMS\Core\SystemResource\Exception\CanNotResolveSystemResourceException`
is thrown while rendering the page. Previously, such broken references were
silently output as-is.

Integrators should make sure that all relative paths in inlined stylesheets
point to existing files, or remove obsolete references from the CSS.

..  index:: Fluid, Frontend, ext:fluid
