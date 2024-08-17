:navigation-title: Installation

.. include:: /Includes.rst.txt

.. _installation:

=========================================
Installation of system extension Recycler
=========================================

..  versionchanged:: 13.3
    The TYPO3 Core extension :composer:`typo3/cms-recycler` is now enabled by default for new
    Composer-based TYPO3 installations based on the TYPO3 CMS Base Distribution, and new
    "classic mode" (tarball / ZIP download) installations.

Target group: **Administrators**

This extension is part of the TYPO3 Core, and installed by default for
new Composer-based TYPO3 installations based on the TYPO3 CMS Base Distribution, and new
"classic mode" (tarball / ZIP download) installations.

..  contents:: Table of contents
    :local:

.. _installation-composer:

Installation of typo3/cms-recycler with Composer
================================================

Check whether you are already using the extension :composer:`typo3/cms-recycler` with:

..  code-block:: bash

    composer show | grep recycler

This should either give you no result or something similar to:

..  code-block:: none

    typo3/cms-recycler       v12.4.5

If it is not installed yet, use the :bash:`composer require` command to install
the extension :composer:`typo3/cms-recycler`:

..  code-block:: bash

    composer require typo3/cms-recycler

The given version depends on the version of the TYPO3 Core you are using.

.. _installation-legacy:

Installation of EXT:recycler without Composer
=============================================

In an installation without Composer, the extension is already shipped, but might
not be activated yet. Activate it as follows:

#.  In the backend, navigate to the :guilabel:`Admin Tools > Extensions`
    module.
#.  Click the :guilabel:`Activate` icon for the Recycler extension.

..  figure:: /Images/InstallActivate.png
    :class: with-border
    :alt: Extension manager showing the Recycler extension

    Activation of the Recycler extension

.. _installation-legacy-next-steps:

Next steps
==========

Configure the Recycler module to your needs via
:ref:`user TSconfig <configuration>`.
