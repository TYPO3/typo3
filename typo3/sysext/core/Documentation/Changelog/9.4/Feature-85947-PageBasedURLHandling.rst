.. include:: /Includes.rst.txt

=========================================
Feature: #85947 - Page based URL handling
=========================================

See :issue:`85947`

Description
===========

Page records now have a field called "slug" that contains the website
frontend path to the page, like "/team/about-us/". When a page has the
field filled, the URL which the site is linked to, will receive a full URL
to that page, instead of the common :php:`index.php?id=xy` that TYPO3 builds
by default.

When using Site Handling for a page tree, this page-based URL handling is
enabled by default and requires proper URL Rewrite Rules from the server.

The slug field is shown when editing page records in the backend and is
resolved to the page uid in the frontend if a "Site configuration" in the
site module has been set up.

Note: Page-based URL handling only works if a Site configuration
has been set up since otherwise neither the domain nor the language can
be properly resolved which is a requirement to resolve the page path part
of the URL.

Note #2: If a page has the path segment "/team/about-us", but there is no
other page with a path segment "/team/about-us/", then an automatic 307
HTTP redirect to the proper URI is triggered.

Note #3: Since #85900 any request is always connected to either a Site or a
PseudoSite Object, so even if the site configuration is missing, basic values
are available for processing. Nonetheless, it is recommended to provide a proper site
configurations for any site.

Impact
======

Integrators should configure their sites in the Sites module to take advantage
of the core internal page based routing.

.. index:: Backend, Database, Frontend, TCA
