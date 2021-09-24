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

namespace TYPO3\CMS\Extensionmanager\Remote;

/**
 * Registry of remote connectors.
 *
 * @internal This class is a specific implementation and is not part of the Public TYPO3 API.
 */
class RemoteRegistry
{
    /**
     * @var array<string,array>
     */
    protected $remotes = [];

    /**
     * @var ExtensionDownloaderRemoteInterface
     */
    protected $defaultRemote;

    public function registerRemote(ExtensionDownloaderRemoteInterface $remote, array $configuration): void
    {
        $identifier = $remote->getIdentifier();

        $this->remotes[$identifier] = array_merge(
            $configuration,
            [
                'service' => $remote,
                'listable' => $remote instanceof ListableRemoteInterface,
            ]
        );

        if ($this->remotes[$identifier]['default']
            && $this->remotes[$identifier]['enabled']
            && $this->remotes[$identifier]['listable']
        ) {
            // This overrides a previously set default remote
            $this->defaultRemote = $remote;
        }
    }

    public function hasRemote(string $identifier): bool
    {
        return isset($this->remotes[$identifier]) && $this->remotes[$identifier]['enabled'];
    }

    public function getRemote(string $identifier): ExtensionDownloaderRemoteInterface
    {
        if (isset($this->remotes[$identifier]) && $this->remotes[$identifier]['enabled']) {
            return $this->remotes[$identifier]['service'];
        }
        throw new RemoteNotRegisteredException(
            'The requested remote ' . $identifier . ' is not registered in this system or not enabled',
            1601566451
        );
    }

    public function hasDefaultRemote(): bool
    {
        return isset($this->defaultRemote);
    }

    public function getDefaultRemote(): ExtensionDownloaderRemoteInterface
    {
        if ($this->defaultRemote !== null) {
            return $this->defaultRemote;
        }
        throw new RemoteNotRegisteredException('No default remote registered in this system', 1602226715);
    }

    /**
     * @return ListableRemoteInterface[]
     */
    public function getListableRemotes(): iterable
    {
        foreach ($this->remotes as $remote) {
            if ($remote['listable'] && $remote['enabled']) {
                yield $remote['service'];
            }
        }
    }

    /**
     * @return ExtensionDownloaderRemoteInterface[]
     */
    public function getAllRemotes(): iterable
    {
        foreach ($this->remotes as $remote) {
            if ($remote['enabled']) {
                yield $remote['service'];
            }
        }
    }
}
