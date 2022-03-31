.. include:: /Includes.rst.txt

=============================================================================
Deprecation: #94902 - Deprecate lowerCamelCase options of EXT:impexp commands
=============================================================================

See :issue:`94902`

Description
===========

The CLI commands :bash:`impexp:export` and :bash:`impexp:import` offered
lowerCamelCased options, while the other TYPO3 Core commands offer lowercase
options only. The lowercase option aliases were introduced in both commands and
the lowerCamelCased options were marked as deprecated and will be removed in
TYPO3 v12.


Impact
======

If the CLI commands :bash:`impexp:export` or :bash:`impexp:import` are
executed with lowerCamelCased options, a PHP :php:`E_USER_DEPRECATED` error is
raised.


Affected Installations
======================

Any TYPO3 installation using lowerCamelCased options with commands
:bash:`impexp:export` or :bash:`impexp:import`.


Migration
=========

Switch to the lower-cased option aliases:

1. :bash:`impexp:export --includeRelated` => :bash:`impexp:export --include-related`
2. :bash:`impexp:export --includeStatic` => :bash:`impexp:export --include-static`
3. :bash:`impexp:export --excludeDisabledRecords` => :bash:`impexp:export --exclude-disabled-records`
4. :bash:`impexp:export --excludeHtmlCss` => :bash:`impexp:export --exclude-html-css`
5. :bash:`impexp:export --saveFilesOutsideExportFile` => :bash:`impexp:export --save-files-outside-export-file`
6. :bash:`impexp:import --updateRecords` => :bash:`impexp:import --update-records`
7. :bash:`impexp:import --ignorePid` => :bash:`impexp:import --ignore-pid`
8. :bash:`impexp:import --forceUid` => :bash:`impexp:import --force-uid`
9. :bash:`impexp:import --importMode` => :bash:`impexp:import --import-mode`
10. :bash:`impexp:import --enableLog` => :bash:`impexp:import --enable-log`

.. index:: CLI, NotScanned, ext:impexp
