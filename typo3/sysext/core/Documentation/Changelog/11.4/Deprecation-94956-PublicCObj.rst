.. include:: /Includes.rst.txt

==================================
Deprecation: #94956 - Public $cObj
==================================

See :issue:`94956`

Description
===========

Frontend plugins receive an instance of :php:`\TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer` when
called via :php:`ContentObjectRenderer->callUserFunction()`. This is
typically the case for plugins called as :typoscript:`USER` or indirectly
as :typoscript:`USER_INT` type.

The instance of :php:`ContentObjectRenderer` has previously been set by
declaring a public (!) property :php:`cObj` in the consuming class.

Handing a :php:`ContentObjectRenderer` instance around this way is hard to
follow and has thus been deprecated: Declaring :php:`public $cObj` should
be avoided. Frontend plugins that need the current :php:`ContentObjectRenderer`
should have a public :php:`setContentObjectRenderer()` method instead.


Impact
======

Declaring :php:`public $cObj` in a class called by
:php:`ContentObjectRenderer->callUserFunction()` triggers a PHP :php:`E_USER_DEPRECATED` error.


Affected Installations
======================

Frontend extension classes that neither extend :php:`TYPO3\CMS\Frontend\Plugin\AbstractPlugin`
("pibase") nor Extbase :php:`TYPO3\CMS\Extbase\Mvc\Controller\ActionController`
and have a public property :php:`cObj` are affected.


Migration
=========

When instantiating the frontend plugin, :php:`ContentObjectRenderer->callUserFunction()`
now checks for a public method :php:`setContentObjectRenderer()` to explicitly set
an instance of the :php:`ContentObjectRenderer`.

Many plugins may not need this instance at all. If the ContentObjectRenderer instance
used within the plugin does not rely on further ContentObjectRenderer state, for instance
if it only calls :php:`stdWrap()` or similar without using state like :typoscript:`LOAD_REGISTER`,
the :php:`cObj` class property should be avoided and an own instance of  ContentObjectRenderer
should be created.

Classes that do rely on current ContentObjectRenderer state should adapt their code.

Before::

    class Foo
    {
        public $cObj;
    }


After::

    class Foo
    {
        protected $cObj;

        public function setContentObjectRenderer(ContentObjectRenderer $cObj): void
        {
            $this->cObj = $cObj;
        }
    }


.. index:: Frontend, PHP-API, NotScanned, ext:frontend
