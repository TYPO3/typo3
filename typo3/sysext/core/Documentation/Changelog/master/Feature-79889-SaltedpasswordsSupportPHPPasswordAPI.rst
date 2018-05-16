.. include:: ../../Includes.txt

===========================================================
Feature: #79889 - Saltedpasswords supports PHP password API
===========================================================

See :issue:`79889`

Description
===========

Salted passwords now supports the PHP Password Hashing API: https://secure.php.net/manual/en/ref.password.php

The two hash algorithms `bcrypt` and `argon2i` are available and can be selected in the
settings of the salted passwords extension if the PHP instance supports them.

Impact
======

None. You can start to use the new password hashing methods by selecting "Standard PHP password hashing (bcrypt)"
or "Standard PHP password hashing (argon2i)" in Extension Manager Configuration of saltedpasswords. Password
hashes of existing users will be updated as soon as users log in.

.. index:: Backend, Frontend, PHP-API, ext:saltedpasswords
