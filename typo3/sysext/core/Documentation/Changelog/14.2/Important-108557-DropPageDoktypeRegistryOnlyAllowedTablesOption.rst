..  include:: /Includes.rst.txt

..  _important-108557-1768612632:

======================================================================
Important: #108557 - Drop PageDoktypeRegistry onlyAllowedTables option
======================================================================

See :issue:`108557`

Description
===========

It is possible to limit which tables are allowed for page types (`doktype`). However, up
until now the default behavior when switching types was to ignore
violations of these rules. The behavior could be changed for a particular `doktype`
like this:

..  code-block:: php
    :caption: EXT:my_extension/ext_tables.php

    use TYPO3\CMS\Core\DataHandling\PageDoktypeRegistry;
    use TYPO3\CMS\Core\Utility\GeneralUtility;

    $dokTypeRegistry = GeneralUtility::makeInstance(PageDoktypeRegistry::class);
    $dokTypeRegistry->add(
        116,
        [
            'onlyAllowedTables' => true,
        ],
    );

This made page type `116` strict when switching the page type.

This option is now obsolete, as this functionality is always enabled.
Switching page types is no longer possible if it violates the configured
allowed tables, which makes the system more consistent.

Some remarks: This option was rarely used and often misunderstood. The option
to configure allowed tables was called `allowedTables`. It was not clear what
`onlyAllowedTables` meant without looking in the documentation.

From a practical point of view, it does not make sense to configure
restrictions for page types when they are ignored by default for the action of
switching types. Allowing the rules to be violated makes them ineffective in
the first place. So either remove those restrictions altogether or make them
always apply, which is what happens now.

..  index:: TCA, ext:core
