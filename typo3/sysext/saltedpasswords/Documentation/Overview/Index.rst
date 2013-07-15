.. ==================================================
.. FOR YOUR INFORMATION
.. --------------------------------------------------
.. -*- coding: utf-8 -*- with BOM.

.. include:: ../Includes.txt



.. _overview:

Overview
--------


.. _why-use:

Why you should use salted user password hashes
^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^

By using this extension, you get rid of plain-text passwords or MD5
password hashes for user records in TYPO3. MD5 hashes are no longer
safe to use for passwords. Using rainbow tables is widely spread these
days and retrieving an according valid plain-text password is just a
matter of time. With salted hashes, an attacker needs to create
separate rainbow tables for each salt. The salt itself is different
for each stored password hash. So retrieving plain-text passwords for
all user records in a TYPO3 installation is quite expensive in terms
of complexity.

You still are advised to use well-chosen passwords. Avoid wordlist
entries; use arbitrary complex non-wordlist passwords/passphrases.


.. _supported-hashing-methods:

Supported hashing methods
^^^^^^^^^^^^^^^^^^^^^^^^^

The extension provides several types of hashing method:

- **Portable PHP password hashing** This method allows to exchange
  salted hashes with other CMS like Drupal or Wordpress as they support
  `phpass <http://www.openwall.com/phpass/>`_ too. This is the format of
  previously generated salted user passwords by extension
  t3sec\_saltedpw. This method is derived from a third-party library.
  Portable PHP password hashing method is available in any environment,
  TYPO3 4.3 will run with. It's the **default and recommended setting**.
  .

- **MD5 salted hashing** This method allows to use Salted user password
  hashes for other server daemon authentications (mailserver, etc.) too.
  Use this setting if you need to authenticate other services against
  TYPO3 user records. This method uses PHP standard capabilities.

- **Blowfish salted hashing** This method provides increased security in
  comparison to MD5 salted hashing. Use this setting if you have higher
  requirements on password security. This requires a PHP > 5.3.0, PHP
  5.X.X with suhosin patch applied or PHP compiled with a recent glibc.
  You might want to execute the Unit Tests brought together with this
  extension; if tests in blowfish test suite fail, your server
  installation most probably does not support blowfish. Once you've
  chosen blowfish hashing, you need to make sure blowfish is available
  on the server you might move to in future. Otherwise, users won't be
  able to login any longer.


.. _server-environment:

Server environment
^^^^^^^^^^^^^^^^^^

Due to the nature of salted user password hashes, the server needs to
have a plain-text password to check against stored salted user
password hashes of a database user record during authentication. This
requires a transfer of the plain-text password from a user's browser
to the TYPO3 server.

You obviously want to send the password over an encrypted channel.
According possibilities are the usage of either SSL with your web
server or the TYPO3 system extension rsaauth.

