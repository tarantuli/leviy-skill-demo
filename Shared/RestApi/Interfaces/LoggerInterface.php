<?php
namespace Shared\RestApi\Interfaces;

use Shared\RestApi\RequestData;

interface LoggerInterface
{
    /************************
     *   Instance methods   *
     ***********************/

    /**
     * Logs the request
     *
     * @param  RequestData  $request
     * @param  mixed        $body
     *
     * @return  void
     */
    public function logRequest(RequestData $request, $body): void;

    /**
     * Adds the response code and body to the log
     *
     * @param  int     $code
     * @param  string  $body
     *
     * @return  void
     */
    public function logResponse(int $code, string $body): void;
}
