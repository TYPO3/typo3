.. include:: /Includes.rst.txt

.. _deprecation-100670-1681916011:

================================================
Deprecation: #100670 - DI-aware FormEngine nodes
================================================

See :issue:`100670`

Description
===========

When the FormEngine construct (used when editing records in the backend) has
been rewritten back in TYPO3 v7, dependency injection for non-Extbase
constructs has not been a thing, yet.

With dependency injection being part of the TYPO3 Core extension since TYPO3 v10,
and the Extbase solution being out-phased, it is time to make FormEngine
dependency injection aware as well.

This has some impact on classes implementing
:php:`\TYPO3\CMS\Backend\Form\NodeInterface` directly, or indirectly by
extending :php:`\TYPO3\CMS\Backend\Form\AbstractNode` and
:php:`\TYPO3\CMS\Backend\Form\Element\AbstractFormElement`. Custom
implementations *can* use this already, but the full power will
only be leveraged with TYPO3 v13.

Similar changes as described below can be done for classes implementing
:php:`\TYPO3\CMS\Backend\Form\NodeResolverInterface` as well, but the
impact is much smaller since this construct is used less often in the wild.

Additionally, classes should either implement one of the interfaces
directly, or extend an appropriate abstract. They must not extend any
of the existing "leaf" classes the core provides, since those will be
declared :php:`final` with TYPO3 v13.


Impact
======

Using dependency injection within FormEngine related classes
becomes possible in TYPO3 v12.


Affected installations
======================

Instances with extensions that come with own FormEngine additions
may be affected. The extensions scanner is not configured to find
affected classes.


Migration
=========

Compatibility with TYPO3 v11 and v12
------------------------------------

Extensions that strive for both TYPO3 v11 and v12 compatibility should
just keep their implementation as is.

Compatibility with TYPO3 v12 and v13
------------------------------------

Extensions that strive for TYPO3 v12 compatibility, skipping v11, that
want to support v13 as well, must adapt their implementations.

As main change, :php:`NodeInterface` no longer declares :php:`__construct()`,
the class constructor is now "free" for injection. The :php:`NodeFactory` uses
the existence of method :php:`setData()` as indicator if :php:`NodeFactory` and
:php:`$data` array should be hand over as manual constructor argument (old way),
or if :php:`setData()` should be called after object instantiation. Note
:php:`setData()` will be activated as interface method with TYPO3 v13.

A class with both TYPO3 v12 and v13 compatibility should look like this:

..  code-block:: php

    public function __construct(
        // If the class creates sub elements
        NodeFactory $nodeFactory,
        // If the class needs IconFactory
        IconFactory $iconFactory,
        // Further dependencies
        private readonly MyService $myService,
    ) {
        $this->nodeFactory = $nodeFactory;
        $this->iconFactory = $iconFactory;
    }

    public function setData(array $data): void
    {
        $this->data = $data;
    }

    public function render(): array
    {
        // Implement render(), note the "array" return type hint,
        // which will be mandatory in TYPO3 v13.
    }

The class has to be registered for public DI in :file:`Services.yaml` as well, since
it is instantiated by :php:`NodeFactory` using :php:`GeneralUtility::makeInstance()`:

..  code-block:: yaml

    MyVendor\MyExtension\Form\Element\MyElementClass:
      public: true


Compatibility with v13
----------------------

Extensions dropping TYPO3 v12 compatibility and going with v13 and up, can
simplify the construct: In v13, :php:`setData()` will be added to :php:`AbstractNode`,
extending classes don't need to implement it anymore. The two class
properties :php:`$nodeFactory` and :php:`$iconFactory` (:php:`AbstractFormElement`
only) will be removed from the abstracts, constructor property promotion
can be used for them. Also, a dependency injection service provider pass will
be added, to automatically set classes public that implement implement :php:`NodeInterface`,
so a :yaml:`public: true` entry in :file:`Services.yaml` can be skipped.

A typical class extending :php:`AbstractNode` looks like this:

..  code-block:: php

    public function __construct(
        private readonly NodeFactory $nodeFactory,
        private readonly IconFactory $iconFactory,
        private readonly MyService $myService,
    ) {
    }

    // Implement render().


.. index:: PHP-API, NotScanned, ext:backend
