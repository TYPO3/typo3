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

namespace TYPO3\CMS\Beuser\Domain\Model;

/**
 * Module data object
 * @internal This class is a TYPO3 Backend implementation and is not considered part of the Public TYPO3 API.
 */
class ModuleData
{
    protected Demand $demand;
    protected array $compareUserList = [];

    public function __construct()
    {
        $this->demand = new Demand();
    }

    public static function fromUc(array $uc): self
    {
        $moduleData = new self();
        $moduleData->compareUserList = (array)($uc['compareUserList'] ?? []);
        $moduleData->demand = Demand::fromUc($uc['demand'] ?? []);
        return $moduleData;
    }

    public function forUc(): array
    {
        return [
            'compareUserList' => $this->compareUserList,
            'demand' => $this->demand->forUc(),
        ];
    }

    public function getDemand(): Demand
    {
        return $this->demand;
    }

    public function setDemand(Demand $demand): void
    {
        $this->demand = $demand;
    }

    protected function setCompareUserList(array $compareUserList): void
    {
        $this->compareUserList = $compareUserList;
    }

    /**
     * Returns the compare list as array of user uids
     */
    public function getCompareUserList(): array
    {
        return array_keys($this->compareUserList);
    }

    public function resetCompareUserList(): void
    {
        $this->compareUserList = [];
    }

    /**
     * Adds one backend user (by uid) to the compare user list
     * Cannot be ObjectStorage, must be array
     *
     * @param int $uid
     */
    public function attachUidCompareUser(int $uid): void
    {
        $this->compareUserList[$uid] = true;
    }

    /**
     * Strip one backend user from the compare user list
     *
     * @param int $uid
     */
    public function detachUidCompareUser(int $uid): void
    {
        unset($this->compareUserList[$uid]);
    }
}
