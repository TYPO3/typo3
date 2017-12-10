.. include:: ../../Includes.txt

=============================================================
Deprecation: #81460 - Deprecate getByTag() on cache frontends
=============================================================

See :issue:`81460`

Description
===========

The method :php:`getByTag($tag)` on :php:`TYPO3\CMS\Core\Cache\Frontend\FrontendInterface` and all implementations have been
deprecated with no alternative planned. This is done because the concept of cache tags were originally designed for
invalidation purposes, not for identification and retrieval.

Cache frontends still support the much more efficient :php:`flushByTag` and :php:`flushByTags` methods to perform invalidation
by tag, rather than use the deprecated method to retrieve a list of identifiers and removing each.


Impact
======

Calling this method on any TYPO3 provided cache frontend implementations triggers a deprecation log entry, with the
exception of :php:`StringFrontend` which has itself been deprecated in a separate patch.


Affected Installations
======================

Avoid usage of the method - if necessary, use the same cache to store a list of identifiers for each tag.


Migration
=========

Where possible, switch to :php:`flushByTag` or :php:`flushByTags`. In cases where you depend on getting identifiers by tag,
reconsider your business logic - and if necessary, keep track of which identifiers use a given tag, using a separate
list that you for example store in the cache alongside the usual cached entries.

.. index:: PHP-API, FullyScanned
