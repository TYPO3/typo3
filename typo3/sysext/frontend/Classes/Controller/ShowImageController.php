<?php

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

namespace TYPO3\CMS\Frontend\Controller;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use TYPO3\CMS\Core\Exception;
use TYPO3\CMS\Core\Http\Response;
use TYPO3\CMS\Core\Resource\File;
use TYPO3\CMS\Core\Resource\FileInterface;
use TYPO3\CMS\Core\Resource\Folder;
use TYPO3\CMS\Core\Resource\ProcessedFile;
use TYPO3\CMS\Core\Resource\ResourceFactory;
use TYPO3\CMS\Core\Resource\Security\FileNameValidator;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\MathUtility;

/**
 * eID-Script "tx_cms_showpic"
 *
 * Shows a picture from FAL in enlarged format in a separate window.
 * Picture file and settings is supplied by GET-parameters:
 *
 *  - file = fileUid or Combined Identifier
 *  - encoded in a parameter Array (with weird format - see ContentObjectRenderer about ll. 1500)
 *  - width, height = usual width an height, m/c supported
 *  - frame
 *  - bodyTag
 *  - title
 *
 * @internal this is a concrete TYPO3 implementation and solely used for EXT:frontend and not part of TYPO3's Core API.
 */
class ShowImageController
{
    protected const ALLOWED_PARAMETER_NAMES = ['width', 'height', 'crop', 'bodyTag', 'title'];

    /**
     * @var \Psr\Http\Message\ServerRequestInterface
     */
    protected $request;

    /**
     * @var File|Folder|null
     */
    protected $file;

    /**
     * @var string
     */
    protected $width;

    /**
     * @var int
     */
    protected $height;

    /**
     * @var string
     */
    protected $crop;

    /**
     * @var int
     */
    protected $frame;

    /**
     * @var string
     */
    protected $bodyTag = '<body>';

    /**
     * @var string
     */
    protected $title = 'Image';

    /**
     * @var string
     */
    protected $content = <<<EOF
<!DOCTYPE html>
<html>
<head>
	<title>###TITLE###</title>
	<meta name="robots" content="noindex,follow" />
</head>
###BODY###
	###IMAGE###
</body>
</html>
EOF;

    /**
     * @var string
     */
    protected $imageTag = '<img src="###publicUrl###" alt="###alt###" title="###title###" width="###width###" height="###height###" />';

    /**
     * Init function, setting the input vars in the global space.
     *
     * @throws \InvalidArgumentException
     * @throws \TYPO3\CMS\Core\Resource\Exception\FileDoesNotExistException
     */
    public function initialize()
    {
        $fileUid = $this->request->getQueryParams()['file'] ?? null;
        $parametersArray = $this->request->getQueryParams()['parameters'] ?? null;

        // If no file-param or parameters are given, we must exit
        if (!$fileUid || !isset($parametersArray) || !is_array($parametersArray)) {
            throw new \InvalidArgumentException('No valid fileUid given', 1476048455);
        }

        // rebuild the parameter array and check if the HMAC is correct
        $parametersEncoded = implode('', $parametersArray);

        /* For backwards compatibility the HMAC is transported within the md5 param */
        $hmacParameter = $this->request->getQueryParams()['md5'] ?? null;
        $hmac = GeneralUtility::hmac(implode('|', [$fileUid, $parametersEncoded]));
        if (!is_string($hmacParameter) || !hash_equals($hmac, $hmacParameter)) {
            throw new \InvalidArgumentException('hash does not match', 1476048456);
        }

        // decode the parameters Array - `bodyTag` contains HTML if set and would lead
        // to a false-positive XSS-detection, that's why parameters are base64-encoded
        $parameters = json_decode(base64_decode($parametersEncoded), true) ?? [];
        foreach ($parameters as $parameterName => $parameterValue) {
            if (in_array($parameterName, static::ALLOWED_PARAMETER_NAMES, true)) {
                $this->{$parameterName} = $parameterValue;
            }
        }

        if (MathUtility::canBeInterpretedAsInteger($fileUid)) {
            $this->file = GeneralUtility::makeInstance(ResourceFactory::class)->getFileObject((int)$fileUid);
        } else {
            $this->file = GeneralUtility::makeInstance(ResourceFactory::class)->retrieveFileOrFolderObject($fileUid);
        }
        if (!($this->file instanceof FileInterface && $this->isFileValid($this->file))) {
            throw new Exception('File processing for local storage is denied', 1594043425);
        }

        $this->frame = $this->request->getQueryParams()['frame'] ?? null;
    }

    /**
     * Main function which creates the image if needed and outputs the HTML code for the page displaying the image.
     * Accumulates the content in $this->content
     */
    public function main()
    {
        $processedImage = $this->processImage();
        $imageTagMarkers = [
            '###publicUrl###' => htmlspecialchars($processedImage->getPublicUrl() ?? ''),
            '###alt###' => htmlspecialchars($this->file->getProperty('alternative') ?: $this->title),
            '###title###' => htmlspecialchars($this->file->getProperty('title') ?: $this->title),
            '###width###' => $processedImage->getProperty('width'),
            '###height###' => $processedImage->getProperty('height'),
        ];
        $this->imageTag = str_replace(array_keys($imageTagMarkers), array_values($imageTagMarkers), $this->imageTag);
        $markerArray = [
            '###TITLE###' => $this->file->getProperty('title') ?: $this->title,
            '###IMAGE###' => $this->imageTag,
            '###BODY###' => $this->bodyTag,
        ];

        $this->content = str_replace(array_keys($markerArray), array_values($markerArray), $this->content);
    }

    /**
     * Does the actual image processing
     *
     * @return \TYPO3\CMS\Core\Resource\ProcessedFile
     */
    protected function processImage()
    {
        $max = str_contains($this->width . $this->height, 'm') ? 'm' : '';
        $this->height = MathUtility::forceIntegerInRange($this->height, 0);
        $this->width = MathUtility::forceIntegerInRange((int)$this->width, 0) . $max;

        $processingConfiguration = [
            'width' => $this->width,
            'height' => $this->height,
            'frame' => $this->frame,
            'crop' => $this->crop,
        ];
        return $this->file->process(ProcessedFile::CONTEXT_IMAGECROPSCALEMASK, $processingConfiguration);
    }

    /**
     * Fetches the content and builds a content file out of it
     *
     * @param ServerRequestInterface $request the current request object
     * @return ResponseInterface the modified response
     */
    public function processRequest(ServerRequestInterface $request): ResponseInterface
    {
        $this->request = $request;

        try {
            $this->initialize();
            $this->main();
            $response = new Response();
            $response->getBody()->write($this->content);
            return $response;
        } catch (\InvalidArgumentException $e) {
            // add a 410 "gone" if invalid parameters given
            return (new Response())->withStatus(410);
        } catch (Exception $e) {
            return (new Response())->withStatus(404);
        }
    }

    protected function isFileValid(FileInterface $file): bool
    {
        return $file->getStorage()->getDriverType() !== 'Local'
            || GeneralUtility::makeInstance(FileNameValidator::class)
                ->isValid(basename($file->getIdentifier()));
    }
}
