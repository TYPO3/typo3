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

namespace TYPO3Tests\ParentChildTranslation\Controller;

use Psr\Http\Message\ResponseInterface;
use TYPO3\CMS\Extbase\Mvc\Controller\ActionController;
use TYPO3Tests\ParentChildTranslation\Domain\Repository\MainRepository;

final class MainController extends ActionController
{
    public function __construct(public readonly MainRepository $mainRepository) {}

    public function listAction(): ResponseInterface
    {
        $this->view->assign('items', $this->mainRepository->findAll());

        return $this->htmlResponse();
    }
}
