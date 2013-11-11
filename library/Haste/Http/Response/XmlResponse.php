<?php

namespace Haste\Http\Response;


class XmlResponse extends Response
{
    /**
     * Creates a new XML HTTP response
     * @param   string The response content
     * @param   integer The response HTTP status code
     * @throws \InvalidArgumentException When the HTTP status code is not valid
     */
    public function __construct($strContent, $intStatus = 200)
    {
        parent::__construct($strContent, $intStatus);

        $this->setHeader('Content-Type', 'application/xml');
    }
}