.. include:: ../../Includes.txt

=============================================================================
Deprecation: #94902 - Deprecate lowerCamelCase options of EXT:impexp commands
=============================================================================

See :issue:`94902`

Description
===========

The CLI commands :shell:`impexp:export` and :shell:`impexp:import` offered
lowerCamelCased options, while the other TYPO3 Core commands offer lowercase
options only. The lowercase option aliases were introduced in both commands and
the lowerCamelCased options were marked as deprecated and will be removed in
TYPO3 v12.


Impact
======

If the CLI commands :shell:`impexp:export` or :shell:`impexp:import` are
executed with lowerCamelCased options, a PHP :php:`E_USER_DEPRECATED` error is
raised.


Affected Installations
======================

Any TYPO3 installation using lowerCamelCased options with commands
:shell:`impexp:export` or :shell:`impexp:import`.


Migration
=========

Switch to the lower-cased option aliases:

1. :shell:`impexp:export --includeRelated` => :shell:`impexp:export --include-related`
2. :shell:`impexp:export --includeStatic` => :shell:`impexp:export --include-static`
3. :shell:`impexp:export --excludeDisabledRecords` => :shell:`impexp:export --exclude-disabled-records`
4. :shell:`impexp:export --excludeHtmlCss` => :shell:`impexp:export --exclude-html-css`
5. :shell:`impexp:export --saveFilesOutsideExportFile` => :shell:`impexp:export --save-files-outside-export-file`
6. :shell:`impexp:import --updateRecords` => :shell:`impexp:import --update-records`
7. :shell:`impexp:import --ignorePid` => :shell:`impexp:import --ignore-pid`
8. :shell:`impexp:import --forceUid` => :shell:`impexp:import --force-uid`
9. :shell:`impexp:import --importMode` => :shell:`impexp:import --import-mode`
10. :shell:`impexp:import --enableLog` => :shell:`impexp:import --enable-log`

.. index:: CLI, NotScanned, ext:impexp
