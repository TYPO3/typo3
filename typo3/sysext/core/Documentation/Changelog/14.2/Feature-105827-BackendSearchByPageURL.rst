
.. include:: /Includes.rst.txt

.. _feature-105827-1734338503:

===================================================================================================
Feature: #105827 - Search in backend page tree and live search can find pages by their frontend URI
===================================================================================================

See :issue:`105827` and :issue:`105833`

Description
===========

The backend page tree search functionality has been enhanced to allow entering
a full URI like `https://mysite.example.com/de/any/subtree/page/`, which will
show the matching page in the result tree.

Multiple URIs can be separated by the comma separator (`,`), just like multiple
page IDs can be entered.

Combining a search input like this is possible:

..  code-block::
    :caption: Combining multiple search parts

    4,8,https://example.com/first,http://sub.example.com/en/second,anyPageTitle

Matches in frontend URIs of translated pages will be marked as such.

This functionality uses the PSR-14 event `BeforePageTreeIsFilteredEvent`
(see :ref:`feature-105833-1734420558`) for this specific enhancement and can be used
as inspiration for custom search variations.

Additionally, the Live Search is also enhanced to perform the same search by
a single URI to lookup its page. This is achieved with the new PSR-14 event
`ModifyConstraintsForLiveSearchEvent` (see :ref:`feature-105827-1751912675`).

The live search will present both the default language page derived from the URI,
as well as the actual translated page as a result.

Configuration
=============

Search by frontend URI is enabled by default and can be controlled in two ways,
similar to :ref:`search by translation <feature-107961-1762076523>`:

User TSconfig
-------------

Administrators can control the availability of translation search via User TSconfig:

..  code-block:: typoscript

    # Disable searching by frontend URI for specific users/groups
    options.pageTree.searchByFrontendUri = 0

User Preference
---------------

Individual backend users can toggle this setting using the page tree toolbar menu.
The preference is stored in the backend user's configuration, allowing each user
to customize their search behavior.

Impact
======

Editors can now easily locate a backend page when only having the frontend URI
available. Permissions to edit/see the page are evaluated. Invalid or non-matching
URIs are ignored.

.. index:: Backend, ext:backend
