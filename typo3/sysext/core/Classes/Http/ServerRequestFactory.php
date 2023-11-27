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

namespace TYPO3\CMS\Core\Http;

use Psr\Http\Message\ServerRequestFactoryInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\UploadedFileInterface;
use Psr\Http\Message\UriInterface;
use TYPO3\CMS\Core\Core\Environment;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Class ServerRequestFactory to create ServerRequest objects
 *
 * Highly inspired by https://github.com/phly/http/
 *
 * @internal Note that this is not public API yet.
 */
class ServerRequestFactory implements ServerRequestFactoryInterface
{
    /**
     * Create a new server request.
     *
     * Note that server-params are taken precisely as given - no parsing/processing
     * of the given values is performed, and, in particular, no attempt is made to
     * determine the HTTP method or URI, which must be provided explicitly.
     *
     * @param string $method The HTTP method associated with the request.
     * @param UriInterface|string $uri The URI associated with the request.
     * @param array $serverParams Array of SAPI parameters with which to seed the generated request instance.
     */
    public function createServerRequest(string $method, $uri, array $serverParams = []): ServerRequestInterface
    {
        return new ServerRequest($uri, $method, null, [], $serverParams);
    }

    /**
     * Create a request from the original superglobal variables.
     *
     * @return ServerRequest
     * @throws \InvalidArgumentException when invalid file values given
     * @internal Note that this is not public API yet.
     */
    public static function fromGlobals()
    {
        $serverParameters = $_SERVER;
        $headers = static::prepareHeaders($serverParameters);

        $method = $serverParameters['REQUEST_METHOD'] ?? 'GET';
        try {
            $uri = new Uri(GeneralUtility::getIndpEnv('TYPO3_REQUEST_URL'));
        } catch (\InvalidArgumentException $e) {
            if (Environment::isCli()) {
                throw new InvalidRequestUrlOnCliException(
                    'Usage of ' . __METHOD__ . ' on CLI is discouraged. In case you rely on the method, you have to fake a valid request URL using $_SERVER.',
                    1701105725
                );
            }
            throw $e;
        }

        $request = new ServerRequest(
            $uri,
            $method,
            'php://input',
            $headers,
            $serverParameters,
            static::normalizeUploadedFiles($_FILES)
        );
        if (!empty($_COOKIE)) {
            $request = $request->withCookieParams($_COOKIE);
        }
        if (!empty($_GET)) {
            $request = $request->withQueryParams($_GET);
        }
        $parsedBody = $_POST;
        if (empty($parsedBody) && in_array($method, ['PUT', 'PATCH', 'DELETE'])) {
            parse_str((string)file_get_contents('php://input'), $parsedBody);
        }
        if (!empty($parsedBody)) {
            $request = $request->withParsedBody($parsedBody);
        }
        return $request;
    }

    /**
     * Fetch headers from $_SERVER variables
     * which are only the ones starting with HTTP_* and CONTENT_*
     *
     * @return array
     */
    protected static function prepareHeaders(array $server)
    {
        $headers = [];
        foreach ($server as $key => $value) {
            if (!is_string($key)) {
                continue;
            }
            if (str_starts_with($key, 'HTTP_COOKIE')) {
                // Cookies are handled using the $_COOKIE superglobal
                continue;
            }
            if (!empty($value)) {
                if (str_starts_with($key, 'HTTP_')) {
                    $name = str_replace('_', ' ', substr($key, 5));
                    $name = str_replace(' ', '-', ucwords(strtolower($name)));
                    $name = strtolower($name);
                    $headers[$name] = $value;
                } elseif (str_starts_with($key, 'CONTENT_')) {
                    $name = substr($key, 8); // Content-
                    $name = 'Content-' . (($name === 'MD5') ? $name : ucfirst(strtolower($name)));
                    $name = strtolower($name);
                    $headers[$name] = $value;
                }
            }
        }
        return $headers;
    }

    /**
     * Normalize uploaded files
     *
     * Transforms each value into an UploadedFileInterface instance, and ensures that nested arrays are normalized.
     *
     * @param array $files
     * @return array
     * @throws \InvalidArgumentException for unrecognized values
     */
    protected static function normalizeUploadedFiles(array $files)
    {
        $normalizedFileUploads = [];
        foreach ($files as $key => $value) {
            if ($value instanceof UploadedFileInterface) {
                $normalizedFileUploads[$key] = $value;
            } elseif (is_array($value)) {
                if (isset($value['tmp_name'])) {
                    $uploadedFiles = self::createUploadedFile($value);
                    if ($uploadedFiles) {
                        $normalizedFileUploads[$key] = $uploadedFiles;
                    }
                } else {
                    $normalizedFileUploads[$key] = self::normalizeUploadedFiles($value);
                }
            } else {
                throw new \InvalidArgumentException('Invalid value in files specification.', 1436717282);
            }
        }
        return $normalizedFileUploads;
    }

    /**
     * Create and return an UploadedFile instance from a $_FILES specification.
     *
     * If the specification represents an array of values, this method will
     * recursively resolve uploaded files.
     *
     * @param array $value $_FILES structure
     * @return UploadedFileInterface[]|UploadedFileInterface|null
     */
    protected static function createUploadedFile(array $value)
    {
        if (is_array($value['tmp_name'])) {
            $files = [];
            foreach (array_keys($value['tmp_name']) as $key) {
                $data = [
                    'tmp_name' => $value['tmp_name'][$key],
                    'error'    => $value['error'][$key],
                    'name'     => $value['name'][$key],
                    'type'     => $value['type'][$key],
                ];
                if (isset($value['size'][$key])) {
                    $data['size'] = $value['size'][$key];
                }
                $result = self::createUploadedFile($data);
                if ($result) {
                    $files[$key] = $result;
                }
            }
            return $files;
        }
        if (!empty($value['tmp_name'])) {
            return new UploadedFile($value['tmp_name'], $value['size'] ?? 0, $value['error'], $value['name'], $value['type']);
        }
        return null;
    }
}
