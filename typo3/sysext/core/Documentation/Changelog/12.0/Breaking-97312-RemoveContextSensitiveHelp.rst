.. include:: /Includes.rst.txt

.. _breaking-97312:

================================================
Breaking: #97312 - Remove context sensitive help
================================================

See :issue:`97312`

Description
===========

The arguments for removing context sensitive help were:

* The help was not really context sensitive, it only relied on tablename
  and fieldname, if a field was used for different purposes in different
  content types, the CSH always showed the same help
* There was outdated information in CSH (e.g. Screenshots form TYPO3 4.x)
  and nobody is available to update the information
* Some CSH descriptions explained the same content with different words
  which is confusing (e.g. tt_content - CType > Title: "Type" > CSH
  Tooltip: "Select the kind of Page Content this element represents.
  New options will appear when you save the record.")
* Many CSH texts did not provide useful additional information
  (e.g. tt_content - header > Title: "Header" > CSH Tooltip:
  "Enter header text for the Content Element.")
* The available online documentation https://docs.typo3.org/ improved
  a lot and helps better than the CSH in most cases, as it is up to date
* CSH was hidden for most users, as it was only available by clicking on
  a label (no hint that help was available without hovering the label by
  mouse, not available for keyboard users)
* `description` is available for explanations when they are required.
  Adding relevant information as `description` will help everyone as it is
  visible.
* The removal was already proposed in 2019 (see
  https://decisions.typo3.org/t/drop-context-sensitive-help-in-core/511)
  and most arguments against removal can be solved using the `description`
  or by linking to the official documentation
* Removal of CSH also removed a lot of outdated files (and results in
  smaller footprint of the TYPO3 Core package)

The route `help_cshmanual_popup` has been removed.

Help buttons :php:`Components\Buttons\Action\HelpButton` only return an
empty string and trigger a deprecation warning.

The CSH descriptions are not loaded any longer for tables.

All labels are adjusted to not contain :html:`<abbr>` tags inside any longer.

The method :php:`cshItem()` of
:php:`TYPO3\CMS\Backend\Utility\BackendUtility` always returns an empty
string and triggers a deprecation warning.

The TYPO3 Manual menu item has been removed and a link to the
TYPO3 Online Documentation has been added to the menu.

The backend display related TCA option
:php:`$GLOBALS['TCA'][my_table]['interface']['always_description']`
is not evaluated anymore.

Impact
======

The context sensitive help is removed completely and only loading help
items for SelectCheckboxElements is still supported.

Affected Installations
======================

All installations that use CSH for own fields.

Migration
=========

Important CSH texts need to be migrated to a TCA :php:`description`, to
make the information available for all users.

An example for a TCA description is the :php:`protected` column in the
:php:`sys_redirect` TCA.

The TCA option :php:`['interface']['always_description']` can be removed from
any TCA definition.

.. index:: Backend, NotScanned, ext:core
