..  include:: /Includes.rst.txt

..  _feature-106992-1751621341:

==================================================================
Feature: #106992 - Remember last opened category in record wizards
==================================================================

See :issue:`106992`

Description
===========

Following :issue:`106934`, which introduced the dynamic *Recently used*
category in record wizards, the component has been extended to
store the last selected category. When opening a record wizard,
for example, the wizard to create new content elements, it will now
automatically preselect the category that was last used.

This enhancement improves usability and consistency, especially in
installations with many categories, including those added by third-party
extensions.

Impact
======

Record wizards now automatically preselect the last used category when opened again.

No migration or configuration is required. The feature is enabled by default.

..  index:: Backend, JavaScript, ext:backend
