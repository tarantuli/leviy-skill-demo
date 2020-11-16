<?php
namespace Shared\RestApi;

use Shared\DataControl\Variable;
use Shared\Exceptions\InvalidInputException;
use Shared\Functions;
use Shared\Http\RequestData as HttpRequestData;

/**
 * Analyses the HTTP request in a RESTful manner
 */
class RequestData extends HttpRequestData
{
    /*****************
     *   Constants   *
     ****************/

    private const MAX_COLLECTION_LIMIT  = 5000;
    private const MAX_COLLECTION_OFFSET = PHP_INT_MAX;


    /**************************
     *   Instance variables   *
     *************************/

    /**
     * @var  array
     */
    private $allParameters;

    /**
     * @var  string
     */
    private $baseUri = '/';

    /**
     * @var  array
     */
    private $filters;

    /**
     * @var  array
     */
    private $parameters;

    /**
     * @var  string[]
     */
    private $queryParameters;

    /**
     * @var  string[]
     */
    private $uriParts;


    /************************
     *   Instance methods   *
     ***********************/

    /**
     * RequestData constructor
     */
    public function __construct()
    {
        $this->parseData();
    }

    /**
     * Returns the access token from the "Authorization" header
     *
     * @return  string
     */
    public function getAccessToken(): ?string
    {
        $header = $this->getHeaderValue('Authorization');

        [$jwt] = sscanf($header, 'Bearer %s');

        return $jwt;
    }

    /**
     * Returns the uri parts that are send with a request
     *
     * @return  array[]  An associated array with all the request params
     */
    public function getAllParameters(): array
    {
        if ($this->allParameters === null) {
            // Do NOT replace spaces by %2B before parse_str(); we've fixed it in determineQueryParameters()
            parse_str($this->getQueryString(), $this->allParameters);
        }

        return $this->allParameters;
    }

    /**
     * @param  string  $baseUri
     *
     * @return  void
     */
    public function setBaseUri(string $baseUri): void
    {
        if (substr($baseUri, -1) !== SLASH) {
            $baseUri .= SLASH;
        }

        $this->baseUri = $baseUri;

        $this->parseData();
    }

    /**
     * @return  string
     */
    public function getBaseUri(): string
    {
        return $this->baseUri;
    }

    /**
     * Returns the resource fields that should be expanded in the response
     *
     * @return  array
     */
    public function getExpands(): array
    {
        return $this->parameters['expand'];
    }

    /**
     * Returns the resource fields to return, null to return all
     *
     * @return  array
     */
    public function getFields(): array
    {
        return $this->parameters['fields'];
    }

    /**
     * Returns an array of filters to apply to the requested resource. Returns an
     * array of parameters, each parameter being an associated array with "name",
     * "operator" and "value" values
     *
     * @return  array
     */
    public function getFilters(): array
    {
        return $this->filters;
    }

    /**
     * Returns an array with all parsed elements
     *
     * @return  array
     */
    public function getInformation(): array
    {
        return [
            'version'      => $this->getVersion(),
            'sourceAddress'
                           => $this->getSourceAddress(),
            'sourcePort'   => $this->getSourcePort(),
            'headers'      => $this->getHeaders(),
            'accessToken'  => $this->getAccessToken(),
            'method'       => $this->getMethod(),
            'resourcePath' => $this->getResourcePath(),
            'fields'       => $this->getFields(),
            'expand'       => $this->getExpands(),
            'filters'      => $this->getFilters(),
            'limit'        => $this->getLimit(),
            'offset'       => $this->getOffset(),
            'body'         => $this->getBody(),
            'responseType' => $this->getResponseType(),
            'allParameters'
                           => $this->getAllParameters(),
            'baseUri'      => $this->getBaseUri(),
        ];
    }

    /**
     * Returns the maximum number of items to return in the requested collection
     *
     * @param  int|null  $default  The value to return if it is not set
     *
     * @return  int
     */
    public function getLimit(?int $default = null): ?int
    {
        $limit = $this->parameters['limit'] === null ? $default : (int) $this->parameters['limit'];

        return $limit === null ? null : Functions::clamp($limit, 0, self::MAX_COLLECTION_LIMIT);
    }

    /**
     * Returns the requested collection offset to use
     *
     * @param  int|null  $default  The value to return if it is not set
     *
     * @return  int
     */
    public function getOffset(?int $default = null): ?int
    {
        $offset = $this->parameters['offset'] === null ? $default : (int) $this->parameters['offset'];

        return $offset === null ? null : Functions::clamp($offset, 0, self::MAX_COLLECTION_OFFSET);
    }

    public function hasParameter(string $parameter): bool
    {
        return Variable::hasKey($this->getAllParameters(), $parameter);
    }

    /**
     * @param  string  $parameter
     *
     * @return  string
     */
    public function getParameterValue(string $parameter): ?string
    {
        return Variable::keyval($this->getAllParameters(), $parameter);
    }

    /**
     * Returns the query string of the request URI as-is, useful for redirection
     * requests
     *
     * @return  string
     */
    public function getQueryString(): string
    {
        return http_build_query($this->queryParameters);
    }

    /**
     * Returns the resource that was requested, as parsed from the requested address
     *
     * @return  string|null
     */
    public function getResourcePath(): ?string
    {
        return $this->uriParts['resource'];
    }

    /**
     * Returns the content type of the response as requested
     *
     * @return  string
     *
     * @throws  InvalidInputException
     */
    public function getResponseType(): string
    {
        $type = $this->uriParts['response_type'] ?: 'json';

        if (false !== $pos = strpos($type, ';')) {
            $type = substr($type, 0, $pos);
        }

        if ($type == 'application/json') {
            return 'application/json';
        }

        if ($type == 'json') {
            return 'application/json';
        }

        if ($type == 'pdf') {
            return 'application/pdf';
        }

        if ($type == 'xml') {
            return 'text/xml';
        }

        throw new InvalidInputException($type, 'response type');
    }

    /**
     * Returns whether this is a valid RESTful request, based on the version number
     *
     * @return  bool
     */
    public function isValidRequest(): bool
    {
        return $this->getVersion() >= 1;
    }

    /**
     * Returns the API version to use, as parsed from the requested address
     *
     * @return  int
     */
    public function getVersion(): int
    {
        return (int) $this->uriParts['version'];
    }

    /**
     * @return  string
     */
    private function compileRequestUriRegex(): string
    {
        $baseUri         = preg_quote($this->baseUri, '#');
        $versionRegex    = 'v(\d+)';
        $resourceRegex   = '/(.*?)';
        $typeRegex       = '(?:\.(\w+))?';
        $parametersRegex = '(?:\?(.*))?';

        return '#^' . $baseUri . $versionRegex . $resourceRegex . $typeRegex . $parametersRegex . '$#';
    }

    /**
     * @return  void
     */
    private function determineFilters(): void
    {
        $filters = $this->parameters['filters'];

        // Combine equal filters
        $equalFilters = [];
        $otherFilters = [];

        foreach ($filters as $filter) {
            if ($filter[0] === 'XDEBUG_PROFILE') {
                continue;
            }

            if ($filter[1] === '=') {
                if (is_scalar($filter[2]) && preg_match('#\[(.+)]#', $filter[2], $content)) {
                    foreach (explode(',', $content[1]) as $value) {
                        $equalFilters[$filter[0]][] = $value;
                    }
                }
                else {
                    $equalFilters[$filter[0]][] = $filter[2];
                }
            }
            else {
                $otherFilters[] = $filter;
            }
        }

        foreach ($equalFilters as $field => $values) {
            $otherFilters[] = [$field, '=', $values];
        }

        $this->filters = $otherFilters;
    }

    /**
     * Splits the query into its constituent parameters
     */
    private function determineParameters(): void
    {
        $this->parameters = [
            'fields'  => [],
            'expand'  => [],
            'offset'  => null,
            'limit'   => null,
            'filters' => [],
        ];

        foreach ($this->queryParameters as $name => $value) {
            $name = urldecode($name);

            // Process the value
            if ($name === 'fields') {
                $value = RestFunctions::parseTerseObjectString($value);
                $this->parameters['fields'] = array_merge($this->parameters['fields'], $value);

                continue;
            }

            if ($name === 'expand') {
                $value = RestFunctions::parseTerseObjectString($value);
                $this->parameters['expand'] = array_merge($this->parameters['expand'], $value);

                continue;
            }

            if ($name === 'offset') {
                $this->parameters['offset'] = $value;

                continue;
            }

            if ($name === 'limit') {
                $this->parameters['limit'] = $value;

                continue;
            }

            // It's a filter
            [$operator, $name] = RestFunctions::normalizeFilterOperator($name);

            $this->parameters['filters'][] = [$name, $operator, $value];
        }
    }

    /**
     * @return  void
     */
    private function determineQueryParameters(): void
    {
        // parse_str() automatically applies urldecode(), and we want to preserve pluses (they don't stand for spaces here)
        // so explicitly replace pluses by their url encoding "%2B"
        parse_str(str_replace('+', '%2B', $this->uriParts['parameters']), $this->queryParameters);

        // Add $_GET parameters
        foreach ($_GET as $name => $value) {
            if ($name !== 'requestPath' && !isset($this->queryParameters[$name])) {
                $this->queryParameters[$name] = $value;
            }
        }
    }

    /**
     * Splits the URI into its constituent parts
     */
    private function determineUriParts(): void
    {
        $uri   = $this->getRequestUri();
        $regex = $this->compileRequestUriRegex();

        // Match the request URI
        preg_match($regex, $uri, $m);

        $this->uriParts = [
            'version'       => array_key_exists(1, $m) ? (int) $m[1] : null,
            'resource'      => Variable::keyval($m, 2),
            'response_type' => Variable::keyval($m, 3),
            'parameters'    => Variable::keyval($m, 4),
        ];

        $this->determineQueryParameters();
    }

    private function parseData(): void
    {
        $this->determineUriParts();
        $this->determineParameters();
        $this->determineFilters();
    }
}
