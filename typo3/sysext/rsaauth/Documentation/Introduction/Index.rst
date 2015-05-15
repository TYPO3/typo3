.. ==================================================
.. FOR YOUR INFORMATION
.. --------------------------------------------------
.. -*- coding: utf-8 -*- with BOM.

.. include:: ../Includes.txt



.. _introduction:

Introduction
------------


.. _what-does-it-do:

What does it do?
^^^^^^^^^^^^^^^^

This extension adds encrypted authentication for Frontend and Backend logins to TYPO3.
It is safer than plain text authentication because it uses a one time generated public and private key pair.
The password is encrypted with a new public key each time before it is transferred over the network.
Next the password is decrypted on the server using one time generated private key.

Frontend RSA authentication works with the system extension
:code:`felogin` only.

The extension requires either an :code:`openssl` PHP module or the
:code:`openssl` binary to be available to TYPO3.

This extension does not change how login forms look like. Thus no
screenshots available.

Bugs for the extension should be submitted to the TYPO3 bug tracker at
`http://forge.typo3.org/
<http://forge.typo3.org/projects/typo3v4-core/issues>`_ . Questions
should be submitted to the TYPO3 mailing lists.

