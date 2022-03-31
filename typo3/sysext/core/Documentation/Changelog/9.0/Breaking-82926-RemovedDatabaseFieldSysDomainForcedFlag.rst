.. include:: /Includes.rst.txt

===========================================================
Breaking: #82926 - Removed database field sys_domain.forced
===========================================================

See :issue:`82926`

Description
===========

The database field "sys_domain.forced" (Checkbox "Always prepend this domain in links" in Domain Records)
and its functionality has been removed.

Previously, setting the checkbox allowed to prepend a specific domain to TypoLink-generated links, but only
if the visiting HTTP_HOST did not match any of other domain records on the same page (without redirect).

It was however, only partially useful, as - depending on which HTTP_HOST the site was accessed the first time,
and thus, the links were generated and written to cache - resulting in ambiguous cache entries.

Impact
======

Custom links having multiple domains in one pagetree without redirects and the forced flag will
not force a certain domain anymore via TypoLink.


Affected Installations
======================

Installations using this flag (can be checked with a simple SQL query :sql:`SELECT uid, pid, domainName
FROM sys_domain WHERE forced=1`) and using that on purpose with a lot of non-redirect domains
for the same page tree.


Migration
=========

If a site has a special use-case, hooks for page link generation can be used to prepend specific domains to links.

Also, if queries are made against sys_domain in third-party extensions, ensure this field is not selected or
evaluated anymore, in order to avoid SQL errors.

.. index:: Database, PHP-API, NotScanned
