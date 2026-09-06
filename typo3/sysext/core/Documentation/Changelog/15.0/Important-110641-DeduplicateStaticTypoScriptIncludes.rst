..  include:: /Includes.rst.txt

..  _important-110641:

===============================================================================
Important: #110641 - Deduplicate static TypoScript includes in template records
===============================================================================

See :issue:`110641`

Description
===========

Duplicate entries in :sql:`sys_template.include_static_file` are now included
only once per template record, matching the behavior of
:file:`include_static_file.txt` lists. Whitespace is trimmed and empty entries
are removed before deduplication. The first occurrence and the order of
distinct includes are preserved.

For example, a list containing ``A,B,A`` is now processed as ``A,B``.
Installations relying on the second inclusion of ``A`` to override ``B``
should review their include order and move the required overrides into
explicit TypoScript configuration.

The same static include can still be included by separate template records.

..  index:: TypoScript, ext:core
