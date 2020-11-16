<?php
namespace Shared\Http;

use Shared\Backend\Mode;
use Shared\DataControl\IpAddress;
use Shared\Providers\AbstractSingletonProvider;

class RequestData extends AbstractSingletonProvider
{
    /**********************
     *   Static methods   *
     *********************/

    /**
     * Parses raw HTTP request data
     *
     * Adapted from
     * http://www.chlab.ch/blog/archives/php/manually-parse-raw-http-data-php,
     * fetched on 2014-01-06
     *
     * Any files found in the request will be added by their field name to the
     * $reply['files'] array
     *
     * @param  string  $input
     *
     * @return  array  Associative array of request data
     */
    public static function parseRawHttpRequest(string $input)
    {
        $aData = [];

        // Grab multipart boundary from content type header
        preg_match(
            '/boundary=(.*)$/',
            array_key_exists('CONTENT_TYPE', $_SERVER) ? $_SERVER['CONTENT_TYPE'] : '',
            $matches
        );

        if (!count($matches)) {
            // Content type is probably regular form-encoded
            // we expect regular puts to containt a query string containing data
            parse_str(urldecode($input), $aData);

            return $aData;
        }

        $boundary = $matches[1];

        // Split content by boundary and get rid of last -- element
        $aBlocks = preg_split("/-+$boundary/", $input);

        array_pop($aBlocks);

        // loop data blocks
        foreach ($aBlocks as $block) {
            if (empty($block)) {
                continue;
            }

            // you'll have to var_dump $block to understand this and maybe replace \n or \r with a visibile char
            // parse uploaded files
            if (strpos($block, 'application/octet-stream') !== false) {
                // match "name", then everything after "stream" (optional) except for prepending newlines
                preg_match("/name=\"([^\"]*)\".*stream[\n|\r]+([^\n\r].*)?$/s", $block, $matches);

                $aData['files'][$matches[1]] = $matches[2];
            }

            // parse all other fields
            else {
                if (strpos($block, 'filename') !== false) {
                    // match "name" and optional value in between newline sequences
                    preg_match(
                        '/name=\"([^\"]*)\"; filename=\"([^\"]*)\"[\n|\r]+([^\n\r].*)?\r$/s',
                        $block,
                        $matches
                    );
                    preg_match('/Content-Type: (.*)?/', $matches[3], $mime);

                    // match the mime type supplied from the browser
                    $image = preg_replace('/Content-Type: (.*)[^\n\r]/', '', $matches[3]);

                    // get current system path and create tempory file name & path
                    $path = sys_get_temp_dir() . '/php' . substr(sha1(mt_rand()), 0, 6);

                    // write temporary file to emulate $_FILES super global
                    $err = file_put_contents($path, $image);

                    // Did the user use the infamous &lt;input name="array[]" for multiple file uploads?
                    if (preg_match('/^(.*)\[]$/i', $matches[1], $tmp)) {
                        $aData[$tmp[1]]['name'][] = $matches[2];
                    }
                    else {
                        $aData[$matches[1]]['name'][] = $matches[2];
                    }

                    // Create the remainder of the $_FILES super global
                    $aData[$tmp[1]]['type'][]     = $mime[1];
                    $aData[$tmp[1]]['tmp_name'][] = $path;
                    $aData[$tmp[1]]['error'][]    = ($err === false) ? $err : 0;
                    $aData[$tmp[1]]['size'][]     = filesize($path);
                }
                else {
                    // match "name" and optional value in between newline sequences
                    preg_match('/name=\"([^\"]*)\"[\n|\r]+([^\n\r].*)?\r$/s', $block, $matches);

                    if (preg_match('/^(.*)\[]$/i', $matches[1], $tmp)) {
                        $aData[$tmp[1]][] = $matches[2];
                    }
                    else {
                        $aData[$matches[1]] = $matches[2];
                    }
                }
            }
        }

        return $aData;
    }


    /**************************
     *   Instance variables   *
     *************************/

    /**
     * @var  int
     */
    private $firstRequestTime;

    /**
     * @var  mixed
     */
    private $parsedBody;

    /**
     * @var  string
     */
    private $requestedEndpoint;

    /**
     * @var  string
     */
    private $requestedHost;

    /**
     * @var  string
     */
    private $testingBody;

    /**
     * @var  string[]
     */
    private $testingHeaders = [];

    /**
     * @var  string
     */
    private $testingMethod;


    /************************
     *   Instance methods   *
     ***********************/

    /**
     * Returns the body of the request. '' if it contained no body, false if the body
     * was invalid
     *
     * @return  mixed
     */
    public function getBody()
    {
        if ($this->parsedBody !== null) {
            return $this->parsedBody;
        }

        // With put method the body can only be requested once
        // http://stackoverflow.com/questions/9684315/php-get-put-request-body
        if (Mode::inTestingMode() && $this->testingBody) {
            $body = $this->testingBody;
        }
        else {
            $body = file_get_contents('php://input');
        }

        $this->parsedBody = $body ? $this->decodeBody($body) : '';

        return $this->parsedBody;
    }

    /**
     * Returns the E-Tag that was sent with this request if any
     *
     * @return  string
     */
    public function getETag(): ?string
    {
        if ($this->getMethod() !== 'GET') {
            return null;
        }

        return $this->getETagFromIfNoneMatch($this->getHeaderValue('If-None-Match'));
    }

    /**
     * Returns the ETag value from the given If-None-Match header value
     *
     * @param  string|null  $ifNoneMatch
     *
     * @return  string
     */
    public function getETagFromIfNoneMatch(?string $ifNoneMatch): ?string
    {
        // Strip gzip marker
        if (preg_match('/"([a-f0-9]+)-gzip"/', $ifNoneMatch, $match)) {
            $ifNoneMatch = sprintf('"%s"', $match[1]);
        }

        return $ifNoneMatch;
    }

    /**
     * Returns whether the request contains a header with that name
     *
     * @param  string  $name
     *
     * @return  bool
     */
    public function hasHeader(string $name): bool
    {
        return array_key_exists($name, $this->getHeaders());
    }

    /**
     * Returns the names of the headers of the request
     *
     * @return  string[]
     */
    public function getHeaderNames(): array
    {
        return array_keys($this->getHeaders());
    }

    /**
     * Returns an associated array with request header names as keys
     *
     * @return  string[]
     */
    public function getHeaders(): array
    {
        $headers = function_exists('getallheaders') ? getallheaders() : [];

        if (Mode::inTestingMode()) {
            $headers = array_merge($headers, $this->testingHeaders);
        }

        return $headers;
    }

    /**
     * Returns the value of the given header
     *
     * @param  string  $name
     *
     * @return  string
     */
    public function getHeaderValue(string $name): ?string
    {
        foreach ($this->getHeaders() as $headerName => $value) {
            if (strcasecmp($headerName, $name) === 0) {
                return $value;
            }
        }

        return null;
    }

    /**
     * Returns the request method, e.g. GET, POST
     *
     * @return  string
     */
    public function getMethod(): ?string
    {
        if (Mode::inTestingMode() && $this->testingMethod) {
            return $this->testingMethod;
        }

        $method = array_key_exists('REQUEST_METHOD', $_SERVER) ? $_SERVER['REQUEST_METHOD'] : null;

        if (substr($method, 0, 4) === 'null') {
            $method = substr($method, 4);
        }

        return $method;
    }

    /**
     * @return  string
     */
    public function getRequestedHost(): string
    {
        if ($this->requestedHost === null) {
            $this->determineRequestUrl();
        }

        return $this->requestedHost;
    }

    /**
     * @return  string
     */
    public function getRequestedUrl(): string
    {
        if ($this->requestedHost === null) {
            $this->determineRequestUrl();
        }

        return $this->requestedHost . $this->requestedEndpoint;
    }

    /**
     * @return  string
     */
    public function getRequestProtocol(): ?string
    {
        return array_key_exists('SERVER_PROTOCOL', $_SERVER) ? $_SERVER['SERVER_PROTOCOL'] : null;
    }

    /**
     * @return  int
     */
    public function getRequestTime(): int
    {
        if (array_key_exists('REQUEST_TIME', $_SERVER)) {
            return (int) $_SERVER['REQUEST_TIME'];
        }

        if ($this->firstRequestTime === null) {
            $this->firstRequestTime = time();
        }

        return $this->firstRequestTime;
    }

    /**
     * @return  string
     */
    public function getRequestUri(): string
    {
        if (array_key_exists('REQUEST_URI', $_SERVER)) {
            return $_SERVER['REQUEST_URI'];
        }

        return '';
    }

    /**
     * Returns whether the user requested us to return ASAP instead of waiting for
     * the result of the method
     *
     * @return  bool
     */
    public function returnAsap(): bool
    {
        return $this->getHeaderValue('Return-Asap') !== null;
    }

    /**
     * Returns the address of the request source
     *
     * @return  string
     */
    public function getSourceAddress(): ?string
    {
        if (array_key_exists('HTTP_X_REAL_IP', $_SERVER)) {
            return IpAddress::stringToBinary($_SERVER['HTTP_X_REAL_IP']);
        }

        if (array_key_exists('REMOTE_ADDR', $_SERVER)) {
            return IpAddress::stringToBinary($_SERVER['REMOTE_ADDR']);
        }

        return null;
    }

    /**
     * @return  string|null
     */
    public function getSourceHost(): ?string
    {
        return array_key_exists('HTTP_ORIGIN', $_SERVER) ? $_SERVER['HTTP_ORIGIN'] : null;
    }

    /**
     * Returns the address port of the request source
     *
     * @return  int
     */
    public function getSourcePort(): ?int
    {
        return array_key_exists('REMOTE_PORT', $_SERVER) ? (int) $_SERVER['REMOTE_PORT'] : null;
    }

    /**
     * Sets a request body for testing purposes
     *
     * @param  string  $body
     */
    public function setTestingBody(string $body): void
    {
        $this->testingBody = $body;
        $this->parsedBody  = null;
    }

    /**
     * Sets a request header for testing purposes
     *
     * @param  string  $name
     * @param  string  $value
     */
    public function setTestingHeader(string $name, string $value): void
    {
        $this->testingHeaders[$name] = $value;
    }

    /**
     * Sets a request method for testing purposes
     *
     * @param  string  $method
     */
    public function setTestingMethod(string $method): void
    {
        $this->testingMethod = $method;
    }

    /**
     * @return  string|null
     */
    public function getUserAgent(): ?string
    {
        return array_key_exists('HTTP_USER_AGENT', $_SERVER) ? $_SERVER['HTTP_USER_AGENT'] : null;
    }

    /**
     * @param  string  $body
     *
     * @return  mixed
     */
    private function decodeBody(string $body)
    {
        $contentType = $this->getHeaderValue('Content-Type');

        return Body::parseFromString($body, $contentType);
    }

    private function determineRequestUrl(): void
    {
        if (array_key_exists('REQUEST_SCHEME', $_SERVER)) {
            $requestScheme = $_SERVER['REQUEST_SCHEME'];
            $serverName    = $_SERVER['HTTP_HOST'];
            $serverPort    = $_SERVER['SERVER_PORT'];
            $this->requestedEndpoint = $_SERVER['REQUEST_URI'];
        }
        else {
            $requestScheme = 'http';
            $serverName    = 'localhost';
            $serverPort    = 80;
            $this->requestedEndpoint = '';
        }

        // Build the template based on request parameters
        $normalHttpPort  = ($requestScheme === 'http' && $serverPort == 80);
        $normalHttpsPort = ($requestScheme === 'https' && $serverPort == 443);

        if ($normalHttpPort || $normalHttpsPort) {
            $portPart = '';
        }
        else {
            $portPart = ':' . $serverPort;
        }

        $this->requestedHost = sprintf('%s://%s%s', $requestScheme, $serverName, $portPart);
    }
}
