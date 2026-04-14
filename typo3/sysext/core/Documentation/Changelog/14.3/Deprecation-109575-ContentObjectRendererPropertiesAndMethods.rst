..  include:: /Includes.rst.txt

..  _deprecation-109575:

=======================================================================
Deprecation: #109575 - Various ContentObjectRenderer properties/methods
=======================================================================

See :issue:`109575`

Description
===========

Several properties and a methods of
:php:`\TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer` have been
deprecated.


Properties
----------

:php:`ContentObjectRenderer->$lastTypoLinkResult`
~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

The property held the :php:`\TYPO3\CMS\Frontend\Typolink\LinkResultInterface`
produced by the most recent :php:`createLink()` call. Relying on a
side-effect property that is overwritten on every subsequent link call is
fragile. Use the return value of :php:`createLink()` directly instead.


:php:`ContentObjectRenderer->$currentRecordNumber` and :php:`ContentObjectRenderer->$parentRecordNumber`
~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

These counters are incremented by :php:`ContentContentObject` and
:php:`RecordsContentObject` while iterating over their record sets, and
exposed to TypoScript via :typoscript:`getData cobj:parentRecordNumber`.
They carry no known use case for third-party extensions and are
deprecated.


:php:`ContentObjectRenderer->$checkPid_badDoktypeList`
~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

The property was intended to cache a comma-separated list of page
doctypes that should be excluded from link target checks, but it was
never written to or read from any code path in TYPO3 itself.


Methods
-------

:php:`ContentObjectRenderer->readFlexformIntoConf()`
~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

The method parses a FlexForm XML string or array into a flat TypoScript
configuration array. It only covered the ``sDEF`` sheet and had no
equivalent in TYPO3 core itself. Use
:php:`\TYPO3\CMS\Core\Configuration\FlexForm\FlexFormTools::convertFlexFormContentToArray()`
to decode FlexForm data, and map the result into your own configuration
structure as needed.


TypoScript getData type
-----------------------

The :typoscript:`cobj:parentRecordNumber` type for the
:ref:`getData <t3tsref:data-type-gettext-cobj>` function is deprecated.
It returned the value of :php:`$parentRecordNumber`, which is now
deprecated.


:php:`ContentObjectRenderer->getRequest()` fallback to :php:`$GLOBALS['TYPO3_REQUEST']`
----------------------------------------------------------------------------------------

The :php:`getRequest()` method falls back to :php:`$GLOBALS['TYPO3_REQUEST']` when no request
had been set via :php:`setRequest()` before. This fallback has been deprecated. Third-party code that
instantiates :php:`ContentObjectRenderer` must call :php:`setRequest(ServerRequestInterface $request)`
before calling :php:`start()` or any other method that requires the request.


Impact
======

Accessing :php:`$lastTypoLinkResult` or
:php:`$checkPid_badDoktypeList`, calling :php:`readFlexformIntoConf()`,
evaluating the :typoscript:`cobj:parentRecordNumber` getData type, or
triggering the :php:`$GLOBALS['TYPO3_REQUEST']` fallback in
:php:`getRequest()` all raise :php:`E_USER_DEPRECATED` errors at runtime.
The fallback will additionally throw an exception in TYPO3 v15 when no
request has been set.

:php:`$currentRecordNumber` and :php:`$parentRecordNumber` carry only a
docblock :php:`@deprecated` annotation for now — they remain functional
and do not raise runtime errors.


Affected installations
======================

Installations with extensions that:

* Read :php:`$cObj->lastTypoLinkResult` after calling :php:`createLink()`
* Read :php:`$currentRecordNumber`, :php:`$parentRecordNumber`, or
  :php:`$checkPid_badDoktypeList`
* Call :php:`$cObj->readFlexformIntoConf()`
* Use the :typoscript:`cobj:parentRecordNumber` getData type in TypoScript;
* Instantiate :php:`ContentObjectRenderer` without calling :php:`setRequest()` before :php:`start()`.

The extension scanner detects usages of the deprecated properties as
weak matches.


Migration
=========

:php:`$lastTypoLinkResult`
--------------------------

Capture the return value of :php:`createLink()` directly:

..  code-block:: php

    // Before
    $cObj->createLink($linkText, $conf);
    $result = $cObj->lastTypoLinkResult;

    // After
    $result = $cObj->createLink($linkText, $conf);


:php:`setRequest()` before :php:`start()`
-----------------------------------------

Call :php:`setRequest()` immediately after instantiation, before any other method:

..  code-block:: php

    // Before
    $cObj = GeneralUtility::makeInstance(ContentObjectRenderer::class);
    $cObj->start($data, $table);

    // After
    $cObj = GeneralUtility::makeInstance(ContentObjectRenderer::class);
    $cObj->setRequest($request);
    $cObj->start($data, $table);


:php:`readFlexformIntoConf()`
----------------------------

Replace with :php:`FlexFormTools::convertFlexFormContentToArray()` and
map the decoded array into the configuration structure as needed:

..  code-block:: php

    // Before
    $conf = [];
    $cObj->readFlexformIntoConf($flexFormXml, $conf);

    // After
    $conf = $this->flexFormTools->convertFlexFormContentToArray($flexFormXml);


All other deprecated items
--------------------------

Remove all usages. None of the remaining deprecated properties, nor
:typoscript:`cobj:parentRecordNumber` getData type have a replacement.

..  index:: PHP-API, TypoScript, PartiallyScanned, ext:frontend
