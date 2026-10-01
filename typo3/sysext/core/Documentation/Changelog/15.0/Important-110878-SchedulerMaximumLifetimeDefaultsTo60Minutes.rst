..  include:: /Includes.rst.txt

..  _important-110878-1790888400:

======================================================================
Important: #110878 - Scheduler maximum lifetime defaults to 60 minutes
======================================================================

See :issue:`110878`

Description
===========

The default value of the extension setting
:php:`$GLOBALS['TYPO3_CONF_VARS']['EXTENSIONS']['scheduler']['maxLifetime']`
has been lowered from 1440 minutes (24 hours) to 60 minutes. The same
value is used as fallback when the setting is empty or `0`.

The maximum lifetime defines when the Scheduler considers a task that
is still marked as "running" to be crashed. Every Scheduler run removes
such stale execution marks, so tasks that do not allow parallel
execution are picked up again. The typical cause of a stale mark is a
task exceeding the PHP `memory_limit`. With the previous default, a
single crashed run blocked such a task for a whole day.

60 minutes is enough for regular tasks on current hardware. Long-running
work is better split into smaller units and dispatched to the
:ref:`message bus <t3coreapi:message-bus>`, where it is processed by
the :bash:`messenger:consume` command.

Removing the execution mark does not stop a process that is still
running. Installations with tasks that legitimately run longer than 60
minutes and do not allow parallel execution should raise the setting
above the runtime of their longest task in
:guilabel:`System > Settings > Extension Configuration > scheduler`.
Otherwise such a task may be started a second time while the first run
is still in progress.

Existing installations are not changed: activating the extension writes
its settings, including the previous default of `1440`, to
:file:`config/system/settings.php`. To use the new default, set the value
to `60` in the extension configuration.

..  index:: LocalConfiguration, ext:scheduler
