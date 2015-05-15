<?php
/**
* This file is part of the League.url library
*
* @license http://opensource.org/licenses/MIT
* @link https://github.com/thephpleague/url/
* @version 4.0.0
* @package League.url
*
* For the full copyright and license information, please view the LICENSE
* file that was distributed with this source code.
*/
namespace League\Url\Util;

/**
* Trait to validate Url standard Port
*
* @package League.url
* @since 4.0.0
*/
trait StandardPort
{
    /**
     * Standard Port for known schemes
     *
     * @var array
     */
    protected static $standardPorts = [
        'ftp'   => [21],
        'ftps'  => [989, 990],
        'https' => [443],
        'http'  => [80],
        'ldap'  => [389],
        'ldaps' => [636],
        'ssh'   => [22],
        'ws'    => [80],
        'wss'   => [443],
    ];

    protected static $standardSchemes = [
        21  => ['ftp'],
        22  => ['ssh'],
        80  => ['http', 'ws'],
        389 => ['ldap'],
        443 => ['https', 'wss'],
        636 => ['ldaps'],
        989 => ['ftps'],
        990 => ['ftps'],
    ];

    /**
     * Return all the port attached to a given scheme
     *
     * @param  string $scheme
     * @return array
     */
    protected function getStandardPortsFromScheme($scheme)
    {
        $res = [];
        if (array_key_exists($scheme, static::$standardPorts)) {
            $res = static::$standardPorts[$scheme];

        }
        sort($res);

        return $res;
    }

    /**
     * Return all the scheme attached to a given port
     *
     * @param  int $port
     * @return array
     */
    protected function getStandardSchemesFromPort($port)
    {
        $res = [];
        if (array_key_exists($port, static::$standardSchemes)) {
            $res = static::$standardSchemes[$port];
        }
        sort($res);

        return $res;
    }
}
