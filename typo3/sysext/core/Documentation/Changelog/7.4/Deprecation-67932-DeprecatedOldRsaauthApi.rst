
.. include:: ../../Includes.txt

===========================================================
Deprecation: #67932 - Deprecated old rsaauth encryption API
===========================================================

See :issue:`67932`

Description
===========

The rsaauth API has been rebuilt to be more generic. Therefore the Ajax Handler `BackendLogin::getRsaPublicKey()` has
been marked as deprecated and the eID script `FrontendLoginRsaPublicKey` has been removed.


Affected Installations
======================

Any installation using one of the entry points above in a third-party extension.


Migration
=========

There is no reason to use the entry points on your own anymore. Please update your scripts to use the new rsaauth API.
For backend requests you should use the provided ajax handler `RsaEncryption::getRsaPublicKey()`.
For frontend request you should use the provided eID script `RsaPublicKeyGenerationController`.
