.. ==================================================
.. FOR YOUR INFORMATION
.. --------------------------------------------------
.. -*- coding: utf-8 -*- with BOM.

.. include:: ../../Includes.txt



.. _sql-standard:

SQL standard
^^^^^^^^^^^^

When the core of TYPO3 including a number of global extensions were
converted to the database wrapper class, ``DatabaseConnection``, it was found that
the usage of SQL in TYPO3 was luckily quite simple and consistently
using the same features. This made it fairly easy to convert the whole
application into using the wrapper functions. But it also meant that
there was a basis for defining a subset of the MySQL features that are
those officially supported by TYPO3.

Yet, this subset is not defined in a document but there exist a class,
``\TYPO3\CMS\Dbal\Database\SqlParser``, which contains parser functions for SQL and
compliance with "TYPO3 sql" is basically defined by whether this class
can parse your SQL without errors. (The debug-options /DBAL debug
backend module from this extension can be helpful to spot non-
compliant SQL.)

This means that TYPO3 now has an official "SQL abstraction language"
based on SQL itself and being a subset of the features that MySQL has.
Contrary to creating SQL code from a homemade abstraction language
there are several advantages in using (a subset of) SQL itself  *as*
the abstraction language:

- We do not re-invent the wheel by imposing a new "SQL abstraction
  language" for programmers - they just use simple SQL.

- MySQL (and compatibles) has "native" support and does not need
  translation (= high speed for our primary database).

- Other databases might need transformation but the overhead can be
  reduced drastically by simply using the right functions in the DBAL -
  optionally. And basically such transformation is what would otherwise
  occur with *any* abstraction language anyways.

- We are able to parse the SQL and validate conformity with the "TYPO3
  SQL standard" defined at any time by ``\TYPO3\CMS\Dbal\Database\SqlParser`` - and we can
  always extend it as need arises.


.. _sql-calls:

SQL calls
"""""""""

The PHP API for MySQL contains a long list of functions. By the
inspection of the TYPO3 core it was found that only quite few of these
were used and subsequently only those has made it into the ``DatabaseConnection``
class (For instance ``mysql()`` was typically used to execute a query and
subsequently the result was traversed by ``mysql_fetch_assoc()`` in 95%
of the cases).

The wrapper functions in the class ``DatabaseConnection`` are now the only
functions that are "allowed" to be used for database connectivity and
it is believed that these functions is sufficient for all TYPO3
extension programmers. By defining such a limited set of functions we
might force some users to change their "habits" of using MySQL/SQL
databases but we believe that this is good in another way since we get
more consistent code across extensions. If it turns out that some new
functions are needed in the wrapper class it must be based on the
strength of arguments.
