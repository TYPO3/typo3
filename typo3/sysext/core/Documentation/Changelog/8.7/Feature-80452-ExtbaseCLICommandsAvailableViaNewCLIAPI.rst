.. include:: /Includes.rst.txt

================================================================
Feature: #80452 - Extbase CLI commands available via new CLI API
================================================================

See :issue:`80452`

Description
===========

Any Extbase Command Controller can now be accessed via the new Symfony Console CLI entrypoint by
simply calling ``typo3/sysext/core/bin/typo3 controller:command``.

Using the existing CLI entrypoint via ``typo3/cli_dispatch.phpsh extbase controller:command`` still
works as expected.

.. index:: CLI, ext:extbase
