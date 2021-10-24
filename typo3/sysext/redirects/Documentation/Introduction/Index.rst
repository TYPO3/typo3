.. include:: /Includes.rst.txt

============
Introduction
============

Over the course of time URLs of existing pages sometimes change. If no
countermeasures are in place, the user will access a no longer existing page
when surfing your site and will then usually end up on an error page.
This is inefficient and impacts the user experience. Many missing pages or 404
/ 410 HTTP status codes in general may negatively affect SEO (search engine
optimization).

Changing URLs can have multiple reasons, sometimes the name of something changes
and the URL should reflect that or pages are restructured on the site.

Redirects act as an important measure to guide users (and bots) to the new
page. This often happens (in the background) without the user noticing it
because the browser will automatically resolve the redirect.
This works similar to a forwarding request when you move and your address
changes, but in this case it is more efficient.

For a better understanding of how redirects work technically read
`MDN Web Docs Redirections in HTTP <https://developer.mozilla.org/en-US/docs/Web/HTTP/Redirections>`__.

For more information about the types of redirects, see
:ref:`HTTP status codes <http-status-codes>`

What does it do?
================

The TYPO3 system extension EXT:redirects handles redirects within a TYPO3 site.

Features:

-   Redirects module in the backend to view and edit existing redirects
-   Manual creation of redirects in the backend. The redirect information is
    stored in database records in the :sql:`sys_redirect` table.
-   Automatic redirect creation on slug changes (based on sites configuration)
-   Console commands to check the integrity and cleanup existing redirects
-   Provides functionality to show information about conflicting redirects in
    system report

.. note::

    EXT:redirects does not handle redirects created via page types "*Link to External
    URL*" (`pages.doktype=3`), "*Shortcut*" (`pages.doktype=4`) or redirects created
    within the web server (e.g. :file:`.htaccess` or web server configuration).

Conventions
===========

Please see the page :ref:`basics` at the end of this document for a general
definition of terms.

When describing parts of the user interface, we use the :guilabel:`gui label`
to mark texts within the UI.

Common names are formatted in *italics* (though this is not used everywhere to
ease readability).

Sometimes the topic of a paragraph is marked in **bold** to ease skimming of
pages for relevant content.
