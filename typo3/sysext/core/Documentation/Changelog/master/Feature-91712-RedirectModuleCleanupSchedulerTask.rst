.. include:: ../../Includes.txt

=========================================================================
Feature: #91712 - Redirect Module: cleanup scheduler task and CLI command
=========================================================================

See :issue:`91712`

Description
===========

A new CLI command (which can also run as scheduler task) has been added to cleanup existing redirects periodically under given conditions.

In the scheduler task settings it is possible to set the following options:

- Age of records in days ( query usage: createdon < :age )
- Domain(s) comma separated ( query usage: source_host IN (:domains) )
- Hit Count ( query usage: hitcount < :hitCount )
- Status code(s) comma separated ( query usage: target_statuscode IN (:statusCodes) ) (multiple values allowed)
- Path pattern ( query usage: source_path LIKE :path )

Depended on the settings, the query will look like:

- `protected = 0 AND (hitcount < :hitCount) AND (createdon < :age) AND (source_host IN (:domains))`
- `protected = 0 AND (hitcount < 30) AND (createdon < 123456789) AND (source_host IN ('example.org', 'example.com'))`

.. attention::
   A new boolean flag "protected" has been introduced, which will be added as a pre-condition to all queries.
   This flag can be set for any redirect to prevent deletion in the cleanup process.

For the CLI command, the same options exists:

.. code-block:: sh

   bin/typo3 redirects:cleanup --domains foo.com --domains bar.com --age 90 --hitCount 100 --path "/foo/bar%" --statusCodes 302 --statusCodes 303
   bin/typo3 redirects:cleanup -d foo.com -d bar.com -a 90 -c 100 -p "/foo/bar%" -s 302 -s 303

The options of this command in detail:

- `-d, --domains[=DOMAINS]          Domain(s) comma separated (query usage: source_host IN (:domains) (multiple values allowed)`
- `-s, --statusCodes[=STATUSCODES]  Status code(s) comma separated ( query usage: target_statuscode IN (:statusCodes) ) (multiple values allowed)`
- `-a, --age[=AGE]                  Age of records in days (query usage: createdon < :age [default: 90]`
- `-p, --path[=PATH]                Path pattern ( query usage: source_path LIKE :path )`
- `-c, --hitCount[=HITCOUNT]        Hit Count (query usage: hitcount < :hitCount)`

.. index:: Backend, CLI, Frontend, ext:redirects
