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

namespace TYPO3\CMS\Extbase\SignalSlot;

use Psr\Log\LoggerInterface;
use TYPO3\CMS\Core\SingletonInterface;
use TYPO3\CMS\Extbase\Object\ObjectManagerInterface;
use TYPO3\CMS\Extbase\SignalSlot\Exception\InvalidSlotException;
use TYPO3\CMS\Extbase\SignalSlot\Exception\InvalidSlotReturnException;

/**
 * A dispatcher which dispatches signals by calling its registered slot methods
 * and passing them the method arguments which were originally passed to the
 * signal method.
 *
 * @deprecated will be removed in TYPO3 v12.0. Use PSR-14 based events and EventDispatcherInterface instead.
 */
class Dispatcher implements SingletonInterface
{
    /**
     * @var ObjectManagerInterface
     */
    protected $objectManager;

    /**
     * Information about all slots connected a certain signal.
     * Indexed by [$signalClassName][$signalMethodName] and then numeric with an
     * array of information about the slot
     *
     * @var array
     */
    protected $slots = [];

    /**
     * @var LoggerInterface
     */
    protected $logger;

    /**
     * @param ObjectManagerInterface $objectManager
     * @param LoggerInterface $logger
     */
    public function __construct(ObjectManagerInterface $objectManager, LoggerInterface $logger)
    {
        $this->objectManager = $objectManager;
        $this->logger = $logger;
    }

    /**
     * Connects a signal with a slot.
     * One slot can be connected with multiple signals by calling this method multiple times.
     *
     * @param string $signalClassName Name of the class containing the signal
     * @param string $signalName Name of the signal
     * @param mixed $slotClassNameOrObject Name of the class containing the slot or the instantiated class or a Closure object
     * @param string $slotMethodName Name of the method to be used as a slot. If $slotClassNameOrObject is a Closure object, this parameter is ignored
     * @param bool $passSignalInformation If set to TRUE, the last argument passed to the slot will be information about the signal (EmitterClassName::signalName)
     * @throws \InvalidArgumentException
     */
    public function connect(string $signalClassName, string $signalName, $slotClassNameOrObject, string $slotMethodName = '', bool $passSignalInformation = true): void
    {
        $class = null;
        $object = null;
        if (is_object($slotClassNameOrObject)) {
            $object = $slotClassNameOrObject;
            $method = $slotClassNameOrObject instanceof \Closure ? '__invoke' : $slotMethodName;
        } else {
            if ($slotMethodName === '') {
                throw new \InvalidArgumentException('The slot method name must not be empty (except for closures).', 1229531659);
            }
            $class = $slotClassNameOrObject;
            $method = $slotMethodName;
        }
        $slot = [
            'class' => $class,
            'method' => $method,
            'object' => $object,
            'passSignalInformation' => $passSignalInformation === true,
        ];
        // The in_array() comparision needs to be strict to avoid potential issues
        // with complex objects being registered as slot.
        if (!is_array($this->slots[$signalClassName][$signalName] ?? false) || !in_array($slot, $this->slots[$signalClassName][$signalName], true)) {
            $this->slots[$signalClassName][$signalName][] = $slot;
        }
    }

    /**
     * Dispatches a signal by calling the registered Slot methods
     *
     * @param string $signalClassName Name of the class containing the signal
     * @param string $signalName Name of the signal
     * @param array $signalArguments arguments passed to the signal method
     * @return mixed
     * @throws Exception\InvalidSlotException if the slot is not valid
     * @throws Exception\InvalidSlotReturnException if a slot returns invalid arguments (too few or return value is not an array)
     */
    public function dispatch(string $signalClassName, string $signalName, array $signalArguments = [])
    {
        $this->logger->debug('Triggered signal {signal_class} {signal_name} ', [
            'signal_class' => $signalClassName,
            'signal_name' => $signalName,
            'signalArguments' => $signalArguments,
        ]);
        if (!isset($this->slots[$signalClassName][$signalName])) {
            return $signalArguments;
        }
        foreach ($this->slots[$signalClassName][$signalName] as $slotInformation) {
            if (isset($slotInformation['object'])) {
                $object = $slotInformation['object'];
            } else {
                if (!class_exists($slotInformation['class'])) {
                    throw new InvalidSlotException('The given class "' . $slotInformation['class'] . '" is not a registered object.', 1245673367);
                }
                $object = $this->objectManager->get($slotInformation['class']);
            }

            $method = (string)($slotInformation['method'] ?? '');
            $callable = [$object, $method];

            if (!is_object($object) || !is_callable($callable)) {
                throw new InvalidSlotException('The slot method ' . get_class($object) . '->' . $method . '() does not exist.', 1245673368);
            }

            $preparedSlotArguments = $signalArguments;
            if ($slotInformation['passSignalInformation'] === true) {
                $preparedSlotArguments[] = $signalClassName . '::' . $signalName;
            }

            $slotReturn = $callable(...$preparedSlotArguments);

            if ($slotReturn) {
                if (!is_array($slotReturn)) {
                    throw new InvalidSlotReturnException('The slot method ' . get_class($object) . '->' . $method . '()\'s return value is of an not allowed type ('
                        . gettype($slotReturn) . ').', 1376683067);
                }
                if (count($slotReturn) !== count($signalArguments)) {
                    throw new InvalidSlotReturnException('The slot method ' . get_class($object) . '->' . $method . '() returned a different number ('
                        . count($slotReturn) . ') of arguments, than it received (' . count($signalArguments) . ').', 1376683066);
                }
                $signalArguments = $slotReturn;
            }
        }

        return $signalArguments;
    }

    /**
     * Returns all slots which are connected with the given signal
     *
     * @param string $signalClassName Name of the class containing the signal
     * @param string $signalName Name of the signal
     * @return array An array of arrays with slot information
     */
    public function getSlots(string $signalClassName, string $signalName): array
    {
        return $this->slots[$signalClassName][$signalName] ?? [];
    }
}
