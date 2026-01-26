..  include:: /Includes.rst.txt

..  _feature-108776-1769518546:

=====================================================================================
Feature: #108776 - Allow to set user interface language when using CLI to create user
=====================================================================================

See :issue:`108776`

Description
===========

The CLI command `backend:user:create` now supports the option `--language` (or `-l`) to set the desired language for the user interface.

..  code-block:: bash

    ./bin/typo3 backend:user:create --language=de

User creation using environment variables:

..  code-block:: bash

    TYPO3_BE_USER_NAME=username \
    TYPO3_BE_USER_EMAIL=admin@example.com \
    TYPO3_BE_USER_GROUPS=<comma-separated-list-of-group-ids> \
    TYPO3_BE_USER_LANGUAGE=de \
    TYPO3_BE_USER_ADMIN=0 \
    TYPO3_BE_USER_MAINTAINER=0 \
    ./bin/typo3 backend:user:create --no-interaction


..  index:: CLI, ext:backend
