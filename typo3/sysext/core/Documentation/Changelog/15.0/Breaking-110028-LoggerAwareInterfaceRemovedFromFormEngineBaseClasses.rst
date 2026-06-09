.. include:: /Includes.rst.txt

.. _breaking-110028-1781047680:

=============================================================================
Breaking: #110028 - LoggerAwareInterface removed from FormEngine base classes
=============================================================================

See :issue:`110028`

Description
===========

The FormEngine base classes
:php:`\TYPO3\CMS\Backend\Form\AbstractNode` and
:php:`\TYPO3\CMS\Backend\Form\FormDataProvider\AbstractDatabaseRecordProvider`
no longer implement :php:`\Psr\Log\LoggerAwareInterface` and no longer use
:php:`\Psr\Log\LoggerAwareTrait`.

Both base classes never read the injected logger themselves. The node hierarchy
built on :php:`AbstractNode` does not use a logger at all. The two data provider
subclasses that did log, :php:`\TYPO3\CMS\Backend\Form\FormDataProvider\TcaFiles`
and :php:`\TYPO3\CMS\Backend\Form\FormDataProvider\TcaInline`, now receive a
:php:`\Psr\Log\LoggerInterface` through their constructor instead.

Impact
======

Subclasses of these base classes, for example custom FormEngine nodes or custom
database record providers shipped by extensions, are no longer recognized as
:php:`\Psr\Log\LoggerAwareInterface` instances and no longer have a logger
injected automatically. The inherited :php:`setLogger()` method is gone, and
reading the previously inherited :php:`$logger` property raises an error.

Affected installations
======================

Instances with third-party extensions that subclass
:php:`\TYPO3\CMS\Backend\Form\AbstractNode` or
:php:`\TYPO3\CMS\Backend\Form\FormDataProvider\AbstractDatabaseRecordProvider`
and rely on the automatically injected logger.

Migration
=========

Request a :php:`\Psr\Log\LoggerInterface` as a constructor argument in the
affected subclass. The subclass must be registered as a public service using the
:php:`#[Autoconfigure(public: true)]` attribute, so that the FormEngine
:php:`\TYPO3\CMS\Backend\Form\NodeFactory` obtains it through the dependency
injection container, which then resolves the constructor argument automatically.

.. code-block:: php

    use Psr\Log\LoggerInterface;
    use Symfony\Component\DependencyInjection\Attribute\Autoconfigure;
    use TYPO3\CMS\Backend\Form\AbstractNode;

    #[Autoconfigure(public: true)]
    final class MyFormElement extends AbstractNode
    {
        public function __construct(
            private readonly LoggerInterface $logger,
        ) {}

        public function render(): array
        {
            $this->logger->warning('Something the element wants to log');
            // ...
        }
    }

.. index:: Backend, PHP-API, NotScanned, ext:backend
