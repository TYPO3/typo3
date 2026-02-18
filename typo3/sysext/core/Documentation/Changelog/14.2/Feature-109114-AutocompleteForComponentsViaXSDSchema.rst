..  include:: /Includes.rst.txt

..  _feature-109114-1772123512:

=============================================================
Feature: #109114 - Autocomplete for components via XSD schema
=============================================================

See :issue:`109114`

Description
===========

The :ref:`existing CLI command <_feature-104114-1719419341>` `fluid:schema:generate` has been extended to also
cover Fluid components. When executed, the command creates `*.xsd` files in
:path:`var/transient/` for all available ViewHelpers and components, which can
be used by IDEs to offer autocompletion.

Usage:

..  code-block:: bash

    vendor/bin/typo3 fluid:schema:generate

For this to work correctly, the responsible component
collection needs to implement the newly introduced
:php-short:`TYPO3Fluid\Fluid\Core\Component\ComponentListProviderInterface`.
TYPO3's :ref:`Fluid components integration <_feature-108508-1765987901>` already
implements this, so these components are supported out-of-the-box.

Fluid Standalone provides a default implementation for custom component collections
that are based on :php-short:`TYPO3Fluid\Fluid\Core\Component\AbstractComponentCollection`,
which should cover components created before the official components integration
(such as those created with TYPO3 v13). However, if a custom folder structure is used
by overriding the default :php:`resolveTemplateName()`, a custom implementation for
:php:`getAvailableComponents()` needs to be provided as well. In most cases, it
should be easier to switch to the TYPO3 integration and to ditch the custom class.

Impact
======

The CLI command `fluid:schema:generate` now also creates XSD schema files
for Fluid components, which enables autocompletion in supporting IDEs.

..  index:: CLI, Fluid, ext:fluid
