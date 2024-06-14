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

namespace TYPO3\CMS\Core\Imaging\ImageManipulation;

class Ratio
{
    /**
     * @var string
     */
    protected $id;
    /**
     * @var string
     */
    protected $title;
    /**
     * @var float
     */
    protected $value;

    public function __construct(string $id, string $title, float $value)
    {
        $this->id = self::prepareAspectRatioId($id);
        $this->title = $title;
        $this->value = $value;
    }

    public function getId(): string
    {
        return $this->id;
    }

    /**
     * Adjust names of Ratios for special character replacement.
     *
     * @todo in 14 - Rework the ImageManipulationElement.html logic to actually allow dot keys.
     * Ratio names are referenced through fluid, see https://forge.typo3.org/issues/80214
     * Should be possible by iterating {cropVariant.allowedAspectRatios.{cropVariant.selectedRatio}.title}
     * in the controller, and assigning a distinct, un-nested variable.
     * This is a breaking change because then aspect ratios defined with a key
     * will be referred to differently and wouldn't be resolved as before. Probably a migration wizard
     * would be needed.
     *
     * @internal not part of TYPO3 Core API as this method might vanish soon.
     */
    public static function prepareAspectRatioId(string $id): string
    {
        return str_replace('.', '_', $id);
    }

    /**
     * @return list<Ratio>
     * @throws \TYPO3\CMS\Core\Imaging\ImageManipulation\InvalidConfigurationException
     */
    public static function createMultipleFromConfiguration(array $config): array
    {
        $areas = [];
        try {
            foreach ($config as $id => $ratioConfig) {
                $areas[] = new self(
                    $id,
                    (string)($ratioConfig['title'] ?? ''),
                    (float)($ratioConfig['value'] ?? 0.0)
                );
            }
        } catch (\Throwable $throwable) {
            throw new InvalidConfigurationException(sprintf('Invalid type for ratio id given: %s', $throwable->getMessage()), 1486313971, $throwable);
        }
        return $areas;
    }

    /**
     * @internal
     *
     * @return array{id: string, title: string, value: float}
     */
    public function asArray(): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'value' => $this->value,
        ];
    }

    public function getRatioValue(): float
    {
        return $this->value;
    }

    public function isFree(): bool
    {
        return $this->value === 0.0;
    }
}
