.. include:: ../../Includes.txt

============================================================
Important: #85022 - saltedpasswords bulk update task removed
============================================================

See :issue:`85022`

Description
===========

The "Convert user passwords to salted hashes" scheduler bulk update task of the `saltedpasswords`
extension has been removed from the system.

The time line of salted password milestones in the core:

* 4.3.0 (05/2008) Introduction of salted passwords extension
* 4.5.0 (01/2011) Introduction of salted passwords bulk update scheduler task
* 4.5.0 (01/2011) Default hash algorithm is phpass
* 4.5.0 (01/2011) The reports module shows a warning if saltedpasswords extension is not loaded
* 6.2.0 (03/2014) Salted passwords extension is mandatory
* 8.0.0 (03/2016) Default hash algorithm of new instances is pbkdf2

The salted passwords extension is by default configured to upgrade single password hashes to the
currently configured hash algorithm if a user logs in.

The scheduler task itself allowed to convert plain-text and simple md5 hashed passwords of frontend
and backend users to salted md5 passwords. This hash method however is in itself outdated and not
considered secure enough anymore. The task needed to be run only once and disabled itself as soon
as it walked through all frontend and backend users.

We assume all admins took care of basic salted password security within the last ten years if upgrading
from instances older than version 4.3 by running this task once, so we now removed this task with core version 9.

If there are still plain-text or simple md5 stored passwords, they can be found by searching the database
field `password` of tables `fe_users` and `be_users` for entries not starting with `$`. If there are still entries
like that, an administrator should convert them to simple md5 salted hashes by using the convert bulk update
scheduler task in a core version prior to v9, before upgrading the system to v9.

Additionally, as a general note on data protection, inactive frontend and backend users should be removed
from the database after a while! The general idea is that not existing data can't be compromised in
case of a security breach. The `lastlogin` field of the two user tables `fe_users` and `be_users` store
the last login timestamp of a user, and soft deleted user records have the `deleted` field set to `1`.
The "Table garbage collection task" scheduler task can be configured to fully remove those inactive or deleted users
from the system. See the
`scheduler documentation <https://docs.typo3.org/typo3cms/extensions/scheduler/Installation/BaseTasks/Index.html>`__
for details on this task.

.. index:: Backend, Database, Frontend, ext:saltedpasswords
