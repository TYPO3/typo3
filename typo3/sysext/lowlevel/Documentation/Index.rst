.. include:: /Includes.rst.txt

===============
TYPO3 Low Level
===============

:Extension key:
   lowlevel

:Package name:
   typo3/cms-lowlevel

:Version:
   |release|

:Language:
   en

:Author:
   TYPO3 contributors

:License:
   This document is published under the
   `Creative Commons BY 4.0 <https://creativecommons.org/licenses/by/4.0/>`__
   license.

:Rendered:
   |today|

----

The Low Level extension provides command line scripts for technical analysis of
the system. This includes raw database search, checking relations, counting
pages and records.

----

**Table of Contents:**

.. contents::
   :backlinks: top
   :depth: 2
   :local:


Introduction
============

For various reasons your TYPO3 installation may over time accumulate data with
integrity problems or data you wish to delete completely. For instance, why keep
old versions of published content? Keep that in your backup - don't load your
running website with that overhead!

Or what about deleted records? Why not flush them - they also fill up your
database and filesystem and most likely you can rely on your backups in case of
an emergency recovery?

Also, relations between records and files inside TYPO3 may be lost over time for
various reasons.

If your website runs as it should such "integrity problems" are mostly easy to
automatically repair by simply removing the references pointing to a missing
record or file.

However, it might also be "soft references" from eg. internal links
(`<a href="t3://page?id=123">...</a>`) or a file references in a TypoScript
template (`something.file = fileadmin/template/miss_me.jpg`) which are missing.
Those cannot be automatically repaired but the cleanup script incorporates
warnings that will tell you about these problems if they exist and you can
manually fix them.

These scripts provides solutions to these problems by offering an array of tools
that can analyze your TYPO3 installation for various problems and in some cases
offer fixes for them.


Preparations
============

THERE IS ABSOLUTELY NO WARRANTY associated with this script! It is completely on
your OWN RISK that you run it. It may cause accidental data loss due to software
bugs or circumstances that it does not know about yet - or data loss might
happen due to misuse!

ALWAYS make a complete backup of your website! That means:

*  Dump the complete database to an SQL file. This can usually be done from the
   command line like this::

      mysqldump <database name> -u <database user> -p --add-drop-table > ./mywebsite.sql

*  Save all files in the webroot of your site. I usually do this from the
   command line like this::

      tar czf ./mywebsite.tgz <webroot directory of your site>

It could be a good idea to run a `myisamchk` on your database just to make sure
MySQL has everything pulled together right.

Something like this will do::

   myisamchk <path_to_mysql_databases>/<database_name>/*.MYI -s -r


Running the script
==================

The "<base command>" is::

   [typo3_site_directory]/typo3/sysext/core/bin/typo3

Try this first. If it all works out you should see a help-screen. Otherwise
there will be instructions about what to do.

You can use the script entirely by following the help screens. However, through
this document you get some idea about the best order of events since they may
affect each other.

For each of the tools in the test you can see a help screen by running::

   <base command> --help <toolkey>

Example with the tool "orphan_records"::

   <typo3_site_directory>/typo3/sysext/core/bin/typo3 --help cleanup:orphanrecords

Suggested order of clean up
---------------------------

The suggested order below assumes that you are interested in running all these
tests. Maybe you are not! So you should check the description of each one and if
there is any of the tests you wish not to run, just leave it out.

It kind of gets simpler that way since the complexity mostly is when you wish to
run all tests successively in which case there is an optimal order that ensures
you don't have to run the tests all over again.

- `<base command> cleanup:orphanrecords`

  - As a beginning, get all orphaned records out of the system since you
    probably want to. Since orphan records may keep some missing relations from
    being detected it's a good idea to get them out immediately.

- `<base command> cleanup:multiplereferencedfiles`

  - Fix any files referenced twice or more before you delete records (which
    could potentially delete a file that is referenced by another file).

- `<base command> cleanup:deletedrecords`

  - Flush deleted records. As a rule of thumb, tools that create deleted records
    should be run before this one so the deleted records they create are also
    flushed (if you like to of course)

- `<base command> cleanup:missingrelations`

  - Remove missing relations at this point.
  - If you get an error like this::

       \TYPO3\CMS\Core\Database\ReferenceIndex::setReferenceValue(): ERROR: No
       reference record with hash="132ddb399c0b15593f0d95a58159439f" was found!

    just run the test again until no errors occur. The reason is that another
    fixed reference in the same record and field changed the reference index
    hash. Running the test again will find the new hash string which will then
    work for you.

- `<base command> cleanup:flexforms`

  - After the "deleted" tool since we cannot clean-up deleted records and to
    make sure nothing unimportant is cleaned up.

Executed anytime
----------------

These can be executed anytime, however you should wait till all deleted records
and versions are flushed so you don't waste system resources on fixing deleted
records.

::

	<base command> cleanup:missingfiles
	<base command> cleanup:lostfiles

Nightly reports of problems in the system
-----------------------------------------

If you wish to scan your TYPO3 installations for problems with a cronjob or so,
a shell script that outputs a report could look like this::

	#!/bin/sh
	[typo3_site_directory]/typo3/sysext/core/bin/typo3 cleanup:orphanrecords -vv --dry-run
	[typo3_site_directory]/typo3/sysext/core/bin/typo3 cleanup:multiplereferencedfiles --update-refindex -vv --dry-run
	[typo3_site_directory]/typo3/sysext/core/bin/typo3 cleanup:deletedrecords -v --dry-run
	[typo3_site_directory]/typo3/sysext/core/bin/typo3 cleanup:missingrelations --update-refindex -vv --dry-run
	[typo3_site_directory]/typo3/sysext/core/bin/typo3 cleanup:flexforms -vv --dry-run
	[typo3_site_directory]/typo3/sysext/core/bin/typo3 cleanup:missingfiles --update-refindex -vv --dry-run
	[typo3_site_directory]/typo3/sysext/core/bin/typo3 cleanup:lostfiles --update-refindex -vv --dry-run

You may wish to change the verbosity level from `-vv` to `-v` as in the case
above, depending on how important you consider the warnings.

The output can then be put into a logfile so the logging system can report
errors.

You might also wish to disable tests like "deleted" which would report deleted
records - something that might not warrant a warning, frankly speaking...

Example script for checking your installation
---------------------------------------------

::

    #!/bin/sh
    ./typo3/sysext/core/bin/typo3 cleanup:orphanrecords -vv --dry-run
    ./typo3/sysext/core/bin/typo3 cleanup:multiplereferencedfiles -vv --dry-run
    ./typo3/sysext/core/bin/typo3 cleanup:deletedrecords -v --dry-run
    ./typo3/sysext/core/bin/typo3 cleanup:missingrelations -vv --dry-run
    ./typo3/sysext/core/bin/typo3 cleanup:flexforms -vv --dry-run
    ./typo3/sysext/core/bin/typo3 cleanup:missingfiles -vv --dry-run
    ./typo3/sysext/core/bin/typo3 cleanup:lostfiles -vv --dry-run


Example script for cleaning your installation
---------------------------------------------

::

    #!/bin/sh
    ./typo3/sysext/core/bin/typo3 cleanup:orphanrecords -vv
    ./typo3/sysext/core/bin/typo3 cleanup:multiplereferencedfiles -vv --update-refindex
    ./typo3/sysext/core/bin/typo3 cleanup:deletedrecords -v
    ./typo3/sysext/core/bin/typo3 cleanup:missingrelations -vv --update-refindex
    ./typo3/sysext/core/bin/typo3 cleanup:flexforms -vv
    ./typo3/sysext/core/bin/typo3 cleanup:missingfiles --update-refindex
    ./typo3/sysext/core/bin/typo3 cleanup:lostfiles -vv --update-refindex
