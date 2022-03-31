
.. include:: /Includes.rst.txt

================================================================
Feature: #64726 - Added support for multiple FlashMessage queues
================================================================

See :issue:`64726`

Description
===========

In Extbase ControllerContext the method `getFlashMessageQueue()` now optionally
allows to specify which queue to fetch. If none is specified the `default-
messagequeue` for the current controller/plugin will be used.

.. code-block:: php

	$this->controllerContext->getFlashMessageQueue($queueIdentifier);

In Fluid the flashMessages-ViewHelper also allows to specify a queue to
use.

.. code-block:: html

	<f:flashMessages queueIdentifier="myQueue" />


Impact
======

Extensions may now render foreign flash message queues and add messages
to them.


.. index:: PHP-API, Fluid, ext:extbase
