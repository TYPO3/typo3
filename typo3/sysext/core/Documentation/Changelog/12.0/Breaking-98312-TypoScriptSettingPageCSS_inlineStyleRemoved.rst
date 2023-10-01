.. include:: /Includes.rst.txt

.. _breaking-98312-1662725671:

==================================================================
Breaking: #98312 - TypoScript setting page.CSS_inlineStyle removed
==================================================================

See :issue:`98312`

Description
===========

The TypoScript setting :typoscript:`page.CSS_inlineStyle` which was used to
inject an inline CSS string into the TYPO3 Frontend has been removed.

Impact
======

Using this setting has no effect anymore since TYPO3 v12.

Affected installations
======================

TYPO3 installations having this option set in their TypoScript setup.

Migration
=========

Use :typoscript:`page.cssInline` instead, which has been around for many
TYPO3 versions already.

The superior setting :typoscript:`page.cssInline` allows to use
:typoscript:`stdWrap` and :typoscript:`cObject`.

Example for migration:

..  code-block:: typoscript

    page.CSS_inlineStyle = a { color: red; }

    page.cssInline.100 = TEXT
    page.cssInline.100.value = a { color: red; }

.. index:: TypoScript, FullyScanned, ext:frontend
