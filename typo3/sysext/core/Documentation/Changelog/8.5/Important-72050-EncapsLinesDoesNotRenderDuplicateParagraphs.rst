.. include:: /Includes.rst.txt

============================================================================
Important: #72050 - encapsLines does not render duplicate paragraphs anymore
============================================================================

See :issue:`72050`

Description
===========

In the past the `_encapsLines` TypoScript function rendered two paragraphs for one
empty trailing line-break in the content.

See :ref:`t3tsref:encapslines`

This behaviour is now fixed.

Your Frontend appearance might change if you had multiple empty trailing paragraphs
in your RTE content. The last paragraph is no longer rendered twice in the Frontend.

.. index:: Frontend, RTE, TypoScript
