<?php
namespace TYPO3\CMS\Extbase\Tests\Functional\Persistence\Generic\Mapper;

use ExtbaseTeam\BlogExample\Domain\Model\Comment;
use TYPO3\CMS\Core\Tests\FunctionalTestCase;
use TYPO3\CMS\Core\Utility\GeneralUtility;

class DataMapperTest extends FunctionalTestCase
{
    /**
     * @var \TYPO3\CMS\Extbase\Persistence\Generic\PersistenceManager
     */
    protected $persistenceManager;

    /**
     * @var array
     */
    protected $testExtensionsToLoad = array('typo3/sysext/extbase/Tests/Functional/Fixtures/Extensions/blog_example');

    /**
     * @var array
     */
    protected $coreExtensionsToLoad = array('extbase', 'fluid');

    /**
     * @var \TYPO3\CMS\Extbase\Object\ObjectManagerInterface The object manager
     */
    protected $objectManager;

    /**
     * Sets up this test suite.
     */
    protected function setUp()
    {
        parent::setUp();

        $this->objectManager = GeneralUtility::makeInstance(\TYPO3\CMS\Extbase\Object\ObjectManager::class);
        $this->persistenceManager = $this->objectManager->get(\TYPO3\CMS\Extbase\Persistence\Generic\PersistenceManager::class);
    }

    /**
     * @test
     */
    public function datetimeObjectsCanBePersistedToDatetimeDatabaseFields()
    {
        $date = new \DateTime('2016-03-06T12:40:00+01:00');
        $comment = new Comment();
        $comment->setDate($date);

        $this->persistenceManager->add($comment);
        $this->persistenceManager->persistAll();
        $uid = $this->persistenceManager->getIdentifierByObject($comment);
        $this->persistenceManager->clearState();

        /** @var Comment $existingComment */
        $existingComment = $this->persistenceManager->getObjectByIdentifier($uid, Comment::class);

        $this->assertEquals($date->getTimestamp(), $existingComment->getDate()->getTimestamp());
    }
}
