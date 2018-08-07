.. include:: ../../Includes.txt

==============================================================================
Important: #17904 - showAccessRestrictedPages does not work with special menus
==============================================================================

See :issue:`17904`

Description
===========

HMENU setting :ts:`showAccessRestrictedPages = NONE` now acts as documented in
:ref:`TypoScript reference <t3tsref:menu-common-properties-showaccessrestrictedpages>`.

Before: using the option renders :html:`<a>Page title</a>` when page is inaccessible.

After: using the option renders :html:`<a href="index.php?id=123">Page title</a>`
when page is not accessible.

.. index:: Frontend, TypoScript
