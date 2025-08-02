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

namespace TYPO3Tests\ExtbaseUpload\Controller;

use Psr\Http\Message\ResponseInterface;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Attribute\IgnoreValidation;
use TYPO3\CMS\Extbase\Mvc\Controller\ActionController;
use TYPO3Tests\ExtbaseUpload\Domain\Model\Singlefile;
use TYPO3Tests\ExtbaseUpload\Domain\Repository\SinglefileRepository;

class SingleFileUploadController extends ActionController
{
    public function __construct(protected readonly SinglefileRepository $singlefileRepository) {}

    public function listAction(): ResponseInterface
    {
        $this->view->assignMultiple([
            'items' => $this->singlefileRepository->findAll(),
        ]);

        return $this->htmlResponse();
    }

    public function newAction(): ResponseInterface
    {
        $this->view->assignMultiple([
            'item' => GeneralUtility::makeInstance(Singlefile::class),
        ]);

        return $this->htmlResponse();
    }

    public function createAction(Singlefile $item): ResponseInterface
    {
        $item->setPid((int)($this->settings['singleFileUploadPid'] ?? 0));
        $this->singlefileRepository->add($item);

        return $this->redirect('list');
    }

    public function showAction(Singlefile $item): ResponseInterface
    {
        $this->view->assignMultiple([
            'item' => $item,
        ]);

        return $this->htmlResponse();
    }

    #[IgnoreValidation(['argumentName' => 'item'])]
    public function editAction(Singlefile $item): ResponseInterface
    {
        $this->view->assignMultiple([
            'item' => $item,
        ]);

        return $this->htmlResponse();
    }

    public function updateAction(Singlefile $item): ResponseInterface
    {
        $this->singlefileRepository->update($item);

        return $this->redirect('list');
    }
}
