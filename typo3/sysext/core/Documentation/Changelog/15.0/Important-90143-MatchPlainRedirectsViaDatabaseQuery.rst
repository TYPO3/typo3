..  include:: /Includes.rst.txt

..  _important-90143-1791470366:

==============================================================
Important: #90143 - Match plain redirects via a database query
==============================================================

See :issue:`90143`

Description
===========

Previously, every frontend request that was checked for a redirect loaded all
redirects of the requested host and of the wildcard host :sql:`*` from a cache
entry, which was built from the complete :sql:`sys_redirect` table. With a large
number of redirects, for example after moving many pages over time, this cache
entry became slow to load, rebuild and unserialize, and could exceed the
available memory.

Redirects without a regular expression are now looked up with an indexed
database query on :sql:`source_host` and :sql:`source_path`. Only redirects
with a regular expression, which need to be evaluated one by one, and the rare
redirects whose source path ends with multiple slashes are still cached per
host.

To avoid this database query for the vast majority of requests, which do not
match any redirect, the cache entry of each host additionally contains a
`Bloom filter <https://en.wikipedia.org/wiki/Bloom_filter>`__ of the source
paths of all other redirects. It needs about 1.2 bytes per redirect and rules
out a match without a database query for about 99 % of the requests which
do not match a redirect. Regular page views therefore do not cause any query on
the :sql:`sys_redirect` table, just as before.

The order in which redirects are matched is unchanged: host specific redirects
before wildcard redirects, redirects respecting query parameters before plain
redirects, plain redirects before regular expressions, and the lowest
:sql:`uid` first.

The internal classes :php-short:`\TYPO3\CMS\Redirects\Service\RedirectCacheService`
and :php-short:`\TYPO3\CMS\Redirects\Service\RedirectService` have been adapted.
:php:`RedirectCacheService::getRedirects()` now only returns the cached
redirects and the filter: the key :php:`respect_query_parameters` has been
removed, and the key :php:`flat` only contains redirects whose source path ends
with multiple slashes. Custom code relying on them needs to query the
:sql:`sys_redirect` table directly or use the PSR-14 event
:php-short:`\TYPO3\CMS\Redirects\Event\BeforeRedirectMatchDomainEvent`.

..  index:: Database, Frontend, ext:redirects
