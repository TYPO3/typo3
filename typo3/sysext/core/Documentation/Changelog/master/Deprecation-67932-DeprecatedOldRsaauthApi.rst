===========================================================
Deprecation: #67932 - Deprecated old rsaauth encryption API
===========================================================

Description
===========

The rsaauth API was rebuilt to be more generic. Therefore the Ajax Handler ``BackendLogin::getRsaPublicKey`` was marked as
deprecated and the eID script ``FrontendLoginRsaPublicKey`` was removed.


Affected Installations
======================

Any installation using one of the entry points above in a third-party extension.


Migration
=========

There is no reason to use the entry points on your own anymore. Please update your scripts to use the new rsaauth API. For backend
request you should use the provided ajax handler ``RsaEncryption::getRsaPublicKey``. For frontend request you should use the
provided eID script ``RsaPublicKeyGenerationController``.
