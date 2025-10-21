..  include:: /Includes.rst.txt

..  _feature-107795-1761030207:

================================================
Feature: #107795 - Introduce Integrations module
================================================

See :issue:`107795`

Description
===========

The new :guilabel:`Integrations` module has been introduced in the
TYPO3 backend under the :guilabel:`System` section. This module serves
as the central hub for connecting TYPO3 with external systems and
third-party services.

The new module utilizes the :ref:`card-based submodule overview <feature-107712-1760548718>`
feature to provide an intuitive and organized interface for managing different
types of integrations. Currently, it consolidates the following existing modules
as third-level submodules:

*   **Webhooks** - Manage outgoing HTTP webhooks to external systems
*   **Reactions** - Manage incoming HTTP webhooks from external systems

The Integrations module uses a three-level hierarchy structure:

*   System (main module)
*   Integrations (second-level parent module with card-based overview)
*   Webhooks / Reactions (third-level modules)

Module Navigation
=================

The third-level modules (Webhooks and Reactions) now include:

*   **Doc header module menu** - Quick navigation dropdown to switch between
    submodules or return to the Integrations overview
*   **Go back button** - Direct link to return to the Integrations overview

Backward Compatibility
======================

The existing module identifiers continue to work through aliases:

*   :php:`webhooks_management` redirects to :php:`integrations_webhooks`
*   :php:`system_reactions` redirects to :php:`integrations_reactions`

Impact
======

The new Integrations module provides a centralized location for managing all
types of external system integrations in TYPO3. This improves the backend
organization and user experience by grouping related functionality together.

The module is designed to be extensible, allowing future integration types
(such as translation services, AI platforms, or other external tools) to be
added as additional third-level modules within the Integrations hub.

..  index:: Backend, ext:core
