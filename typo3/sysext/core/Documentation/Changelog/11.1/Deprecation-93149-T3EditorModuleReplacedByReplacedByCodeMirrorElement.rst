.. include:: /Includes.rst.txt

==============================================================================
Deprecation: #93149 - T3Editor JavaScript module replaced by CodeMirrorElement
==============================================================================

See :issue:`93149`

Description
===========

The T3Editor - that offers code editing capabilities for TCA
:php:`renderType=t3editor` fields - has been refactored into a custom HTML
element :html:`<typo3-t3editor-codemirror>`.
The element is provided by the new JavaScript module
js:`TYPO3/CMS/T3editor/Element/CodeMirrorElement`.


Impact
======

Using :html:`<textarea class="t3editor">..</textarea>` will work as before.
The new custom element will automatically be used, but a deprecating warning
will be logged to the browser console.


Affected Installations
======================

TYPO3 installations that use the T3Editor library in custom extensions, which
is very unlikely.


Migration
=========

Use the new :js:`TYPO3/CMS/T3editor/Element/CodeMirrorElement` module and adapt
your markup to read:

.. code-block:: html

   <typo3-t3editor-codemirror mode="..." addons="[..]" options="{..}">
       <textarea name="foo">..</textarea>
   </typo3-t3editor-codemirror>

Please make sure to drop the t3editor class from the textarea.

.. index:: Backend, JavaScript, NotScanned, ext:backend
