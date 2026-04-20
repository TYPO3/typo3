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

namespace TYPO3\CMS\Core\Imaging\Effect;

use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Turns the value of a GIFBUILDER EFFECT object, e.g.
 * "gray | rotate = 90 | wave = 10,30", into effect instances.
 * Unknown keywords are ignored.
 *
 * @internal not part of TYPO3 Core API.
 */
final readonly class EffectParser
{
    /**
     * @return list<Blur|Charcoal|Colors|Edge|Emboss|Flip|Flop|Gamma|Gray|Invert|Rotate|Shear|Sharpen|Solarize|Swirl|Wave>
     */
    public function parse(string $setup): array
    {
        $effects = [];
        foreach (explode('|', $setup) as $token) {
            $token = trim($token);
            if ($token === '') {
                continue;
            }
            [$name, $value] = array_pad(explode('=', $token, 2), 2, '');
            $value = trim($value);
            $effect = match (strtolower(trim($name))) {
                'blur' => new Blur((int)$value),
                'charcoal' => new Charcoal((int)$value),
                'colors' => new Colors((int)$value),
                'edge' => new Edge(),
                'emboss' => new Emboss(),
                'flip' => new Flip(),
                'flop' => new Flop(),
                'gamma' => new Gamma((float)$value),
                'gray' => new Gray(),
                'invert' => new Invert(),
                'rotate' => new Rotate((float)$value),
                'shear' => new Shear((int)$value),
                'sharpen' => new Sharpen((int)$value),
                'solarize' => new Solarize((int)$value),
                'swirl' => new Swirl((int)$value),
                // a missing wavelength keeps the default
                'wave' => new Wave(...array_slice(GeneralUtility::intExplode(',', $value), 0, 2)),
                default => null,
            };
            if ($effect !== null) {
                $effects[] = $effect;
            }
        }
        return $effects;
    }
}
