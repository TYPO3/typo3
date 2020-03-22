.. include:: ../../Includes.txt

====================================================
Feature: #86331 - Native URL support for MountPoints
====================================================

See :issue:`86331`

Description
===========

MountPoints allow TYPO3 editors to mount a page (and its subpages) from a different area of the site in the current page tree.

The definitions are as follows:

- MountPoint Page: A page with doktype=7 - a page pointing to a different page ("web mount")
  that should act as replacement for this page and possible descendants.
- Mounted Page a.k.a. "Mount Target": A regular page containing content and subpages.

The idea behind it is to manage content only once and "link" / "mount" to a tree to be used
multiple times - while keeping the website visitor under the impression to actually
navigate just a regular subpage. There are concerns regarding SEO for having duplicate content,
but TYPO3 can be used for more than just simple websites, as Mount Points are an important tool
for heavy multi-site installations or Intranet/Extranet installations.

A MountPoint Page has the option to either display the content of the MountPoint Page itself,
or the content of the target page, when visiting this page.

Linking to a subpage will result in adding "MP" GET Parameters, and altering the root line (tree structure)
of visiting the website, as the "MP" is containing the context. The MP parameter found throughout TYPO3 Core
contains the ID of the Mounted Page and the ID of the MountPoint Page - e.g. "13-23" whereas 13 would be
the Mounted Page and 23 the MountPoint Page (doktype=7).

Recursive mount points are added to the "MP" parameter with ",", like "13-23,84-26".
Recursive mount points are defined as follows: A Mounted Page could have a subpage which in turn
has a subpage which is again a MountPoint Page.

MountPoint support is now added in TYPO3 v9 with Site Handling and slug handling.
Due to TYPO3's principles of slug handling where a page only contains one single slug
containing the URL path, and not various slugs for different places where it might be used,
TYPO3 will work by combining the slug of the MountPoint Page and a smaller part of the Mounted Page
or subpages of the Mounted Page, which will be added to the URL string - removing the necessity to actually
deal with the query parameter `MP` which will never be added again, as it is part of the URL path now.

Using MountPoint functionality on a website plays an important role for menus as this is the
only way to actually link to the subpages in a MountPoint context.

Multi-Site support:

The context for cross-domain sites is also kept, ensuring that the user will never notice that content
might be coming from a completely different site / pagetree within TYPO3.
Creating links for multi-site support is the same as if a Mounted Page is on the same site.


Impact
======

Limitations:

1. Multi-language support
   Please be aware that multi-language setups are supported in general, but this would only fit if both sites support the same language IDs.

2. Slug uniqueness when using Multi-Site setups cannot be ensured
   If a MountPoint Page has the slug "/more", mounting a page with "/imprint" subpage, but the MountPoint Page
   has a regular sibling page with "/more/imprint" a collision cannot be detected, whereas the non-mounted page
   would always work and a subpage of a Mounted Page would never be reached.

For the sake of completeness, please consider the TYPO3 documentation on the following TypoScript properties related to mount points:

- :typoscript:`config.MP_defaults`
- :typoscript:`config.MP_mapRootPoints`
- :typoscript:`config.MP_disableTypolinkClosestMPvalue`

.. index:: Frontend, ext:frontend
