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

namespace TYPO3Tests\TestViewfactoryCustomview\View;

use TYPO3\CMS\Core\View\ViewInterface;

final class CustomView implements ViewInterface
{
    private $variables = [];

    public function assign(string $key, mixed $value): self
    {
        $this->variables[$key] = $value;
        return $this;
    }

    public function assignMultiple(array $values): self
    {
        $this->variables = array_merge($this->variables, $values);
        return $this;
    }

    public function render(string $templateFileName = ''): string
    {
        return json_encode(['template' => $templateFileName, 'variables' => $this->variables]);
    }
}
