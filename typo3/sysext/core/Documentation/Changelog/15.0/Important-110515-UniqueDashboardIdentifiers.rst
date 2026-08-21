..  include:: /Includes.rst.txt

..  _important-110515:

=========================================================
Important: #110515 - Enforce unique dashboard identifiers
=========================================================

See :issue:`110515`

Description
===========

Dashboard identifiers are now generated randomly and protected by a unique
database index. An additional index improves dashboard lookups by owner.

Existing installations may contain duplicate dashboard identifiers. The
database analyzer retains the non-unique identifier index while duplicates
exist, allowing the initial schema update to complete without a uniqueness
violation.

Run the upgrade wizard :guilabel:`Make dashboard identifiers unique` to
replace duplicate identifiers, including those of deleted, hidden and
scheduled dashboards. The first record of each group keeps its identifier.
Dashboard titles, owners and widget configuration are preserved.

After running the wizard, run the database analyzer again to apply the unique
identifier index. Fresh installations create the unique index immediately.

..  index:: Database, Backend, ext:dashboard
