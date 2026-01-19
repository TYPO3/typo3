..  include:: /Includes.rst.txt

..  _feature-108539-1768554158:

===================================================
Feature: #108539 - Introduce default theme "Camino"
===================================================

See :issue:`108539`

Description
===========

A new default theme has been added. Its main purpose
is to build new sites more rapidly in TYPO3 v14.

The name **Camino** (spanish "the way") was chosen as v14 development
series, marking the first steps on the way to a glorious future for
TYPO3.

It serves to show that a new site in TYPO3 can be set up within
minutes, being customizable (at least in a limited way), without
having to rely on any external library, and to avoid ANY error
message for newcomers to TYPO3.

Camino will be packaged for new installations by default
and can be activated for new sites alongside existing sites.

The theme shows off basic page structures as well as
some default content elements - completely without any
third-party dependencies nor requiring the "Fluid styled content"
extension.

The rough structure of the theme:

*   Four different color schemes can be selected in the site settings

*   The main menu structure and footer structure can be configured
    on the root page inside the backend layout / colPos positions

*   Common content elements for a Hero area and regular content
    is available

*   Minimal configuration is handled in TypoScript

The theme is not meant to evolve within TYPO3, as it will
be moved to TER/Packagist/GitHub in a separate repository
in v15.0. In v15.x a new theme will be added with more
modern features, and it will utilize features that will
be added during v15.x development.

The theme is 100% optional and encapsulated - existing setups
will have no interference.

The theme will be fine-tuned before the TYPO3 v14 LTS release,
specific documentation for its features will be provided in
the theme's documentation.

Installation
------------

For now, the theme is the same as a regular TYPO3 extension.

On fresh classic-mode installations, the theme will be enabled
by default. A new site and a first page will be created, which
can be used to insert content.

On Composer-mode installations, the package `typo3/theme-camino`
needs to be required, and a fresh installation will also create
the site and a first page.

For existing installations, the theme must be enabled first
(depending on the TYPO3 setup) either via extension manager,
or by requiring the Composer package.

Once the "extension" is activated, the steps to enable
the Camino frontend are:

*   Create a new root page in the :guilabel:`Content > Layout`
    page tree. Be sure to edit the created page properties and enable
    :guilabel:`Behavior > Use as Root Page` .
*   This will automatically create a new Site. Check :guilabel:`Sites > Setup`
    to see the created Site. Edit that Site's properties. In
    :guilabel:`General > Sets for this Site` ensure that the `Theme: Camino`
    Site set is added as a dependency.
*   (Depending on the TYPO3 setup, TYPO3 caches might need to be cleared)
*   Then edit the created root page properties via :guilabel:`Content > Layout` again,
    and pick `Camino: Start page` from the tab
    :guilabel:`Appearance > Backend Layout (this page only)`.
*   Now the Camino theme will be applied to the site. Content can be added
    in the specific columns, and sub-pages can be created (ensure to set
    the backend layout of subpages to the appropriate Camino backend layout,
    either :guilabel:`Camino: Content page (full-width)` or
    :guilabel:`Camino: Content page (with sidebar)`.
*   A custom logo can be set in root page properties :guilabel:`Appearance`, just
    above the backend layout picker.
*   In :guilabel:`Sites > Setup`, the Site set configuration can be accessed
    to adjust the color scheme and further options.

Impact
======

A default frontend theme is now available. It can be easily
activated in the TYPO3 installation process, or also be enabled
afterwards.

It is dependency-free and provides and utilizes site sets.

..  index:: Frontend, NotScanned, ext:theme_camino
