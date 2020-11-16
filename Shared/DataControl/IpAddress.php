<?php
namespace Shared\DataControl;

use Shared\Exceptions\InvalidInputException;

/**
 * Contains functions to normalize and manipulate IP address values
 */
class IpAddress
{
    /**********************
     *   Static methods   *
     *********************/

    /**
     * Turns the binary representation of an IP address into its canonical text
     * representation
     *
     * @param  string  $ip
     *
     * @return  string
     *
     * @throws  InvalidInputException
     */
    public static function binaryToString(string $ip): string
    {
        $ip = self::normalize($ip);

        if (strlen($ip) == 16 or strlen($ip) == 4) {
            return inet_ntop(pack('A' . strlen($ip), $ip));
        }

        throw new InvalidInputException($ip, 'IP address');
    }

    /**
     * @param  string  $ip
     *
     * @return  string
     */
    public static function binaryToUrlString(string $ip): string
    {
        return str_replace(['.', ':'], ['_', '_'], self::binaryToString($ip));
    }

    /**
     * Returns a normalized binary representation of the given IP address
     *
     * @param  mixed  $ip
     *
     * @return  string
     */
    public static function normalize($ip): string
    {
        if (is_numeric($ip)) {
            $ip = long2ip($ip);
        }

        return self::stringToBinary($ip);
    }

    /**
     * Turns a text representation of an IP address into its canonical binary
     * representation
     *
     * @param  string  $ip
     *
     * @return  string
     */
    public static function stringToBinary(string $ip): string
    {
        if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
            return current(unpack('a4', inet_pton($ip)));
        }
        elseif (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6)) {
            return current(unpack('a16', inet_pton($ip)));
        }

        // It's probably already a binary string
        return $ip;
    }

    /**
     * Returns whether the given binary IP is a valid IPv4 or IPv6 address
     *
     * @param  string  $ip
     *
     * @return  bool
     */
    public static function validate(string $ip): bool
    {
        try
        {
            $ip = self::binaryToString($ip);
        }
        catch (InvalidInputException $e)
        {
            return false;
        }

        return filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)
            || filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6);
    }
}
