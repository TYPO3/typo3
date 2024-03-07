<?php declare(strict_types=1);

namespace TYPO3\CMS\Core\Site\Domain;

use TYPO3\CMS\Core\Site\Entity\Site;
use TYPO3\CMS\Core\Site\Entity\SiteInterface;
use TYPO3\CMS\Resources\Repository\CrudRepositoryInterface;

/**
 * @template T of Site
 * @implements CrudRepositoryInterface<T>
 */
interface SiteRepository extends CrudRepositoryInterface {

}
