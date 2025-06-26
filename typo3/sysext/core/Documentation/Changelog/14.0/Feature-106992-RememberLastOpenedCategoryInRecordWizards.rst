..  include:: /Includes.rst.txt

..  _feature-106992-1751621341:

==================================================================
Feature: #106992 - Remember last opened category in record wizards
==================================================================

See :issue:`106992`

Description
===========

Following :issue:`106934`, which introduced the dynamic "Recently Used"
category in record wizards, the component has been extended to
also store the last selected category. When opening a record wizard,
for example the wizard to create new content elements, it will now
automatically preselect the category you last used.

This enhancement improves the user experience, especially as more
categories, including those from third-party extensions, are added.


Impact
======

Record wizards do now automatically select the last used category.

No migration or configuration is required. The behavior is enabled by default.

..  index:: Backend, JavaScript, ext:backend
