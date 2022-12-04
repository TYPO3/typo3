.. include:: /Includes.rst.txt

.. _deprecation-99098-1668546853:

===========================================================
Deprecation: #99098 - Static usage of FormProtectionFactory
===========================================================

See :issue:`99098`

Description
===========

:php:`\TYPO3\CMS\Core\FormProtection\FormProtectionFactory` has been
constructed in a static class manner in TYPO3 v6.2, using a static property
based instance cache to avoid recreating instances for a specific typed
FormProtection implementation. This design made it impossible to retrieve an
instance of this class via dependency injection. Another side-effect was that
ensuring a properly cleared state between tests has been hard and often
spread to other tests and thus influencing them.

To mitigate these issues, :php:`\TYPO3\CMS\Core\FormProtection\FormProtectionFactory`
is now transformed to a non-static class usage with injected services
and the Core runtime cache, removing the static property cache.

Based on these changes, the old static methods :php:`get()` and
:php:`purgeInstances()` are now deprecated.

There are two general ways to get a specific FormProtection implementation:

* auto-detected from request: :php:`$formProtectionFactory->createFromRequest()`
* create for a specific type: :php:`$formProtectionFactory->createForType()`

Possible types for :php:`$formProtectionFactory->createForType()` are `frontend`
`backend`, `installtool` or `disabled`.


Impact
======

Using any of the following class methods

* :php:`\TYPO3\CMS\Core\FormProtection\FormProtectionFactory::get()`
* :php:`\TYPO3\CMS\Core\FormProtection\FormProtectionFactory::purgeInstances()`

will trigger a PHP deprecation notice and will throw a fatal PHP error in
TYPO3 v13.


Affected installations
======================

The extension scanner will find extensions calling :php:`FormProtectionFactory::get()`
or :php:`FormProtectionFactory::purgeInstances()` as "strong" matches.


Migration
=========

Provided implementation by TYPO3 core
-------------------------------------

Before

..  code-block:: php

    // use TYPO3\CMS\Core\FormProtection\FormProtectionFactory;

    // BackendFormProtection
    $formProtection = FormProtectionFactory::get(BackendFormProtection::class);
    $formProtection = FormProtectionFactory::get('backend');

    // FrontendFormProtection
    $formProtection = FormProtectionFactory::get(FrontedFormProtection::class);
    $formProtection = FormProtectionFactory::get('frontend');

    // Default / Disabled FormProtection
    $formProtection = FormProtectionFactory::get(DisabledFormProtection::class);
    $formProtection = FormProtectionFactory::get('default');

After

It is recommended to use :php:`FormProtectionFactory->createForRequest()` to
auto-detect which type is needed and return the corresponding instance:

..  code-block:: php

    // use TYPO3\CMS\Core\FormProtection\FormProtectionFactory;

    // Better: Get FormProtectionFactory injected by DI.
    $formProtectionFactory = GeneralUtility::makeInstance(FormProtectionFactory::class);
    // $request is assumed to be available, for instance in controller classes.
    $formProtection = $formProtectionFactory->createFromRequest($request);

To create a specific type directly, using following replacements:

..  code-block:: php

    // use TYPO3\CMS\Core\FormProtection\FormProtectionFactory;
    // Better: Get FormProtectionFactory injected by DI.
    $formProtectionFactory = GeneralUtility::makeInstance(FormProtectionFactory::class);

    // BackendFormProtection
    $formProtection = $formProtectionFactory->createFromType('backend');

    // FrontendFormProtection
    $formProtection = $formProtectionFactory->createFromType('frontend');

    // Default / Disabled FormProtection
    $formProtection = $formProtectionFactory->createFromType('disabled');

Custom FormProtection-based implementation
------------------------------------------

Before

..  code-block:: php

    // use TYPO3\CMS\Core\FormProtection\FormProtectionFactory;

    $formProtection = FormProtectionFactory::get(
        Vendor\ExtensionKey\FormProtection\CustomFormProtection::class,
        $customService,
        'someDirectValue',
        ...
    );

After

..  code-block:: php

    // Create an instance of the class yourself, take care of an
    // instance cache if needed.
    GeneralUtility::makeInstance(
        Vendor\ExtensionKey\FormProtection\CustomFormProtection::class,
        $constructorArguments
    );


.. index:: PHP-API, FullyScanned, ext:core
