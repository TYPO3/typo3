.. include:: /Includes.rst.txt

.. _important-100925-1686234441:

========================================================================
Important: #100925 - Use dedicated cache for database schema information
========================================================================

See :issue:`100925`

Description
===========

To implement native json database field and TCA `type=json`
support for TYPO3 v12 the need to cache the database schema
information raised due to performance reason.

Using the core cache for schema information comes with
various drawbacks:

#.  There is no way to flush single core cache entries,
    thus the complete core cache needs to be flushed when
    changing the database schema.
#.  The PHP Frontend provides no benefit, when the to be cached
    information has to be serialized anyway.

Therefore, a new cache is introduced that can be flushed
individually after schema updates.

Additionally, some internal steps taken to mitigate some side
effects are reverted. They are no longer needed with the dedicated
cache.

Due to the nature of the chosen cache no database updates,
configuration changes or other steps are needed.

.. index:: Database, ext:core
