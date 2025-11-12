.. include:: /Includes.rst.txt

.. _breaking-108084-1763033071:

==============================================================
Breaking: #108084 - Allow rootless paths in URI implementation
==============================================================

See :issue:`108084`

Description
===========

Previously, the TYPO3 implementation of UriInterface always prefixed
rootless paths (paths without preceding slash) with a slash.
With this normalization in place is was impossible to represent rootless
paths. This is now changed, that a slash is only prepended to the path,
when an authority (host name) is present.

Example
-------

Input: `rootless/path/`

..  code-block:: php
    :caption: Examples with different URIs

    $uri = new \TYPO3\CMS\Core\Http\Uri('rootless/path/');
    $uriAsString = (string)$uri;
    // before: /rootless/path/
    // after: rootless/path/

    // Same behaviour with authority
    $uri = (new \TYPO3\CMS\Core\Http\Uri('https://example.com))->withPath('rootless/path/');
    $uriAsString = (string)$uri;
    // before: https://example.com/rootless/path/
    // after: https://example.com/rootless/path/

    // Colon in first path segment
    $uri = new \TYPO3\CMS\Core\Http\Uri('rootless:path/to/resource');
    $uriAsString = (string)$uri;
    // before: /rootless:path/to/resource/
    // after: ./rootless:path/to/resource/

Impact
======

Regarding top level TYPO3 API and functionality, nothing has changed.
Required TYPO3 code has been adapted.

Third party code, that is using the Uri class directly will get different
results when representing rootless paths without authority.
Code that relies on the normalization done by TYPO3 before is likely to break.

Since TYPO3 is always dealing with absolute paths, due to URL rewriting
in backend in frontend, likely not much third party code likely relies on
relative paths, so the impact is expected to be low.

Affected installations
======================

Third party code, that is using the Uri class directly and that is
representing rootless paths without authority.

.. index:: PHP-API, NotScanned, ext:core
