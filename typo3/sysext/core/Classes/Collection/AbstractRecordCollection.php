<?php
namespace TYPO3\CMS\Core\Collection;

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
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Database\Query\Restriction\DeletedRestriction;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Abstract implementation of a RecordCollection
 *
 * RecordCollection is a collections of TCA-Records.
 * The collection is meant to be stored in TCA-table sys_collections and is manageable
 * via TCEforms.
 *
 * A RecordCollection might be used to group a set of records (e.g. news, images, contentElements)
 * for output in frontend
 *
 * The AbstractRecordCollection uses SplDoublyLinkedList for internal storage
 */
abstract class AbstractRecordCollection implements RecordCollectionInterface, PersistableCollectionInterface, SortableCollectionInterface
{
    /**
     * The table name collections are stored to
     *
     * @var string
     */
    protected static $storageTableName = 'sys_collection';

    /**
     * The table name collections are stored to
     *
     * @var string
     */
    protected static $storageItemsField = 'items';

    /**
     * Uid of the storage
     *
     * @var int
     */
    protected $uid = 0;

    /**
     * Collection title
     *
     * @var string
     */
    protected $title;

    /**
     * Collection description
     *
     * @var string
     */
    protected $description;

    /**
     * Table name of the records stored in this collection
     *
     * @var string
     */
    protected $itemTableName;

    /**
     * The local storage
     *
     * @var \SplDoublyLinkedList
     */
    protected $storage;

    /**
     * Creates this object.
     */
    public function __construct()
    {
        $this->storage = new \SplDoublyLinkedList();
    }

    /**
     * (PHP 5 >= 5.1.0)
     * Return the current element
     *
     * @link http://php.net/manual/en/iterator.current.php
     * @return mixed Can return any type.
     */
    public function current()
    {
        return $this->storage->current();
    }

    /**
     * (PHP 5 >= 5.1.0)
     * Move forward to next element
     *
     * @link http://php.net/manual/en/iterator.next.php
     */
    public function next()
    {
        $this->storage->next();
    }

    /**
     * (PHP 5 >= 5.1.0)
     * Return the key of the current element
     *
     * @link http://php.net/manual/en/iterator.key.php
     * @return int 0 on failure.
     */
    public function key()
    {
        $currentRecord = $this->storage->current();
        return $currentRecord['uid'];
    }

    /**
     * (PHP 5 >= 5.1.0)
     * Checks if current position is valid
     *
     * @link http://php.net/manual/en/iterator.valid.php
     * @return bool The return value will be casted to boolean and then evaluated.
     */
    public function valid()
    {
        return $this->storage->valid();
    }

    /**
     * (PHP 5 >= 5.1.0)
     * Rewind the Iterator to the first element
     *
     * @link http://php.net/manual/en/iterator.rewind.php
     */
    public function rewind()
    {
        $this->storage->rewind();
    }

    /**
     * (PHP 5 >= 5.1.0)
     * String representation of object
     *
     * @link http://php.net/manual/en/serializable.serialize.php
     * @return string the string representation of the object or &null;
     */
    public function serialize()
    {
        $data = [
            'uid' => $this->getIdentifier()
        ];
        return serialize($data);
    }

    /**
     * (PHP 5 >= 5.1.0)
     * Constructs the object
     *
     * @link http://php.net/manual/en/serializable.unserialize.php
     * @param string $serialized The string representation of the object
     * @return mixed the original value unserialized.
     */
    public function unserialize($serialized)
    {
        $data = unserialize($serialized);
        return self::load($data['uid']);
    }

    /**
     * (PHP 5 >= 5.1.0)
     * Count elements of an object
     *
     * @link http://php.net/manual/en/countable.count.php
     * @return int The custom count as an integer.
     */
    public function count()
    {
        return $this->storage->count();
    }

    /**
     * Getter for the title
     *
     * @return string
     */
    public function getTitle()
    {
        return $this->title;
    }

    /**
     * Getter for the UID
     *
     * @return int
     */
    public function getUid()
    {
        return $this->uid;
    }

    /**
     * Getter for the description
     *
     * @return string
     */
    public function getDescription()
    {
        return $this->description;
    }

    /**
     * Setter for the title
     *
     * @param string $title
     */
    public function setTitle($title)
    {
        $this->title = $title;
    }

    /**
     * Setter for the description
     *
     * @param string $desc
     */
    public function setDescription($desc)
    {
        $this->description = $desc;
    }

    /**
     * Setter for the name of the data-source table
     *
     * @return string
     */
    public function getItemTableName()
    {
        return $this->itemTableName;
    }

    /**
     * Setter for the name of the data-source table
     *
     * @param string $tableName
     */
    public function setItemTableName($tableName)
    {
        $this->itemTableName = $tableName;
    }

    /**
     * Sorts collection via given callBackFunction
     *
     * The comparison function given as must return an integer less than, equal to, or greater than
     * zero if the first argument is considered to be respectively less than, equal to, or greater than the second.
     *
     * @param $callbackFunction
     * @see http://www.php.net/manual/en/function.usort.php
     */
    public function usort($callbackFunction)
    {
        // @todo Implement usort() method with TCEforms in mind
        throw new \RuntimeException('This method is not yet supported.', 1322545589);
    }

    /**
     * Moves the item within the collection
     *
     * the item at $currentPosition will be moved to
     * $newPosition. Omitting $newPosition will move to top.
     *
     * @param int $currentPosition
     * @param int $newPosition
     */
    public function moveItemAt($currentPosition, $newPosition = 0)
    {
        // @todo Implement usort() method with TCEforms in mind
        throw new \RuntimeException('This method is not yet supported.', 1322545626);
    }

    /**
     * Returns the uid of the collection
     *
     * @return int
     */
    public function getIdentifier()
    {
        return $this->uid;
    }

    /**
     * Sets the identifier of the collection
     *
     * @param int $id
     */
    public function setIdentifier($id)
    {
        $this->uid = (int)$id;
    }

    /**
     * Loads the collections with the given id from persistence
     *
     * For memory reasons, per default only f.e. title, database-table,
     * identifier (what ever static data is defined) is loaded.
     * Entries can be load on first access.
     *
     * @param int $id Id of database record to be loaded
     * @param bool $fillItems Populates the entries directly on load, might be bad for memory on large collections
     * @return \TYPO3\CMS\Core\Collection\CollectionInterface
     */
    public static function load($id, $fillItems = false)
    {
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable(static::$storageTableName);
        $queryBuilder->getRestrictions()->removeAll()->add(GeneralUtility::makeInstance(DeletedRestriction::class));
        $collectionRecord = $queryBuilder->select('*')
            ->from(static::$storageTableName)
            ->where($queryBuilder->expr()->eq('uid', $queryBuilder->createNamedParameter($id, \PDO::PARAM_INT)))
            ->execute()
            ->fetch();
        return self::create($collectionRecord, $fillItems);
    }

    /**
     * Creates a new collection objects and reconstitutes the
     * given database record to the new object.
     *
     * @param array $collectionRecord Database record
     * @param bool $fillItems Populates the entries directly on load, might be bad for memory on large collections
     * @return \TYPO3\CMS\Core\Collection\CollectionInterface
     */
    public static function create(array $collectionRecord, $fillItems = false)
    {
        $collection = new static();
        $collection->fromArray($collectionRecord);
        if ($fillItems) {
            $collection->loadContents();
        }
        return $collection;
    }

    /**
     * Persists current collection state to underlying storage
     */
    public function persist()
    {
        $uid = $this->getIdentifier() == 0 ? 'NEW' . rand(100000, 999999) : $this->getIdentifier();
        $data = [
            trim(static::$storageTableName) => [
                $uid => $this->getPersistableDataArray()
            ]
        ];
        // New records always must have a pid
        if ($this->getIdentifier() == 0) {
            $data[trim(static::$storageTableName)][$uid]['pid'] = 0;
        }
        /** @var \TYPO3\CMS\Core\DataHandling\DataHandler $tce */
        $tce = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(\TYPO3\CMS\Core\DataHandling\DataHandler::class);
        $tce->start($data, []);
        $tce->process_datamap();
    }

    /**
     * Returns an array of the persistable properties and contents
     * which are processable by DataHandler.
     *
     * For internal usage in persist only.
     *
     * @return array
     */
    abstract protected function getPersistableDataArray();

    /**
     * Generates comma-separated list of entry uids for usage in DataHandler
     *
     * also allow to add table name, if it might be needed by DataHandler for
     * storing the relation
     *
     * @param bool $includeTableName
     * @return string
     */
    protected function getItemUidList($includeTableName = true)
    {
        $list = [];
        foreach ($this->storage as $entry) {
            $list[] = ($includeTableName ? $this->getItemTableName() . '_' : '') . $entry['uid'];
        }
        return implode(',', $list);
    }

    /**
     * Builds an array representation of this collection
     *
     * @return array
     */
    public function toArray()
    {
        $itemArray = [];
        foreach ($this->storage as $item) {
            $itemArray[] = $item;
        }
        return [
            'uid' => $this->getIdentifier(),
            'title' => $this->getTitle(),
            'description' => $this->getDescription(),
            'table_name' => $this->getItemTableName(),
            'items' => $itemArray
        ];
    }

    /**
     * Loads the properties of this collection from an array
     *
     * @param array $array
     */
    public function fromArray(array $array)
    {
        $this->uid = $array['uid'];
        $this->title = $array['title'];
        $this->description = $array['description'];
        $this->itemTableName = $array['table_name'];
    }
}
