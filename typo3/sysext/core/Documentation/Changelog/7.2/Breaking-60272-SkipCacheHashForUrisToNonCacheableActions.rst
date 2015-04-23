====================================================================
Breaking: #60272 - Skip cache hash for URIs to non-cacheable actions
====================================================================

Description
===========

The cache hash (cHash) parameter is not added to action URIs if the current
request is not cached and the target action is not cacheable.


Impact
======

Less cache entries are generated per page and not every action URI will have
a cHash argument any more. It might be necessary to clear caches of extensions
generating human readable URLs like RealURL.


Affected installations
======================

Extbase extensions that generate links from uncached actions/pages to not
cacheable actions.
