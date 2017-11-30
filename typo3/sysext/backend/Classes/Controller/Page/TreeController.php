<?php
declare(strict_types=1);
namespace TYPO3\CMS\Backend\Controller\Page;

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

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use TYPO3\CMS\Backend\Controller\UserSettingsController;
use TYPO3\CMS\Backend\Tree\Pagetree\Commands;
use TYPO3\CMS\Backend\Tree\Pagetree\ExtdirectTreeDataProvider;
use TYPO3\CMS\Core\Authentication\BackendUserAuthentication;
use TYPO3\CMS\Core\Http\JsonResponse;
use TYPO3\CMS\Core\Type\Bitmask\JsConfirmation;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Controller providing data to the page tree
 */
class TreeController
{

    /**
     * Returns page tree configuration in JSON
     *
     * @return ResponseInterface
     */
    public function fetchConfigurationAction(): ResponseInterface
    {
        $configuration = [
            'allowRecursiveDelete' => !empty($this->getBackendUser()->uc['recursiveDelete']),
            'doktypes' => $this->getDokTypes(),
            'displayDeleteConfirmation' => $this->getBackendUser()->jsConfirmation(JsConfirmation::DELETE),
            'temporaryMountPoint' => Commands::getMountPointPath(),
        ];

        return GeneralUtility::makeInstance(JsonResponse::class, $configuration);
    }

    /**
     * Returns the list of doktypes to display in page tree toolbar drag area
     *
     * Note: The list can be filtered by the user TypoScript
     * option "options.pageTree.doktypesToShowInNewPageDragArea".
     *
     * @return array
     */
    protected function getDokTypes(): array
    {
        $doktypeLabelMap = [];
        foreach ($GLOBALS['TCA']['pages']['columns']['doktype']['config']['items'] as $doktypeItemConfig) {
            if ($doktypeItemConfig[1] === '--div--') {
                continue;
            }
            $doktypeLabelMap[$doktypeItemConfig[1]] = $doktypeItemConfig[0];
        }
        $doktypes = GeneralUtility::intExplode(',', $this->getBackendUser()->getTSConfigVal('options.pageTree.doktypesToShowInNewPageDragArea'));
        $output = [];
        $allowedDoktypes = GeneralUtility::intExplode(',', $GLOBALS['BE_USER']->groupData['pagetypes_select'], true);
        $isAdmin = $this->getBackendUser()->isAdmin();
        // Early return if backend user may not create any doktype
        if (!$isAdmin && empty($allowedDoktypes)) {
            return $output;
        }
        foreach ($doktypes as $doktype) {
            if (!$isAdmin && !in_array($doktype, $allowedDoktypes, true)) {
                continue;
            }
            $label = htmlspecialchars($GLOBALS['LANG']->sL($doktypeLabelMap[$doktype]));
            $output[] = [
                'nodeType' => $doktype,
                'icon' => $GLOBALS['TCA']['pages']['ctrl']['typeicon_classes'][$doktype] ?? '',
                'title' => $label,
                'tooltip' => $label
            ];
        }
        return $output;
    }

    /**
     * Returns JSON representing page tree
     *
     * @param ServerRequestInterface $request
     * @throws \RuntimeException
     * @return ResponseInterface
     */
    public function fetchDataAction(ServerRequestInterface $request): ResponseInterface
    {
        $dataProvider = GeneralUtility::makeInstance(ExtdirectTreeDataProvider::class);
        $node = new \stdClass();
        $node->id = 'root';
        if (!empty($request->getQueryParams()['pid'])) {
            $node->id = $request->getQueryParams()['pid'];
        }
        $nodeArray = $dataProvider->getNextTreeLevel($node->id, $node);

        //@todo refactor the PHP data provider side. Now we're using the old pagetree code and flatten the array afterwards
        $items = $this->nodeToFlatArray($nodeArray);

        return GeneralUtility::makeInstance(JsonResponse::class, $items);
    }

    /**
     * Converts nested tree structure produced by ExtdirectTreeDataProvider to a flat, one level array
     *
     * @param array $nodeArray
     * @param int $depth
     * @param array $inheritedData
     * @return array
     */
    protected function nodeToFlatArray(array $nodeArray, int $depth = 0, array $inheritedData = []): array
    {
        $userSettingsController = GeneralUtility::makeInstance(UserSettingsController::class);
        $state = $userSettingsController->process('get', 'BackendComponents.States.Pagetree');
        $items = [];
        foreach ($nodeArray as $key => $node) {
            $hexId = dechex($node['nodeData']['id']);
            $expanded = $node['nodeData']['expanded'] || (isset($state['stateHash'][$hexId]) && $state['stateHash'][$hexId]);
            $backgroundColor = !empty($node['nodeData']['backgroundColor']) ? $node['nodeData']['backgroundColor'] : ($inheritedData['backgroundColor'] ?? '');
            $items[] = [
                'identifier' => $node['nodeData']['id'],
                'depth' => $depth,
                'hasChildren' => !empty($node['children']),
                'icon' => $node['icon'],
                'name' => $node['editableText'],
                'tip' => $node['qtip'],
                'nameSourceField' => $node['t3TextSourceField'],
                'alias' => $node['alias'],
                'prefix' => $node['prefix'],
                'suffix' => $node['suffix'],
                'overlayIcon' => $node['overlayIcon'],
                'selectable' => true,
                'expanded' => (bool)$expanded,
                'checked' => false,
                'backgroundColor' => htmlspecialchars($backgroundColor),
                'stopPageTree' => $node['nodeData']['stopPageTree'],
                //used to mark versioned records, see $row['_CSSCLASS'], e.g. ver-element
                'class' => (string)$node['cls'],
                'readableRootline' => $node['nodeData']['readableRootline'],
                'isMountPoint' => $node['nodeData']['isMountPoint'],
                'mountPoint' => $node['nodeData']['mountPoint'],
                'workspaceId' => $node['nodeData']['workspaceId'],
            ];
            if (!empty($node['children'])) {
                $items = array_merge($items, $this->nodeToFlatArray($node['children'], $depth + 1, ['backgroundColor' => $backgroundColor]));
            }
        }
        return $items;
    }

    /**
     * Sets a temporary mount point
     *
     * @param ServerRequestInterface $request
     * @return ResponseInterface
     * @throws \RuntimeException
     */
    public function setTemporaryMountPointAction(ServerRequestInterface $request): ResponseInterface
    {
        if (empty($request->getParsedBody()['pid'])) {
            throw new \RuntimeException(
                'Required "pid" parameter is missing.',
                1511792197
            );
        }
        $pid = (int)$request->getParsedBody()['pid'];

        $this->getBackendUser()->uc['pageTree_temporaryMountPoint'] = $pid;
        $this->getBackendUser()->writeUC(static::getBackendUser()->uc);
        $response = [
            'mountPointPath' => Commands::getMountPointPath(),
        ];
        return GeneralUtility::makeInstance(JsonResponse::class, $response);
    }

    /**
     * @return BackendUserAuthentication
     */
    protected function getBackendUser()
    {
        return $GLOBALS['BE_USER'];
    }
}
