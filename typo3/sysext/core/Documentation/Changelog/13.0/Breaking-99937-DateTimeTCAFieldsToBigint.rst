.. include:: /Includes.rst.txt

.. _breaking-99937-1691166389:

=======================================================================
Breaking: #99937 - Utilize BIGINT database column type for datetime TCA
=======================================================================

See :issue:`99937`

Description
===========

The TCA :php:`'type' => 'datetime'` attribute previously created
:sql:`integer signed` types as per the new auto-creation of table columns,
if not specified differently via :file:`ext_tables.sql` or listed as an exception
(:sql:`starttime`, :sql:`endtime`, :sql:`tstamp`, :sql:`crdate`).

A :php:`datetime` field created without an exception would allow date ranges
from 1901 to 2038. While that allows dates before 1970 (usual birthdays),
sadly this field would "end" in 2038.

Because of this, the exceptions (:sql:`starttime`, :sql:`endtime`, :sql:`tstamp`,
:sql:`crdate`) already are created as :sql:`integer unsigned`, which puts them
from 1970 to 2106. Dates before 1970 are not needed, because you will not publish
or create anything in the past, but maybe after 2038.

However, there are many use cases where :php:`datetime` TCA fields should have
a much broader time span, or at least past 2038.

Now, all these fields are changed to use the :sql:`bigint signed` data type.
This allows to define ranges far into the future and past. It uses a few
more bytes within the database, for the benefit of being a unified solution
that can apply to every use case.

Impact
======

All extensions that previously declared :php:`datetime` columns should
remove the column definition from :file:`ext_tables.sql` to utilize the
type :sql:`bigint signed`. This will allow to store timestamps after
2038 (and before 1970).

A future implementation may change from integer-based columns completely
to a native :sql:`datetime` database field.

When executing the database compare utility, the column definitions for a few
Core fields are changed and their storable range increases.

These fields are now able to hold a timestamp beyond 2038 (and also before
1970):

*   :sql:`be_users.lastlogin`
*   :sql:`fe_users.lastlogin`
*   :sql:`pages.lastUpdated`
*   :sql:`pages.newUntil`
*   :sql:`sys_redirect.lasthiton`
*   :sql:`index_config.timer_next_indexing`
*   :sql:`tx_extensionmanager_domain_model_extension.last_updated`
*   :sql:`sys_workspace.publish_time`
*   :sql:`sys_file_metadata.content_creation_date`
*   :sql:`tt_content.date`

.. index:: TCA, ext:core, NotScanned
