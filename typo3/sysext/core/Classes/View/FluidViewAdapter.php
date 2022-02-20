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

namespace TYPO3\CMS\Core\View;

use TYPO3\CMS\Core\View\ViewInterface as CoreViewInterface;
use TYPO3Fluid\Fluid\View\TemplateAwareViewInterface as FluidTemplateAwareViewInterface;
use TYPO3Fluid\Fluid\View\ViewInterface as FluidViewInterface;

/**
 * A view adapter that handles a Typo3Fluid view and implements generic ext:core ViewInterface.
 */
class FluidViewAdapter implements CoreViewInterface
{
    public function __construct(
        protected readonly FluidViewInterface&FluidTemplateAwareViewInterface $view,
    ) {
    }

    public function assign(string $key, mixed $value): self
    {
        $this->view->assign($key, $value);
        return $this;
    }

    public function assignMultiple(array $values): self
    {
        $this->view->assignMultiple($values);
        return $this;
    }

    public function render(string $templateFileName = ''): string
    {
        return $this->view->render($templateFileName);
    }
}
