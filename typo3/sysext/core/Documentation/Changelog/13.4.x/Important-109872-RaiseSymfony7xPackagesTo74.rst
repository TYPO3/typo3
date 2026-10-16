..  include:: /Includes.rst.txt

..  _important-109872-1779735704:

=================================================================
Important: #109872 - Raise symfony/* packages to LTS version 7.4
=================================================================

See :issue:`109872`

Description
===========

Due to the recent security advisories, which have not been fixed for
older Symfony 7.x versions, dependencies are explicitly raised to 7.4.

The following `symfony/*` packages are now required at version `^7.4`
across TYPO3 core and its system extensions:

*  ``symfony/config``
*  ``symfony/console``
*  ``symfony/dependency-injection``
*  ``symfony/doctrine-messenger``
*  ``symfony/expression-language``
*  ``symfony/filesystem``
*  ``symfony/finder``
*  ``symfony/http-foundation``
*  ``symfony/messenger``
*  ``symfony/options-resolver``
*  ``symfony/process``
*  ``symfony/property-access``
*  ``symfony/property-info``
*  ``symfony/rate-limiter``
*  ``symfony/translation``
*  ``symfony/uid``

..  index:: CLI, NotScanned, ext:core
