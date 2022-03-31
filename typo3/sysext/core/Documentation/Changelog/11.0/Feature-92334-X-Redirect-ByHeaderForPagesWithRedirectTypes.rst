.. include:: /Includes.rst.txt

====================================================================
Feature: #92334 - X-Redirect-By Header for pages with redirect types
====================================================================

See :issue:`92334`

Description
===========

The following page types trigger a redirect:

- Shortcut
- Mountpoint pages which should be overlaid but accessed directly
- Link to external URL

Those redirects will now send an additional HTTP Header `X-Redirect-By`, stating what type of page triggered the redirect.
By enabling the new global option :php:`$GLOBALS['TYPO3_CONF_VARS']['FE']['exposeRedirectInformation']` the header will also contain the page ID.
As this exposes internal information about the TYPO3 system publicly, it should only be enabled for debugging purposes.

For shortcut and mountpoint pages: ::

   X-Redirect-By: TYPO3 Shortcut/Mountpoint
   # exposeRedirectInformation is enabled
   X-Redirect-By: TYPO3 Shortcut/Mountpoint at page with ID 123

For *Links to External URL*: ::

   X-Redirect-By: TYPO3 External URL
   # exposeRedirectInformation is enabled
   X-Redirect-By: TYPO3 External URL at page with ID 456

Impact
======

The header `X-Redirect-By` makes it easier to understand why a redirect happens when checking URLs, e.g. by using `curl`: ::

  curl -I 'https://my-typo3-site.com/examples/pages/link-to-external-url/'

  HTTP/1.1 303 See Other
  Date: Thu, 17 Sep 2020 17:45:34 GMT
  X-Redirect-By: TYPO3 External URL at page with ID 12
  X-TYPO3-Parsetime: 0ms
  location: https://typo3.org
  Cache-Control: max-age=0
  Expires: Thu, 17 Sep 2020 17:45:34 GMT
  X-UA-Compatible: IE=edge
  Content-Type: text/html; charset=UTF-8

.. index:: Frontend, ext:frontend
