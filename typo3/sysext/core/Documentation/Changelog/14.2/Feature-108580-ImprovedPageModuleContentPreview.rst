..  include:: /Includes.rst.txt

..  _feature-108580-1734567890:

=======================================================
Feature: #108580 - Improved page module content preview
=======================================================

See :issue:`108580`

Description
===========

The page module's content element preview functionality has been
enhanced to provide editors with a better visual representation of content
elements in the backend.

Sanitized HTML rendering for content
------------------------------------

Content elements with HTML in the bodytext field (such as text, text & images)
now display sanitized HTML in the page module preview instead of plain text.

A new :php:`\TYPO3\CMS\Core\Html\PreviewSanitizerBuilder` has been introduced that creates a sanitizer
specifically for backend previews. This sanitizer:

*   Removes clickable links (unwraps :html:`<a>` tags while preserving
    their content)
*   Removes heading tags (:html:`<h1>` through :html:`<h6>`) while
    preserving their content
*   Allows safe HTML formatting (bold, italic, lists, etc.)

Enhanced bullet list preview
----------------------------

Content elements of type "bullet list" now render as HTML lists in the preview.

Harmonized menu element rendering
---------------------------------

Preview rendering for menu content elements and "insert records" elements
has been harmonized to match the layout used in the record selector wizard.
This provides a consistent experience across different parts of the backend.

Impact
======

These enhancements improve the editorial experience in the TYPO3
backend by providing clearer, more informative content previews. Editors can now:

*   See formatted HTML content as it will appear to users
*   Quickly identify bullet list structure and content

..  index:: Backend, HTML, ext:backend, ext:core
