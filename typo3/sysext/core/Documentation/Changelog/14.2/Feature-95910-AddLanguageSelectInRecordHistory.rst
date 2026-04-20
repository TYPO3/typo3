..  include:: /Includes.rst.txt

..  _feature-95910-1754604644:

=========================================================================
Feature: #95910 - Add a language selector in the record history/undo view
=========================================================================

See :issue:`95910`

Description
===========

The backend view for listing the history or audit trail of a record and for
undo or rollback functionality has been enhanced by adding a language selector
to give editors the ability to switch between different translations of a record.

The new language selection dropdown is shown only if the record is language-
aware. Available languages are determined by the translations and a
user's `allowed_languages` as defined by their groups.

Impact
======

The history/undo view of a translated record can now be accessed from the page
tree and other places where a context menu is available, not just from the
:guilabel:`Content > Records` module and the :guilabel:`Content > Layout` module language comparison view.

..  index:: Backend, ext:backend
