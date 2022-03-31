.. include:: /Includes.rst.txt

==============================================================================
Important: #17904 - showAccessRestrictedPages does not work with special menus
==============================================================================

See :issue:`17904`

Description
===========

HMENU setting `showAccessRestrictedPages=NONE` now acts as documented in
:ref:`t3tsref:menu-common-properties`.

Before: using the option renders `<a>Page title</a>` when page is inaccessible.

After: using the option renders `<a href="index.php?id=123">Page title</a>`
when page is not accessible.

.. index:: Frontend, TypoScript
