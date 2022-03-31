.. include:: /Includes.rst.txt

======================================================================
Feature: #91712 - Cleanup scheduler task and CLI command for redirects
======================================================================

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

Depending on the settings, the query will look like:

- :sql:`protected = 0 AND (hitcount < :hitCount) AND (createdon < :age) AND (source_host IN (:domains))`
- :sql:`protected = 0 AND (hitcount < 30) AND (createdon < 123456789) AND (source_host IN ('example.org', 'example.com'))`

.. tip::

   A new boolean flag "protected" has been introduced, which will be added as a pre-condition to all queries.
   This flag can be set for any redirect to prevent deletion in the cleanup process.

For the CLI command, the same options exist:

- :bash:`bin/typo3 redirects:cleanup --domain foo.com --domain bar.com --age 90 --hitCount 100 --path "/foo/bar%" --statusCode 302 --statusCode 303`
- :bash:`bin/typo3 redirects:cleanup -d foo.com -d bar.com -a 90 -c 100 -p "/foo/bar%" -s 302 -s 303`

The options of this command in detail:

-  `-d, --domain[=DOMAIN]          Cleanup redirects matching provided domain(s) (multiple values allowed)`
-  `-s, --statusCode[=STATUSCODE]  Cleanup redirects matching provided status code(s) (multiple values allowed)`
-  `-a, --days[=DAYS]              Cleanup redirects older than provided number of days`
-  `-c, --hitCount[=HITCOUNT]      Cleanup redirects matching hit counts lower than given number`
-  `-p, --path[=PATH]              Cleanup redirects matching given path (as database like expression)>`

.. index:: Backend, CLI, Frontend, ext:redirects
