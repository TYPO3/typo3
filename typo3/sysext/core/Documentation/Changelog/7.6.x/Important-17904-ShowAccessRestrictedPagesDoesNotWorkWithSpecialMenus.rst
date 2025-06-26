.. include:: /Includes.rst.txt

.. _important-17904:

==============================================================================
Important: #17904 - showAccessRestrictedPages does not work with special menus
==============================================================================

See :issue:`17904`

Description
===========

HMENU setting `showAccessRestrictedPages=NONE` now acts as documented in
`t3tsref:menu-common-properties <https://docs.typo3.org/m/typo3/reference-typoscript/8.7/en-us/MenuObjects/CommonProperties/Index.html>`_.

Before: using the option renders `<a>Page title</a>` when page is inaccessible.

After: using the option renders `<a href="index.php?id=123">Page title</a>`
when page is not accessible.

.. index:: Frontend, TypoScript
