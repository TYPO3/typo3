.. include:: ../../Includes.txt

=============================================================================================
Important: #79005 - Included missing support for persistent connection in Redis cache backend
=============================================================================================

See :issue:`79005`

Description
===========

phpredis has support for persistent connections, but currently the Redis cache backend has hard-coded
the regular connect call. For unknown reasons - possibly a simple oversight - this is the only
connection setting missing from the Redis cache backend is now implemented.

The configuration setting is named ``persistentConnection``. It is an optional boolean option.

For other configuration options see https://docs.typo3.org/typo3cms/CoreApiReference/CachingFramework/FrontendsBackends/Index.html#redis-backend

Impact
======

None. Non-persistent connections remain the default.


.. index:: LocalConfiguration