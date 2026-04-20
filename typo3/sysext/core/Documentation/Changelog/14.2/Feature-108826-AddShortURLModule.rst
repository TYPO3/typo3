..  include:: /Includes.rst.txt

..  _feature-108826-1770219994:

=======================================
Feature: #108826 - Add Short URL module
=======================================

See :issue:`108826`

Description
===========

A new backend module :guilabel:`Link Management > Short URLs` has been introduced.
It enables editors to create and manage short URLs that redirect visitors to a
configurable target. Short URLs are stored as :sql:`sys_redirect` records with a
dedicated record type :sql:`short_url`, providing a streamlined editing form that
hides redirect-specific fields that are irrelevant to short URL use cases.

Creating short URLs
-------------------

Short URLs can be created in two ways:

*   **Manual entry**: Editors type a custom path (for example, `/promo`) into the
    source path field.
*   **Auto-generation**: Clicking the :guilabel:`Generate Short URL` button
    generates a random 8-character path (for example, `/aBcDeFgH`). The
    path is guaranteed to be unique due to server-side collision checking.

Uniqueness enforcement
----------------------

Short URL paths must be unique to each source host. Duplicate detection happens at
two levels:

*   **Client-side validation**: While editing, the source path and source host
    fields are validated against existing records. If a conflict is detected,
    both fields are highlighted with an error state together with a notification.

*   **Server-side enforcement**: On save, the
    :php-short:`\TYPO3\CMS\Core\DataHandling\DataHandler` rejects duplicate short
    URLs and displays a flash message, ensuring data integrity even if
    client-side validation is bypassed.

Immutability
------------

Once a short URL record has been saved, the source path and source host fields
become read-only. This ensures that published short URLs remain stable and
previously shared links continue to work. The redirect target can be
changed at any time.

Clipboard support
-----------------

The full short URL, including protocol and host, can be copied to the clipboard
from both the list overview and the record editing form.

Impact
======

Editors benefit from a dedicated interface for managing short URLs without
needing to understand redirect configuration details. The module provides a
central location for creating, reviewing, and maintaining short URLs with
built-in safeguards against duplicates and accidental modifications.

..  index:: Backend, ext:redirects
