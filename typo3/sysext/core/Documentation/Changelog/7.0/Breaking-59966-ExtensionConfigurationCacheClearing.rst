
.. include:: ../../Includes.txt

=================================================================
Breaking: #59966 - Extension Configuration cache-flushing changed
=================================================================

See :issue:`59966`

Description
===========

On saving the configuration of an extension, the system cache group has been flushed.
This is inefficient as this includes also the classes cache, but most changes will
never need this cache to be cleared.
We optimize this for the common case and stop flushing caches after configuration changes completely.

Impact
======

Extensions which relied on cache-clearing after configuration changes may require a manual cache flush.

Affected installations
======================

Any installation that uses extensions relying on automatic cache flush after extension configuration changes.

Migration
=========

Extensions requiring a cache flush after configuration changes need to implement a slot
for the `afterExtensionConfigurationWrite` signal which allows individual cache flush actions.
