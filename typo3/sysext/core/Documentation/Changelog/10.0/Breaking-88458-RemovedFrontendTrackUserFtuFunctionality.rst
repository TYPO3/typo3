.. include:: /Includes.rst.txt

==================================================================
Breaking: #88458 - Removed Frontend Track User "ftu" functionality
==================================================================

See :issue:`88458`

Description
===========

The "ftu" feature, used to transfer sessions via GET parameter, has been removed.

The implementation and the functionality exposed some security concerns, if enabled via TypoScript
:typoscript:`config.ftu` as sessions could have been taken over by link sharing, although this was mitigated
in the past by a security change.


Impact
======

The following public properties now trigger PHP :php:`E_WARNING` when accessed:

* :php:`TYPO3\CMS\Core\Authentication\AbstractUserAuthentication->get_name`
* :php:`TYPO3\CMS\Core\Authentication\AbstractUserAuthentication->getFallBack`
* :php:`TYPO3\CMS\Core\Authentication\AbstractUserAuthentication->getMethodEnabled`
* :php:`TYPO3\CMS\Core\Authentication\AbstractUserAuthentication->get_URL_ID`
* :php:`TYPO3\CMS\Frontend\Controller\TypoScriptFrontendController->getMethodUrlIdToken`

The TypoScript setting :typoscript:`config.ftu` has no effect anymore.

The global configuration setting :php:`$GLOBALS['TYPO3_CONF_VARS']['FE']['get_url_id_token']` is not
set anymore.


Affected Installations
======================

Any TYPO3 installation using the :typoscript:`config.ftu` functionality.


Migration
=========

Remove any usages to the properties or options, and use a custom session handling without
handing over Session IDs in plaintext via GET parameters. Suggested alternatives for instance are
JWT payloads or OTP links for starting a session.

For cookie-less session handling, a custom functionality depending on the use-case has to be
implemented as TYPO3 extension.

.. index:: Frontend, LocalConfiguration, PHP-API, TypoScript, PartiallyScanned
