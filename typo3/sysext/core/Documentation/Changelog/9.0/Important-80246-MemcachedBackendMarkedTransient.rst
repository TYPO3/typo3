.. include:: /Includes.rst.txt

=====================================================
Important: #80246 - MemcachedBackend marked transient
=====================================================

See :issue:`80246`

Description
===========

The :php:`memcached` cache backend has been marked transient. This has the following effect:

* The backend now supports non-string values (Memcached serializes and compresses data internally, configured in php.ini)
  An Exception is no longer raised if a custom cache frontend attempts to store non-strings in a Memcached backend.
* Unnecessary serialization and unserialization is prevented, slightly improving performance.

There is a single side effect: when used with a VariableFrontend and attempting to store data whose serialized and
compressed representation exceeds the Memcached limit (~1MB), the cache operation fails silently and logs a warning.
The system keeps operating as normal and will log such failures every time it happens.

The side effect only applies to VariableFrontend and only when passing non-string values. When you pass a string bigger
than ~1MB the backend performs chunk-split exactly as before, regardless if string was passed through a VariableFrontend.


.. index:: PHP-API
