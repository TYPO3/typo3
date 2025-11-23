..  include:: /Includes.rst.txt

..  _feature-107081-1752377084:

=================================================================================
Feature: #107081 - Add options to predefine link target and class in link browser
=================================================================================

See :issue:`107081`

Description
===========

The link browser now supports preconfiguring link `target` and `class` attributes.
This allows administrators to set global defaults for all link types or specific
defaults per handler type, reducing manual selection effort for editors.

Configuration can be set via Page TSconfig:

..  code-block:: typoscript

    TCEMAIN.linkHandler.[handlerKey].(target|cssClass).default = _blank

Where handler keys correspond to the link handler types that support these attributes:

*   `page` - for page links
*   `file` - for file links
*   `folder` - for folder links
*   `url` - for external URLs
*   `telephone` - for telephone links
*   `email` - for email links

Global configuration (applies to all link types):

..  code-block:: typoscript
    :caption: EXT:my_extension/Configuration/page.tsconfig

    TCEMAIN.linkHandler.properties{
        target.default = _self
        cssClass.default = my-link-class
    }

Handler-specific configuration (overrides global settings):

..  code-block:: typoscript
    :caption: EXT:my_extension/Configuration/page.tsconfig

    TCEMAIN.linkHandler{
        url.target.default = _blank
        page.target.default = _self
        file.cssClass.default = file-link
    }

..  hint::

    The default `target` attributes of links added via the RTE are configured
    separately through Page TSconfig options such as:

    ..  code-block:: typoscript
        :caption: EXT:my_extension/Configuration/page.tsconfig

        RTE.buttons.link{
            properties.target.default = _blank
            page.properties.target.default = _blank
        }

    For details, see
    `buttons.link.[type].properties.target.default <https://docs.typo3.org/permalink/t3tsref:confval-rte-buttons-link-type-properties-target-default>`_.

Impact
======

This feature improves editor workflows by providing meaningful default values
for link attributes while preserving full flexibility for customization.
The hierarchical configuration system allows both global defaults and
handler-specific overrides, making link creation faster and more consistent.

..  index:: Backend, TSConfig, ext:backend
