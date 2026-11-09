:navigation-title: Command line

..  include:: /Includes.rst.txt
..  _command-line:

===========================================
Command line tools provided by EXT:lowlevel
===========================================

..  _command-line-preparations:

Preparations
============

..  warning::
    **THERE IS ABSOLUTELY NO WARRANTY** associated with this script! It is completely on
    your OWN RISK that you run it. It may cause accidental data loss due to software
    bugs or circumstances that it does not know about yet - or data loss might
    happen due to misuse!

**Always** make a complete backup of your website! That means:

*   Dump the complete database to an SQL file. This can usually be done from the
    command line like this::

        mysqldump [database name] -u [database user] -p --add-drop-table > ./mywebsite.sql

*   Save all files in the webroot of your site. This can be achieved  from the
    command line like this::

  	    tar czf ./mywebsite.tgz [webroot directory of your site]

It could be a good idea to run a `myisamchk` on your database just to make sure
MySQL has everything pulled together right.

Something like this will do::

	myisamchk [path_to_mysql_databases]/[database_name]/*.MYI -s -r


..  _command-line-running-script:

Running the script
==================

The "[base command]" is::

	[typo3_site_directory]/typo3/sysext/core/bin/typo3

Try this first. If it all works out you should see a help-screen. Otherwise
there will be instructions about what to do.

You can use the script entirely by following the help screens. However, through
this document you get some idea about the best order of events since they may
affect each other.

For each of the tools in the test you can see a help screen by running::

	[base command] --help [toolkey]

Example with the tool "orphan_records"::

	[typo3_site_directory]/typo3/sysext/core/bin/typo3 --help cleanup:orphanrecords


..  _command-line-running-script-suggested-order-clean:

Suggested order of clean up
---------------------------

The suggested order below assumes that you are interested in running all these
tests. Maybe you are not! So you should check the description of each one and if
there is any of the tests you wish not to run, just leave it out.

It kind of gets simpler that way since the complexity mostly is when you wish to
run all tests successively in which case there is an optimal order that ensures
you don't have to run the tests all over again.

-   `[base command] cleanup:orphanrecords`

    -   As a beginning, get all orphaned records out of the system since you
        probably want to. Since orphan records may keep some missing relations from
        being detected it's a good idea to get them out immediately.

-   `[base command] cleanup:deletedrecords`

    -   Flush deleted records. As a rule of thumb, tools that create deleted records
        should be run before this one so the deleted records they create are also
        flushed (if you like to of course).

-   `[base command] cleanup:missingrelations`

    -   Remove missing relations at this point.
    -   If you get an error like this: "\TYPO3\CMS\Core\Database\ReferenceIndex::setReferenceValue(): ERROR: No reference
        record with hash="132ddb399c0b15593f0d95a58159439f" was found!" just run the test again until no errors occur.
        The reason is that another fixed reference in the same record and field changed the reference index hash. Running
        the test again will find the new hash string which will then work for you.

-   `[base command] cleanup:flexforms`

    -   After the "deleted" tool since we cannot clean-up deleted records and to
        make sure nothing important is cleaned up.


..  _command-line-running-script-nightly-reports-problems:

Nightly reports of problems in the system
-----------------------------------------

If you wish to scan your TYPO3 installations for problems with a cronjob or so, a shell script that outputs a
report could look like this::

	#!/bin/sh
	[typo3_site_directory]/typo3/sysext/core/bin/typo3 cleanup:orphanrecords -vv --dry-run
	[typo3_site_directory]/typo3/sysext/core/bin/typo3 cleanup:deletedrecords -v --dry-run
	[typo3_site_directory]/typo3/sysext/core/bin/typo3 cleanup:missingrelations --update-refindex -vv --dry-run
	[typo3_site_directory]/typo3/sysext/core/bin/typo3 cleanup:flexforms -vv --dry-run


You may wish to change the verbosity level from `-vv` to `-v` as in the case
above, depending on how important you consider the warnings.

The output can then be put into a logfile so the logging system can report
errors.

You might also wish to disable tests like "deleted" which would report deleted
records - something that might not warrant a warning, frankly speaking ...

..  _command-line-running-script-example-script-checking:

Example script for checking your installation
---------------------------------------------

::

    #!/bin/sh
    ./typo3/sysext/core/bin/typo3 cleanup:orphanrecords -vv --dry-run
    ./typo3/sysext/core/bin/typo3 cleanup:deletedrecords -v --dry-run
    ./typo3/sysext/core/bin/typo3 cleanup:missingrelations -vv --dry-run
    ./typo3/sysext/core/bin/typo3 cleanup:flexforms -vv --dry-run


..  _command-line-running-script-example-script-cleaning:

Example script for cleaning your installation
---------------------------------------------

::

    #!/bin/sh
    ./typo3/sysext/core/bin/typo3 cleanup:orphanrecords -vv
    ./typo3/sysext/core/bin/typo3 cleanup:deletedrecords -v
    ./typo3/sysext/core/bin/typo3 cleanup:missingrelations -vv --update-refindex
    ./typo3/sysext/core/bin/typo3 cleanup:flexforms -vv


..  _command-line-inspect:

Inspecting the configuration
============================

`lowlevel:inspect` shows the data of the
:guilabel:`System > Configuration` backend module on the command line,
including providers added by extensions. It only reads and changes nothing.

..  code-block:: bash

    # All providers, grouped as in the backend module
    vendor/bin/typo3 lowlevel:inspect

    # One provider, one argument per key level
    vendor/bin/typo3 lowlevel:inspect tca tt_content columns header
    vendor/bin/typo3 lowlevel:inspect confVars SYS sitename

    # Complete output for scripts
    vendor/bin/typo3 lowlevel:inspect tca --flat | grep renderType
    vendor/bin/typo3 lowlevel:inspect httpMiddlewareStacks frontend --json

    # Translate label references, in English or another installed language
    vendor/bin/typo3 lowlevel:inspect tca tt_content columns header --resolve-labels=de

Tree (default)
    An overview of about 200 lines. Every entry of the first
    level is listed, branches that don't fit are shown as complete JSON on one
    line or as a shortened preview, and a closing hint says what was cut.
    `--depth=<n>` shows a fixed number of levels instead, `--depth=0`
    everything.

`--flat`
    One line per value: the path relative to the selection, which can be
    pasted after the same command, and the value as JSON.

`--json`
    The selected data as JSON.

`--flat` and `--json` are always complete and refused above 1 MB unless
`--no-size-limit` is given. Errors are written to stderr; the command exits
with code 2 for invalid input and with code 1 if a provider cannot be read on
the command line or the output is too large.

Place `--resolve-labels` after the path segments or write
`--resolve-labels=<lang>`, as a following argument is taken as the language.

For filtered or tabular views of single providers there are dedicated
commands, for example `configuration:show`, `site:sets:list` and
`site:show`.
