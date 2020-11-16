<?php
namespace Shared\Http;

class HttpHeaders
{
    /**********************
     *   Static methods   *
     *********************/

    /**
     * Checks whether headers have already been sent. If so, it throws an exception,
     * otherwise it does nothing
     *
     * @return  void
     *
     * @throws  Exceptions\CannotSendHeadersException
     */
    public static function checkIfSendable()
    {
        if (headers_sent($fileWhereStarted, $lineWhereStarted)) {
            throw new Exceptions\CannotSendHeadersException($fileWhereStarted, $lineWhereStarted);
        }
    }
}
