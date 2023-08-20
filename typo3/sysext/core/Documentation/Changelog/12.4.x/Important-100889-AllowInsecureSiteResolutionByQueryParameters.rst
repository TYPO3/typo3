.. include:: /Includes.rst.txt

.. _important-100889-1690476871:

=======================================================================
Important: #100889 - Allow insecure site resolution by query parameters
=======================================================================

See :issue:`100889`

..  important::
    This change was introduced as part of the
    `TYPO3 12.4.4 and 11.5.30 security releases <https://typo3.org/security/advisory/typo3-core-sa-2023-003>`__.

Description
===========

Resolving sites by the `id` and `L` HTTP query parameters is now denied by
default. However, it is still allowed to resolve a particular page by, for
example, "example.org" - as long as the page ID `123` is in the scope of the
site configured for the base URL "example.org".

The new feature flag
`security.frontend.allowInsecureSiteResolutionByQueryParameters` - which is
disabled per default - can be used to reactivate the previous behavior:

..  code-block:: php

    $GLOBALS['TYPO3_CONF_VARS']['SYS']['features']['security.frontend.allowInsecureSiteResolutionByQueryParameters'] = true;


Impact
======

Resolving a page via query parameters is now restricted to the specific
site where the page is located.

Affected installations
======================

Installations which resolve pages from one domain via another domain.

.. index:: Frontend, NotScanned, ext:core
