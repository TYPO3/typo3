..  include:: /Includes.rst.txt

..  _deprecation-108810-1738253894:

==================================================================
Deprecation: #108810 - BackendUtility localization-related methods
==================================================================

See :issue:`108810`

Description
===========

The following methods in :php:`\TYPO3\CMS\Backend\Utility\BackendUtility` have
been deprecated in favor of new methods in
:php:`\TYPO3\CMS\Backend\Domain\Repository\Localization\LocalizationRepository`:

*   :php:`BackendUtility::getRecordLocalization()` - use
    :php:`LocalizationRepository::getRecordTranslation()` instead
*   :php:`BackendUtility::getExistingPageTranslations()` - use
    :php:`LocalizationRepository::getPageTranslations()` instead
*   :php:`BackendUtility::translationCount()` - use
    :php:`LocalizationRepository::getRecordTranslations()` instead

See :ref:`feature-108799-1738094060` for details of the new methods.

Impact
======

Calling any of the deprecated methods triggers a deprecation-level log entry.
The methods will be removed in TYPO3 v15.0 and result in a fatal PHP
error.

The extension scanner reports usages as a **strong** match.

Affected installations
======================

Instances or extensions that directly call any of the deprecated methods are
affected.

Migration
=========

Inject :php-short:`\TYPO3\CMS\Backend\Domain\Repository\Localization\LocalizationRepository`
and use the new methods. The new methods return
:php:`\TYPO3\CMS\Core\Domain\RawRecord` objects instead of plain arrays.

getRecordLocalization()
-----------------------

..  code-block:: php

    use TYPO3\CMS\Backend\Domain\Repository\Localization\LocalizationRepository;
    use TYPO3\CMS\Backend\Utility\BackendUtility;

    // Before
    $translations = BackendUtility::getRecordLocalization($table, $uid, $languageId);
    if (is_array($translations) && !empty($translations)) {
        $translation = $translations[0];
    }

    // After
    $translation = $this->localizationRepository->getRecordTranslation($table, $uid, $languageId);
    if ($translation !== null) {
        // $translation is a RawRecord object
        $translatedUid = $translation->getUid();
    }

getExistingPageTranslations()
-----------------------------

..  code-block:: php

    use TYPO3\CMS\Backend\Domain\Repository\Localization\LocalizationRepository;
    use TYPO3\CMS\Backend\Utility\BackendUtility;

    // Before
    $pageTranslations = BackendUtility::getExistingPageTranslations($pageUid);

    // After
    // Returns an array of RawRecord objects indexed by language ID
    $pageTranslations = $this->localizationRepository->getPageTranslations($pageUid);

translationCount()
------------------

..  code-block:: php

    use TYPO3\CMS\Backend\Domain\Repository\Localization\LocalizationRepository;
    use TYPO3\CMS\Backend\Utility\BackendUtility;

    // Before
    $message = BackendUtility::translationCount($table, $uid . ':' . $pid, 'Found %s translation(s)');
    // or just counting
    $count = (int)BackendUtility::translationCount($table, $uid . ':' . $pid);

    // After
    $translations = $this->localizationRepository->getRecordTranslations($table, $uid);
    $count = count($translations);
    $message = sprintf('Found %s translation(s)', $count);

..  index:: PHP-API, FullyScanned, ext:backend
