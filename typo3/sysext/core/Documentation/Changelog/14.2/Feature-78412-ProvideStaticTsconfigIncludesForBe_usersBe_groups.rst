.. include:: /Includes.rst.txt

.. _feature-78412-1719144405:

===========================================================================
Feature: #78412 - Provide static TSconfig includes for be_users & be_groups
===========================================================================

See :issue:`78412`

Description
===========

The tables `be_users` and `be_groups` are each extended by an additional field
that allows static TSconfig to be selected that is defined by extensions. These fields follow the
syntax of the `tsconfig_includes` field in the `pages` table. The following
methods are available:

For backend users:

..  code-block:: php
    :caption: EXT:my_extension/Configuration/TCA/Overrides/be_users.php

    use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;

    ExtensionManagementUtility::registerUserTSConfigFile(
        'extensionKey',
        'Configuration/Tsconfig/Static/example1.tsconfig',
        'Example 1'
    );

For backend user groups:

..  code-block:: php
    :caption:  EXT:my_extension/Configuration/TCA/Overrides/be_groups.php

    use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;

    ExtensionManagementUtility::registerUserGroupTSConfigFile(
        'extensionKey',
        'Configuration/Tsconfig/Static/example2.tsconfig',
        'Example 2'
    );

Impact
======

The new fields can be used to define user TSconfig for specific users and
user groups provided by extensions.

Using this approach, instead of writing TSconfig directly to the database field
of `be_users` or `be_groups`, reduces the amount of configuration stored in
the database. This has several advantages:

*   Running a TYPO3 instance with automated deployment and Git version control
    makes it easy to create or modify such an includable TSconfig snippet via a
    file change. It also helps keep configuration streamlined for multiple
    environments such as staging or production. Once the file is included, you
    can have the same user or group configuration without having to manually change
    the database for each affected user or group.

*   Extension authors can ship predefined user TSconfig files that can be
    included by TYPO3 backend users. This also applies to local (site) packages
    or your agency's base package.

*   Possible breaking changes in major upgrades can be handled automatically
    with tools like TYPO3 Fractor (an enhancement for TYPO3 Rector). If a
    breaking change occurs within user TSconfig, such a tool can automatically
    upgrade the configuration, ensuring that you do not miss occurrences
    in the database. This approach can reduce recurring manual work,
    especially in large TYPO3 instances with many `be_users` or
    `be_groups` records.

*   Searching through user TSconfig stored in the database is now
    possible in your IDE, as all TSconfig resides in your codebase. This can
    improve productivity and simplify major upgrades. You can also go
    further and disable or hide the `TSconfig` database field in projects to
    prevent saving user TSconfig to the database for users or user groups.

.. index:: Backend, TSConfig, ext:core
