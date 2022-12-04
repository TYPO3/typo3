.. include:: /Includes.rst.txt

.. _feature-97747-1669740094:

=============================================
Feature: #99221 - Introduce CLI setup command
=============================================

See :issue:`99221`

Description
===========

To be able to automate the setup process for new TYPO3 installations,
a new CLI command `setup` is introduced as an alternative to the existing
GUI based web installer.

Impact
======

You can now use `./bin/typo3 setup` to set up your TYPO3 installation without
needing to run through the web installer.

Example
-------

Interactive / guided setup (questions/answers):

..  code-block:: bash

    ./bin/typo3 setup

Automated setup:

..  code-block:: bash

    TYPO3_DB_DRIVER=mysqli \
    TYPO3_DB_USERNAME=db \
    TYPO3_DB_PASSWORD=db \
    TYPO3_DB_PORT=3306 \
    TYPO3_DB_HOST=db \
    TYPO3_DB_DBNAME=db \
    TYPO3_SETUP_ADMIN_EMAIL=admin@email.com \
    TYPO3_SETUP_ADMIN_USERNAME=admin \
    TYPO3_SETUP_PASSWORD=password \
    TYPO3_PROJECT_NAME="Automated Setup" \
    TYPO3_CREATE_SITE="https://your-typo3-site.example.com/" \
    ./bin/typo3 setup --force


.. index:: ext:install
