
.. include:: /Includes.rst.txt

===================================================================
Deprecation: #24387 - TypoScript option config.xhtmlDoctype=xhtml_2
===================================================================

See :issue:`24387`

Description
===========

The TypoScript option `config.xhtmlDoctype = xhtml_2` is marked for removal in CMS 8, due to the W3C decision to
fully work on HTML5 instead of XHTML2. See http://www.w3.org/MarkUp/ and http://www.w3.org/News/2010.html#entry-8982
for more details.


Affected installations
======================

Any TYPO3 installation with TypoScript that relies on `config.xhtmlDoctype = xhtml_2`.

Migration
=========

Use other doctypes like html5 to render the frontend of the TYPO3 site.


.. index:: TypoScript, Frontend
