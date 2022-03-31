.. include:: /Includes.rst.txt

===============================================
Breaking: #85761 - Authentication chain changes
===============================================

See :issue:`85761`

Description
===========

Most TYPO3 instances can ignore this.

An instance must consider this security relevant documentation if all of the below criteria are met:

* Additional authentication services are active in an instance, for example an LDAP extension,
  an openId extension, some single sign on extension, or similar. The reports module with top
  module selection "Installed services" shows those extensions. If an instance is only dealing
  with core related authentication services like "saltedpasswords", "rsaauth" and "core", it is
  not affected.
* One of these not native core services is registered with a priority lower than 70 and higher than 50, see
  the configuration module in the backend and verify if some non-core extension registers with
  such a priority. Most additional authentication services however register with a priority higher than 70.
* The additional authentication service is registered for type 'authUserBE' or 'authUserFE'.

In the unlikely case such a service type with a priority between 70 and 50 has been registered,
security relevant changes may be needed to be applied when upgrading to TYPO3 v9.

The core service to compare a password against a salted password hash in the database has been
moved from priority 70 to priority 50. The salted passwords service on priority 70 did not continue
to lower prioritized authentication services if the password in the database has been recognized by
salted passwords as a valid hash, but the password did not match. The default core service denied
calling services lower in the chain if the password has been recognized as hash which the
salted passwords hash service could handle, but the password did not validate.

With reducing the priority of the salted password hash check from priority 70 to 50 the following
edge case applies: If a service is registered between 70 and 50, this service is now called before
the salted passwords hash check. It thus may be called more often than before and may need to change
its return value. It can no longer rely on the salted passwords service to deny a successful
authentication if the submitted password is stored in the database as hashed password, but the
database hash does not match the submitted password a user has sent to login.


Impact
======

If an instance provides additional authentication services, and if one of that services does
not return correct authentication values, this may open a authentication bypass security issue
when upgrading to TYPO3 v9.


Affected Installations
======================

See description.


Migration
=========

If an instance is affected, consider the following migration thoughts:

* Ensure the authentication service between priority 70 and 50 on type 'authUserBE' and 'authUserFE'
  does not rely on the result auf the salted passwords evaluation.
* Consider this authentication services is called more often than before since the previous service
  that denied login on priority 70 is now located at priority 50.
* Check the return values of the authentication services.
* Read the source code of :php:`TYPO3\CMS\Core\Authentication->authUser()` for more details on possible
  return values. Consider the priority driven call chain.

.. index:: PHP-API, NotScanned, ext:saltedpasswords
