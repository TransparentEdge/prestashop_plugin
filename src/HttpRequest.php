<?php
namespace Transparent\TransparentEdge;

/**
 * Class HttpRequest
 *
 */
class HttpRequest
{
    /**#@+
     * HTTP methods supported by REST.
     */
    public const HTTP_METHOD_GET = 'GET';
    public const HTTP_METHOD_DELETE = 'DELETE';
    public const HTTP_METHOD_PUT = 'PUT';
    public const HTTP_METHOD_POST = 'POST';
    /**#@-*/

    /**
     * Character set which must be used in request.
     */
    public const REQUEST_CHARSET = 'utf-8';

    public const DEFAULT_ACCEPT = '*/*';
}