..  include:: /Includes.rst.txt

..  _feature-107795-1761030207:

================================================
Feature: #107795 - Introduce Integrations module
================================================

See :issue:`107795`

Description
===========

The new :guilabel:`Integrations` module has been introduced in the TYPO3
backend under the :guilabel:`Administration` section. This module serves as
the central hub for connecting TYPO3 with external systems and third-party
services.

The module uses the :ref:`card-based submodule overview
<feature-107712-1760548718>` feature to provide an intuitive and organized
interface for managing different types of integrations. At present, it
consolidates the following existing modules as third-level submodules:

*   **Webhooks** - Manage outgoing HTTP webhooks to external systems
*   **Reactions** - Manage incoming HTTP webhooks from external systems

The Integrations module uses a three-level hierarchy structure:

*   Administration (main module)
*   Integrations (second-level parent module with card-based overview)
*   Webhooks / Reactions (third-level modules)

..  note::
    The top-level backend modules were renamed in TYPO3 v14.
    The module now called :guilabel:`Administration` was formerly named
    :guilabel:`System`, and the module now called :guilabel:`System` was formerly
    named :guilabel:`Admin Tools`.
    For details, see:
    `Feature: #107628 – Improved backend module naming and structure
    <https://docs.typo3.org/permalink/changelog:feature-107628-1729026000>`_.


Module navigation
=================

The third-level modules (:guilabel:`Administration > Integrations > Webhooks`
and :guilabel:`Administration > Integrations > Reactions) now
include:

*   **Doc header module menu** - Quick navigation dropdown to switch between
    submodules or return to the Integrations overview
*   **Go back button** - Direct link to return to the Integrations overview


Backward compatibility
======================

The existing module identifiers continue to work through aliases:

*   :php-short:`webhooks_management` redirects to
    :php-short:`integrations_webhooks`
*   :php-short:`system_reactions` redirects to
    :php-short:`integrations_reactions`


Impact
======

The new :guilabel:`Administration > Integrations` module provides a centralized
location for managing all types of external system integrations in TYPO3. This
improves backend organization and user experience by grouping related
functionality together.

The module is designed to be extensible, allowing future integration types,
such as translation services, AI platforms, or other external tools, to be
added as additional third-level modules within the Integrations hub.

..  index:: Backend, ext:core
