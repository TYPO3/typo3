.. ==================================================
.. FOR YOUR INFORMATION
.. --------------------------------------------------
.. -*- coding: utf-8 -*- with BOM.

.. include:: ../../Includes.txt



.. _installing:

Installing the extension
^^^^^^^^^^^^^^^^^^^^^^^^

The process is pretty straightforward: just install the extension in
the Extension Manager. One database table will be created. Once the
extension is installed, the following settings are available:

- **Enable sample tasks** : the Scheduler provides two sample tasks
  (called "Scheduler test task" and "Scheduler sleep task") which are
  useful during development and as code examples. However they have
  little use in real life, so this option makes it possible to turn them
  off. If disabled, they won't appear in the list of available tasks
  anymore.

- **Maximum lifetime** : it may happen that a task crashes while
  executing. In this case it will stay in a state marked as "running".
  That may prevent it from being executed again, if parallel executions
  are denied (see "Tasks execution" above). The maximum lifetime
  parameter ensures that old executions are removed after a while. The
  lifetime is expressed in **minutes** . The default is 15 minutes.

.. figure:: ../../Images/ExtensionConfiguration.png
    :alt: Extension configuration

    Configuring the extension settings



