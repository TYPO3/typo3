.. ==================================================
.. FOR YOUR INFORMATION
.. --------------------------------------------------
.. -*- coding: utf-8 -*- with BOM.

.. include:: ../../Includes.txt



.. _configuration-introduction:

Introduction
^^^^^^^^^^^^

Before the DBAL will do anything different for you than just
connecting to the default database you will have to configure it. By
default it connects using the "native" handler type - which means
direct interaction with MySQL.

Since the DBAL offers to store information in multiple sources and not
just a single database you might have to understand handlers first.

First, some definitions:

- **handler type** - which kind of interface is used for a data handler.
  The options are "native", "adodb" or "userdefined".

  - native - Connects directly to MySQL with hardcoded PHP functions

  - adodb - Is an instance of ADOdb database API offering support for a
    long list of databases other than MySQL. The DBAL extension has been
    developed with a focus on ADOdb until now, so it should work.

  - userdefined - Is an instance of a userdefined class which must contain
    certain functions to supply results from the "database" - offers
    support for just any kind of data source you can program an interface
    to yourself!

- **handlerKey** - a string which uniquely identifies a data handler.
  Each handler represents an instance of a handler type (see above). The
  handlerKey can be any alphanumeric string. The handler key "\_DEFAULT"
  is the default handler for all tables unless otherwise configured.

- **tablename** - the database table name seen from the TYPO3 side in
  the system (might differ from the  *real* database name if mapping is
  enabled!)

