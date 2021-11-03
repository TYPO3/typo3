<?php

declare(strict_types=1);

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

namespace TYPO3\CMS\Extensionmanager\Service;

use GuzzleHttp\Exception\TransferException;
use TYPO3\CMS\Core\Core\Environment;
use TYPO3\CMS\Core\Http\RequestFactory;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extensionmanager\Utility\EmConfUtility;

/**
 * Service for generating composer manifest proposals
 */
class ComposerManifestProposalGenerator
{
    private const TER_COMPOSER_ENDPOINT = 'https://extensions.typo3.org/composerize';

    /**
     * @var RequestFactory
     */
    protected $requestFactory;

    /**
     * @var EmConfUtility
     */
    protected $emConfUtility;

    public function __construct(RequestFactory $requestFactory, EmConfUtility $emConfUtility)
    {
        $this->requestFactory = $requestFactory;
        $this->emConfUtility = $emConfUtility;
    }

    /**
     * Return the generated composer manifest content
     *
     * @param string $extensionKey
     * @return string
     */
    public function getComposerManifestProposal(string $extensionKey): string
    {
        if (!$this->isValidExtensionKey($extensionKey)) {
            throw new \InvalidArgumentException('Extension key ' . $extensionKey . ' is not valid.', 1619446379);
        }

        $composerManifestPath = Environment::getExtensionsPath() . '/' . $extensionKey . '/composer.json';

        if (file_exists($composerManifestPath)) {
            $composerManifest = json_decode((file_get_contents($composerManifestPath) ?: ''), true) ?? [];
            if (!is_array($composerManifest) || $composerManifest === []) {
                return '';
            }
            $composerManifest['extra']['typo3/cms']['extension-key'] = $extensionKey;
        } else {
            try {
                $composerManifest = $this->getComposerManifestProposalFromTer($extensionKey);
            } catch (TransferException $e) {
                return '';
            }
        }

        return (string)json_encode($composerManifest, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
    }

    /**
     * Calls the TER API endpoint by providing the extensions' ext_emconf
     * to receive a composer manifest proposal with resolved dependencies.
     */
    protected function getComposerManifestProposalFromTer(string $extensionKey): array
    {
        $emConf = $this->emConfUtility->includeEmConf($extensionKey, Environment::getExtensionsPath() . '/' . $extensionKey);

        if (!is_array($emConf) || $emConf === []) {
            return [];
        }

        $response = $this->requestFactory->request(
            self::TER_COMPOSER_ENDPOINT . '/' . $extensionKey,
            'post',
            ['json' => [$emConf]]
        );

        if ($response->getStatusCode() !== 200 || ($responseContent = $response->getBody()->getContents()) === '') {
            return [];
        }

        $composerManifest = json_decode($responseContent, true) ?? [];
        return is_array($composerManifest) ? $composerManifest : [];
    }

    protected function isValidExtensionKey(string $extensionKey): bool
    {
        return preg_match('/^[0-9a-z._\-]+$/i', $extensionKey)
            && GeneralUtility::isAllowedAbsPath(Environment::getExtensionsPath() . '/' . $extensionKey);
    }
}
