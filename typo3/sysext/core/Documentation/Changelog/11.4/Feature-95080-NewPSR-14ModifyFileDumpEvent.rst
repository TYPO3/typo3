.. include:: /Includes.rst.txt

================================================
Feature: #95077 - New PSR-14 ModifyFileDumpEvent
================================================

See :issue:`95077`

Description
===========

A new PSR-14 event :php:`\TYPO3\CMS\Core\Resource\Event\ModifyFileDumpEvent`
has been added to TYPO3 Core. This event is fired in the
:php:`\TYPO3\CMS\Core\Controller\FileDumpController` and allows extensions
to perform additional access / security checks before dumping a file. The
event does not only contain the file to dump but also the PSR-7 Request.

In case the file dump should be rejected, the event has to set a PSR-7
:php:`\Psr\Http\Message\ResponseInterface`, usually with a `403` status code.
This will then immediately stop the propagation.

With the new event, it's not only possible to reject the file dump request,
but also to replace the file, which should be dumped.

Registration of the Event in your extensions' :file:`Services.yaml`:

.. code-block:: yaml

  MyVendor\MyPackage\Resource\MyEventListener:
    tags:
      - name: event.listener
        identifier: 'my-package/resource/my-event-listener'

The corresponding event listener class:

.. code-block:: php

    use TYPO3\CMS\Core\Resource\Event\ModifyFileDumpEvent;

    class MyEventListener {

        public function __invoke(ModifyFileDumpEvent $event): void
        {
            // do magic here
        }

    }

Impact
======

This event can be used to modify the file dump request, by either
adding an alternative response or by replacing the file being dumped.

.. index:: PHP-API, ext:core
