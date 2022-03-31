.. include:: /Includes.rst.txt

=======================================================================
Important: #92655 - Make request timeout configurable for linkvalidator
=======================================================================

See :issue:`92655`

Description
===========

The external link checking now uses a default (total) timeout of 20 seconds.
Previously, a timeout was not set, which resulted in the default from
Global Configuration :php:`$GLOBALS['TYPO3_CONF_VARS']['HTTP']['timeout']`
being used, which was 0 by default. 0 means no timeout.

In some edge cases, this caused the link checking to hang indefinitely,
which also lead to a scheduler task hanging indefinitely.

The timeout now defaults to 20 seconds (which is twice the time that is set
as connect_timeout in the core Global Configuration).

The timeout can be changed in Page TSconfig:

.. code-block:: typoscript

   mod.linkvalidator.linktypesConfig.external.timeout = 10

You can also unset it, which will result in the Global Configuration
:php:`$GLOBALS['TYPO3_CONF_VARS']['HTTP']['timeout']` being used:

.. code-block:: typoscript

   mod.linkvalidator.linktypesConfig.external.timeout >

.. important::

   It is not recommended to use 0.

Background information
======================

The Linkvalidator :php:`ExternalLinktype` class uses the core
:php:`RequestFactory` (which uses Guzzle under the hood).
:php:`RequestFactory::request` expects a set of options where
the timeout can be passed along.

If it is not, the core :php:`$GLOBALS['TYPO3_CONF_VARS']['HTTP']['timeout']`
is used.

If a timeout for querying an external link is not set, the request may linger
indefinitely and will not terminate. See the related issues for steps to
reproduce this.

How does HTTP request timeout generally work?
---------------------------------------------

Depending on the library used and the tool, you can usually set:

* connect timeout
* read timeout
* general timeout

Libraries and utilities often have these options separately, including
- for example - the curl command line tool or Guzzle.

TYPO3 uses Guzzle under the hood.

Core Global Configuration
-------------------------

These are currently the defaults in the core:

* :php:`$GLOBALS['TYPO3_CONF_VARS']['HTTP']['connect_timeout'] = 10;`
* :php:`$GLOBALS['TYPO3_CONF_VARS']['HTTP']['timeout'] = 0;`

This sets the corresponding timeouts in Guzzle.

Guzzle request options
----------------------

These are currently the default timeouts in Guzzle (but connect_timeout
and timeout will be overridden by the core):

* connect_timeout: 0
* read_timeout: Defaults to the value of the default_socket_timeout PHP ini
  setting
* timeout: 0

More information
================

* `Guzzle Request Options <https://docs.guzzlephp.org/en/stable/request-options.html>`__
* see :file:`typo3/sysext/core/Configuration/DefaultConfiguration.php in core`
* see :php:`GuzzleClientFactory` and :php:`RequestFactory` in the core

.. index:: Backend, ext:linkvalidator
