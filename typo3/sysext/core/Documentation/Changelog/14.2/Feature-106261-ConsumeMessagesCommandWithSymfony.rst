..  include:: /Includes.rst.txt

..  _feature-106261-1762614000:

=========================================================================================
Feature: #106261 - Align command line arguments of message consumer with Symfony original
=========================================================================================

See :issue:`106261`

Description
===========

This change aligns the command line arguments of the TYPO3 Console
`messenger:consume` command with the original Symfony Messenger
implementation.

The following new options have been added:

*   :bash:`--limit` / :bash:`-l`: Limits the number of received messages.
*   :bash:`--failure-limit` / :bash:`-f`: Limits the number of failed
    messages the worker can consume.
*   :bash:`--memory-limit` / :bash:`-m`: Sets the memory limit available
    to the worker.
*   :bash:`--time-limit` / :bash:`-t`: Sets the time limit in seconds
    during which the worker can handle new messages.
*   :bash:`--bus` / :bash:`-b`: Specifies the name of the bus to which
    received messages are dispatched.
*   :bash:`--all`: Consumes messages from all receivers.
*   :bash:`--keepalive`: Uses the transport keepalive mechanism, if
    implemented.

Scheduler integration
=====================

The command can be configured as a scheduler task in TYPO3, enabling
automated consumption of messages from the configured transports. This is
particularly useful for processing asynchronous messages in the
background.

This integration helps projects adopt asynchronous message handling by
providing a reliable way to process messages without manual
intervention. Messages can be dispatched asynchronously during normal
request handling and consumed in the background by the scheduler task,
improving application performance and user experience.

..  important::

    The :bash:`messenger:consume` command blocks other scheduler tasks
    from executing while it is running. It is therefore strongly
    recommended to set the :bash:`--time-limit` option to a value lower
    than the scheduler's cron interval.

    For example, if the scheduler runs every 5 minutes (300 seconds),
    set the time limit to 240 seconds (4 minutes) to ensure the task
    completes before the next scheduler run and allows other tasks to
    execute.

Usage
=====

Consume messages from a specific receiver:

..  code-block:: bash

    vendor/bin/typo3 messenger:consume my_receiver

Consume messages with a message limit:

..  code-block:: bash

    vendor/bin/typo3 messenger:consume my_receiver --limit=10

Stop the worker after 2 failed messages:

..  code-block:: bash

    vendor/bin/typo3 messenger:consume my_receiver --failure-limit=2

Stop the worker when the memory limit is exceeded:

..  code-block:: bash

    vendor/bin/typo3 messenger:consume my_receiver --memory-limit=128M

Stop the worker after a time limit:

..  code-block:: bash

    vendor/bin/typo3 messenger:consume my_receiver --time-limit=3600

Consume from specific queues only:

..  code-block:: bash

    vendor/bin/typo3 messenger:consume my_receiver --queues=fasttrack

Consume from all configured receivers:

..  code-block:: bash

    vendor/bin/typo3 messenger:consume --all

..  index:: PHP-API, ext:core
