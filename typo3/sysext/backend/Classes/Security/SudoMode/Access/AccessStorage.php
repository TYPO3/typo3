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

namespace TYPO3\CMS\Backend\Security\SudoMode\Access;

use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use TYPO3\CMS\Core\Authentication\BackendUserAuthentication;

/**
 * Wrapper for storing `AccessClaim` and `AccessGrant` items in the backend user session storage.
 *
 * @internal
 */
class AccessStorage implements LoggerAwareInterface
{
    use LoggerAwareTrait;

    protected const CLAIM_KEY = 'backend.sudo-mode.claim';
    protected const GRANT_KEY = 'backend.sudo-mode.grant';

    protected readonly int $currentTimestamp;

    public function __construct(
        protected readonly AccessFactory $factory
    ) {
        $this->currentTimestamp = (int)($GLOBALS['EXEC_TIME'] ?? time());
    }

    public function findGrantsBySubject(AccessSubjectInterface $subject): array
    {
        $relevantItems = array_filter(
            $this->fetchGrants(),
            // either group matches (if given), or subject matches
            fn (array $item) => $this->subjectMatchesItem($subject, $item)
        );
        return array_map($this->factory->buildGrantFromArray(...), $relevantItems);
    }

    public function addGrant(AccessGrant $grant): void
    {
        $items = $this->fetchGrants();
        $identity = $grant->subject->getIdentity();
        if (isset($items[$identity])) {
            $this->logger->warning(
                sprintf('Grant %s does already exist', $identity),
                $grant->jsonSerialize()
            );
        }
        $items[$identity] = $grant;
        $this->commitItems(self::GRANT_KEY, $items);
    }

    public function findClaimById(string $id): ?AccessClaim
    {
        $item = $this->fetchClaims()[$id] ?? null;
        return !empty($item) ? $this->factory->buildClaimFromArray($item) : null;
    }

    public function findClaimBySubject(AccessSubjectInterface $subject): ?AccessClaim
    {
        foreach ($this->fetchClaims() as $item) {
            if ($this->subjectMatchesItem($subject, $item)) {
                return $this->factory->buildClaimFromArray($item);
            }
        }
        return null;
    }

    public function addClaim(AccessClaim $claim): void
    {
        $items = $this->fetchClaims();
        $items[$claim->id] = $claim;
        $this->commitItems(self::CLAIM_KEY, $items);
    }

    public function removeClaim(AccessClaim $claim): void
    {
        $items = $this->fetchClaims();
        unset($items[$claim->id]);
        $this->commitItems(self::CLAIM_KEY, $items);
    }

    protected function fetchGrants(): array
    {
        return $this->fetchItems(self::GRANT_KEY);
    }

    protected function fetchClaims(): array
    {
        return $this->fetchItems(self::CLAIM_KEY);
    }

    protected function fetchItems(string $sessionKey): array
    {
        $sessionData = $this->getBackendUser()->getSessionData($sessionKey);
        $items = json_decode((string)$sessionData, true, 16) ?? [];
        $purgedItems = array_filter(
            $items,
            fn (array $item) => ($item['expiration'] ?? 0) >= $this->currentTimestamp
        );
        if (count($purgedItems) < count($items)) {
            $this->commitItems($sessionKey, $purgedItems);
        }
        return $purgedItems;
    }

    protected function commitItems(string $sessionKey, array $items): void
    {
        // using `json_encode` here, since `UserSession` still uses PHP `serialize`
        $this->getBackendUser()->setAndSaveSessionData($sessionKey, json_encode($items));
    }

    protected function subjectMatchesItem(AccessSubjectInterface $subject, array $item): bool
    {
        // either group matches (if given), or subject matches
        return ($item['subject']['identity'] ?? null) === $subject->getIdentity()
            || ($subject->getGroup() !== null && ($item['subject']['group'] ?? null) === $subject->getGroup());
    }

    protected function getBackendUser(): BackendUserAuthentication
    {
        return $GLOBALS['BE_USER'];
    }
}
