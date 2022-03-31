.. include:: /Includes.rst.txt

==============================================================
Breaking: #92457 - Extension Repository database table removed
==============================================================

See :issue:`92457`

Description
===========

The existing extension manager had functionality to add
multiple repositories by adding new database rows into the
database table :sql:`tx_extensionmanager_domain_model_repository`.

Because this functionality has been superseded by a configurable
and more robust Remote API, where the configuration of possible
additional TER endpoints are not stored in the database anymore,
the database table is removed.


Impact
======

Accessing :sql:`tx_extensionmanager_domain_model_repository` will
result in a SQL error, as existing TYPO3 installations will drop this
database table in the Database Compare View during upgrade.


Affected Installations
======================

TYPO3 installations with third-party extensions accessing this
database table, which is highly unlikely.

Also, TYPO3 installations depending on additional repositories
rather than the official TYPO3 Extension Repository (TER) at
extensions.typo3.org, will not work anymore.


Migration
=========

Additional Extension Repositories (remotes) have to be added in
:file:`Configuration/Services.yaml` using the :yaml:`extension.remote` tag.

.. code-block:: yaml

  extension.remote.myremote:
    class: 'TYPO3\CMS\Extensionmanager\Remote\TerExtensionRemote'
    arguments:
      $identifier: 'myremote'
      $options:
         remoteBase: 'https://my_own_remote/'
    tags:
      - name: 'extension.remote'
        enabled: true

.. index:: Database, FullyScanned, ext:extensionmanager
