
.. include:: /Includes.rst.txt

=======================================================================
Breaking: #44879 - TypoScript inline styles from blockquote tag removed
=======================================================================

See :issue:`44879`

Description
===========

CSS Styled Content renders blockquote tags using :code:`lib.parseFunc_RTE` TypoScript.
For TYPO3 CMS 7.1, the following TypoScript lines have been removed without substitution:

.. code-block:: typoscript

    lib.parseFunc_RTE.externalBlocks.blockquote.callRecursive.tagStdWrap.HTMLparser = 1
    lib.parseFunc_RTE.externalBlocks.blockquote.callRecursive.tagStdWrap.HTMLparser.tags.blockquote.overrideAttribs = style="margin-bottom:0;margin-top:0;"

The effect is that the following inline styles have been removed from blockquote tags without substitution:

.. code-block:: css

	margin-bottom:0;
	margin-top:0;

Impact
======

Styling of blockquote tags based on CSS Styled Content could differ after an upgrade to CMS 7.1.


Affected installations
======================

All CMS 7.1 installations that were upgraded from 7.0 and below which use blockquote tags rendered by :code:`lib.parseFunc_RTE`.


Migration
=========

It is recommended to fix the margins inside your website CSS. Alternatively, you can re-add the above TypoScript lines
to your website TypoScript template (not recommended).


.. index:: TypoScript, Frontend, ext:css_styled_content
