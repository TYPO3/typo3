.. include:: ../../Includes.txt

========================================================================
Feature: #93663 - Backend Users preferred UI language stored as DB field
========================================================================

See :issue:`93663`

Description
===========

In previous TYPO3 versions, Administrators could create new backend
users and select from the list of all supported TYPO3-internal
languages for their Backend Language (= all labels from
XLIFF files). This information was stored in the database field
"be_users.lang" and was only used on the first log in of a user
into TYPO3 Backend.

The backend users themselves could use the User settings module
to change the UI language to their preferred language, based on the
available language packs in the system.

This information was then stored in the users' "uc" (user configuration),
an arbitrary settings field.

This approach - built over 18 years ago without any significant
changes - had several downsides:
* The database field "be_users.lang" was not really needed
* Administrators did not see available language packs when changing
* Administrators could only change an Editors' preferred language by switching to the user (Switch User Mode).
* Administrators could not filter / sort editors to see what languages the users had chosen
* Fetching the users' preferred language always meant to fetch the whole "uc" information and unpack it.
* The preferred language was only selected if the user had logged in for the first time to initialize the "uc" values.

Instead, TYPO3 now keeps the current language preference in the
database field "be_users.lang", allowing both editors and administrators
to access the same value for fetching this information.


Impact
======

When the user changes their language in the user settings module,
the database record gets updated, and it is clear where this information is
stored. It is now the same logic when an Administrator updates the Editor's
record via FormEngine.

The value is now always filled, and if English is chosen, the value
is set to "default" (instead of an empty value).

An upgrade wizard migrates existing "uc" values into the database
fields. The "uc" entry "user->uc[lang]" is kept in sync for
backwards-compatibility.

.. index:: Backend, JavaScript, ext:backend
