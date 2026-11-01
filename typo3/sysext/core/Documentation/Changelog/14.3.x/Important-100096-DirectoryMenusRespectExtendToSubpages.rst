..  include:: /Includes.rst.txt

..  _important-100096-1793558400:

================================================================
Important: #100096 - Directory menus respect "Extend to Subpages"
================================================================

See :issue:`100096`

Description
===========

An :typoscript:`HMENU` with :typoscript:`special = directory` no longer lists
the subpages of a page if a visitor cannot access that page because of
"Extend to Subpages" on the page itself or further up the rootline. Before,
the menu linked to these subpages and requesting them was denied.

With :typoscript:`showAccessRestrictedPages`, restrictions by frontend user
group still do not remove the subpages from the menu. Hidden pages and pages
outside their publish dates do.

Other values of :typoscript:`special` are not affected.

..  index:: Frontend, TypoScript, ext:frontend
