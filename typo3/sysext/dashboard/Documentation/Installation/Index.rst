.. include:: /Includes.rst.txt
.. highlight:: bash

.. _installation:

============
Installation
============

Target group: **Administrators**

This extension is part of the TYPO3 Core, but not installed by default.

..  contents:: Table of contents
    :local:

Installation with Composer
==========================

Check whether you are already using the extension with:

..  code-block:: bash

    composer show | grep dashboard

This should either give you no result or something similar to:

..  code-block:: none

    typo3/cms-dashboard       v12.4.11

If it is not installed yet, use the ``composer require`` command to install
the extension:

..  code-block:: bash

    composer require typo3/cms-dashboard

The given version depends on the version of the TYPO3 Core you are using.

Installation without Composer
=============================

In an installation without Composer, the extension is already shipped. You just have to activate it.
Head over to the extension manager and activate the extension.

.. figure:: /Images/InstallActivate.png
   :class: with-shadow
   :alt: Extension manager showing Dashboard extension

   Extension manager showing Dashboard extension
