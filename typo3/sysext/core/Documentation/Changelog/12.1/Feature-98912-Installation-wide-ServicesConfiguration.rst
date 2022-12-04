.. include:: /Includes.rst.txt

.. _feature-98912-1667814888:

==========================================================
Feature: #98912 - Installation-wide services configuration
==========================================================

See :issue:`98912`

Description
===========

It is possible to set up a global services configuration for a
project that can be used in multiple project-specific extensions. This way you
can, for example, alias an interface with a concrete implementation to be used in
several extensions. It is also possible to register project-specific CLI commands
without having the need for a project-specific extension.

However, this only works - due to security restrictions - if TYPO3 is configured
in a way that the project root is outside the document root, which usually
happens in Composer-based installations.

Impact
======

The global services configuration files :file:`services.yaml` and
:file:`services.php` are now read within the the :file:`config/system/` path
of a TYPO3 project in Composer-based installations.

Example
-------

You want to use the interface of the PHP package `stella-maris/clock` as type
hint for DI in the service classes of your project's various extensions. Then
the concrete implementation may change without touching your code. In this
example we use `lcobucci/clock` for the concrete implementation.

..  code-block:: php
    :caption: config/system/services.php

    use Lcobucci\Clock\SystemClock;
    use StellaMaris\Clock\ClockInterface;
    use Symfony\Component\DependencyInjection\ContainerBuilder;
    use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;

    return static function (ContainerConfigurator $containerConfigurator, ContainerBuilder $containerBuilder): void {
        $services = $containerConfigurator->services();
        $services->set(ClockInterface::class)
            ->factory([SystemClock::class, 'fromUTC']);
    };


.. index:: ext:core
