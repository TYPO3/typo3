.. include:: /Includes.rst.txt

===================================================================
Breaking: #92989 - AbstractUserAuthentication->loginFailure removed
===================================================================

See :issue:`92989`

Description
===========

The public PHP property :php:`loginFailure` of the PHP class :php:`AbstractUserAuthentication` has
been removed. This property stored information if a login attempt was made
but was not successful.


Impact
======

Accessing or setting the property from third-party code via PHP has no effect
anymore.


Affected Installations
======================

TYPO3 installations with custom code in PHP accessing or setting this property,
which is highly unlikely as this property only had limited use and the existing
hook is better suited for doing custom work.


Migration
=========

If this information is needed, it is recommended to use the hook
:php:`$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['t3lib/class.t3lib_userauth.php']['postLoginFailureProcessing']`
which allows to run custom PHP code if a login attempt has been made which was
not successful.

.. index:: PHP-API, FullyScanned, ext:core
