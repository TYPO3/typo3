<?php
declare(strict_types = 1);
namespace TYPO3\CMS\Rsaauth;

/*
 * This file is part of the TYPO3 CMS project.
 *
 * It is free software; you can redistribute it and/or modify it under
 * the terms of the GNU General Public License, either version 2
 * of the License, or any later version.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 *
 * The TYPO3 project - inspiring people to share!
 */

use TYPO3\CMS\Core\Page\PageRenderer;
use TYPO3\CMS\Core\SingletonInterface;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * This class adds necessary Javascript code to encrypt fields in a form
 */
class RsaEncryptionEncoder implements SingletonInterface
{
    /**
     * @var bool
     */
    protected $moduleLoaded = false;

    /**
     * @var PageRenderer
     */
    protected $pageRenderer;

    /**
     * This method is called by the hook constructPostProcess
     */
    public function enableEncryptionFromBackendControllerPostConstructor()
    {
        $this->enableRsaEncryption(true);
    }

    /**
     * Load all necessary Javascript files
     *
     * @param bool $useRequireJsModule
     */
    public function enableRsaEncryption($useRequireJsModule = false)
    {
        if ($this->moduleLoaded || !$this->isAvailable()) {
            return;
        }
        $this->moduleLoaded = true;
        $pageRenderer = GeneralUtility::makeInstance(PageRenderer::class);
        // Include necessary javascript files
        if ($useRequireJsModule) {
            $pageRenderer->loadRequireJsModule('TYPO3/CMS/Rsaauth/RsaEncryptionModule');
        } else {
            // Register ajax handler url
            $code = 'var TYPO3RsaEncryptionPublicKeyUrl = ' . GeneralUtility::quoteJSvalue(GeneralUtility::getIndpEnv('TYPO3_SITE_PATH') . 'index.php?eID=RsaPublicKeyGenerationController') . ';';
            $pageRenderer->addJsInlineCode('TYPO3RsaEncryptionPublicKeyUrl', $code);
            if (!$GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['debug']) {
                $files = ['RsaEncryptionWithLib.min.js'];
            } else {
                $files = [
                    'RsaLibrary.js',
                    'RsaEncryption.js',
                ];
            }
            foreach ($files as $file) {
                $pageRenderer->addJsFile('EXT:rsaauth/Resources/Public/JavaScript/' . $file);
            }
        }
    }

    /**
     * @return bool
     */
    public function isAvailable()
    {
        return trim($GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['loginSecurityLevel']) === 'rsa';
    }

    /**
     * Gets RSA Public Key.
     *
     * @return Keypair|null
     */
    public function getRsaPublicKey(): ?Keypair
    {
        $keyPair = null;
        $backend = Backend\BackendFactory::getBackend();

        if ($backend !== null) {
            $keyPair = $backend->createNewKeyPair();
            $storage = Storage\StorageFactory::getStorage();
            $storage->put($keyPair->getPrivateKey());
            session_commit();
        }

        return $keyPair;
    }
}
