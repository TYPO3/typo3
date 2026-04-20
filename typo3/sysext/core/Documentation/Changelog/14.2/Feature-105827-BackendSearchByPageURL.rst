..  include:: /Includes.rst.txt

..  _feature-105827-1734338503:

===================================================================================================
Feature: #105827 - Search in backend page tree and live search can find pages by their frontend URI
===================================================================================================

See :issue:`105827` and :issue:`105833`

Description
===========

The backend page tree search functionality has been enhanced to allow users to
enter a full URI such as `https://mysite.example.com/de/any/subtree/page/`,
which shows the matching page in the result tree.

Multiple URIs can be separated with commas (`,`), just like multiple page IDs.

It is also possible to combine different search input:

..  code-block::
    :caption: Combining multiple search parts

    4,8,https://example.com/first,http://sub.example.com/en/second,anyPageTitle

Matches in frontend URIs of translated pages are marked accordingly.

This functionality uses the PSR-14 event
:php-short:`TYPO3\CMS\Backend\Tree\Repository\BeforePageTreeIsFilteredEvent`
(see :ref:`feature-105833-1734420558`) and can serve as
inspiration for custom search variations.

In addition, live search has been enhanced to perform the same lookup based on
a single URI. This is achieved with the new PSR-14 event
:php-short:`TYPO3\CMS\Backend\Search\Event\ModifyConstraintsForLiveSearchEvent`
(see :ref:`feature-105827-1751912675`).

Live search returns both the default language page derived from the URI and the
matching translated page.

Configuration
=============

Search by frontend URI is enabled by default and can be controlled in two ways,
similar to :ref:`search by translation <feature-107961-1762076523>`:

User TSconfig
-------------

Administrators can control the availability of frontend URI search with
user TSconfig:

..  code-block:: typoscript

    # Disable searching by frontend URI for specific users/groups
    options.pageTree.searchByFrontendUri = 0

User preference
---------------

Individual backend users can toggle this setting in the page tree toolbar menu.
The preference is stored in the backend user's configuration, allowing each
user to customize search behavior.

Impact
======

Editors can now easily locate a backend page when only the frontend URI is
available. Permissions to view or edit the page are respected. Invalid or
non-matching URIs are ignored.

..  index:: Backend, ext:backend
