..  include:: /Includes.rst.txt

..  _deprecation-108557-1768610680:

===================================================================
Deprecation: #108557 - TCA option allowedRecordTypes for Page Types
===================================================================

See :issue:`108557`

Description
===========

The following methods of :php:`TYPO3\CMS\Core\DataHandling\PageDoktypeRegistry`
have been marked as deprecated:

* :php:`PageDoktypeRegistry->add()`
* :php:`PageDoktypeRegistry->addAllowedRecordTypes()`
* :php:`PageDoktypeRegistry->doesDoktypeOnlyAllowSpecifiedRecordTypes()`


Impact
======

Calling any of the above methods will trigger a deprecation-level log
entry and result in a fatal PHP error in TYPO3 v15.0.


Affected installations
======================

All installations using the :php-short:`TYPO3\CMS\Core\DataHandling\PageDoktypeRegistry`
to configure page types using the :php:`add()` method. Also, in some rare cases, using the
methods :php:`addAllowedRecordTypes()` or
:php:`doesDoktypeOnlyAllowSpecifiedRecordTypes()`.


Migration
=========

A new TCA option is introduced to configure allowed record types for pages:

Before:

.. code-block:: php
    :caption: EXT:my_extension/ext_tables.php

    use TYPO3\CMS\Core\DataHandling\PageDoktypeRegistry;
    use TYPO3\CMS\Core\Utility\GeneralUtility;

    $dokTypeRegistry = GeneralUtility::makeInstance(PageDoktypeRegistry::class);
    $dokTypeRegistry->add(
        116,
        [
            'allowedTables' => '*',
        ],
    );

After:

.. code-block:: php
    :caption: EXT:my_extension/Configuration/TCA/Overrides/pages.php

    $GLOBALS['TCA']['pages']['types']['116']['allowedRecordTypes'] = ['*'];

The array can contain a list of table names or a single entry with an asterisk `*`
to allow all types. If no second argument was provided to the :php:`add()` method,
then the specific configuration can be omitted, as it will fall back to the
default allowed records.

Also, note that Page Types are registered by TCA types. The former usage of
:php-short:`TYPO3\CMS\Core\DataHandling\PageDoktypeRegistry` was only useful
to define allowed record types different to the default.

The option `allowedRecordType` is only evaluated in the "pages" table.

..  index:: TCA, PartiallyScanned, ext:core
