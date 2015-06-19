<?php

namespace Pim\Bundle\PrestashopConnectorBundle\Webservice;

use Pim\Bundle\PrestashopConnectorBundle\Validator\Exception\InvalidRestpUrlException;
use Pim\Bundle\PrestashopConnectorBundle\Validator\Exception\NotReachableUrlException;
use Guzzle\Http\Exception\BadResponseException;
use Guzzle\Http\Exception\CurlException;
use Guzzle\Service\ClientInterface;

/**
 * Allows to get the content of an url.
 *
 */
class UrlExplorer
{
    /** @staticvar int */
    const TIMEOUT = 10;

    /** @staticvar int */
    const CONNECT_TIMEOUT = 10;

    /** @var ClientInterface */
    protected $client;

    /** @var array */
    protected $resultCache;

    /**
     * @param ClientInterface $client
     */
    public function __construct(ClientInterface $client)
    {
        $this->client      = $client;
        $this->resultCache = [];
    }

    /**
     * Reaches url and get his content.
     *
     * @param PrestashopRestClientParameters $clientParameters
     *
     * @return string Xml content as string
     *
     * @throws NotReachableUrlException
     * @throws InvalidRestUrlException
     */
    public function getUrlContent(PrestashopRestClientParameters $clientParameters)
    {
        try {
            $response = $this->connect($clientParameters);
        } catch (CurlException $e) {
            throw new NotReachableUrlException($e->getMessage());
        } catch (BadResponseException $e) {
            throw new InvalidRestUrlException($e->getMessage());
        }

        if (false === $response->isContentType('text/xml')) {
            throw new InvalidRestUrlException('Content type is not XML');
        }

        return $response->getBody(true);
    }

    /**
     * It connects to the url and give response.
     *
     * @param PrestashopRestClientParameters $clientParameters
     *
     * @return \Guzzle\Http\Message\Response
     *
     * @throws \Exception
     */
    protected function connect($clientParameters)
    {
        $parametersHash = $clientParameters->getHash();

        if (!isset($this->resultCache[$parametersHash])) {
            $guzzleParams = [
                'connect_timeout' => self::CONNECT_TIMEOUT,
                'timeout'         => self::TIMEOUT,
                'auth'            => [
                    $clientParameters->getHttpLogin(),
                    $clientParameters->getHttpPassword(),
                ],
            ];

            $request = $this->client->get($clientParameters->getSoapUrl(), [], $guzzleParams);
            $request->getCurlOptions()->set(CURLOPT_CONNECTTIMEOUT, self::CONNECT_TIMEOUT);
            $request->getCurlOptions()->set(CURLOPT_TIMEOUT, self::TIMEOUT);

            try {
                $response = $this->client->send($request);
                $this->resultCache[$parametersHash] = $response;
            } catch (\Exception $e) {
                $this->resultCache[$parametersHash] = $e;
                throw $e;
            }
        } else {
            $response = $this->resultCache[$parametersHash];
            if ($response instanceof \Exception) {
                throw $response;
            }
        }

        return $response;
    }
}
