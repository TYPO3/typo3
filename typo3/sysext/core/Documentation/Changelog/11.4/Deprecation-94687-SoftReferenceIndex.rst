.. include:: /Includes.rst.txt

==================================================
Deprecation: #94687 - Deprecate SoftReferenceIndex
==================================================

See :issue:`94687`

Description
===========

The :php:`TYPO3\CMS\Core\Database\SoftReferenceIndex` class combined all core
soft reference parser implementations into one class. Each and every parser
had its own method residing in one class. It is now possible to define
a dedicated class for each parser, as a result :php:`SoftReferenceIndex` is not
needed anymore and has been therefore marked as deprecated.

The related method :php:`\TYPO3\CMS\Backend\Utility\BackendUtility::softRefParserObj()`
was used to get the according soft reference parser object and was basically a
factory method. This logic has been moved into
:php:`TYPO3\CMS\Core\DataHandling\SoftReference\SoftReferenceParserFactory`.
:php:`BackendUtility::softRefParserObj` has been marked as internal in TYPO3 v11
already. To ease migration, the old static method is still in place and triggers
a PHP :php:`E_USER_DEPRECATED` error when called.

Another tightly coupled method :php:`\TYPO3\CMS\Backend\Utility\BackendUtility::explodeSoftRefParserList()`,
which was used to parse the comma separated list of soft reference parsers
and return them as an array, has now also been marked as deprecated. It was mostly used
for internal purposes. The corresponding logic now resides in the
:php:`getParsersBySoftRefParserList` method of
:php:`TYPO3\CMS\Core\DataHandling\SoftReference\SoftReferenceParserFactory`.

All soft reference parsers are now required to implement the
:php:`TYPO3\CMS\Core\DataHandling\SoftReference\SoftReferenceParserInterface`.
Not doing so will trigger a PHP :php:`E_USER_DEPRECATED` error. In TYPO3 v12 this will throw an exception.

Impact
======

The following class is marked as deprecated. Instantiating this class will
trigger a PHP :php:`E_USER_DEPRECATED` error.

*  :php:`TYPO3\CMS\Core\Database\SoftReferenceIndex`

The following methods are marked as deprecated. Calling these methods will trigger a PHP :php:`E_USER_DEPRECATED` error.

*  :php:`\TYPO3\CMS\Backend\Utility\BackendUtility::softRefParserObj()`
*  :php:`\TYPO3\CMS\Backend\Utility\BackendUtility::explodeSoftRefParserList()`

Soft reference parsers must implement
:php:`TYPO3\CMS\Core\DataHandling\SoftReference\SoftReferenceParserInterface`.
Otherwise a a PHP :php:`E_USER_DEPRECATED` error will be triggered and an exception will be thrown
in TYPO3 v12.

Affected Installations
======================

*  All installations registering user-defined soft reference parsers not
   implementing :php:`TYPO3\CMS\Core\DataHandling\SoftReference\SoftReferenceParserInterface`.
*  All installations calling any of the above-mentioned methods.
*  All installations, which are using :php:`TYPO3\CMS\Core\Database\SoftReferenceIndex`
   directly.

Migration
=========

Among other methods
:php:`TYPO3\CMS\Core\DataHandling\SoftReference\SoftReferenceParserInterface`
ensures the method :php:`parse` is implemented. The previously used method name
:php:`findRef()` can be simply renamed to :php:`parse()`. The first 4 parameters
:php:`$table`, :php:`$field`, :php:`$uid` and :php:`$content` stay the same, as
well as the seventh (now fifth) and last parameter :php:`$structurePath`. The
remaining two parameters :php:`$spKey` (now :php:`$parserKey`) and
:php:`$spParams` (now :php:`$parameters`) have to be set by the
:php:`setParserKey()` method, in case they are needed. The key can be retrieved
by using the :php:`getParserKey()` method.

The return type has been changed to an instance of
:php:`TYPO3\CMS\Core\DataHandling\SoftReference\SoftReferenceParserResult`. It
provides as static factory method simply called :php:`create()`. It expects the
`content` part of the old array as the first parameter and the `elements` part
as the second. If there are no matches, one can simply call
:php:`SoftReferenceParserResult::createWithoutMatches()`.

If needed, one could also extend
:php:`TYPO3\CMS\Core\DataHandling\SoftReference\AbstractSoftReferenceParser`.
This abstract class comes with the helper method :php:`makeTokenID()` (originally
in :php:`TYPO3\CMS\Core\Database\SoftReferenceIndex`) and a new method
:php:`setTokenIdBasePrefix`, which sets the concatenated string for the property
:php:`tokenID_basePrefix`.

Example before:

.. code-block:: php

    class MySoftReferenceParser implements SingletonInterface
    {
        public function findRef($table, $field, $uid, $content, $spKey, $spParams, $structurePath = '')
        {
            ...

            if (!empty($elements)) {
                $resultArray = [
                    'content' => $content,
                    'elements' => $elements
                ];
                return $resultArray;
            }

            return null;
        }
    }

Example after:

.. code-block:: php

    class MySoftReferenceParser implements SoftReferenceParserInterface
    {
        protected string $parserKey = '';
        protected array $parameters = [];

        public function parse(string $table, string $field, int $uid, string $content, string $structurePath = ''): SoftReferenceParserResult
        {
            ...

            if (!empty($elements)) {
                return SoftReferenceParserResult::create(
                    $content,
                    $elements
                );
            }
            return SoftReferenceParserResult::createWithoutMatches();
        }

        /**
         * @param string $parserKey The softref parser key.
         * @param array $parameters Parameters of the softlink parser. Basically this is the content inside optional []-brackets after the softref keys. Parameters are exploded by ";
         */
        public function setParserKey(string $parserKey, array $parameters): void
        {
            $this->parserKey = $parserKey;
            $this->parameters = $parameters;
        }

        public function getParserKey(): string
        {
            return $this->parserKey;
        }
    }


Instead of calling :php:`BackendUtility::softRefParserObj()` one should now create
an instance of :php:`TYPO3\CMS\Core\DataHandling\SoftReference\SoftReferenceParserFactory`.
This factory has a method: :php:`getSoftReferenceParser()`, which expects the
soft reference key as first argument (just like the BackendUtility method).

Example before:

.. code-block:: php

    $softRefObj = BackendUtility::softRefParserObj('typolink');

Example after:

.. code-block:: php

    $softReferenceParserFactory = GeneralUtility::makeInstance(SoftReferenceParserFactory::class);
    $softReferenceParser = $softReferenceParserFactory->getSoftReferenceParser('typolink');

The method :php:`BackendUtility::explodeSoftRefParserList()` should be replaced by
instantiating :php:`TYPO3\CMS\Core\DataHandling\SoftReference\SoftReferenceParserFactory`
and calling :php:`getParsersBySoftRefParserList()`. This method expects the
:php:`$parserList` as first argument, same as in the :php:`BackendUtility`
The second argument is a fallback configuration array for softref parsers.
This method returns an iterable of
:php:`TYPO3\CMS\Core\DataHandling\SoftReference\SoftReferenceParserInterface`.

Example before:

.. code-block:: php

    $softRefs = BackendUtility::explodeSoftRefParserList($conf['softref']);

    foreach ($softRefs as $spKey => $spParams) {
        $softRefObj = BackendUtility::softRefParserObj($spKey);
        $resultArray = $softRefObj->findRef($table, $field, $idRecord, $valueField, $spKey, $softRefParams);
    }

Example after:

.. code-block:: php

    foreach ($softReferenceParserFactory->getParsersBySoftRefParserList($conf['softref'], $softRefParams) as $softReferenceParser) {
        $parserResult = $softReferenceParser->parse($table, $field, $idRecord, $valueField);
    }


Related
=======

*  :doc:`RegisterSoftReferenceParsersViaDI (Feature) <Feature-94741-RegisterSoftReferenceParsersViaDI>`
*  :doc:`RegisterSoftReferenceParsersViaDI (Deprecation) <Deprecation-94741-RegisterSoftReferenceParsersViaDI>`

.. index:: Backend, PHP-API, PartiallyScanned, ext:core
