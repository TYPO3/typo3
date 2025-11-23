..  include:: /Includes.rst.txt

..  _breaking-108277-1763903595:

=========================================================================
Breaking: #108277 - Remove superfluous CacheHashCalculator public methods
=========================================================================

See :issue:`108277`

Description
===========

The following superfluous public methods have been removed:

*   :php:`\TYPO3\CMS\Frontend\Page\CacheHashCalculator::setConfiguration()`
*   :php:`\TYPO3\CMS\Frontend\Page\CacheHashConfiguration::with()`


Impact
======

Calling the removed methods will result in a fatal PHP error.

Both methods were only used internally for testing purposes and were not part
of the public API contract.


Affected installations
======================

TYPO3 installations that used these methods directly in custom code are
affected. However, this is highly unlikely as they were intended for internal
testing only.


Migration
=========

Instead of modifying configuration after instantiation using the removed
:php:`setConfiguration()` method, merge configuration arrays before creating
the :php:`CacheHashConfiguration` instance, then pass it to the
:php:`CacheHashCalculator` constructor.

Example:

..  code-block:: php

    // Before (removed approach):
    $subject = new CacheHashCalculator(
        new CacheHashConfiguration($baseConfiguration),
        $hashService
    );
    $subject->setConfiguration([
        'cachedParametersWhiteList' => ['whitep1', 'whitep2'],
    ]);

    // After (correct approach):
    $configuration = new CacheHashConfiguration(
        array_merge($baseConfiguration, [
            'cachedParametersWhiteList' => ['whitep1', 'whitep2'],
        ])
    );
    $subject = new CacheHashCalculator($configuration, $hashService);

..  index:: Frontend, FullyScanned, ext:core