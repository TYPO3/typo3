..  include:: /Includes.rst.txt

..  _breaking-111498-1795103999:

===================================================================
Breaking: #111498 - Backend user checks delegate to security voters
===================================================================

See :issue:`111498`

Description
===========

The permission checks of
:php:`\TYPO3\CMS\Core\Authentication\BackendUserAuthentication` ask the
security voters, see :ref:`feature-111237-1795017600`, for every permission
a voter decides on:

*   :php:`check()` for `tables_select` and `tables_modify` of tables with
    TCA, `non_exclude_fields` of fields in the TCA, `explicit_allowdeny`,
    `allowed_languages`, `pagetypes_select`, `mfa_providers` and
    `custom_options`
*   :php:`checkAuthMode()`
*   :php:`checkLanguageAccess()`

The legacy checks and :php:`isGranted()` thus give the same answer, as long
as :php:`$backendUser->groupData` is not changed at runtime, and a voter of
an extension restricts both. :php:`check()` takes such changes into account,
while the principal of the request :php:`isGranted()` decides for keeps the
group data registered at login. :php:`check()` is unchanged for
questions without a subject the voters decide on: tables without TCA,
fields unknown to the TCA, such as exclude fields of FlexForms, and
`modules` and `available_widgets`, as the voters decide on modules and
widgets by their objects only. It is unchanged for any other permission as
well, such as `webmounts` or `file_permissions`.

The permissions the voters decide on are denied without the security
voters, e.g. in the install tool with the failsafe container or in unit
tests without a container, and for a backend user object without a
principal, e.g. without a user record or with an original user of a switch
user session that cannot be resolved. This holds for administrators as well.

The :php:`DataHandler` asks :php:`check()` whether a table may be changed
and whether a page type may be set, so that a voter of an extension restricts
these as well. It grants both to administrators before asking, so that a
voter does not restrict administrators there. The following code still reads :php:`$backendUser->groupData`
directly, so that a voter of an extension does not restrict it yet:

*   :php:`DataHandler`, the list of tables copied or deleted with a page
    (`tables_modify`)
*   :php:`\TYPO3\CMS\Backend\Form\FormDataProvider\AbstractItemProvider`, the
    page types offered (`pagetypes_select`)
*   :php:`\TYPO3\CMS\Backend\Form\FormDataProvider\TcaFlexProcess`, the
    exclude fields of FlexForms (`non_exclude_fields`)
*   :php:`\TYPO3\CMS\Backend\Controller\ContentElement\ElementInformationController`
    (`tables_select`)
*   :php:`\TYPO3\CMS\Backend\Tree\Repository\PageTreeFilter` and
    :php:`\TYPO3\CMS\Linkvalidator\QueryRestrictions\EditableRestriction`
    (`allowed_languages`)
*   :php:`\TYPO3\CMS\Workspaces\Service\GridDataService`
    (`non_exclude_fields`)
*   the recycler (`tables_modify`) and the user information of the backend
    users module

Impact
======

For users who are no administrators, the checks answer as follows where
they answered differently before:

*   :php:`check('tables_select', $table)` and
    :php:`check('tables_modify', $table)` deny tables marked `adminOnly`,
    even if a backend group grants them, including a group added through
    the :php:`AfterGroupsResolvedEvent`.
*   :php:`check('non_exclude_fields', 'table:field')` grants fields that are
    no exclude fields.
*   :php:`check('allowed_languages', $languageId)` grants every language if
    no language is granted, and "all languages" (`-1`) always, as
    :php:`checkLanguageAccess()` does.
*   An empty value, a value listing several items, a value of
    `allowed_languages` or `pagetypes_select` that is no integer and a value
    of `non_exclude_fields` or `explicit_allowdeny` without all of its parts
    never match a grant.
*   :php:`checkAuthMode()` denies a value without table or field, or of a
    table of a sub-schema such as `tt_content.textmedia`, also for
    administrators.
*   A security voter, or an implementation of
    :php:`AdminOnlyTableGrantInterface`, that calls :php:`check()`,
    :php:`checkAuthMode()` or :php:`checkLanguageAccess()` of the backend user
    object for a permission the voters decide on, while the voters decide on
    such a permission of the same object, throws a :php:`\LogicException`
    instead of recursing without end.

For all users, administrators included, the permissions the voters decide
on are denied for a backend user object without a user record and without
the security voters. The :php:`DataHandler` then drops the values of exclude
fields and of the language field without an error.

Changes to :php:`$backendUser->groupData` at runtime are still taken into
account by :php:`check()`.

Affected installations
======================

Installations with extensions that rely on any of the answers listed above,
or that grant tables marked `adminOnly` to users who are no administrators
through :sql:`be_groups.tables_select` or :sql:`be_groups.tables_modify`.

Migration
=========

Check single values only. To grant a table marked `adminOnly` to users who
are no administrators, implement
:php:`\TYPO3\CMS\Core\Security\Authorization\Policy\AdminOnlyTableGrantInterface`,
see :ref:`feature-111237-1795017600`, or remove `adminOnly` from the `ctrl`
section of the TCA of the table. The :php:`DataHandler` keeps writing such
tables for administrators only.

Code that builds a backend user object by hand, e.g. in a command or a test,
authenticates a user with a record instead, e.g. through
:php:`Bootstrap::initializeBackendAuthentication()` in a command, or
:php:`setUpBackendUser()` in a functional test. Unit tests that call the
checks on a real backend user object mock them instead.

A security voter decides on the principal it is given and does not call
:php:`check()`, :php:`checkAuthMode()` or :php:`checkLanguageAccess()` of the
backend user object for a permission the voters decide on.

New code asks the authorization checker instead of the backend user object,
see :ref:`feature-111237-1795017600`.

..  index:: Backend, PHP-API, NotScanned, ext:core
