<?php

defined('TYPO3') or die();

\TYPO3\CMS\Extbase\Utility\ExtensionUtility::registerPlugin('FormCachingTests', 'AllActionsCached', 'form caching test - all actions cached', 'information-typo3-version');
\TYPO3\CMS\Extbase\Utility\ExtensionUtility::registerPlugin('FormCachingTests', 'RenderActionIsCached', 'form caching test - render action cached', 'information-typo3-version');
\TYPO3\CMS\Extbase\Utility\ExtensionUtility::registerPlugin('FormCachingTests', 'AllActionsUncached', 'form caching test - all actions uncached', 'information-typo3-version');
