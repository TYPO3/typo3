.. include:: /Includes.rst.txt



.. _installing:

Installing the extension
^^^^^^^^^^^^^^^^^^^^^^^^

The process is pretty straightforward: just install the extension in
the Extension Manager. One database table will be created. Once the
extension is installed, the following setting is available:

- **Maximum lifetime** : it may happen that a task crashes while
  executing. In this case it will stay in a state marked as "running".
  That may prevent it from being executed again, if parallel executions
  are denied (see "Tasks execution" above). The maximum lifetime
  parameter ensures that old executions are removed after a while. The
  lifetime is expressed in **minutes** . The default is 15 minutes.

.. figure:: ../../Images/ExtensionConfiguration.png
    :alt: Extension configuration

    Configuring the extension settings



