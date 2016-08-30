<?php
namespace TYPO3\CMS\Frontend\Controller;

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
use TYPO3\CMS\Core\Exception;
use TYPO3\CMS\Core\Resource\ResourceFactory;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\MathUtility;

/**
 * eID-Script "tx_cms_showpic"
 *
 * Shows a picture from FAL in enlarged format in a separate window.
 * Picture file and settings is supplied by GET-parameters:
 *
 *  - file = fileUid or Combined Identifier
 *  - encoded in an parameter Array (with weird format - see ContentObjectRenderer about ll. 1500)
 *  - width, height = usual width an height, m/c supported
 *  - frame
 *  - bodyTag
 *  - title
 */
class ShowImageController
{
    /**
     * @var \Psr\Http\Message\ServerRequestInterface
     */
    protected $request;

    /**
     * @var \TYPO3\CMS\Core\Resource\File
     */
    protected $file;

    /**
     * @var int
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
     * @return void
     * @throws \InvalidArgumentException
     * @throws \TYPO3\CMS\Core\Resource\Exception\FileDoesNotExistException
     */
    public function initialize()
    {
        $fileUid = isset($this->request->getQueryParams()['file']) ? $this->request->getQueryParams()['file'] : null;
        $parametersArray = isset($this->request->getQueryParams()['parameters']) ? $this->request->getQueryParams()['parameters'] : null;

        // If no file-param or parameters are given, we must exit
        if (!$fileUid || !isset($parametersArray) || !is_array($parametersArray)) {
            throw new \InvalidArgumentException('No valid fileUid given');
        }

        // rebuild the parameter array and check if the HMAC is correct
        $parametersEncoded = implode('', $parametersArray);

        /* For backwards compatibility the HMAC is transported within the md5 param */
        $hmacParameter = isset($this->request->getQueryParams()['md5']) ? $this->request->getQueryParams()['md5'] : null;
        $hmac = GeneralUtility::hmac(implode('|', [$fileUid, $parametersEncoded]));
        if ($hmac !== $hmacParameter) {
            throw new \InvalidArgumentException('hash does not match');
        }

        // decode the parameters Array
        $parameters = unserialize(base64_decode($parametersEncoded));
        foreach ($parameters as $parameterName => $parameterValue) {
            $this->{$parameterName} = $parameterValue;
        }

        if (MathUtility::canBeInterpretedAsInteger($fileUid)) {
            $this->file = ResourceFactory::getInstance()->getFileObject((int)$fileUid);
        } else {
            $this->file = ResourceFactory::getInstance()->retrieveFileOrFolderObject($fileUid);
        }
        $this->frame = isset($this->request->getQueryParams()['frame']) ? $this->request->getQueryParams()['frame'] : null;
    }

    /**
     * Main function which creates the image if needed and outputs the HTML code for the page displaying the image.
     * Accumulates the content in $this->content
     *
     * @return void
     */
    public function main()
    {
        $processedImage = $this->processImage();
        $imageTagMarkers = [
            '###publicUrl###' => htmlspecialchars($processedImage->getPublicUrl()),
            '###alt###' => htmlspecialchars($this->file->getProperty('alternative') ?: $this->title),
            '###title###' => htmlspecialchars($this->file->getProperty('title') ?: $this->title),
            '###width###' => $processedImage->getProperty('width'),
            '###height###' => $processedImage->getProperty('height')
        ];
        $this->imageTag = str_replace(array_keys($imageTagMarkers), array_values($imageTagMarkers), $this->imageTag);
        $markerArray = [
            '###TITLE###' => ($this->file->getProperty('title') ?: $this->title),
            '###IMAGE###' => $this->imageTag,
            '###BODY###' => $this->bodyTag
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
        if (strstr($this->width . $this->height, 'm')) {
            $max = 'm';
        } else {
            $max = '';
        }
        $this->height = MathUtility::forceIntegerInRange($this->height, 0);
        $this->width = MathUtility::forceIntegerInRange($this->width, 0) . $max;

        $processingConfiguration = [
            'width' => $this->width,
            'height' => $this->height,
            'frame' => $this->frame,
            'crop' => $this->crop,
        ];
        return $this->file->process('Image.CropScaleMask', $processingConfiguration);
    }

    /**
     * Fetches the content and builds a content file out of it
     *
     * @param ServerRequestInterface $request the current request object
     * @param ResponseInterface $response the available response
     * @return ResponseInterface the modified response
     */
    public function processRequest(ServerRequestInterface $request, ResponseInterface $response)
    {
        $this->request = $request;

        try {
            $this->initialize();
            $this->main();
            $response->getBody()->write($this->content);
            return $response;
        } catch (\InvalidArgumentException $e) {
            // add a 410 "gone" if invalid parameters given
            return $response->withStatus(410);
        } catch (Exception $e) {
            return $response->withStatus(404);
        }
    }
}
