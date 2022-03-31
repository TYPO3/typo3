.. include:: /Includes.rst.txt
.. highlight:: yaml

=========================================
Feature: #86214 - Implement static routes
=========================================

See :issue:`86214`

Description
===========

The site configuration module now has configuration options to add static routes on a per site basis.
Take the following example: In a multi-site installation you want to have different :file:`robots.txt` files for each site that
should be reachable at ``/robots.txt`` on each site. You can now add a static route "robots.txt" to your site and
define which content should be delivered.

The TYPO3 SEO extension provides a sitemap for TYPO3 out of the box, but it's only reachable at a specific page type.
To enable easier access you can now configure a static route :file:`sitemap.xml` that maps to that page type (see example
below).

Routes can be configured as toplevel files (as in the :file:`sitemap.xml` and :file:`robots.txt` case) but may also be configured
to deeper route paths (`my/deep/path/to/a/static/text` for example). Matching is done on the full path but without any
parameters.

Impact
======

Static routes can be configured via the user interface or directly in the yaml configuration.
There are two options: deliver static text or resolve a TYPO3 URL.

StaticText
----------

The :yaml:`staticText` option allows to deliver simple text content. The text can be added through a text field directly in
the site configuration. This is suitable for files like :file:`robots.txt` or :file:`humans.txt`.

YAML Configuration Example::

   route: robots.txt
   type: staticText
   content: |
     Sitemap: https://example.com/sitemap.xml
     User-agent: *
     Allow: /
     Disallow: /forbidden/

TYPO3 URL (t3://)
-----------------

The type :yaml:`uri` for TYPO3 URL provides the option to render either a file, page or url. Internally a request to the
file or URL is done and its content delivered.

YAML Configuration Examples::

   -
     route: sitemap.xml
     type: uri
     source: 't3://page?uid=1&type=1533906435'
   -
     route: favicon.ico
     type: uri
     source: 't3://file?uid=77'


Implementation
==============

Static route resolving is implemented as a PSR-15 middleware. If the route path requested matches any one of the
configured site routes, a response is directly generated and returned. This way there is minimal bootstrap code to
be executed on a static route resolving request, mainly the site configuration needs to be loaded. Static routes cannot
get parameters as the matching is done solely on the path level.


.. index:: Frontend, ext:frontend
