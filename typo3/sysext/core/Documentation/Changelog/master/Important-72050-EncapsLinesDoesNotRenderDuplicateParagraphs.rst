.. include:: ../../Includes.txt

============================================================================
Important: #72050 - encapsLines does not render duplicate paragraphs anymore
============================================================================

See :issue:`72050`

Description
===========

In the past the
`_encapsLines` TypoScript function
https://docs.typo3.org/typo3cms/TyposcriptReference/Functions/Encapslines/Index.html
rendered two paragraphs for one empty trailing line-break in the content.

This behaviour is now fixed.

Your Frontend appearance might change if you had multiple empty trailing paragraphs
in your RTE content. The last paragraph is not rendered twice in the Frontend anymore.

.. index:: Frontend, RTE, TypoScript
