<?php
namespace Shared\RestApi;

use Exception;
use Shared\Authentication\Caller;
use Shared\Authentication\Exceptions\UnauthenticatedAccessException;
use Shared\Authentication\Exceptions\UserAccountExpiredException;
use Shared\Authentication\Interfaces\AccountInterface;
use Shared\Authentication\Interfaces\AuthenticatorInterface;
use Shared\Backend\Mode;
use Shared\DataControl\Variable;
use Shared\Databases\Exceptions\QueryException;
use Shared\Entities\Exceptions\ValuesAlreadyInUseException;
use Shared\Entities\Interfaces\EntityProviderInterface;
use Shared\Exceptions\InvalidInputException;
use Shared\Http\RequestData as HttpRequestData;
use Shared\Http\ResponseCodes;
use Shared\Logging\Interfaces\ErrorLoggerInterface;
use Shared\System\Exceptions\AlmostOutOfMemoryException;
use Shared\Tokens\Exceptions\InvalidTokenException;

/**
 * Processes a RESTful request
 */
class RequestProcessor
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

    public static function get(): ?self
    {
        return self::$instance;
    }


    /**************************
     *   Instance variables   *
     *************************/

    /**
     * @var  AccountInterface
     */
    private $authenticatedAccount;

    /**
     * @var  AuthenticatorInterface
     */
    private $authenticator;

    /**
     * @var  array
     */
    private $debugMessages = [];

    /**
     * @var  ErrorLoggerInterface
     */
    private $errorLogger;

    /**
     * @var  string
     */
    private $objectToUrlTemplate;

    /**
     * @var  string[]
     */
    private $pathToClassMapping = [];

    /**
     * @var  array
     */
    private $publicPaths = [];

    /**
     * @var  string
     */
    private $requestBody;

    /**
     * @var  RequestData
     */
    private $requestData;

    /**
     * @var  Interfaces\LoggerInterface
     */
    private $requestLogger;

    /**
     * @var  array
     */
    private $requestParameters;

    /**
     * @var  Response
     */
    private $response;

    /**
     * @return  RequestProcessor
     */
    private $responseProcessor;

    /**
     * @var  string
     */
    private $urlToObjectTemplate;


    /************************
     *   Instance methods   *
     ***********************/

    /**
     * Request constructor
     *
     * @param  Interfaces\LoggerInterface|null  $logger
     */
    public function __construct(Interfaces\LoggerInterface $logger = null)
    {
        $this->requestData   = RequestData::get();
        $this->requestLogger = $logger;
        $this->response      = new Response($logger, $this->requestData->getETag());
        $this->responseProcessor = new ResponseProcessor();

        self::$instance = $this;
    }

    /**
     * @param  AuthenticatorInterface  $authenticator
     */
    public function setAuthenticator(AuthenticatorInterface $authenticator)
    {
        $this->authenticator = $authenticator;
    }

    /**
     * @param  string  $baseUri
     */
    public function setBaseUri(string $baseUri): void
    {
        $this->requestData->setBaseUri($baseUri);
    }

    /**
     * @param  int  $maxRows
     */
    public function setCollectionMaxRows(int $maxRows): void
    {
        $this->responseProcessor->setCollectionMaxRows($maxRows);
    }

    /**
     * @param  int  $offset
     */
    public function setCollectionOffset(int $offset): void
    {
        $this->responseProcessor->setCollectionOffset($offset);
    }

    /**
     * @param  ErrorLoggerInterface  $errorLogger
     */
    public function setErrorLogger(ErrorLoggerInterface $errorLogger): void
    {
        $this->errorLogger = $errorLogger;

        Caller::setLogger($errorLogger);
    }

    /**
     * @return  void
     */
    public function go(): void
    {
        try
        {
            $this->process();
        }
        catch (InvalidInputException | QueryException | ValuesAlreadyInUseException $e)
        {
            $this->sendError(ResponseCodes::BAD_REQUEST, $e->getCode(), $e->getMessage());
        }
        catch (AlmostOutOfMemoryException $e)
        {
            $this->registerExceptionWithErrorLogger($e);
            $this->sendError(ResponseCodes::REQUEST_ENTITY_TOO_LARGE, $e->getCode(), $e->getMessage());
        }
        catch (UnauthenticatedAccessException | InvalidTokenException $e)
        {
            $this->sendError(ResponseCodes::UNAUTHORIZED, $e->getCode(), $e->getMessage());
        }
        catch (Exception $e)
        {
            $this->sendError(ResponseCodes::INTERNAL_SERVER_ERROR, $e->getCode(), $e->getMessage());
        }
    }

    /**
     * Ends script execution
     *
     * @param  mixed  $data
     *
     * @return  void
     */
    public function handleFatalError($data): void
    {
        $messageCode = Variable::keyval($data, 'messageCode');
        $message     = Variable::keyval($data, 'message');

        $this->sendError(ResponseCodes::INTERNAL_SERVER_ERROR, $messageCode, $message);

        die();
    }

    /**
     * @param  string  $token
     *
     * @return  bool
     */
    public function getIsTokenValid(string $token): bool
    {
        return $this->authenticator->isAccessTokenValid($token);
    }

    /**
     * @return  string
     */
    public function getObjectToUrlTemplate(): string
    {
        if ($this->objectToUrlTemplate === null) {
            $this->objectToUrlTemplate = sprintf(
                '%s%sv%u/%%s/%%u',
                HttpRequestData::get()->getRequestedHost(),
                $this->requestData->getBaseUri(),
                $this->requestData->getVersion()
            );
        }

        return $this->objectToUrlTemplate;
    }

    /**
     * @param  string  $directory
     */
    public function setPublicFileDirectory(string $directory): void
    {
        $this->responseProcessor->setPublicFileDirectory($directory);
    }

    /**
     * @param  array  $classes
     *
     * @return  void
     */
    public function registerPathToClassMapping(array $classes): void
    {
        $mapping = [];

        foreach ($classes as $path => $class) {
            $class = str_replace(SLASH, BACKSLASH, str_replace('*', '.+?', $class));

            if (substr($class, -1) !== BACKSLASH) {
                $class = BACKSLASH . $class;
            }

            $path = str_replace(SLASH, BACKSLASH, str_replace('*', '.+?', $path));
            $mapping[$path] = $class;
        }

        $this->pathToClassMapping = array_merge($this->pathToClassMapping, $mapping);
    }

    /**
     * @param  array  $paths
     *
     * @return  void
     */
    public function registerPublicPaths(array $paths): void
    {
        foreach ($paths as & $path) {
            $path = str_replace('*', '.*?', $path);
        }

        $this->publicPaths = array_merge($this->publicPaths, $paths);
    }

    /**
     * Sends an error response
     *
     * @param  int          $responseCode
     * @param  int|null     $messageCode
     * @param  string|null  $message
     *
     * @return  void
     */
    public function sendError(int $responseCode, int $messageCode = null, string $message = null)
    {
        $this->sendBody(
            $messageCode ? ['errorCode' => $messageCode, 'errorMessage' => $message] : $message,
            $responseCode
        );
    }

    /**
     * Replaced the strings "true", "false" and "null" by the boolean values true or
     * false, or a null respectively
     *
     * @param  string  $value
     *
     * @return  void
     */
    public function stringToBoolean(string & $value): void
    {
        if ($value === 'false') {
            $value = false;
        }

        if ($value === 'true') {
            $value = true;
        }

        if ($value === 'null') {
            $value = null;
        }
    }

    /**
     * @param  array  ...$messages
     */
    public function addToDebug(... $messages): void
    {
        $caller = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 1);
        $source = sprintf('%s:%u', $caller[0]['file'], $caller[0]['line']);

        if (!array_key_exists($source, $this->debugMessages)) {
            $this->debugMessages[$source] = [];
        }

        $this->debugMessages[$source][] = $messages;
    }

    /**
     * If the given value is a REST resource URL, it is turned into an instance
     *
     * @param  string  &$value
     *
     * @return  void
     */
    public function urlToObject(& $value): void
    {
        if (!is_string($value)) {
            return;
        }

        if (!preg_match($this->getUrlToObjectTemplate(), $value, $match)) {
            return;
        }

        $provider = $this->resourceToProvider($match[1]);
        $id       = $match[2];
        $value    = $provider->getInstance($id);
    }

    /**
     * @return  string
     */
    public function getUrlToObjectTemplate(): string
    {
        if ($this->urlToObjectTemplate === null) {
            $this->urlToObjectTemplate = sprintf(
                '#^%s%sv%u/(.+?)/(\d+)$#',
                HttpRequestData::get()->getRequestedHost(),
                $this->requestData->getBaseUri(),
                $this->requestData->getVersion()
            );
        }

        return $this->urlToObjectTemplate;
    }

    /**
     * Checks whether the request is properly authenticated
     *
     * @param  string  $resourcePath
     *
     * @return  void
     *
     * @throws  UnauthenticatedAccessException
     */
    private function checkAuthentication(string $resourcePath): void
    {
        if (!$this->authenticator) {
            return;
        }

        $this->authenticatedAccount = false;

        foreach ($this->publicPaths as $publicPath) {
            if (preg_match(sprintf('#^%s$#i', $publicPath), $resourcePath)) {
                return;
            }
        }

        $accessToken = $this->requestData->getAccessToken();

        if (!$accessToken) {
            throw new UnauthenticatedAccessException();
        }

        // Check the validity of the token
        try
        {
            $account  = $this->authenticator->getAccountFromToken($accessToken);
            $frontend = $this->authenticator->getFrontendFromToken($accessToken);
        }
        catch (UserAccountExpiredException $exception)
        {
            throw new UnauthenticatedAccessException();
        }

        if (!$frontend || !$account) {
            throw new UnauthenticatedAccessException();
        }

        Caller::setFrontend($frontend);
        Caller::setAccount($account);

        $this->authenticatedAccount = true;

        $this->authenticator->saveCaches();
    }

    /**
     * @return  void
     */
    private function initializeErrorHandler(): void
    {
        if (!$this->errorLogger) {
            return;
        }

        $this->errorLogger->setErrorHandler([$this, 'handleFatalError']);
        $this->errorLogger->setRequestInformation($this->requestData->getInformation());
        $this->errorLogger->setRequestParameters($this->requestData->getAllParameters());
    }

    /**
     * @param  string  $primaryResource
     *
     * @return  Stack
     */
    private function initializeStack(string $primaryResource): Stack
    {
        $stack = new Stack($this, $primaryResource);

        $stack->setRequestFilters($this->requestData->getFilters());
        $stack->setRequestLimit($this->requestData->getLimit(100));
        $stack->setRequestOffset($this->requestData->getOffset(0));

        return $stack;
    }

    /**
     * Logs the request
     *
     * @return  void
     */
    private function logRequest(): void
    {
        if ($this->requestLogger) {
            $this->requestLogger->logRequest($this->requestData, $this->getRequestBody());
        }
    }

    /**
     * Process this request from start to finish
     *
     * @return  void  It ends script execution!
     *
     * @throws  InvalidInputException
     */
    private function process(): void
    {
        if (array_key_exists('TESTING_MODE', $_COOKIE)) {
            Mode::enterTestingMode();
        }

        $this->initializeErrorHandler();

        // Remove one header
        header_remove('X-Powered-By');

        // Add CORS and security headers
        $this->response->setHeaderValue('Access-Control-Allow-Origin', '*');
        $this->response->setHeaderValue('Strict-Transport-Security',   'max-age=31536000; includeSubDomains');
        $this->response->setHeaderValue('X-Frame-Options',             'SAMEORIGIN');
        $this->response->setHeaderValue('X-XSS-Protection',            '1; mode=block');
        $this->response->setHeaderValue('X-Content-Type-Options',      'nosniff');
        $this->response->setHeaderValue('Referrer-Policy',             'no-referrer');

        $requestMethod = $this->requestData->getMethod();

        // Process OPTIONS requests and return ASAP
        if ($requestMethod === 'OPTIONS') {
            $this->processOptionsRequest();

            return;
        }

        // Log the request
        $this->logRequest();

        // Determine and parse the resource path
        $resourcePath = $this->requestData->getResourcePath();

        if (!$resourcePath) {
            throw new InvalidInputException($resourcePath, 'resource path');
        }

        // Check authentication
        $this->checkAuthentication($resourcePath);

        [
            $primaryResource,
            $secondaryProcessors,
            $lastProcessor
        ] = $this->splitPathIntoProcessors($resourcePath);

        // Apply the secondary processors, one by one
        $stack = $this->initializeStack($primaryResource);

        foreach ($secondaryProcessors as $processor) {
            $stack->applySecondaryProcessor($processor);
        }

        // Apply the last processor
        $stack->applyFinalProcessor(
            $lastProcessor,
            $requestMethod,
            $this->getRequestParameters(),
            $this->getRequestBody()
        );

        // Apply the return specificiation
        $this->sendBody($stack->getReturnValue());
    }

    /**
     * Processes the OPTIONS request. It sets appropriate headers, then ends script
     * execution
     */
    private function processOptionsRequest(): void
    {
        header('Access-Control-Allow-Headers: Authorization,Content-Type,If-Match,If-None-Match');
        header('Access-Control-Allow-Methods: GET,POST,PUT,DELETE,OPTIONS');

        $this->response->setResponseType('text/html');
        $this->response->send(ResponseCodes::NO_CONTENT);
    }

    /**
     * @param  Exception  $e
     */
    private function registerExceptionWithErrorLogger(Exception $e): void
    {
        if (!$this->errorLogger) {
            return;
        }

        $trace = $e->getTrace();

        // Add the source line to the backtrace
        array_unshift($trace, ['file' => $e->getFile(), 'line' => $e->getLine()]);

        $this->errorLogger->setBacktrace($trace);
        $this->errorLogger->error($e->getCode(), $e->getMessage());
    }

    /**
     * @return  mixed
     */
    private function getRequestBody()
    {
        if ($this->requestBody === null) {
            $this->requestBody = $this->requestData->getBody();

            // Replace resource URLs by instances
            if (is_array($this->requestBody)) {
                array_walk_recursive($this->requestBody, [$this, 'urlToObject']);
            }
        }

        return $this->requestBody;
    }

    /**
     * @return  mixed
     */
    private function getRequestParameters()
    {
        if ($this->requestParameters === null) {
            $this->requestParameters = $this->requestData->getAllParameters();

            // Replace resource URLs by instances
            if (is_array($this->requestParameters)) {
                array_walk_recursive($this->requestParameters, [$this, 'urlToObject']);
                array_walk_recursive($this->requestParameters, [$this, 'stringToBoolean']);
            }
        }

        return $this->requestParameters;
    }

    /**
     * @param  mixed  $resourceName
     *
     * @return  EntityProviderInterface
     */
    private function resourceToProvider($resourceName): EntityProviderInterface
    {
        $providerName = Variable::keyval(
            $this->pathToClassMapping,
            str_replace(SLASH, BACKSLASH, $resourceName)
        );

        return $providerName::get();
    }

    /**
     * Finalizes and sends a response
     *
     * @param  mixed  $response
     * @param  null   $responseCode
     */
    private function sendBody($response, $responseCode = null): void
    {
        if ($this->debugMessages) {
            $this->debugMessages['response'] = $response;
            $response = $this->debugMessages;
        }

        $response = $this->responseProcessor->go($response, $this->pathToClassMapping);

        $this->response->send($responseCode, $response);
    }

    /**
     * @param  string  $resourcePath
     *
     * @return  array
     *
     * @throws  InvalidInputException
     */
    private function splitPathIntoProcessors(string $resourcePath): array
    {
        $parts = explode('/', $resourcePath);
        $secondaryProcessors = [];

        while (true) {
            $primaryResource = implode(BACKSLASH, $parts);

            if (array_key_exists($primaryResource, $this->pathToClassMapping)) {
                $className = $this->pathToClassMapping[$primaryResource];

                if (class_exists($className)) {
                    $lastProcessor = array_pop($secondaryProcessors);

                    return [$className, $secondaryProcessors, $lastProcessor];
                }
            }

            if (class_exists($primaryResource)) {
                $lastProcessor = array_pop($secondaryProcessors);

                return [$primaryResource, $secondaryProcessors, $lastProcessor];
            }

            if (empty($parts)) {
                break;
            }

            array_unshift($secondaryProcessors, array_pop($parts));
        }

        throw new InvalidInputException($resourcePath, 'resource path');
    }
}
