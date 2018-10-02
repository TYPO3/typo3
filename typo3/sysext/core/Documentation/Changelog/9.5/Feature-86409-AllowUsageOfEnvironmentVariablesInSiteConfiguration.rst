.. include:: ../../Includes.txt

============================================================================
Feature: #86409 - Allow usage of environment variables in site configuration
============================================================================

See :issue:`86409`

Description
===========

To enable environment variable based configuration the TYPO3 Core Yaml loader has been adjusted to
be able to resolve environment variables. Resolving of variables in the loader can be enabled or
disabled via flags. When editing the site configuration through the backend interface the resolving
of environment variables needs to be disabled to be able to add environment configuration through
the interface.

The format for environment variables is `%env(ENV_NAME)%`. Environment variables may be used to replace
complete values or parts of a value.


Impact
======

In site configuration environment variables can be used. One common example would be the base url
that can now be configured via an environment variable.

Additionally, the Yaml Loader class has two new flags: :yaml:`PROCESS_PLACEHOLDERS` and :yaml:`PROCESS_IMPORTS`.

* :yaml:`PROCESS_PLACEHOLDERS` decides whether or not placeholders (`%abc%`) will be resolved.
* :yaml:`PROCESS_IMPORTS` decides whether or not imports (`imports` key) will be resolved.

Example usage in site configuration:

.. code-block:: yaml

	base: 'https://%env(BASE_DOMAIN)%/'

.. index:: Backend, ext:core
