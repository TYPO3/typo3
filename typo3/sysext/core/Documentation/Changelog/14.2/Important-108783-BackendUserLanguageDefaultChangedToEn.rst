..  include:: /Includes.rst.txt

..  _important-108783-1737910000:

==================================================================
Important: #108783 - Backend user language default changed to "en"
==================================================================

See :issue:`108783`

Description
===========

The backend user language field (`be_users.lang`) historically used `default`
as the value for English. This has been changed to use the standard ISO 639-1
language code `en` instead.

The language key `default` is still accepted for backwards compatibility with
custom code, but is no longer selectable in the backend user interface.

An upgrade wizard "Migrate backend user language from 'default' to 'en'" is
available to migrate existing backend user records.

Impact
======

* New backend users will have `en` as their default language instead of
  `default`.
* Existing backend users with `lang=default` should run the upgrade wizard
  to migrate to `lang=en`.
* In general: Code that uses `default` as a language key (e.g. custom
  instances of :php:`LanguageService`) will continue to work as
  `default` is still mapped to `en` internally.

..  index:: Backend, ext:core
