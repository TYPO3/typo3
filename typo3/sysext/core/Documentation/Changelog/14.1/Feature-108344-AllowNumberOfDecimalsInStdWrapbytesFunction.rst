..  include:: /Includes.rst.txt

..  _feature-108344-1764239726:

===================================================================================
Feature: #108344 - Allow number of decimals in :typoscript:`stdWrap.bytes` function
===================================================================================

See :issue:`108344`

Description
===========

The TypoScript function :typoscript:`stdWrap.bytes` now accepts an additional
configuration parameter :typoscript:`decimals`. It allows to explicitly define
the number of decimals in the resulting number representation. By default, the
number of decimals is derived from the formatted size.

In addition, the consumed PHP function
:php:`TYPO3\CMS\Core\Utility\GeneralUtility::formatSize` is extended as well.
The additional parameter :php:`$decimals` is added and defaults to :php:`null`,
which results in the same behavior as for the TypoScript function
:typoscript:`stdWrap.bytes`.

Example
-------

..  code-block:: typoscript
    :emphasize-lines: 5

    lib.fileSize = TEXT
    lib.fileSize {
        value = 123456
        bytes = 1
        bytes.decimals = 1
    }


Impact
======

By allowing to configure the number of decimals in :typoscript:`stdWrap.bytes`,
integrators can now better adapt the output of the formatted size returned by
the TypoScript function. This was previously not possible by default and needed
some workarounds in TypoScript.


..  index:: TypoScript, ext:frontend
