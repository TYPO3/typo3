.. include:: /Includes.rst.txt

.. _feature-99341-1670827943:

===================================================
Feature: #99341 - Introduce CLI create user command
===================================================

See :issue:`99341`

Description
===========

A new CLI command `backend:user:create`, which automates backend user creation,
is introduced as an alternative to the existing backend module.

Impact
======

You can now use `./bin/typo3 backend:user:create` to create a backend user
without touching the GUI.

Example
-------

Interactive / guided setup (questions/answers):

..  code-block:: bash

    ./bin/typo3 backend:user:create

User creation using environment variables:

.. code-block:: bash

    TYPO3_BE_USER_NAME=username \
    TYPO3_BE_USER_EMAIL=admin@example.com \
    TYPO3_BE_USER_GROUPS=<comma-separated-list-of-group-ids> \
    TYPO3_BE_USER_ADMIN=0 \
    TYPO3_BE_USER_MAINTAINER=0 \
    ./bin/typo3 backend:user:create --no-interaction

.. warning::

    Variable `TYPO3_BE_USER_PASSWORD` and options `-p` or `--password` can be
    used to provide a password. Using this can be a security risk since the password
    may end up in shell history files. Prefer the interactive mode. Additionally,
    writing a command to shell history can be suppressed by prefixing the command
    with a space when using `bash` or `zsh`.

.. index:: ext:backend
