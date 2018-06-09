.. include:: ../../Includes.txt

============================================
Important: #85026 - salted passwords changes
============================================

See :issue:`85026`

Description
===========

Several changes to the processing of user passwords and storage have been applied to
the system. Default settings of the system have been adapted over time, these changes
only apply for instances that actively disabled features during upgrading.

All changes are justified by looking at the time line of salted password milestones
in the TYPO3 core and should automatically work if no manual changes have been applied
to the salted passwords evaluation and configuration settings:

* 4.3.0 (05/2008) Introduction of salted passwords extension
* 4.5.0 (01/2011) Introduction of salted passwords bulk update scheduler task
* 4.5.0 (01/2011) Default hash algorithm is phpass
* 4.5.0 (01/2011) The reports module shows a warning if saltedpasswords extension is not loaded
* 6.2.0 (03/2014) Salted passwords extension is mandatory
* 6.2.0 (03/2014) Salted password storage can not be disabled for backend users anymore
* 8.0.0 (03/2016) Default hash algorithm of new instances is pbkdf2


Data thriftness
^^^^^^^^^^^^^^^

As a best practice on the principle of data minimisation, inactive frontend and backend users should be removed from
the database after a while! The main idea is that not existing data can't be compromised in case of a security breach.
The `lastlogin` field of the two user tables `fe_users` and `be_users` store the last login timestamp of a user, and
soft deleted user records have the `deleted` field set to `1`. The "Table garbage collection task" scheduler task can
be configured to fully remove those inactive or deleted users from the system. See the
`scheduler documentation <https://docs.typo3.org/typo3cms/extensions/scheduler/Installation/BaseTasks/Index.html>`__
for details on this task.


Salted passwords bulk update task removed
^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^

The "Convert user passwords to salted hashes" scheduler bulk update task of the `saltedpasswords`
extension has been removed from the system.

The salted passwords extension is by default configured to upgrade single password hashes to the
currently configured hash algorithm if a user logs in.

The scheduler task itself allowed to convert clear-text and simple md5 hashed passwords of frontend
and backend users to salted md5 passwords. This hash method however is in itself outdated and not
considered secure enough anymore. The task needed to be run only once and disabled itself as soon
as it walked through all frontend and backend users.

TYPO3 v9 assumes all admins took care of basic salted password security within the last ten years if upgrading from
instances older than version 4.3 by running this task once, the upgrade task has now been removed with core version 9.

If there are still clear-text or simple md5 stored passwords, they can be found by searching the database
field `password` of tables `fe_users` and `be_users` for entries not starting with `$`. If there are still entries
like that, an administrator should convert them to simple md5 salted hashes by using the convert bulk update
scheduler task in a core version prior to v9, before upgrading the system to v9.


Disabled clear-text storage of frontend user passwords
^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^

Storing frontend user passwords as non clear-text but as salted password hashes has been optional since
core version 4.3 and enabled by default since core version 4.5. An option to store passwords in clear-text
had to be manually configured. This has been removed for backend users in 6.2 already and is now dropped
for frontend users as well.

There has been little reason to store passwords in clear-text in the database in the past, most of them only
justified by third party systems being directly connected to the TYPO3 database. Those cases should be solved
using the :ref:`Authentication service API <t3coreapi:authentication>` instead, which can hand over the
clear-text user password upon successful user login, but never persists the native clear-text password. If a third party
layer such as LDAP is used for authentication to TYPO3, the user password should not be stored in the TYPO3 internal
tables at all. The authentication service chain supports all of these scenarios, it is a common business use case to
connect third party applications without the need to store passwords in clear-text anywhere.

In case a TYPO3 instance messed around with configuration options of the salted passwords extension in the past, the
toggle `FE.enabled` is now ignored and users still having clear-text passwords in the database will get their password
storage strategy automatically upgraded to the configured salted password hash algorithm upon successful login.


.. index:: Backend, Database, Frontend, ext:saltedpasswords
