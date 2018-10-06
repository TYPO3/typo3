.. include:: ../../Includes.txt

========================================================
Breaking: #84131 - Removed classes of language extension
========================================================

See :issue:`84131`

Description
===========

The language pack update module - formerly known as "Admin Tools" -> "Language"
module has been moved to "Maintenance" -> "Manage language packs".

PHP classes implementing the old solution have been removed:

* :php:`TYPO3\CMS\Lang\Command\LanguageUpdateCommand`
* :php:`TYPO3\CMS\Lang\Controller\LanguageController`
* :php:`TYPO3\CMS\Lang\Domain\Model\Extension`
* :php:`TYPO3\CMS\Lang\Domain\Model\Language`
* :php:`TYPO3\CMS\Lang\Domain\Repository\ExtensionRepository`
* :php:`TYPO3\CMS\Lang\Domain\Repository\LanguageRepository`
* :php:`TYPO3\CMS\Lang\Exception`
* :php:`TYPO3\CMS\Lang\Exception\Language`
* :php:`TYPO3\CMS\Lang\Exception\Ter`
* :php:`TYPO3\CMS\Lang\Exception\XmlParser`
* :php:`TYPO3\CMS\Lang\Service\RegistryService`
* :php:`TYPO3\CMS\Lang\Service\TerService`
* :php:`TYPO3\CMS\Lang\Service\TranslationService`
* :php:`TYPO3\CMS\Lang\View\AbstractJsonView`
* :php:`TYPO3\CMS\Lang\View\Language\ActivateLanguageJson`
* :php:`TYPO3\CMS\Lang\View\Language\DeactivateLanguageJson`
* :php:`TYPO3\CMS\Lang\View\Language\GetTranslationsJson`
* :php:`TYPO3\CMS\Lang\View\Language\RemoveLanguageJson`
* :php:`TYPO3\CMS\Lang\View\Language\UpdateLanguageJson`
* :php:`TYPO3\CMS\Lang\View\Language\UpdateTranslationJson`


Impact
======

Using one of the mentioned classes will throw a fatal PHP error.


Affected Installations
======================

It is unlikely extensions used the mentioned classes, the extension scanner will find usages. The only well-known
usage of one of this classes is the signal/slot to override the base download url of language packs per extension
and the registration did not change and should still be done like this:

.. code-block:: php

    /** @var \TYPO3\CMS\Extbase\SignalSlot\Dispatcher $signalSlotDispatcher */
    $signalSlotDispatcher = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(\TYPO3\CMS\Extbase\SignalSlot\Dispatcher::class);

    $signalSlotDispatcher->connect(
        'TYPO3\\CMS\\Lang\\Service\\TranslationService',
        'postProcessMirrorUrl',
        \Company\Extension\Slots\CustomMirror::class,
        'postProcessMirrorUrl'
    );


Migration
=========

No migration available.

.. index:: Backend, PHP-API, FullyScanned, ext:lang
