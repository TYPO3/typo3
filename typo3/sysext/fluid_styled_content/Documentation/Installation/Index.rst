.. include:: /Includes.rst.txt

.. _installation:

============
Installation
============

.. _installation_composer:

With Composer
=============

If your :ref:`TYPO3 installation <t3start:install>` is using Composer,
install the extension `fluid_styled_content` by:

.. code-block:: bash

    composer require typo3/cms-fluid-styled-content

If you are not working with the latest version of TYPO3
you will need to add a version constraint:

.. code-block:: bash

    composer require typo3/cms-fluid-styled-content:"^10.4"

Installing the extension prior to TYPO3 11.4
--------------------------------------------

Before TYPO3 11.4 it was still necessary to manually activate extensions
installed via Composer using the :guilabel:`Extensions` module.

If you are using TYPO3 with Composer and are using a version of TYPO3 that is
older than 11.4, you will need to activate the extension:

- Access :guilabel:`Admin Tools > Extensions > Installed Extensions`
- Search for `fluid_styled_content` (note the underscores)
- Activate the extension by selecting the :guilabel:`Activate` button in the
  column labeled :guilabel:`A/D`

.. _installation_legacy:

Without Composer
================

If you are working with a
:ref:`legacy installation <t3start:legacyinstallation>` of TYPO3, this
extension will already be part of the installation due to the fact that
"classic" `.tar` & `.zip` packages come bundled with all system extensions.

However, whilst the extension is already downloaded, it might not be activated.

To activate the extension `fluid_styled_content`, navigate to
:guilabel:`Admin Tools > Extensions > Installed Extensions` and
search for `fluid_styled_content` (note the underscores). If the extension
is not active, activate it by selecting
the :guilabel:`Activate` button in the column labeled :guilabel:`A/D`.

.. figure:: /Images/ManualScreenshots/Installation/ActivateExtension.png
   :alt: Activate the extension by clicking the Activate button.

   Activate the extension by clicking the :guilabel:`Activate` button.

System Maintainer rights are required to activate the extension.

.. _upgrading:

Upgrade
=======

If you upgrade your TYPO3 CMS installation from one major version to another
(for example 10.4 to 11.5), it is advised to run the
:ref:`Upgrade Wizard <t3install:postupgradetasks>`.
It guides you through the necessary steps to upgrade your database records.

Open the tool at :guilabel:`Admin Tools > Upgrade > Upgrade Wizard` and run all
suggested steps.

Next step
=========

Include the :ref:`default TypoScript template <include-default-typoscript>`.
