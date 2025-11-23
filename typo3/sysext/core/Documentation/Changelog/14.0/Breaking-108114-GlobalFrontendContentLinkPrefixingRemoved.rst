..  include:: /Includes.rst.txt

..  _breaking-108114-1763085569:

==================================================================
Breaking: #108114 - Global frontend content link prefixing removed
==================================================================

See :issue:`108114`

Description
===========

The frontend rendering contained logic that searched for links to resources
within the generated Response content to globally replace them with the
configured URL prefix (TypoScript :typoscript:`config.absRefPrefix`).

This solution has always been brittle and has now been obsoleted with the
introduction of the :ref:`System Resource API <feature-107537-1759136314>`.

The global search and replace code has been removed, which also obsoletes
setting :php:`$GLOBALS['TYPO3_CONF_VARS']['FE']['additionalAbsRefPrefixDirectories']`.

Impact
======

Generated content can no longer expect links to resources to be globally
"fixed". They must create the final URL themselves.

The obsolete
:php:`$GLOBALS['TYPO3_CONF_VARS']['FE']['additionalAbsRefPrefixDirectories']`
will be automatically removed from :file:`settings.php` after upgrading to
TYPO3 v14 and accessing the install tool.

Affected installations
======================

The System Resource API is integrated into TYPO3 in a way that extensions
usually create proper links automatically as long as the provided Core API is
used.

Instances with extensions that hard code link generation may be affected.

Migration
=========

Instances should check the rendered frontend for broken links after upgrading
to TYPO3 v14 and update hard coded link generation to use proper API calls,
for example based on the various URL, URI and asset-related Fluid ViewHelpers.

This change depends heavily on the specific extension code. There is no general
advice that covers all possible cases for extension developers.

..  index:: Frontend, PHP-API, NotScanned, ext:frontend
