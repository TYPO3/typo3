..  include:: /Includes.rst.txt

..  _important-110540-1788349876:

========================================================
Important: #110540 - File collector sorting uses an enum
========================================================

See :issue:`110540`

Description
===========

The sorting order passed to
:php:`\TYPO3\CMS\Frontend\Resource\FileCollector->sort()` is no longer a plain
string but the new enum
:php:`\TYPO3\CMS\Frontend\Resource\FileCollectionSorting` with the cases
:php:`Ascending`, :php:`Descending` and :php:`Random`.

:php:`FileCollector` is marked as :php:`@internal`, so this is not treated as a
breaking change. Extensions calling :php:`sort()` directly must nevertheless
adapt their code:

..  code-block:: php

    // before
    $fileCollector->sort('title', 'descending');

    // after
    $fileCollector->sort('title', FileCollectionSorting::Descending);

    // when the value comes from configuration or user input
    $fileCollector->sort(
        'title',
        FileCollectionSorting::fromKeyword($direction),
    );

:php:`FileCollectionSorting::fromKeyword()` resolves both the short and the
long keywords (:php:`asc`, :php:`ascending`, :php:`desc`, :php:`descending`,
:php:`rand`, :php:`random`) case-insensitively, and throws a
:php:`\ValueError` for anything else.

The TypoScript option :typoscript:`sorting.direction` of the :typoscript:`FILES`
content object and of the :typoscript:`files` data processor keeps accepting the
same keywords as before. An unknown keyword is no longer silently treated as
"ascending" though, but now results in a :php:`\ValueError`.

..  index:: FAL, Frontend, PHP-API, TypoScript, NotScanned, ext:frontend
