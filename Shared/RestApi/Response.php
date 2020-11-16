<?php
namespace Shared\RestApi;

use Shared\Backend\CacheFile;
use Shared\Exceptions\InvalidInputException;
use Shared\Http\HttpHeaders;
use Shared\Http\RequestData;
use Shared\Http\ResponseCodes;
use Shared\Json\BinaryJson;

/**
 * Builds the HTTp response in a RESTful manner
 */
class Response
{
    /************************
     *   Static variables   *
     ***********************/

    /**
     * @var  self
     */
    private static $instance;


    /**********************
     *   Static methods   *
     *********************/

    /**
     * @return  self
     */
    public static function get(): ?self
    {
        return self::$instance;
    }


    /**************************
     *   Instance variables   *
     *************************/

    /**
     * @var  bool
     */
    private $hasBeenSent = false;

    /**
     * @var  string[]
     */
    private $headers = [];

    /**
     * @var  Interfaces\LoggerInterface
     */
    private $logger;

    /**
     * @var  string
     */
    private $requestETag;

    /**
     * @var  int
     */
    private $responseCode = 200;

    /**
     * @var  string
     */
    private $responseType = 'application/json';


    /************************
     *   Instance methods   *
     ***********************/

    /**
     * Response constructor
     *
     * @param  Interfaces\LoggerInterface|null  $logger
     * @param  string|null                      $requestETag
     */
    public function __construct(?Interfaces\LoggerInterface $logger, ?string $requestETag)
    {
        $this->logger      = $logger;
        $this->requestETag = $requestETag;

        self::$instance = $this;
    }

    /**
     * Returns whether a response has been sent
     *
     * @return  bool
     */
    public function hasBeenSent()
    {
        return $this->hasBeenSent;
    }

    /**
     * Calculates our ETag value for the given body and headers
     *
     * @param  string|null  $body
     * @param  array        $headers
     *
     * @return  string
     */
    public function calculateEtag(?string $body, array $headers)
    {
        return sprintf('"%s"', md5(json_encode(['body' => $body, 'headers' => $headers])));
    }

    /**
     * @param  int  $timeInSec
     */
    public function setExpireHeader(int $timeInSec)
    {
        $this->setHeaderValue('Expires', gmdate('D, d M Y H:i:s T', $timeInSec));
    }

    /**
     * Returns whether the given header is to be sent
     *
     * @param  string  $name
     *
     * @return  bool
     */
    public function hasHeader(string $name)
    {
        return array_key_exists($name, $this->getHeaders());
    }

    /**
     * Returns the names of the headers to be sent
     *
     * @return  string[]
     */
    public function getHeaderNames()
    {
        return array_keys($this->getHeaders());
    }

    /**
     * Returns an associated array with header names to be sent as keys
     *
     * @return  string[]
     */
    public function getHeaders()
    {
        return $this->headers;
    }

    /**
     * Changes the value of the given header to be sent
     *
     * @param  string  $name
     * @param  string  $value
     *
     * @return  void
     *
     * @throws  InvalidInputException
     */
    public function setHeaderValue(string $name, string $value)
    {
        // Disallow non-scalar values
        if (!is_scalar($value)) {
            throw new InvalidInputException($value, 'header value');
        }

        $this->headers[$name] = $value;
    }

    /**
     * Sets the response code
     *
     * @param  int  $code
     *
     * @return  bool
     *
     * @throws  InvalidInputException
     */
    public function setResponseCode(int $code)
    {
        if (!array_key_exists($code, ResponseCodes::RESPONSE_TEXTS)) {
            throw new InvalidInputException($code, 'response code');
        }

        $this->responseCode = $code;

        return true;
    }

    /**
     * @param  string  $responseType
     */
    public function setResponseType(string $responseType)
    {
        $this->responseType = $responseType;
    }

    /**
     * Compiles the request, then sends it
     *
     * @param  int|null  $responseCode  The response code to use
     * @param  mixed     $body
     *
     * @throws  InvalidInputException
     */
    public function send(int $responseCode = null, $body = null)
    {
        // Encode and print the body according to the Content-Type
        switch ($this->responseType) {
            case 'application/json':
                $body = BinaryJson::encode($body);

                break;

            case 'text/html':
                // Do nothing
                break;

            default:
                throw new InvalidInputException($this->responseType, 'response type');
        }

        $eTag = $this->calculateEtag($body, $this->getHeaders());

        if ($eTag === $this->requestETag) {
            $responseCode = ResponseCodes::NOT_MODIFIED;
        }
        else {
            $this->setHeaderValue('ETag', $eTag);
        }

        if ($responseCode) {
            $this->setResponseCode($responseCode);
        }

        $doPrintBody = true;

        // Set headers if this was a server request
        if ($protocol = RequestData::get()->getRequestProtocol()) {
            HttpHeaders::checkIfSendable();

            // Set the protcol and response code header
            header(
                sprintf(
                    '%s %u %s',
                    $protocol,
                    $this->responseCode,
                    ResponseCodes::codeToText($this->responseCode)
                )
            );

            if ($this->responseCode !== ResponseCodes::NOT_MODIFIED) {
                // Add the Access-Control-Expose-Headers header to allow CORS access to our custom headers
                // http://stackoverflow.com/questions/17038436/reading-response-headers-when-using-http-of-angularjs?rq=1
                if ($this->getHeaderNames()) {
                    header(
                        sprintf('%s: %s', 'Access-Control-Expose-Headers', implode(',', $this->getHeaderNames()))
                    );
                }

                // Set additional headers
                foreach ($this->getHeaders() as $name => $value) {
                    header(sprintf('%s: %s', $name, $value));
                }

                // Set the Content-Type header
                header(sprintf('Content-Type: %s', $this->responseType));
            }
            else {
                $doPrintBody = false;
            }
        }

        if ($doPrintBody) {
            echo $body;
        }

        if ($this->logger) {
            // Log the Response
            $this->logger->logResponse($this->responseCode, $body);
        }

        CacheFile::get()->saveAll();

        $this->hasBeenSent = true;
    }
}
