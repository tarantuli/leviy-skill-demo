<?php
namespace Shared\Http;

class ResponseCodes
{
    /*****************
     *   Constants   *
     ****************/

    public const ACCEPTED = 202;
    public const BAD_GATEWAY          = 502;
    public const BAD_REQUEST          = 400;
    public const CONFLICT = 409;
    public const CREATED = 201;
    public const EXPECTATION_FAILED   = 417;
    public const FORBIDDEN = 403;
    public const FOUND = 302;
    public const GATEWAY_TIMEOUT      = 504;
    public const GONE = 410;
    public const HTTP_VERSION_NOT_SUPPORTED = 505;
    public const INTERNAL_SERVER_ERROR = 500;
    public const LENGTH_REQUIRED      = 411;
    public const METHOD_NOT_ALLOWED   = 405;
    public const MOVED_PERMANENTLY    = 301;
    public const MULTIPLE_CHOICES     = 300;
    public const NO_CONTENT           = 204;
    public const NON_AUTHORITATIVE_INFORMATION = 203;
    public const NOT_ACCEPTABLE       = 406;
    public const NOT_FOUND = 404;
    public const NOT_IMPLEMENTED      = 501;
    public const NOT_MODIFIED         = 304;
    public const OK = 200;
    public const PARTIAL_CONTENT      = 206;
    public const PAYMENT_REQUIRED     = 402;
    public const PRECONDITION_FAILED  = 412;
    public const PROXY_AUTHENTICATION_REQUIRED = 407;
    public const REQUEST_ENTITY_TOO_LARGE = 413;
    public const REQUEST_TIMEOUT      = 408;
    public const REQUEST_URI_TOO_LONG = 414;
    public const REQUESTED_RANGE_NOT_SATISFIABLE = 416;
    public const RESET_CONTENT        = 205;

    /**
     * @var  string[]
     */
    public const RESPONSE_TEXTS = [
        200 => 'OK',
        201 => 'Created',
        202 => 'Accepted',
        203 => 'Non-Authoritative Information',
        204 => 'No Content',
        205 => 'Reset Content',
        206 => 'Partial Content',
        300 => 'Multiple Choices',
        301 => 'Moved Permanently',
        302 => 'Found',
        303 => 'See Other',
        304 => 'Not Modified',
        305 => 'Use Proxy',
        307 => 'Temporary Redirect',
        400 => 'Bad Request',
        401 => 'Unauthorized',
        402 => 'Payment Required',
        403 => 'Forbidden',
        404 => 'Not Found',
        405 => 'Method Not Allowed',
        406 => 'Not Acceptable',
        407 => 'Proxy Authentication Required',
        408 => 'Request Timeout',
        409 => 'Conflict',
        410 => 'Gone',
        411 => 'Length Required',
        412 => 'Precondition Failed',
        413 => 'Request Entity Too Large',
        414 => 'Request-URI Too Long',
        415 => 'Unsupported Media Type',
        416 => 'Requested Range Not Satisfiable',
        417 => 'Expectation Failed',
        500 => 'Internal Server Error',
        501 => 'Not Implemented',
        502 => 'Bad Gateway',
        503 => 'Service Unavailable',
        504 => 'Gateway Timeout',
        505 => 'HTTP Version Not Supported',
    ];

    public const SEE_OTHER    = 303;
    public const SERVICE_UNAVAILABLE = 503;
    public const TEMPORARY_REDIRECT = 307;
    public const UNAUTHORIZED = 401;
    public const UNSUPPORTED_MEDIA_TYPE = 415;
    public const USE_PROXY    = 305;


    /**********************
     *   Static methods   *
     *********************/

    /**
     * @param  int  $code
     *
     * @return  string
     */
    public static function codeToText(int $code)
    {
        return array_key_exists($code, self::RESPONSE_TEXTS) ? self::RESPONSE_TEXTS[$code] : null;
    }
}
