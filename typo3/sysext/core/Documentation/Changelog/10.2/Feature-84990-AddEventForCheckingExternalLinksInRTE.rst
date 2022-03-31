.. include:: /Includes.rst.txt

==============================================================
Feature: #84990 - Add event for checking external links in RTE
==============================================================

See :issue:`84990`

Description
===========

A new PSR-14-based event :php:`TYPO3\CMS\Core\Html\Event\BrokenLinkAnalysisEvent`
can be used to get information about broken links set in the rich text editor (RTE).

Up until now, TYPO3 only displayed page links with extra markup (yellow background with red
border) if a link would point to an internal page which does not exist anymore.

This was previously checked internally in RTE, and is now moved to an Event Listener to make
this check more flexible and interchangeable.

The procedure for marking the broken links in the RTE is as follow:

#. RTE content is fetched from the database. Before it is displayed in
   the edit form, RTE transformations are performed.
#. The transformation function parses the text and detects links.
#. For each link, a new PSR-14 event is dispatched.
#. If a listener is attached, it may set the link as broken and will set
   the link as "checked".
#. If a link is detected as broken, RTE will mark it as broken.

An implementation for external and page links is now supplied by the system
extension linkvalidator. External links are currently checked using the existing
`tx_linkvalidator_links` table.

Other extensions can use the event to override the default behaviour.


Impact
======

The behaviour for page links stays the same - they are also marked if
they do not exist anymore - however, marking the links is now unified and only available
when the system extension `linkvalidator` is installed.

If linkvalidator is installed and regularly crawls for broken links, broken external links
will be marked as well.

If linkvalidator is used, it is recommended to use the scheduler to regularly crawl for broken links.


.. index:: RTE, ext:linkvalidator
