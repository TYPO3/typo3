.. include:: /Includes.rst.txt

===================================================
Feature: #92457 - Improved Extension Repository API
===================================================

See :issue:`92457`

Description
===========

In previous TYPO3 Installations, connecting to a different repository type
than the "official" TER_ (TYPO3 Extension Repository), for downloading
publicly available third-party extensions, the Extension Manager component
of TYPO3 Core used a non-documented API to connect to this endpoint.

In the past, there were even mirrors available, which is not practical
in the current internet world anymore.

In order to be more flexible in the future, all functionality has now been
encapsulated into a single API called "Extension Remotes". These are adapters
to fetch a list of extensions via the :php:`ListableRemoteInterface`, or to
download an extension via the :php:`ExtensionDownloaderRemoteInterface`.

This way it is possible to adapt any kind of remote, where as the existing
concrete implementation is now built into a configuration, rather than the
database for "repositories".

It is also still possible to add new remotes, disable registered remotes
or change the default remote.

Custom remote configuration can be added in the
:file:`Configuration/Services.yaml` of the corresponding extension.

.. code-block:: yaml

  extension.remote.myremote:
    class: 'TYPO3\CMS\Extensionmanager\Remote\TerExtensionRemote'
    arguments:
      $identifier: 'myremote'
      $options:
         remoteBase: 'https://my_own_remote/'
    tags:
      - name: 'extension.remote'
        default: true

Using :yaml:`default: true`, "myremote" will be used as the default remote.

To disable an already registered remote, :yaml:`enabled: false` can be set.

It is also possible to use custom remote implementations to not have to deal
with `t3x` files anymore.

.. code-block:: yaml

  extension.remote.myremote:
    class: 'Vendor\SitePackage\Remote\MyRemote'
    arguments:
      $identifier: 'myremote'
    tags:
      - name: 'extension.remote'
        default: true

Please note that :php:`Vendor\SitePackage\Remote\MyRemote` must implement
:php:`ExtensionDownloaderRemoteInterface` to be registered as remote.

Furthermore setting :yaml:`default: true` only works if the defined service
implements :php:`ListableRemoteInterface`.


Impact
======

Because of the removed mirror functionality and the encapsulation
of the TER API into one concrete implementation, it is much easier
to extend the Extension Manager functionality for third-party usages.

This is only relevant for non-composer-mode installations,
as composer-based installations use the download functionality
of composer.

.. _TER: https://extensions.typo3.org/

.. index:: ext:extensionmanager
