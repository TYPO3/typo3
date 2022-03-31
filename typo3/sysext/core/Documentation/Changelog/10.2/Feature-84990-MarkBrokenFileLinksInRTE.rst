.. include:: /Includes.rst.txt

===============================================
Feature: #84990 - Mark broken file links in RTE
===============================================

See :issue:`84990`

Description
===========

Links to files that were detected as broken by the system extension
`linkvalidator` are now marked accordingly in the RTE via
:php:`TYPO3\CMS\Core\Html\Event\BrokenLinkAnalysisEvent`.

Those links are now marked with extra markup (yellow background with
red border) in RTE.

The procedure for marking the broken links in the RTE is as follow:

#. RTE content is fetched from the database. Before it is displayed in
   the edit form, RTE transformations are performed.
#. The transformation function parses the text and detects links.
#. For each link, a new PSR-14 event is dispatched.
#. If a listener is attached, it may set the link as broken and will set
   the link as "checked".
#. If a link is detected as broken, RTE will mark it as broken.

The implementation for checking file links is supplied by the system
extension `linkvalidator`.

Other extensions can use the event to override the default behaviour.


Impact
======

The behaviour stays the same as before unless the system extension `linkvalidator`
is installed.

If `linkvalidator` is installed and regularly checks for broken file links, those
links will be marked in the RTE.

If `linkvalidator` is used, it is recommended to use the scheduler to regularly
check for broken links.


.. index:: RTE, ext:linkvalidator
