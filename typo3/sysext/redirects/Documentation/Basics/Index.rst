.. include:: /Includes.rst.txt

.. _glossary:
.. _basics:

======
Basics
======

This page defines and explains some basics and basic terms which are not
specific to EXT:redirects.

.. todo: add term for t3:// URI (e.g. typo3 URI, linkhandler URI, etc.) after term is clarified
.. see https://forge.typo3.org/issues/95820

Components of a URL
===================

A URL contains the following components:
`scheme://host:port/path?query-parameters#fragment`

Example: https://example.org/path?key=value#c123

If the following terms are used in this documentation, it refers to the parts
of the URL:

-   scheme
-   host
-   path
-   query parameters
-   fragment

.. _http-status-codes:

HTTP status codes
=================

When redirecting, a HTTP status code is sent to the client (usually a browser
or a bot). This status code informs the client
about the type of redirect. We differentiate between a permanent and a temporary
redirect.

For a full list of possible HTTP status codes for redirects (e.g. 301, 302, 307
etc), see https://developer.mozilla.org/en-US/docs/Web/HTTP/Status.

*   `301 <https://developer.mozilla.org/en-US/docs/Web/HTTP/Status/301>`__:
    Moved permanently
*   `302 <https://developer.mozilla.org/en-US/docs/Web/HTTP/Status/302>`__:
    Found
*   `303 <https://developer.mozilla.org/en-US/docs/Web/HTTP/Status/303>`__:
    See other
*   `307 <https://developer.mozilla.org/en-US/docs/Web/HTTP/Status/307>`__:
    Temporary redirect
*   `308 <https://developer.mozilla.org/en-US/docs/Web/HTTP/Status/308>`__:
    Permanent redirect

.. note::

    Which redirect to use for which use cases is beyond the scope of this
    documentation. We give you some pointers here, but information like this
    can be outdated and it is best to read up on this elsewhere.

As rule of thumb:

There are "temporary" and "permanent" redirects. 301 and 308 are "permanent"
redirects.

    Don’t use a 301 if you ever want to use that specific (source) URL ever again.

Source: `6 questions about redirects for SEO (Yoast)
<https://yoast.com/6-questions-about-redirects-for-seo/>`__

    For routine redirect tasks, 301 (permanent redirect) and 307 (temporarily
    redirect) status codes can be used depending on what type of change you
    are implementing on your website.

Source: `A Technical SEO Guide to Redirects (SEJ)
<https://www.searchenginejournal.com/technical-seo/redirects/>`__

For automatically created redirects, it is not recommended to use 301. You can
use 307, which is also the default in the redirects extension. However,
if you create redirects manually, it **may** make sense to use 301 for these.

With permanent redirects (301 and 308) the "link juice" (ranking factor) is
transferred to the redirect target. The search engines are notified this way
that the URL has changed permanently and that they should update their index
accordingly. Thus, from SEO point of view, permanent redirects are often a good
choice. If domains are changed or sites restructured, 301 are often used.

.. _redirect-chain:

Redirect chain
==============

Contrary to the redirects loops, the pages can still be loaded. Redirect
chains are inefficient because a number of redirects must be processed before
the final page is loaded.

Examples for redirect chains:

-   `/a => /b => /c` (it would be more efficient if `/a` redirected to `/c`
    directly and `/b` redirected to `/c`)

.. _redirect-loop:

Redirect loop
=============

A number of one or more redirects which will cause a loop by redirecting back
to the origin. The page can no longer be loaded and a HTTP status code 500 is
usually returned.

Examples for redirect loops:

-   `/a => /a` (source and target for a redirect resolve to the same URL)
-   `/a => /b => /a`

Slug
====

A slug is the part of the URL path specific to the page. The slug is stored as
:sql:`pages.slug` in the database. The slug does not necessarily exactly reflect
the URL path which is used in the URL to access the page. The actual URL may
depend on the entry point configured in the site configuration, additional route
enhancers and decorators.

Example: A slug `/path` is used, the final URL may be
`https://example.org/en/path.html`.



