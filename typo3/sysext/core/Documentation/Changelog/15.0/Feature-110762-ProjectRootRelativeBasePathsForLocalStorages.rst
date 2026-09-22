..  include:: /Includes.rst.txt

..  _feature-110762-1789250000:

======================================================================
Feature: #110762 - Project-root relative base paths for local storages
======================================================================

See :issue:`110762`

Description
===========

A file storage using the :php:`Local` driver is configured with a base path.
Until now, this path was either absolute, or relative to the public web
directory when the additional option :php:`pathType` was set to
:php:`relative`. A directory outside the web root, for example a storage for
files that must never be served directly, could therefore only be configured
with an absolute path, which ties the configuration to one server.

A relative base path without the :php:`pathType` option is now resolved
against the project path
(:php:`\TYPO3\CMS\Core\Core\Environment::getProjectPath()`).
In a Composer-based installation this is the directory containing
:file:`composer.json`, so :php:`public/fileadmin/` denotes the default
:file:`fileadmin/` directory and :php:`storage/private/` a directory next to
:file:`config/` and :file:`var/`. In classic mode the project path and the
public path are the same directory, so :php:`fileadmin/` keeps its meaning.

Whether such a storage is public is derived as before: a base path below the
public directory gets a public URL, any other path needs a :php:`baseUri` or
is served through the file dump controller.

The following rules apply to the :php:`basePath` option of the local driver:

*   An absolute path is used as is.
*   A relative path with :php:`pathType` set to :php:`relative` is resolved
    against the public path. This is the behaviour of existing storage records
    and stays unchanged.
*   Any other relative path is resolved against the project path.

The same rules apply when TYPO3 resolves a legacy file path such as
:php:`fileadmin/images/logo.png` to the storage containing it.

Impact
======

Local storages can be placed anywhere below the project directory with a
relative path that works on every server the project is deployed to. Existing
storage records are not affected, as they carry the :php:`pathType` option.

..  index:: FAL, PHP-API, ext:core
