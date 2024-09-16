.. include:: /Includes.rst.txt

.. _feature-104773-1724939348:

=======================================
Feature: #104773 - Generic view factory
=======================================

See :issue:`104773`

Description
===========

Class :php:`\TYPO3\CMS\Core\View\ViewFactoryInterface` has been added as a
generic view interface to create views that return an instance of
:php:`\TYPO3\CMS\Core\View\ViewInterface`. This implements the "V" of "MVC"
in a generic way and is used throughout the TYPO3 Core.

This obsoletes all custom view instance creation approaches within the TYPO3 Core
and within TYPO3 extensions. Extensions should retrieve view instances based
on this :php-short:`\TYPO3\CMS\Core\View\ViewFactoryInterface`.

Impact
======

Instances of this interface should be injected using dependency injection. The
default injected implementation is a Fluid view, and can be reconfigured using
dependency injection configuration, typically in a :file:`Services.yaml` file.

A casual example to create and render a view looks like this.

.. code-block:: php

    use TYPO3\CMS\Core\View\ViewFactoryInterface;

    class MyController
    {
        public function __construct(
            private readonly ViewFactoryInterface $viewFactory,
        ) {}

        public function myAction(ServerRequestInterface $request): string
        {
            $viewFactoryData = new ViewFactoryData(
                templateRootPaths: ['EXT:myExt/Resources/Private/Templates'],
                partialRootPaths: ['EXT:myExt/Resources/Private/Partials'],
                layoutRootPaths: ['EXT:myExt/Resources/Private/Layouts'],
                request: $request,
            );
            $view = $this->viewFactory->create($viewFactoryData);
            $view->assign('myData', 'myData');
            return $view->render('path/to/template');
        }
    }

Note Extbase-based extensions create a view instance based on this factory
by default and are accessible as :php:`$this->view`.

.. index:: Fluid, PHP-API, ext:core
