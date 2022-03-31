.. include:: /Includes.rst.txt

=====================================================
Deprecation: #81852 - Deprecated Usage of EXT:rsaauth
=====================================================

See :issue:`81852`


Description
===========

The extension rsaauth has been marked as deprecated. The reasons are:

* With the extension the password transmission is the only thing that is encrypted
* Even though the transmission is encrypted, the public key exchange from server to client
  is not authenticated. This means an attacker in the middle can hand out a bogus public key
  to the client, which means that the password will then be encrypted with the key of the attacker
* Session ids via cookies are still transferred unencrypted. Since (valid) session ids are almost
  as valuable as passwords, jumping through hoops to protect the password, but keeping the session
  id unencrypted, seems irrational (and is insecure).


Impact
======

An additional report checks the usage of the extension and if the backend is served via https.


Affected Installations
======================

Any TYPO3 site using the extension rsaauth.


Migration
=========

Use https:// for any site, especially for pages which transfer passwords, including TYPO3 backend and frontend logins.

The usage of a secure connection is also enforced by the browsers which mark http:// pages that
collect passwords or credit cards as insecure. In long-term all http:// sites will be marked as insecure.

After removing rsaauth, the :php:`$GLOBALS['TYPO3_CONF_VARS']['BE']['loginSecurityLevel']` and
:php:`$GLOBALS['TYPO3_CONF_VARS']['FE']['loginSecurityLevel']` must be set to :php:`normal`,
otherwise login will not be possible.

.. index:: Backend, Frontend, NotScanned
