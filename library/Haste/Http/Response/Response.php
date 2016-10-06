<?php

/**
 * Haste utilities for Contao Open Source CMS
 *
 * Copyright (C) 2012-2013 Codefog & terminal42 gmbh
 *
 * @package    Haste
 * @link       http://github.com/codefog/contao-haste/
 * @license    http://opensource.org/licenses/lgpl-3.0.html LGPL
 */

namespace Haste\Http\Response;

use Contao\CoreBundle\Exception\ResponseException;
use Haste\Util\InsertTag;
use Symfony\Component\HttpFoundation\Response as SymfonyResponse;

class Response
{
    /**
     * Headers
     * @var array
     */
    protected $arrHeaders = array();

    /**
     * Content
     * @var string
     */
    protected $strContent = '';

    /**
     * HTTP Status code
     * @var integer
     */
    protected $intStatus;

    /**
     * Status codes translation table.
     * The list of codes is complete according to the
     * {@link http://www.iana.org/assignments/http-status-codes/ Hypertext Transfer Protocol (HTTP) Status Code Registry}
     * (last updated 2012-02-13).
     *
     * Unless otherwise noted, the status code is defined in RFC2616.
     * @var array
     */
    public static $arrStatuses = array
    (
        100 => 'Continue',
        101 => 'Switching Protocols',
        102 => 'Processing',            // RFC2518
        200 => 'OK',
        201 => 'Created',
        202 => 'Accepted',
        203 => 'Non-Authoritative Information',
        204 => 'No Content',
        205 => 'Reset Content',
        206 => 'Partial Content',
        207 => 'Multi-Status',          // RFC4918
        208 => 'Already Reported',      // RFC5842
        226 => 'IM Used',               // RFC3229
        300 => 'Multiple Choices',
        301 => 'Moved Permanently',
        302 => 'Found',
        303 => 'See Other',
        304 => 'Not Modified',
        305 => 'Use Proxy',
        306 => 'Reserved',
        307 => 'Temporary Redirect',
        308 => 'Permanent Redirect',    // RFC-reschke-http-status-308-07
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
        418 => 'I\'m a teapot',                                               // RFC2324
        422 => 'Unprocessable Entity',                                        // RFC4918
        423 => 'Locked',                                                      // RFC4918
        424 => 'Failed Dependency',                                           // RFC4918
        425 => 'Reserved for WebDAV advanced collections expired proposal',   // RFC2817
        426 => 'Upgrade Required',                                            // RFC2817
        428 => 'Precondition Required',                                       // RFC6585
        429 => 'Too Many Requests',                                           // RFC6585
        431 => 'Request Header Fields Too Large',                             // RFC6585
        500 => 'Internal Server Error',
        501 => 'Not Implemented',
        502 => 'Bad Gateway',
        503 => 'Service Unavailable',
        504 => 'Gateway Timeout',
        505 => 'HTTP Version Not Supported',
        506 => 'Variant Also Negotiates (Experimental)',                      // RFC2295
        507 => 'Insufficient Storage',                                        // RFC4918
        508 => 'Loop Detected',                                               // RFC5842
        510 => 'Not Extended',                                                // RFC2774
        511 => 'Network Authentication Required',                             // RFC6585
    );

    /**
     * Creates a new HTTP response
     *
     * @param string $strContent The response content
     * @param int    $intStatus  The response HTTP status code
     */
    public function __construct($strContent = '', $intStatus = 200)
    {
        $this->setContent($strContent);
        $this->setStatusCode($intStatus);

        // Set default Content-Type
        $this->setHeader('Content-Type', 'text/plain');
    }

    /**
     * Sets a header
     *
     * @param string $strName    The header name
     * @param string $strContent The header content
     *
     * @return Response
     */
    public function setHeader($strName, $strContent)
    {
        $this->arrHeaders[$strName] = $strContent;

        return $this;
    }

    /**
     * Remove a header
     *
     * @param string $strName The header name
     *
     * @return Response
     */
    public function removeHeader($strName)
    {
        unset($this->arrHeaders[$strName]);

        return $this;
    }

    /**
     * Get a header
     *
     * @param string $strName The header name
     *
     * @return string The header content
     */
    public function getHeader($strName)
    {
        return $this->arrHeaders[$strName];
    }

    /**
     * Gets the status code
     *
     * @return int Status code
     */
    public function getStatusCode()
    {
        return $this->intStatus;
    }

    /**
     * Set the status code
     *
     * @param int $intCode
     *
     * @throws  \InvalidArgumentException When the HTTP status code is not valid
     */
    public function setStatusCode($intCode)
    {
        $intCode = (int) $intCode;

        if (!in_array($intCode, array_keys(static::$arrStatuses))) {
            throw new \InvalidArgumentException('The status code "' . $intCode . '" is invalid!');
        }

        $this->intStatus = $intCode;
    }

    /**
     * Sets the content
     * @param   string
     */
    public function setContent($strContent)
    {
        // Replace insert tags
        $this->strContent = InsertTag::replaceRecursively($strContent);

        // Content-Length
        $this->setHeader('Content-Length', strlen($this->strContent));
    }

    /**
     * Prepare response
     */
    protected function prepare()
    {
        // Fix charset
        $strContentType = $this->getHeader('Content-Type');

        if (false === strpos($strContentType, 'charset')) {
            $strCharset = $GLOBALS['TL_CONFIG']['characterSet'] ?: 'utf-8';
            $this->setHeader('Content-Type', $strContentType . '; charset=' . $strCharset);
        }

        // Always remove existing headers to prevent duplicates
        foreach ($this->arrHeaders as $name => $value) {
            header_remove($name);
        }
    }

    /**
     * Sends the HTTP headers
     */
    protected function sendHeaders()
    {
        if (headers_sent()) {
            return;
        }

        // Status
        $strVersion = ('HTTP/1.0' === $_SERVER['SERVER_PROTOCOL']) ? '1.0' : '1.1';
        header(sprintf('HTTP/%s %s %s', $strVersion, $this->intStatus, static::$arrStatuses[$this->intStatus]));

        // Headers
        foreach ($this->arrHeaders as $strName => $strContent) {
            header($strName . ': ' . $strContent);
        }
    }

    /**
     * Send the response
     *
     * @param bool $blnExit Exit script
     *
     * @return Response|null
     */
    public function send($blnExit = true)
    {
        $this->prepare();

        if ($blnExit && class_exists('Contao\CoreBundle\Exception\ResponseException')) {
            throw new ResponseException(
                new SymfonyResponse($this->strContent, $this->intStatus, $this->arrHeaders)
            );
        }

        // Clean the output buffer
        ob_end_clean();

        // Send
        $this->sendHeaders();
        echo $this->strContent;

        if ($blnExit) {
            exit;
        }

        return $this;
    }

    /**
     * Prints the response
     * @return  string
     */
    public function __toString()
    {
        $strOutput = '';
        $this->prepare();
        $strVersion = ('HTTP/1.0' === $_SERVER['SERVER_PROTOCOL']) ? '1.0' : '1.1';
        $strOutput .= sprintf('HTTP/%s %s %s', $strVersion, $this->intStatus, static::$arrStatuses[$this->intStatus]) . "\n";

        // Headers
        foreach ($this->arrHeaders as $strName => $strContent) {
            $strOutput .= $strName . ': ' . $strContent . "\n";
        }

        $strOutput .= "\n" . $this->strContent;
        return $strOutput;
    }
}
