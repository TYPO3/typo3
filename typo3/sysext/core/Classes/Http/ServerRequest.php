<?php
namespace TYPO3\CMS\Core\Http;

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

use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\StreamInterface;
use Psr\Http\Message\UploadedFileInterface;

/**
 * Represents a typical request incoming from the server to be processed
 * by the TYPO3 Core. The original request is built from the ServerRequestFactory
 * inside TYPO3's Bootstrap.
 *
 * Note that the PSR-7 standard works with immutable value objects, meaning that
 * any modification to a Request object using the "with" methods will result
 * in a new Request object.
 *
 * Highly inspired by https://github.com/phly/http/
 *
 * @internal Note that this is not public API yet.
 */
class ServerRequest extends Request implements ServerRequestInterface
{
    /**
     * @var array
     */
    protected $attributes = [];

    /**
     * @var array
     */
    protected $cookieParams = [];

    /**
     * @var array
     */
    protected $parsedBody;

    /**
     * @var array
     */
    protected $queryParams = [];

    /**
     * @var array
     */
    protected $serverParams = [];

    /**
     * @var array
     */
    protected $uploadedFiles = [];

    /**
     * Constructor, the only place to set all parameters of this Message/Request
     *
     * @param string|null $uri URI for the request, if any.
     * @param string|null $method HTTP method for the request, if any.
     * @param string|resource|StreamInterface $body Message body, if any.
     * @param array $headers Headers for the message, if any.
     * @param array $serverParams Server parameters, typically from $_SERVER
     * @param array $uploadedFiles Upload file information, a tree of UploadedFiles
     * @throws \InvalidArgumentException for any invalid value.
     */
    public function __construct($uri = null, $method = null, $body = 'php://input', array $headers = [], array $serverParams = [], array $uploadedFiles = null)
    {
        if ($uploadedFiles !== null) {
            $this->validateUploadedFiles($uploadedFiles);
        }

        parent::__construct($uri, $method, $body, $headers);

        $this->serverParams  = $serverParams;
        $this->uploadedFiles = $uploadedFiles;
    }

    /**
     * Retrieve server parameters.
     *
     * Retrieves data related to the incoming request environment,
     * typically derived from PHP's $_SERVER superglobal. The data IS NOT
     * REQUIRED to originate from $_SERVER.
     *
     * @return array
     */
    public function getServerParams()
    {
        return $this->serverParams;
    }

    /**
     * Retrieve cookies.
     *
     * Retrieves cookies sent by the client to the server.
     *
     * The data MUST be compatible with the structure of the $_COOKIE
     * superglobal.
     *
     * @return array
     */
    public function getCookieParams()
    {
        return $this->cookieParams;
    }

    /**
     * Return an instance with the specified cookies.
     *
     * The data IS NOT REQUIRED to come from the $_COOKIE superglobal, but MUST
     * be compatible with the structure of $_COOKIE. Typically, this data will
     * be injected at instantiation.
     *
     * This method MUST NOT update the related Cookie header of the request
     * instance, nor related values in the server params.
     *
     * This method MUST be implemented in such a way as to retain the
     * immutability of the message, and MUST return an instance that has the
     * updated cookie values.
     *
     * @param array $cookies Array of key/value pairs representing cookies.
     * @return ServerRequest
     */
    public function withCookieParams(array $cookies)
    {
        $clonedObject = clone $this;
        $clonedObject->cookieParams = $cookies;
        return $clonedObject;
    }

    /**
     * Retrieve query string arguments.
     *
     * Retrieves the deserialized query string arguments, if any.
     *
     * Note: the query params might not be in sync with the URI or server
     * params. If you need to ensure you are only getting the original
     * values, you may need to parse the query string from `getUri()->getQuery()`
     * or from the `QUERY_STRING` server param.
     *
     * @return array
     */
    public function getQueryParams()
    {
        return $this->queryParams;
    }

    /**
     * Return an instance with the specified query string arguments.
     *
     * These values SHOULD remain immutable over the course of the incoming
     * request. They MAY be injected during instantiation, such as from PHP's
     * $_GET superglobal, or MAY be derived from some other value such as the
     * URI. In cases where the arguments are parsed from the URI, the data
     * MUST be compatible with what PHP's parse_str() would return for
     * purposes of how duplicate query parameters are handled, and how nested
     * sets are handled.
     *
     * Setting query string arguments MUST NOT change the URI stored by the
     * request, nor the values in the server params.
     *
     * This method MUST be implemented in such a way as to retain the
     * immutability of the message, and MUST return an instance that has the
     * updated query string arguments.
     *
     * @param array $query Array of query string arguments, typically from
     *     $_GET.
     * @return ServerRequest
     */
    public function withQueryParams(array $query)
    {
        $clonedObject = clone $this;
        $clonedObject->queryParams = $query;
        return $clonedObject;
    }

    /**
     * Retrieve normalized file upload data.
     *
     * This method returns upload metadata in a normalized tree, with each leaf
     * an instance of Psr\Http\Message\UploadedFileInterface.
     *
     * These values MAY be prepared from $_FILES or the message body during
     * instantiation, or MAY be injected via withUploadedFiles().
     *
     * @return array An array tree of UploadedFileInterface instances; an empty
     *     array MUST be returned if no data is present.
     */
    public function getUploadedFiles()
    {
        return $this->uploadedFiles;
    }

    /**
     * Create a new instance with the specified uploaded files.
     *
     * This method MUST be implemented in such a way as to retain the
     * immutability of the message, and MUST return an instance that has the
     * updated body parameters.
     *
     * @param array $uploadedFiles An array tree of UploadedFileInterface instances.
     * @return ServerRequest
     * @throws \InvalidArgumentException if an invalid structure is provided.
     */
    public function withUploadedFiles(array $uploadedFiles)
    {
        $this->validateUploadedFiles($uploadedFiles);
        $clonedObject = clone $this;
        $clonedObject->uploadedFiles = $uploadedFiles;
        return $clonedObject;
    }

    /**
     * Retrieve any parameters provided in the request body.
     *
     * If the request Content-Type is either application/x-www-form-urlencoded
     * or multipart/form-data, and the request method is POST, this method MUST
     * return the contents of $_POST.
     *
     * Otherwise, this method may return any results of deserializing
     * the request body content; as parsing returns structured content, the
     * potential types MUST be arrays or objects only. A null value indicates
     * the absence of body content.
     *
     * @return array|object|null The deserialized body parameters, if any.
     *     These will typically be an array or object.
     */
    public function getParsedBody()
    {
        return $this->parsedBody;
    }

    /**
     * Return an instance with the specified body parameters.
     *
     * These MAY be injected during instantiation.
     *
     * If the request Content-Type is either application/x-www-form-urlencoded
     * or multipart/form-data, and the request method is POST, use this method
     * ONLY to inject the contents of $_POST.
     *
     * The data IS NOT REQUIRED to come from $_POST, but MUST be the results of
     * deserializing the request body content. Deserialization/parsing returns
     * structured data, and, as such, this method ONLY accepts arrays or objects,
     * or a null value if nothing was available to parse.
     *
     * As an example, if content negotiation determines that the request data
     * is a JSON payload, this method could be used to create a request
     * instance with the deserialized parameters.
     *
     * This method MUST be implemented in such a way as to retain the
     * immutability of the message, and MUST return an instance that has the
     * updated body parameters.
     *
     * @param array|object|null $data The deserialized body data. This will
     *     typically be in an array or object.
     * @return ServerRequest
     * @throws \InvalidArgumentException if an unsupported argument type is
     *     provided.
     */
    public function withParsedBody($data)
    {
        $clonedObject = clone $this;
        $clonedObject->parsedBody = $data;
        return $clonedObject;
    }

    /**
     * Retrieve attributes derived from the request.
     *
     * The request "attributes" may be used to allow injection of any
     * parameters derived from the request: e.g., the results of path
     * match operations; the results of decrypting cookies; the results of
     * deserializing non-form-encoded message bodies; etc. Attributes
     * will be application and request specific, and CAN be mutable.
     *
     * @return array Attributes derived from the request.
     */
    public function getAttributes()
    {
        return $this->attributes;
    }

    /**
     * Retrieve a single derived request attribute.
     *
     * Retrieves a single derived request attribute as described in
     * getAttributes(). If the attribute has not been previously set, returns
     * the default value as provided.
     *
     * This method obviates the need for a hasAttribute() method, as it allows
     * specifying a default value to return if the attribute is not found.
     *
     * @see getAttributes()
     *
     * @param string $name The attribute name.
     * @param mixed $default Default value to return if the attribute does not exist.
     * @return mixed
     */
    public function getAttribute($name, $default = null)
    {
        return isset($this->attributes[$name]) ? $this->attributes[$name] : $default;
    }

    /**
     * Return an instance with the specified derived request attribute.
     *
     * This method allows setting a single derived request attribute as
     * described in getAttributes().
     *
     * This method MUST be implemented in such a way as to retain the
     * immutability of the message, and MUST return an instance that has the
     * updated attribute.
     *
     * @see getAttributes()
     *
     * @param string $name The attribute name.
     * @param mixed $value The value of the attribute.
     * @return ServerRequest
     */
    public function withAttribute($name, $value)
    {
        $clonedObject = clone $this;
        $clonedObject->attributes[$name] = $value;
        return $clonedObject;
    }

    /**
     * Return an instance that removes the specified derived request attribute.
     *
     * This method allows removing a single derived request attribute as
     * described in getAttributes().
     *
     * This method MUST be implemented in such a way as to retain the
     * immutability of the message, and MUST return an instance that removes
     * the attribute.
     *
     * @see getAttributes()
     *
     * @param string $name The attribute name.
     * @return ServerRequest
     */
    public function withoutAttribute($name)
    {
        $clonedObject = clone $this;
        if (!isset($clonedObject->attributes[$name])) {
            return $clonedObject;
        }
        unset($clonedObject->attributes[$name]);
        return $clonedObject;
    }

    /**
     * Recursively validate the structure in an uploaded files array.
     *
     * @param array $uploadedFiles
     * @throws \InvalidArgumentException if any leaf is not an UploadedFileInterface instance.
     */
    protected function validateUploadedFiles(array $uploadedFiles)
    {
        foreach ($uploadedFiles as $file) {
            if (is_array($file)) {
                $this->validateUploadedFiles($file);
                continue;
            }
            if (!$file instanceof UploadedFileInterface) {
                throw new \InvalidArgumentException('Invalid file in uploaded files structure.', 1436717281);
            }
        }
    }
}
