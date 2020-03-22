<?php
declare(strict_types=1);
namespace ExtbaseTeam\BlogExample\Domain\Model;

use TYPO3\CMS\Extbase\DomainObject\AbstractEntity;

class DateTimeImmutableExample extends AbstractEntity
{

    /**
     * A datetimeImmutable stored in a text field
     *
     * @var \DateTimeImmutable
     */
    protected $datetimeImmutableText;

    /**
     * A datetime stored in an integer field
     *
     * @var \DateTimeImmutable
     */
    protected $datetimeImmutableInt;

    /**
     * A datetime stored in a datetime field
     *
     * @var \DateTimeImmutable
     */
    protected $datetimeImmutableDatetime;

    /**
     * @return \DateTimeImmutable
     */
    public function getDatetimeImmutableText(): \DateTimeImmutable
    {
        return $this->datetimeImmutableText;
    }

    /**
     * @param \DateTimeImmutable $datetimeImmutableText
     */
    public function setDatetimeImmutableText(\DateTimeImmutable $datetimeImmutableText)
    {
        $this->datetimeImmutableText = $datetimeImmutableText;
    }

    /**
     * @return \DateTimeImmutable
     */
    public function getDatetimeImmutableInt(): \DateTimeImmutable
    {
        return $this->datetimeImmutableInt;
    }

    /**
     * @param \DateTimeImmutable $datetimeImmutableInt
     */
    public function setDatetimeImmutableInt(\DateTimeImmutable $datetimeImmutableInt)
    {
        $this->datetimeImmutableInt = $datetimeImmutableInt;
    }

    /**
     * @return \DateTimeImmutable
     */
    public function getDatetimeImmutableDatetime(): \DateTimeImmutable
    {
        return $this->datetimeImmutableDatetime;
    }

    /**
     * @param \DateTimeImmutable $datetimeImmutableDatetime
     */
    public function setDatetimeImmutableDatetime(\DateTimeImmutable $datetimeImmutableDatetime)
    {
        $this->datetimeImmutableDatetime = $datetimeImmutableDatetime;
    }
}
