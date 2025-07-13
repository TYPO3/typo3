..  include:: /Includes.rst.txt

..  _feature-107081-1752377084:

================================================================================
Feature: #107081 - Add options to predefine link target and class in LinkBrowser
================================================================================

See :issue:`107081`

Description
===========

The LinkBrowser now supports preconfiguring link target and class attributes.
This allows administrators to set global defaults for all link types or specific
defaults per handler type, reducing manual selection effort for editors.

Configuration can be set via Page TSconfig:

..  code-block:: html
    TCEMAIN.linkHandler.[handlerKey].(target|cssClass).default = _blank

Where handler keys correspond to link handlers that support these attributes:

..  code-block:: html
    - page (for page links)
    - file (for file links)
    - folder (for folder links)
    - url (for external URL links)
    - telephone (for telephone number css class)
    - email (for email css class)

Global configuration (applies to all link types):

..  code-block:: typoscript
    TCEMAIN.linkHandler.properties.target.default = _self
    TCEMAIN.linkHandler.properties.cssClass.default = my-link-class

Handler-specific configuration (overrides global settings):

..  code-block:: typoscript
    TCEMAIN.linkHandler.url.target.default = _blank
    TCEMAIN.linkHandler.page.target.default = _self
    TCEMAIN.linkHandler.file.cssClass.default = file-link

..  hint::

    Note that default `target` attributes of links added via the RTE are configured
    individually via Page TSConfig options like:

    ..  code-block:: typoscript

        RTE.buttons.link.properties.target.default = _blank
        RTE.buttons.link.page.properties.target.default = _blank

For details, see `<https://docs.typo3.org/permalink/t3tsref:buttons-link-type-properties-target-default>`__.

Impact
======

This feature improves editor workflow by providing sensible defaults
for link attributes while maintaining full flexibility to override when needed.
The hierarchical configuration allows both global policies and specific
handler customization.

.. index:: Backend, TSConfig, ext:backend
