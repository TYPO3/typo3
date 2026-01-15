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

Impact
======

A default frontend theme is now available. It can be easily
activated in the TYPO3 installation process, or also be enabled
afterwards.

It is dependency-free and provides and utilizes site sets.

..  index:: Frontend, NotScanned, ext:theme_camino
