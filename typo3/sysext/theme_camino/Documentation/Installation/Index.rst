.. include:: /Includes.rst.txt

.. _installation:

============
Installation
============

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

..  contents:: Table of contents
    :local:

Installation with Composer
==========================

Check whether you are already using the extension with:

..  code-block:: bash

    composer show | grep camino

This should either give you no result or something similar to:

..  code-block:: none

    typo3/theme-camino       v14.1.0

If it is not installed yet, use the ``composer require`` command to install
the extension:

..  code-block:: bash

    composer require typo3/theme-camino

The given version depends on the version of the TYPO3 Core you are using.

Installation without Composer
=============================

In an installation without Composer, the extension is already shipped but might
not be activated yet. Activate it as follows:

#.  In the backend, navigate to the :guilabel:`System > Extensions`
    module.
#.  Click the :guilabel:`Activate` icon for the Camino theme extension.

Setup Camino
============

These steps only need to be performed in case an existing TYPO3 installation
should make use of the theme.

Once the "extension" is activated, the steps to enable
the Camino frontend are:

*   Create a new root page in the :guilabel:`Content > Layout`
    page tree. Be sure to edit the created page properties and enable
    :guilabel:`Behavior > Use as Root Page`.
*   This will automatically create a new Site. Check :guilabel:`Sites > Setup`
    to see the created Site. Edit that Site's properties. In
    :guilabel:`General > Sets for this Site` ensure that the `Theme: Camino`
    Site set is added as a dependency.
*   (Depending on the TYPO3 setup, TYPO3 caches might need to be cleared)
*   Then edit the created root page properties via :guilabel:`Content > Layout` again,
    and pick :guilabel:`Camino: Start page` from the tab
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
