..  include:: /Includes.rst.txt

..  _breaking-108114-1763085569:

==================================================================
Breaking: #108114 - Global frontend content link prefixing removed
==================================================================

See :issue:`108114`

Description
===========

The frontend rendering contained logic that searched for links to resources within
the generated Response content to globally replace them with the configured URL
prefix (TypoScript :typoscript:`config.absRefPrefix`).

This solution has always been brittle and has finally been obsoleted with the
introduction of the :ref:`system resource API <feature-107537-1759136314>`.

The global search and replace code has been removed which obsoletes setting
:php:`$GLOBALS['TYPO3_CONF_VARS']['FE']['additionalAbsRefPrefixDirectories']`
as well.


Impact
======

Generated content can no longer expect their links to resources being globally
"fixed". They need to create the final URL themself.

The obsolete :php:`$GLOBALS['TYPO3_CONF_VARS']['FE']['additionalAbsRefPrefixDirectories']`
will be automatically removed from :file:`settings.php` after upgrading to
TYPO3 v14 and accessing the install tool.


Affected installations
======================

The system resource API is wired into the system in a way that extensions usually
create proper links automatically as long as the provided core API is used.

Instances with extensions that hard code link generation may be affected, though.


Migration
=========

Instances should check the rendered frontend for broken links after upgrading
to TYPO3 v14 and substitute hard coded link generation with proper API calls,
for instance based on the various URL, URI and asset related Fluid view helpers.

This change heavily depends on the specific extension code. There is no
good advise for extension developers to catch all possible cases.


..  index:: Frontend, PHP-API, NotScanned, ext:frontend
