<?php
namespace TYPO3\CMS\Install\Controller\Action\Ajax;

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

/**
 * Check if a younger version is available
 */
class CoreUpdateIsUpdateAvailable extends AbstractCoreUpdate
{
    /**
     * Executes the action
     *
     * @return array Rendered content
     */
    protected function executeAction()
    {
        $status = [];
        if ($this->coreVersionService->isInstalledVersionAReleasedVersion()) {
            $isDevelopmentUpdateAvailable = $this->coreVersionService->isYoungerPatchDevelopmentReleaseAvailable();
            $isUpdateAvailable = $this->coreVersionService->isYoungerPatchReleaseAvailable();
            $isUpdateSecurityRelevant = $this->coreVersionService->isUpdateSecurityRelevant();

            if (!$isUpdateAvailable && !$isDevelopmentUpdateAvailable) {
                $status = $this->getMessage('notice', 'No regular update available');
            } elseif ($isUpdateAvailable) {
                $newVersion = $this->coreVersionService->getYoungestPatchRelease();
                if ($isUpdateSecurityRelevant) {
                    $status = $this->getMessage('warning', 'Update to security relevant released version ' . $newVersion . ' is available!');
                    $action = $this->getAction('Update now', 'updateRegular');
                } else {
                    $status = $this->getMessage('info', 'Update to regular released version ' . $newVersion . ' is available!');
                    $action = $this->getAction('Update now', 'updateRegular');
                }
            } elseif ($isDevelopmentUpdateAvailable) {
                $newVersion = $this->coreVersionService->getYoungestPatchDevelopmentRelease();
                $status = $this->getMessage('info', 'Update to development release ' . $newVersion . ' is available!');
                $action = $this->getAction('Update now', 'updateDevelopment');
            }
        } else {
            $status = $this->getMessage('warning', 'Current version is a development version and can not be updated');
        }

        $this->view->assign('success', true);
        $this->view->assign('status', [$status]);
        if (isset($action)) {
            $this->view->assign('action', $action);
        }

        return $this->view->render();
    }

    /**
     * @param string $severity
     * @param string $title
     * @param string $message
     * @return \TYPO3\CMS\Install\Status\StatusInterface
     */
    protected function getMessage($severity, $title, $message = '')
    {
        /** @var $statusMessage \TYPO3\CMS\Install\Status\StatusInterface */
        $statusMessage = $this->objectManager->get('TYPO3\\CMS\\Install\\Status\\' . ucfirst($severity) . 'Status');
        $statusMessage->setTitle($title);
        $statusMessage->setMessage($message);

        return $statusMessage;
    }

    /**
     * @param string $title
     * @param string $action
     * @return array
     */
    protected function getAction($title, $action)
    {
        return [
            'title' => $title,
            'action' => $action,
        ];
    }
}
