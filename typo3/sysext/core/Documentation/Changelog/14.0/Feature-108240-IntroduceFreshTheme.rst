..  include:: /Includes.rst.txt

..  _feature-108240-1732187656:

========================================
Feature: #108240 - Introduce Fresh theme
========================================

See :issue:`108240`

Description
===========

The new :guilabel:`Fresh` theme has been introduced for the TYPO3 backend,
marking the beginning of broader backend customization capabilities. This theme
offers users a modern alternative appearance option with a friendlier purple
accent color palette.

Built on the modern CSS architecture, the Fresh theme is now the default for
new users. It complements the existing Modern and Classic themes - all three
themes will be maintained and enhanced equally going forward.

The extensive CSS framework improvements made during the 14.0 cycle now enable
easier theme adaptation with minimal code changes, setting the foundation for
the expanding theme system.

Theme Selection
===============

Users can select their preferred theme in the :guilabel:`User Settings` module
under the :guilabel:`Backend appearance` section. The `Fresh` theme provides a
more contemporary look and feel while maintaining the familiar TYPO3 backend
structure and usability.

All existing themes remain fully supported:

*   **Fresh** (default for new users) - Modern purple accent colors
*   **Modern** - Contemporary appearance with traditional accent colors
*   **Classic** - Traditional TYPO3 backend appearance

Future Development
==================

Additional customization options and modern UI elements will be incrementally
added to the theme system leading up to the LTS release. The modular CSS
architecture ensures that all themes benefit from these enhancements equally.

.. important::

   The backend CSS framework is currently considered internal API. No public
   CSS variables or settings have been defined yet that are guaranteed to
   remain stable across future versions. Areas such as color palette generation,
   accent colors, stateful colors, and tinting mechanisms are still in active
   development and subject to change before a long-term solution is established.

Impact
======

The introduction of the `Fresh` theme demonstrates TYPO3's commitment to
providing a modern, customizable backend experience. The modular CSS architecture
allows for continuous improvement and evolution of the backend design system.

New TYPO3 installations will automatically use the `Fresh` theme, while existing
users can opt-in through their :guilabel:`User Settings`. The choice of theme
is stored per-user, allowing teams with different preferences to work
comfortably within the same TYPO3 installation.

..  index:: Backend, ext:backend
