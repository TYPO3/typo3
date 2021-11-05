.. include:: /Includes.rst.txt

============
Introduction
============

During the lifetime of a website, the URLs of pages often change. If no
countermeasures are in place, users will attempt to access pages that no
longer exist when browsing your site. Typically, when this occurs an error page is returned.
This is inefficient and impacts the user experience. When multiple missing pages or 404
/ 410 HTTP status codes are returned, the overall SEO ranking is negatively affected.

Changing URLs can have multiple reasons, sometimes the name of something changes
and the URL should reflect that or pages are restructured on the site.

There are many reasons as to why URLs are changed. This can include a restructure
of the site's pages and also occurs when the name of a page is changed.
and the URL in turn changes as well to reflect this.

HTTP redirects act as an important measure to guide users (and bots) to new
pages. This often happens in the background without the user noticing it
because the browser will automatically resolve the redirect.
This works similar to a forwarding request when you move house and your address
changes.

For more technical information on how redirects work, visit
`MDN Web Docs Redirections in HTTP <https://developer.mozilla.org/en-US/docs/Web/HTTP/Redirections>`__.

For more information about the types of redirects, see
:ref:`HTTP status codes <http-status-codes>`

What does it do?
================

The TYPO3 system extension EXT:redirects handles redirects within a TYPO3 site.

Features:

-   Manually create redirects in the backend. The redirect information is
    stored in the :sql:`sys_redirect` table.
-   View and edit existing redirect records in the redirects backend module.
-   Automatic redirect creation on slug changes (based on site configuration).
-   Console commands to check the integrity and cleanup existing redirects.
-   System reports that display information about any conflicting redirects.

.. note::

    EXT:redirects does not handle redirects created via page types "*Link to External
    URL*" (`pages.doktype=3`), "*Shortcut*" (`pages.doktype=4`) or redirects created
    within the web server (e.g. :file:`.htaccess` or web server configuration).

Conventions
===========

Visit the :ref:`basics` page found at the end of this document for a general
definition of terms.

When describing parts of the user interface, we use the :guilabel:`gui label`
to mark texts within the UI.

Common names are formatted in *italics* (though this is not used everywhere to
ease readability).

Sometimes the topic of a paragraph is marked in **bold** to ease skimming of
pages for relevant content.
