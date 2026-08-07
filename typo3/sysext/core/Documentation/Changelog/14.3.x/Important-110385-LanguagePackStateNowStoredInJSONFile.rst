..  include:: /Includes.rst.txt

..  _important-110385-1787735051:

============================================================
Important: #110385 - Language pack state stored in JSON file
============================================================

See :issue:`110385`

Description
===========

The "last updated" timestamps of downloaded language packs used to be stored
in the :sql:`sys_registry` database table. When language packs are updated as
part of a deployment, the database connection may not yet be available at
that stage, which led to errors.

These timestamps are now stored in a JSON file,
:file:`language_pack_states.json`, next to the downloaded language packs
themselves, in :file:`var/labels/` (Composer-based installations) or
:file:`typo3conf/l10n/` (classic installations). Existing entries are migrated
from :sql:`sys_registry` to this file once, using the upgrade wizard
:guilabel:`Migrate the "languagePacks" registry entries to JSON storage`.

This is relevant for deployment workflows that commit the downloaded
language packs to version control instead of triggering a language pack
update as part of every deployment: :file:`language_pack_states.json` must
be committed (or otherwise provisioned) together with the language pack
files it describes. Otherwise, the backend's "Manage Language Packs" module
will not display accurate "last updated" information for packs that already
exist on disk.

..  index:: Backend, NotScanned, ext:core
