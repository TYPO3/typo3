.. include:: ../../Includes.txt

===========================================================================================
Feature: #90505 - Allow PageContentErrorHandler to resolve internal pages with sub requests
===========================================================================================

See :issue:`90505`

Description
===========

The PageContentErrorHandler provided by the core can take in either a URL or a page uid for resolving an error page in the frontend. In both cases, the class would then start a Guzzle/cURL request to fetch the error page content.
This has now been changed for internal pages, where a page uid has been given. In this case, the PageContentErrorHandler will now dispatch an internal SubRequest instead, to avoid an unnecessary cURL call.


Impact
======

In staging environments, the website would often be access protected with basic auth options (for example a .htpasswd auth file on Apache Webservers).
In such a case, error pages with the default PageContentErrorHandler would have failed before, as the internal cURL call for fetching the error page was lacking these required basic auth options.
For internal pages, a sub request is now used, bypassing the need for an external cURL call.

.. index:: Frontend, ext:core
