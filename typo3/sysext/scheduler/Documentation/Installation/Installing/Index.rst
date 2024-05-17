..  include:: /Includes.rst.txt

..  _installing:

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

    composer show | grep scheduler

This should either give you no result or something similar to:

..  code-block:: none

    typo3/cms-scheduler       v12.4.11

If it is not installed yet, use the ``composer require`` command to install
the extension:

..  code-block:: bash

    composer require typo3/cms-scheduler

The given version depends on the version of the TYPO3 Core you are using.

Installation without Composer
=============================

In an installation without Composer, the extension is already shipped but might
not be activated yet. Activate it as follows:

#.  In the backend, navigate to the :guilabel:`Admin Tools > Extensions`
    module.
#.  Click the :guilabel:`Activate` icon for the Scheduler extension.

..  figure:: /Images/InstallActivate.png
    :class: with-border
    :alt: Extension manager showing Scheduler extension

    Extension manager showing Scheduler extension

Next steps
==========

Once the extension is installed, the following setting is available:

- **Maximum lifetime** : it may happen that a task crashes while
  executing. In this case it will stay in a state marked as "running".
  That may prevent it from being executed again, if parallel executions
  are denied (see "Tasks execution" above). The maximum lifetime
  parameter ensures that old executions are removed after a while. The
  lifetime is expressed in **minutes** . The default is 15 minutes.

.. figure:: /Images/ExtensionConfiguration.png
    :alt: Extension configuration

    Configuring the extension settings
