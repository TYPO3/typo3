.. include:: /Includes.rst.txt

============================================================================
Breaking: #88772 - JavaScript script tags omit type=text/javascript in HTML5
============================================================================

See :issue:`88772`

Description
===========

When rendering HTML5 output, :html:`<script>` tags do not the additional attribute :html:`type=text/javascript`
anymore as it is considered optional, and if none given, modern browsers fall back to this type
already.

See the official W3C definition here: https://www.w3.org/TR/html52/semantics-scripting.html#element-attrdef-script-type

For this reason, all of TYPO3's Backend (which is rendering HTML5) and Installer do not include
this optional attribute in :html:`<script>` tags anymore.

For TYPO3 Frontend rendering, the attribute is omitted when having no doctype or HTML5 as doctype
configured (via TypoScript :typoscript:`config.doctype = html5`). This leads to a minimal smaller
HTML document submitted to the client.

For any XHTML or HTML4-based website, the attribute is still added.


Impact
======

TYPO3's Frontend rendering does not render :html:`type=text/javascript` anymore in :html:`<script>` tags when
rendering a HTML5 output, unless explicitly specified.


Affected Installations
======================

Any TYPO3 installation running a HTML5-based frontend output.


Migration
=========

As all modern browsers do not need this tag, and the specification says it's optional, there is
no migration needed at all.

If still requested by a specific project, it can be added via:

.. code-block:: javascript

   page.includeJS.myfile = EXT:site_mysite/Resources/Public/JavaScript/myfile.js
   page.includeJS.myfile.type = text/javascript

.. index:: Frontend, TypoScript, NotScanned
