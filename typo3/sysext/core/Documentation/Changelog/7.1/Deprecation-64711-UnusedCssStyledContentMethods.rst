
.. include:: ../../Includes.txt

==========================================================================
Deprecation: #64711 - Various methods within CSS Styled Content Controller
==========================================================================

See :issue:`64711`

Description
===========

The following methods within the main CSS Styled Content Controller responsible for rendering
custom HTML due to lack of TypoScript logic in the past have been marked for removal for TYPO3 CMS 8.
They are not part of the default CSS Styled Content TypoScript code since TYPO3 CMS 6.

.. code-block:: php

	CssStyledContentController->render_bullets()
	CssStyledContentController->render_uploads()
	CssStyledContentController->beautifyFileLink()

Impact
======

Using the methods in custom TypoScript code or CSS Styled Content methods will throw a deprecation message.

Migration
=========

Use default TypoScript from CSS Styled Content derived from the current version.
