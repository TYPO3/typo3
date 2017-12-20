
.. include:: ../../Includes.txt

=====================================================
Feature: #44127 - Introduced two new Hooks for OpenID
=====================================================

See :issue:`44127`

Description
===========

Two hooks were added to the OpenIdService. They make it possible to modify the request sent to the OpenID Server,
or to modify/create backend users on the fly during OpenID login.


Hooks
=====

The following hooks were introduced:

- `$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['openid']['getUserRecord']`
	Modifies the userRecord after it has been fetched (or none was found).
	Can be used to e.g. create a new record if none was found or update an existing one.
	The following parameters are passed to the hook: `record`, `response`, `authInfo`.

- `$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['openid']['authRequest']`
	Modifies the Authentication Request, before it's sent.
	Can be used to e.g. request additional attributes like a nickname from the OpenID Server.
	The following parameters are passed to the hook: `authRequest`, `authInfo`.


.. index:: PHP-API, Backend, ext:openid
