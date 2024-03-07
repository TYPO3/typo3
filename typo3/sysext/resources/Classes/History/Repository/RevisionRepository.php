<?php declare(strict_types=1);

namespace TYPO3\CMS\Resources\History\Repository;

use TYPO3\CMS\Resources\History\Model\Revision;

/**
 * A repository which can access resources held in a variety of Revisions.
 */
interface RevisionRepository
{
    /**
     * Returns the revision of the resource it was last changed in.
     */
    public function findLastChangeRevision(mixed $identifier): Revision;

    /*
     * Returns the resource with the given ID in the given revision number.
     */
    public function findRevision(mixed $identifier, mixed $revisionNumber): Revision;

    /**
     * Returns all Revisions of a resource with the given id.
     * @return Revision[]
     */
    public function findRevisions(mixed $identifier): iterable;

    /**
     * Returns a Page of revisions for the entity with the given id.
     */
    // public function findRevisions(mixed $identifier, Pageable pageable);

}
