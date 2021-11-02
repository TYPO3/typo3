.. include:: /Includes.rst.txt

.. _installation:

============
Installation
============

.. _installation_composer:

With Composer
=============

If your TYPO3 installation is using Composer, install the t3editor
extension by:

.. code-block:: bash

    composer require typo3/cms-t3editor

If you are using Composer and not working with the latest version of TYPO3
you will need to add a version constraint:

.. code-block:: bash

    composer require typo3/cms-t3editor:"^10.4"

Installing the extension prior to version 11.4
----------------------------------------------

Before TYPO3 11.4 it was still necessary to manually activate extensions
installed via Composer using the Extension Manager.

If you are using TYPO3 with Composer and are using a version of TYPO3 that is
lower than 11.4, you will need to activate the extension:

- Access :guilabel:`Admin Tools > Extension Manager > Installed Extensions`
- Search for `t3editor`
- Activate the extension by selecting the :guilabel:`Activate` button in the
  column labeled :guilabel:`A/D`

.. _installation_legacy:

Without Composer
================

If you are working with an installation of TYPO3 that doesn't use Composer, this
extension will already be part of the installation due to the fact that
legacy `.tar` & `.zip` packages come bundled with all system extensions.

However, whilst the extension is already downloaded, it is likely that the
extension is not activated.

To activate the t3editor, navigate to
:guilabel:`Admin Tools > Extension Manager > Installed Extensions` and
search for "t3editor". If the extension is not active, activate it by selecting
the :guilabel:`Activate` button in the column labeled :guilabel:`A/D`.

System Maintainer rights are required to activate the extension.
