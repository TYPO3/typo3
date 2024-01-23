.. include:: /Includes.rst.txt

.. _breaking-101671-1691924837:

==============================================================================
Breaking: #101671 - Disable external linktypes by default in EXT:linkvalidator
==============================================================================

See :issue:`101671`

Description
===========

There are several known problems with external link checking in Linkvalidator,
such as:

*   "False positives": Some links are reported broken, but are not broken, see
    :issue:`101670`.
*   External sites are checked without rate limit which may cause sites which
    perform link checking to be blocked, see :issue:`89287`.
*   No caching of results (except for a runtime cache during link checking which
    will be invalid on next run)

These issues are currently not easily solvable and should also be addressed
specifically for the site concerned.

We now deactivate checking external link types by default in the configuration:
:ref:`ext_linkvalidator:linktypes`.
The "external" link types checking still works but must be enabled explicitly.

This will make administrators more aware of problems and the specific problems
can be addressed, for example, by providing a custom class to replace
:php:`\TYPO3\CMS\Linkvalidator\Linktype\ExternalLinktype`. Additionally, a page
:ref:`Known Problems <ext_linkvalidator:known-problems>` was
already added to the documentation in a previous `patch
<https://review.typo3.org/c/Packages/TYPO3.CMS/+/80421>`__.


Impact
======

External links will no longer be checked by default in EXT:linkvalidator
unless :typoscript:`mod.linkvalidator.linktypes` is specifically set via page
TSconfig.


Affected installations
======================

Installations using EXT:linkvalidator.


Migration
=========

Either leave external link checking deactivated or find ways to mitigate the
problems with external link checking.

Solutions:

*   do not use external link checking
*   or, create a custom linktype class to replace :php:`ExternalLinktype`

    *   the custom link type should rate limit when checking external links,
        for example, by adding a crawl delay in the link targets with the same domain
    *   the custom link type should find a way to handle possible false positives
    *   alternatively the external link type should restrict link checking to known
        domains without problems
    *   alternatively, there should be a method to exclude specific URLs or domains
        from link checking
    *   excessive checking of external links should be avoided, for example, by
        using a link target cache

More information is available in the Linkvalidator documentation:

*   :ref:`ext_linkvalidator:known-problems`
*   :ref:`ext_linkvalidator:linktype-implementation`

Example for activating external linktype
----------------------------------------

..  code-block:: typoscript
    :caption: EXT:my_sitepackage/Configuration/page.tsconfig

    mod.linkvalidator.linktypes = db,file,external


.. index:: Backend, NotScanned, ext:linkvalidator
