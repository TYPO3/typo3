.. include:: /Includes.rst.txt

.. _feature-103187-1708943723:

======================================================================
Feature: #103187 - Introduce CLI command to create backend user groups
======================================================================

See :issue:`103187`

Description
===========

A new CLI command :bash:`./bin/typo3 setup:begroups:default` has been
introduced as an alternative to the existing backend module. This command
automates the creation of backend user groups, enabling the creation of
two pre-configured backend user groups with permission presets applied.

..  note::

    The pre-configured backend user group permissions are subject to be
    further changed and adjusted and defines a first set. It is also possible
    that additional groups may be added or made configurable. That means,
    that the :bash:`./bin/typo3 setup:begroups:default` command and the
    pre-defined permissions are considerable `experimental` during the
    TYPO3 v13 development cycle.

Impact
======

You can now use :bash:`./bin/typo3 setup:begroups:default` to create
pre-configured backend user groups without touching the GUI.

Example
-------

Interactive / guided setup (questions/answers):

..  code-block:: bash
    :caption: Basic command

    ./bin/typo3 setup:begroups:default

The backend user group can be set via the :bash:`--groups|-g` option. Allowed
values for groups are :bash:`Both`, :bash:`Editor` and :bash:`Advanced Editor`:

..  code-block:: bash
    :caption: Command examples

    ./bin/typo3 setup:begroups:default --groups Both
    ./bin/typo3 setup:begroups:default --groups Editor
    ./bin/typo3 setup:begroups:default --groups "Advanced Editor"

When using the :bash:`--no-interaction` option, this defaults to :bash:`Both`.

..  note::

    At the moment, the command does not support the creation of backend user
    groups with custom names or permissions (they can be modified later through
    the backend module). It is limited to creating two pre-configured backend
    user groups with permission presets applied.

.. index:: Backend, CLI, ext:backend
