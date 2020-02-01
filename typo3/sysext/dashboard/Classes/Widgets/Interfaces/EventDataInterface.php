<?php
declare(strict_types = 1);

namespace TYPO3\CMS\Dashboard\Widgets\Interfaces;

/**
 * Interface EventDataInterface
 * In case a widget should provide additional data as JSON payload, the widget must implement this interface.
 */
interface EventDataInterface
{
    /**
     * This method returns data which should be send to widget as JSON encoded value.
     * @return array
     */
    public function getEventData(): array;
}
