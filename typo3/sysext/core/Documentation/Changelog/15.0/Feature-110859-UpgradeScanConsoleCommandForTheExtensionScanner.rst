..  include:: /Includes.rst.txt

..  _feature-110859-1790541000:

===========================================================================
Feature: #110859 - Extension scanner available as "upgrade:scan" on the CLI
===========================================================================

See :issue:`110859`

Description
===========

The extension scanner of the install tool is now available on the command line
as :bash:`vendor/bin/typo3 upgrade:scan`. It reports the same findings as the
"Scan Extension Files" card of the *Upgrade* module, for the same set of
extensions, against the rule set of the installed Core version.

..  code-block:: bash

    # All extensions that are not part of the Core
    vendor/bin/typo3 upgrade:scan

    # Named extensions only
    vendor/bin/typo3 upgrade:scan my_extension my_other_extension

    # Machine readable
    vendor/bin/typo3 upgrade:scan --json

    # Also list the changelog files each finding refers to
    vendor/bin/typo3 upgrade:scan -v

Extension keys and option values can be completed with the shell completion of
the Symfony console.

Findings carry an indicator. A :bash:`strong` finding is reliable, a false
positive is unlikely. A :bash:`weak` finding is a probable one, usually a bare
method or property name that could not be resolved to a class, and it may well
be a false positive. Reports that add both numbers up overstate the work ahead,
which is why the two are always shown separately.

Limiting findings to one indicator
----------------------------------

:bash:`--indicator` lists only the findings with that indicator; without it,
both are listed. The summary counts and the exit code always consider all
findings, so a run can fail because of findings that are not listed; the table
then says so.

..  code-block:: bash

    # Reliable findings only
    vendor/bin/typo3 upgrade:scan --indicator=strong

    # Probable findings only
    vendor/bin/typo3 upgrade:scan --indicator=weak

Files the parser could not read are listed whatever the indicator.

Exit code
---------

By default only strong findings, and files the parser could not read, make the
exit code non-zero:

..  code-block:: bash

    # Default: strong findings and unparsable files fail
    vendor/bin/typo3 upgrade:scan --fail-on=strong

    # Any finding fails, weak ones included
    vendor/bin/typo3 upgrade:scan --fail-on=any

    # Report only, always exit 0
    vendor/bin/typo3 upgrade:scan --fail-on=none

A file the parser could not read has not been cleared, so it counts against the
run: otherwise an extension nobody could look at would report all clear.

JSON output
-----------

:bash:`--json` writes one object to standard output. It carries the Core
version the rule set came from, because a scan result without it can not be
interpreted later. :bash:`results` holds only files with findings that match
:bash:`--indicator`, or a parse error, each with the key of its extension;
:bash:`summary` counts all files and findings, unfiltered.

..  code-block:: json

    {
        "typo3Version": "15.0.0-dev",
        "indicator": null,
        "summary": {
            "filesScanned": 288,
            "filesWithHits": 2,
            "filesIgnored": 0,
            "filesWithParseErrors": 0,
            "hits": {
                "total": 3,
                "byIndicator": { "strong": 1, "weak": 2 }
            },
            "note": "Static analysis only - dynamically composed calls or runtime class names are out of scope."
        },
        "results": [
            {
                "extensionKey": "my_extension",
                "file": "/var/www/html/packages/my_extension/Classes/Foo.php",
                "isFileIgnored": false,
                "effectiveCodeLines": 42,
                "ignoredLines": 0,
                "parseError": null,
                "hits": [
                    {
                        "line": 12,
                        "indicator": "strong",
                        "matcher": "ClassNameMatcher",
                        "message": "Usage of class \"RemoveXSS\"",
                        "restFiles": ["Breaking-80700-DeprecatedFunctionalityRemoved.rst"]
                    }
                ]
            }
        ]
    }

Impact
======

Upgrade preparation can run in continuous integration without clicking through
the install tool. Because the command lives in EXT:install, it is unavailable in
installations that removed :composer:`typo3/cms-install` from production — the
other :bash:`upgrade:*` commands ship with EXT:core and remain available there.

..  index:: CLI, ext:install
