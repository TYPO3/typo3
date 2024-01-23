.. include:: /Includes.rst.txt

.. _breaking-101137-1688397315:

===================================================
Breaking: #101137 - Page Doktype "Recycler" removed
===================================================

See :issue:`101137`

Description
===========

TYPO3 had multiple concepts of a recycler / trash bin. One of the oldest
concepts was the ability to create a manual page of the type "Recycler"
(page records with doktype=255 set) where editors could manually move content
to such a page instead of deleting it. One other option is to use the
:guilabel:`Web > Recycler` backend module (available with the shipped recycler
system extension). This process is much more user-friendly: Any kind of record
which has been (soft-)deleted can be viewed and re-added via this module, no
manual process during the deletion process is needed.

For reasons of consistency and de-cluttering the UI, the former functionality
has been removed from TYPO3 Core, along with the PHP class
constant :php:`\TYPO3\CMS\Domain\Repository\PageRepository::DOKTYPE_RECYCLER`.


Impact
======

The recycler doktype has been removed and cannot be selected or used anymore. Any
existing recycler pages are migrated to a page of type "Backend User Section"
which is also not accessible, if there is no valid backend user with permission
to see this page.


Affected installations
======================

TYPO3 installations using this special page doktype "Recycler".


Migration
=========

A migration is in place, it is recommended to use the :guilabel:`Recycler`
module with soft-deleting records.

.. index:: Backend, PHP-API, PartiallyScanned, ext:core
