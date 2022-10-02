.. include:: /Includes.rst.txt

.. _breaking-96904:

===================================================================
Breaking: #96904 - ext:reports reports do not receive parent object
===================================================================

See :issue:`96904`

Description
===========

Extensions that add own reports in EXT:reports do not receive an instance
of :php:`TYPO3\CMS\Reports\Controller\ReportController` as constructor
argument anymore.

Handing over "parent object" to single reports as manual constructor argument
was pretty much useless since all state in :php:`ReportController`
is :php:`protected`. Not having this constructor argument has the advantage
that reports can now use dependency injection.

Impact
======

Extensions that register own reports to the EXT:reports extension and type hint
:php:`ReportController` as constructor argument will trigger a fatal PHP error
since that argument is no longer provided by the API.

Affected Installations
======================

Instances with extensions that add own reports to EXT:reports may be affected.

Migration
=========

Do not expect to retrieve an instance of :php:`ReportController` as constructor
argument anymore. Code before:

..  code-block:: php

    class MyClass implements ReportInterface
    {
        public function __construct(ReportController $reportController)
        {
            // ...
        }
    }

..  code-block:: php

    class MyClass implements ReportInterface
    {
        // No manual constructor argument anymore, but have a dependency injection as example.
        public function __construct(private readonly SomeDependency $someDependency)
        {
        }
    }

Single reports are currently instantiated using :php:`GeneralUtility::makeInstance()`.
To use dependency injection in own reports, a report class thus needs to be defined
:yaml:`public: true` in a :file:`Configuration/Services.yaml` file. This may change with
further TYPO3 v12 development if the reports registration is changed, though. If in doubt,
just try to go without :yaml:`public: true`. If this leads to a fatal PHP error, add it.

.. index:: Backend, PHP-API, NotScanned, ext:reports
