.. include:: /Includes.rst.txt

========================================================
Feature: #83460 - Hide restricted columns in page module
========================================================

See :issue:`83460`

Description
===========

In order to get a cleaner page layout view for backend users, an option to hide the restricted columns in page module
has been introduced.

When restricting a list of columns to the user, the restricted columns are rendered with a message that the user has no
access to these columns which might be undesired in certain cases (imagine a user having access to only one of 20
columns total).

With assigning the following setting to the UserTS, these columns are hidden and the user will only see the columns they
are allowed to edit or add content to:

`mod.web_layout.hideRestrictedCols = 1`

If you use backend layouts to provide an abstract view of the frontend, hiding the columns with this setting **will**
break your layout, so handle it with care.


.. index:: Backend
