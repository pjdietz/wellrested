<?php

namespace WellRESTed\Transmission;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\StreamInterface;
use WellRESTed\Dispatching\Dispatcher;
use WellRESTed\Dispatching\DispatcherInterface;

class Transmitter implements TransmitterInterface
{
    /** @var int */
    private $chunkSize = 0;
    /** @var DispatcherInterface  */
    private $dispatcher;

    public function __construct(DispatcherInterface $dispatcher = null)
    {
        if ($dispatcher === null) {
            $dispatcher = new Dispatcher();
        }
        $this->dispatcher = $dispatcher;
    }

    /**
     * Outputs a response to the client.
     *
     * This method outputs the status line, headers, and body to the client.
     *
     * This method will also provide a Content-length header if:
     *   - Response does not have a Content-length header
     *   - Response does not have a Transfer-encoding: chunked header
     *   - Response body stream is readable and reports a non-null size
     *
     * @param ServerRequestInterface $request
     * @param ResponseInterface $response Response to output
     */
    public function transmit(
        ServerRequestInterface $request,
        ResponseInterface $response
    ) {
        // Prepare the response for output.
        $response = $this->prepareResponse($request, $response);

        // Status Line
        header($this->getStatusLine($response));
        // Headers
        foreach ($response->getHeaders() as $key => $headers) {
            $replace = true;
            foreach ($headers as $header) {
                header("$key: $header", $replace);
                $replace = false;
            }
        }
        // Body
        $body = $response->getBody();
        if ($body->isReadable()) {
            $this->outputBody($response->getBody());
        }
    }

    /**
     * @param int $chunkSize
     */
    public function setChunkSize($chunkSize)
    {
        $this->chunkSize = $chunkSize;
    }

    protected function prepareResponse(
        ServerRequestInterface $request,
        ResponseInterface $response
    ) {
        // Add a Content-length header to the response when all of these are true:
        //
        // - Response does not have a Content-length header
        // - Response does not have a Transfer-encoding: chunked header
        // - Response body stream is readable and reports a non-null size
        //
        if (!$response->hasHeader("Content-length")
            && !(strtolower($response->getHeaderLine("Transfer-encoding")) === "chunked")
        ) {
            $size = $response->getBody()->getSize();
            if ($size !== null) {
                $response = $response->withHeader("Content-length", (string) $size);
            }
        }
        return $response;
    }

    private function getStatusLine(ResponseInterface $response)
    {
        $protocol = $response->getProtocolVersion();
        $statusCode = $response->getStatusCode();
        $reasonPhrase = $response->getReasonPhrase();
        if ($reasonPhrase) {
            return "HTTP/$protocol $statusCode $reasonPhrase";
        } else {
            return "HTTP/$protocol $statusCode";
        }
    }

    private function outputBody(StreamInterface $body)
    {
        if ($this->chunkSize > 0) {
            if ($body->isSeekable()) {
                $body->rewind();
            }
            while (!$body->eof()) {
                print $body->read($this->chunkSize);
            }
        } else {
            print (string) $body;
        }
    }
}
