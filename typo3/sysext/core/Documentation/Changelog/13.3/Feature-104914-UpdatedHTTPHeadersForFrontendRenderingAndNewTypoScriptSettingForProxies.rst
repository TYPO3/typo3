.. include:: /Includes.rst.txt

.. _feature-104914-1726075631:

=====================================================================================================
Feature: #104914 - Updated HTTP Headers for Frontend Rendering and new TypoScript setting for proxies
=====================================================================================================

See :issue:`104914`

Description
===========

In a typical Frontend rendering scenario, TYPO3 sends HTTP Response Headers to
deny caching to clients (= browsers) when e.g. a Frontend User is logged in,
a Backend User's previewing a page, or a non-cacheable plugin is on a page.

When a frontend page is "client-cacheable", TYPO3 does not send any HTTP headers
by default, but only when :typoscript:`config.sendCacheHeaders = 1` is set
via TypoScript.

In this case, TYPO3 sends the following HTTP Headers (example):

    Expires: Thu, 26 Aug 2024 08:52:00 GMT
    ETag: "d41d8cd98f00b204ecs00998ecf8427e"
    Cache-Control: max-age=86400
    Pragma: public

However, in the past, this could lead to problems, because recurring website
users might see outdated content for up to 24 hours (by default) or even longer,
even if other website visitors already see new content, depending on various
cache_timeout settings.

Thus, :typoscript:`config.sendCacheHeaders = 1` should be used with extreme care.

However, this option was also used when a proxy / CDN / shared cache such as
Varnish was put in between TYPO3 / the webserver and the client. The reverse
proxy can then evaluate the HTTP Response Headers sent by TYPO3 Frontend, put
the TYPO3 response from the actual webserver into its "shared cache" and send
a manipulated / adapted response to the client.

However, when working with proxies, it is much more helpful to take load
off of TYPO3 / the webserver by keeping a cached version for a period of
time and answering requests from the client, while still telling the
client to not cache the response inside the browser cache.

This is now achieved with a new option
:typoscript:`config.sendCacheHeadersForSharedCaches = auto`.

With this option enabled, TYPO3 now evaluates if the current TYPO3 Frontend
request is executed behind a Reverse Proxy, and if so, TYPO3 sends the following
HTTP Response Headers at a cached response:

    Expires: Thu, 26 Aug 2024 08:52:00 GMT
    ETag: "d41d8cd98f00b204ecs00998ecf8427e"
    Cache-Control: max-age=0, s-maxage=86400
    Pragma: public

With :typoscript:`config.sendCacheHeadersForSharedCaches = force` the reverse
proxy evaluation can be omitted, which can be used for local webserver internal
caches.

"s-maxage" is a directive to tell shared caches - CDNs and reverse proxies - to keep
a cached version of the HTTP response for a period of time (based on various
cache settings) in their shared cache, while max-age=0 is evaluated at the
client level. See
https://developer.mozilla.org/en-US/docs/Web/HTTP/Headers/Cache-Control for more
details and if your reverse proxy supports this directive.

The new option takes precedence over :typoscript:`config.sendCacheHeaders = 1`
if running behind a reverse proxy.

Impact
======

By utilizing the new TypoScript setting, TYPO3 caches cacheable contents,
and also instructs shared caches such as reverse proxies or CDNs to cache
the HTTP Response, while always delivering fresh content to the client,
if certain routines for cache invalidation are in place. The latter is
typically handled by TYPO3 extensions which hook into the cache invalidation
process of TYPO3 to also invalidate cache entries in the reverse proxies.

In addition, compared to previous TYPO3 versions, client-cacheable HTTP Responses
now send "Cache-Control: private, no-store" if no option applies.

.. index:: Frontend, TypoScript, ext:frontend
