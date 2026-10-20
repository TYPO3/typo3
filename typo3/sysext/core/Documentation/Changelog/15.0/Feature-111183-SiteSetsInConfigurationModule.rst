..  include:: /Includes.rst.txt

..  _feature-111183-1792519211:

=========================================================
Feature: #111183 - Show site sets in Configuration module
=========================================================

See :issue:`111183`

Description
===========

The backend :guilabel:`System > Configuration` module now provides the entry
:guilabel:`Sites: Sets` in the :guilabel:`Sites` group. The module
is available when the system extension :composer:`typo3/cms-lowlevel` is
installed.

The configuration tree contains two entries:

*   `sites` groups the resolved sets by site identifier. The sets appear in
    their actual loading order, including required and available optional
    dependencies. Shared dependencies appear only once per site.
*   `siteSets` lists all available sets in the registry's dependency order,
    with dependencies before the sets that use them. This includes hidden
    sets and sets that are not used by any site. The effective loading order
    for each site is shown under `sites`.

Each set exposes its metadata, including its label, settings, definitions and
configuration sources. The `dependencies` and `optionalDependencies` entries
contain the referenced sets and their metadata recursively. Unavailable
optional dependencies are listed with an empty value.

Impact
======

Integrators and developers can inspect the effective set order of each site
and browse all available sets and their dependencies in the backend, without
adding debug output.

..  index:: Backend, ext:lowlevel
