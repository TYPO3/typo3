.. include:: /Includes.rst.txt

=============================================================================================
Important: #79005 - Included missing support for persistent connection in Redis cache backend
=============================================================================================

See :issue:`79005`

Description
===========

phpredis has support for persistent connections, but until now the Redis cache backend had
the regular connect call hard-coded. For unknown reasons this is the only
connection setting missing from the Redis cache backend but has now been implemented.

The configuration setting is named `persistentConnection`. It is an optional boolean option.

For other configuration options see https://docs.typo3.org/typo3cms/CoreApiReference/CachingFramework/FrontendsBackends/Index.html#redis-backend


Impact
======

None. Non-persistent connections remain the default.


.. index:: LocalConfiguration
