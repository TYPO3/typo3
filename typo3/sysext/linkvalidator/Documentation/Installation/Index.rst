.. include:: /Includes.rst.txt

.. _installation:

============
Installation
============

This extension is part of the TYPO3 Core, but not installed by default.

..  contents:: Table of contents
    :local:

Installation with Composer
==========================

Check whether you are already using the extension with:

..  code-block:: bash

    composer show | grep linkvalidator

This should either give you no result or something similar to:

..  code-block:: none

    typo3/cms-linkvalidator       v12.4.11

If it is not installed yet, use the ``composer require`` command to install
the extension:

..  code-block:: bash

    composer require typo3/cms-linkvalidator

The given version depends on the version of the TYPO3 Core you are using.

Installation without Composer
=============================

In an installation without Composer, the extension is already shipped but might
not be activated yet. Activate it as follows:

#.  In the backend, navigate to the :guilabel:`Admin Tools > Extensions`
    module.
#.  Click the :guilabel:`Activate` icon for the LinkValidator extension.

..  figure:: /Images/ActivateLinkValidator.png
    :class: with-border
    :alt: Extension manager showing LinkValidator extension

    Extension manager showing LinkValidator extension

Next step
=========

LinkValidator uses the HTTP request library shipped with TYPO3.
Please have a look in the :ref:`Global Configuration <t3coreapi:typo3ConfVars>`,
particularly at the HTTP settings.

There, you may define a default timeout. Generally, it is recommended
to always specify timeouts when working with the LinkValidator.


