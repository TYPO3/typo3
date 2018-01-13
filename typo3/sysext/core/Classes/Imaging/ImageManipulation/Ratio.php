<?php
declare(strict_types = 1);
namespace TYPO3\CMS\Core\Imaging\ImageManipulation;

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
        $this->id = str_replace('.', '_', $id);
        $this->title = $title;
        $this->value = $value;
    }

    /**
     * @return string
     */
    public function getId(): string
    {
        return $this->id;
    }

    /**
     * @param array $config
     * @return Ratio[]
     * @throws \TYPO3\CMS\Core\Imaging\ImageManipulation\InvalidConfigurationException
     */
    public static function createMultipleFromConfiguration(array $config): array
    {
        $areas = [];
        try {
            foreach ($config as $id => $ratioConfig) {
                $areas[] = new self(
                    $id,
                    $ratioConfig['title'],
                    (float)$ratioConfig['value']
                );
            }
        } catch (\Throwable $throwable) {
            throw new InvalidConfigurationException(sprintf('Invalid type for ratio id given: %s', $throwable->getMessage()), 1486313971, $throwable);
        }
        return $areas;
    }

    /**
     * @return array
     * @internal
     */
    public function asArray(): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'value' => $this->value,
        ];
    }

    /**
     * @return float
     */
    public function getRatioValue(): float
    {
        return $this->value;
    }

    /**
     * @return bool
     */
    public function isFree(): bool
    {
        return $this->value === 0.0;
    }
}
