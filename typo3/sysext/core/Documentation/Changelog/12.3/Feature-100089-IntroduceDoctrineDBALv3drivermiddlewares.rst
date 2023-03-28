.. include:: /Includes.rst.txt

.. _feature-100089-1677961107:

================================================================
Feature: #100089 - Introduce Doctrine DBAL v3 driver middlewares
================================================================

See :issue:`100089`

Description
===========

Since v3, Doctrine DBAL supports adding custom driver middlewares. These
middlewares act as a decorator around the actual `Driver` component.
Subsequently, the `Connection`, `Statement` and `Result` components can be
decorated as well. These middlewares must implement the
:php:`\Doctrine\DBAL\Driver\Middleware` interface.
A common use case would be a middleware for implementing SQL logging capabilities.

For more information on driver middlewares,
see https://www.doctrine-project.org/projects/doctrine-dbal/en/current/reference/architecture.html.
Furthermore, you can look up the implementation of the
:php:`\TYPO3\CMS\Adminpanel\Log\DoctrineSqlLoggingMiddleware` in ext:adminpanel
as an example.

Registering a new driver middleware
===================================

..  code-block:: php

    $GLOBALS['TYPO3_CONF_VARS']['DB']['Connections']['Default']['driverMiddlewares']['adminpanel_loggingmiddleware']
        = \TYPO3\CMS\Adminpanel\Log\DoctrineSqlLoggingMiddleware::class;

Impact
======

Using custom middlewares allows to enhance the functionality of Doctrine
components.

.. index:: Database, ext:core
