..  include:: /Includes.rst.txt
..  _installing:

============
Installation
============

This extension is part of the TYPO3 Core, but not installed by default.

..  contents:: Table of contents
    :local:

..  _installing-composer:

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


..  _installing-classic:

Classic installation without Composer
=====================================

In an installation without Composer, the extension is already shipped but might
not be activated yet. Activate it as follows:

#.  In the backend, navigate to the :guilabel:`System > Extensions`
    module.
#.  Click the :guilabel:`Activate` icon for the Scheduler extension.

..  figure:: /Images/InstallActivate.png
    :class: with-border
    :alt: Extension manager showing Scheduler extension

    Extension manager showing Scheduler extension

..  _installing-cnext:

Next steps
==========

Once the extension is installed, the following setting is available in
:guilabel:`System > Settings > Extension Configuration > scheduler`:

..  _installing-max-lifetime:

Maximum lifetime
----------------

:Setting: ``maxLifetime``
:Type: integer, in **minutes**
:Default: ``60``

While a task runs, the Scheduler stores an execution mark on it, which
makes it show up as "running". The mark is removed again when the task
finishes. If the PHP process dies before that, the mark stays. The most
common cause is a task exceeding the PHP ``memory_limit``; a timeout or a
killed process has the same effect.

If parallel execution is not allowed for the task (see
:ref:`tasks-execution`), a stale mark prevents the task from ever running
again. Every run of the Scheduler therefore removes execution marks that
are older than the maximum lifetime and writes a log entry for each of
them.

Removing the mark does **not** stop a process that is still running. If
a task legitimately runs longer than the maximum lifetime, its mark is
removed while it is still working, and the next Scheduler run can start
the same task a second time in parallel.

Set the maximum lifetime to a value that is higher than the runtime of
your longest-running task, but low enough that a crashed task is picked
up again within an acceptable time. An empty value or ``0`` falls back to
the default of 60 minutes, which is enough for regular tasks on current
hardware.

Work that takes longer, such as processing large amounts of data, is
better split into smaller chunks, for example by dispatching messages to
the `message bus <https://docs.typo3.org/permalink/t3coreapi:message-bus>`_
and consuming them with the ``messenger:consume`` command, instead of
running in a single long scheduler task.

A stale mark can also be cleared manually, see :ref:`stopping-a-task`.

..  figure:: /Images/ExtensionConfiguration.png
    :alt: Extension configuration

    Configuring the extension settings
