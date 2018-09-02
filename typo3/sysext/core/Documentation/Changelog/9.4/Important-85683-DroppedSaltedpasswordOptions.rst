.. include:: ../../Includes.txt

====================================================
Important: #85683 - Dropped salted passwords options
====================================================

See :issue:`85683`

Description
===========

Some extension configuration of the salted passwords extension has been dropped:

- FE.forceSalted and BE.forceSalted
  By explicitly setting forceSalted to 1 (default 0) in the saltedpasswords extension configuration it was possible to
  deny login of users who had not stored their password as salted password yet. This option has been removed.
  User who have been upgraded to salted passwords using the "Convert user passwords to salted hashes" from
  core version 8 are still able to login and will get their passwords updated to the configured salted password
  mechanism upon first successful login. This upgrade will be dropped in TYPO3 v10. Other non salted passwords
  mechanisms (simple md5 or plaintext) will however lead to a failed login. Administrators who did not yet upgrade
  their user base to salted passwords must perform the "Convert user passwords to salted hashes" in TYPO3 v7
  or TYPO3 v8 before upgrading to TYPO3 v9.

- FE.updatePasswd and BE.updatePasswd
  By explicitly setting updatePasswd to 0 (default 1) in the saltedpasswords extension configuration it was possible
  to avoid updating a given hashed password to the currently configured hash algorithm, but still allow login. This option has
  been dropped: A user submitting a valid password using an old salted passwords algorithm that is no longer configured
  as current salted passwords algorithm will always get his password updated and stored using the currently
  configured password salt.

- FE.onlyAuthService and BE.onlyAuthService
  By explicitly setting onlyAuthService to 1 (default 0), it was possible to deny any further authentication service
  to successfully validate a user. This setting is mostly useless since any different authentication service is usually
  configured to kick in before the native TYPO3 internal authentication service. It does not make sense to have this toggle
  and a search in open extensions revealed no usage. On upgrading to v9, if you are running additional authentication services,
  please verify those have a higher priority than the default :php:`SaltedPasswordService`, action is only needed if
  additionally onlyAuthService has been set to 1 in salted passwords configuration, which is probably never the case.


.. index:: Backend, Database, Frontend, ext:saltedpasswords
