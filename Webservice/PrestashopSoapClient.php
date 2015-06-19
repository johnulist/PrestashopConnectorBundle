<?php

namespace Pim\Bundle\PrestashopConnectorBundle\Webservice;

use Pim\Bundle\PrestashopConnectorBundle\Guesser\AbstractGuesser;
use Symfony\Component\Stopwatch\Stopwatch;

/**
 * A prestashop soap client to abstract interaction with the php soap client.
 *
 */
class PrestashopSoapClient
{
    /** @staticvar string */
    const PRESTASHOP_BAD_CREDENTIALS     = '2';

    /** @staticvar string */
    const PRESTASHOP_CLIENT_NOT_CALLABLE = 'Client';

    /** @var string */
    protected $session;

    /** @var \SoapClient */
    protected $client;

    /** @var array */
    protected $calls;

    /** @var PrestashopRestClientParameters */
    protected $clientParameters;

    /** @var PrestashopSoapClientProfiler */
    protected $profiler;

    /**
     * Create and init the soap client.
     *
     * @param PrestashopRestClientParameters $clientParameters
     * @param \SoapClient                 $soapClient
     * @param PrestashopSoapClientProfiler   $profiler
     *
     * @throws ConnectionErrorException
     */
    public function __construct(
        PrestashopRestClientParameters $clientParameters,
        \SoapClient $soapClient = null,
        PrestashopSoapClientProfiler $profiler = null
    ) {
        $this->clientParameters = $clientParameters;
        $this->profiler         = $profiler;

        if (!$soapClient) {
            $wsdlUrl     = $this->clientParameters->getSoapUrl();
            $soapOptions = [
                'encoding'   => 'UTF-8',
                'trace'      => true,
                'exceptions' => true,
                'login'      => $this->clientParameters->getHttpLogin(),
                'password'   => $this->clientParameters->getHttpPassword(),
                'cache_wsdl' => WSDL_CACHE_BOTH,
            ];

            try {
                $this->client = new \SoapClient($wsdlUrl, $soapOptions);
            } catch (\SoapFault $e) {
                throw new ConnectionErrorException(
                    'The soap connection could not be established',
                    $e->getCode(),
                    $e
                );
            }
        } else {
            $this->client = $soapClient;
        }

        $this->connect();
    }

    /**
     * Initialize the soap client with the local information.
     *
     * @throws InvalidCredentialException If given credentials are invalid
     * @throws RestCallException          If Prestashop is not accessible.
     * @throws \SoapFault
     */
    protected function connect()
    {
        try {
            $this->session = $this->client->login(
                $this->clientParameters->getSoapUsername(),
                $this->clientParameters->getSoapApiKey()
            );
        } catch (\SoapFault $e) {
            if (static::PRESTASHOP_BAD_CREDENTIALS === $e->faultcode) {
                throw new InvalidCredentialException(
                    sprintf(
                        'Error on Prestashop SOAP credentials to "%s": "%s".'.
                        'You should check your login and Prestashop API key.',
                        $this->clientParameters->getSoapUrl(),
                        $e->getMessage()
                    ),
                    $e->getCode(),
                    $e
                );
            } elseif (static::PRESTASHOP_CLIENT_NOT_CALLABLE === $e->faultcode) {
                $lastResponse = $this->client->__getLastResponse();
                echo "DEBUG: Last SOAP response: ".$lastResponse."\n";
                if (strlen($lastResponse) <= 200) {
                    $truncatedLastResponse  = htmlentities($lastResponse);
                } else {
                    $truncatedLastResponse = substr(htmlentities($lastResponse), 0, 100).
                        nl2br("\n...\n").substr(htmlentities($lastResponse), -100);
                }
                throw new RestCallException(
                    sprintf(
                        'Error on Prestashop client to "%s": "%s".'.
                        'Something is probably wrong in the last SOAP response:'.nl2br("\n").'"%s"',
                        $this->clientParameters->getSoapUrl(),
                        $e->getMessage(),
                        $truncatedLastResponse
                    ),
                    $e->getCode(),
                    $e
                );
            } else {
                throw $e;
            }
        }
    }

    /**
     * Is the client connected ?
     *
     * @return boolean
     */
    public function isConnected()
    {
        return (bool) $this->session;
    }

    /**
     * Call soap api.
     *
     * @param string $resource
     * @param array  $params
     *
     * @return array
     *
     * @throws NotConnectedException
     * @throws RestCallException
     */
    public function call($resource, $params = null)
    {
        if ($this->isConnected()) {
            try {
                $stopWatch = new Stopwatch(microtime(true));
                $stopWatch->start('prestashopSoapCall');
                $response = $this->client->call($this->session, $resource, $params);
                $event = $stopWatch->stop('prestashopSoapCall');
                $this->profiler->logCallDuration($event, $resource);
            } catch (\SoapFault $e) {
                if ($resource === 'core_prestashop.info' && $e->getMessage()
                    === AbstractGuesser::PRESTASHOP_CORE_ACCESS_DENIED) {
                    $response = ['prestashop_version' => AbstractGuesser::UNKNOWN_VERSION];
                } elseif ($e->getMessage() === AbstractGuesser::PRESTASHOP_CORE_ACCESS_DENIED) {
                    throw new RestCallException(
                        sprintf(
                            'Error on Prestashop soap call to "%s" : "%s" Called resource : "%s" with parameters : %s.'.
                            ' Soap user needs access on this resource. Please '.
                            'check in your Prestashop webservice soap roles and '.
                            'users configuration.',
                            $this->clientParameters->getSoapUrl(),
                            $e->getMessage(),
                            $resource,
                            json_encode($params)
                        ),
                        $e->getCode(),
                        $e
                    );
                } else {
                    throw new RestCallException(
                        sprintf(
                            'Error on Prestashop soap call to "%s" : "%s". Called resource : "%s" with parameters : %s',
                            $this->clientParameters->getSoapUrl(),
                            $e->getMessage(),
                            $resource,
                            json_encode($params)
                        ),
                        $e->getCode(),
                        $e
                    );
                }
            }

            if (is_array($response) && isset($response['isFault']) && $response['isFault']) {
                throw new RestCallException(
                    sprintf(
                        'Error on Prestashop soap call to "%s" : "%s". Called resource : "%s" with parameters : %s.'.
                        'Response from API : %s',
                        $this->clientParameters->getSoapUrl(),
                        $e->getMessage(),
                        $resource,
                        json_encode($params),
                        json_encode($response)
                    )
                );
            }

            return $response;
        } else {
            throw new NotConnectedException();
        }
    }

    /**
     * Add a call to the soap call stack.
     *
     * @param array $call A prestashop soap call
     */
    public function addCall(array $call)
    {
        $this->call($call[0], $call[1]);
    }

    /**
     * Send pending calls to the prestashop soap api (with multiCall function).
     */
    public function sendCalls()
    {
        if (count($this->calls) > 0) {
            if ($this->isConnected()) {
                try {
                    $this->client->multiCall(
                        $this->session,
                        $this->calls
                    );
                } catch (\SoapFault $e) {
                    throw new RestCallException(
                        sprintf(
                            'Error on Prestashop soap call : "%s". Called resources : "%s".',
                            $e->getMessage(),
                            json_encode($this->calls)
                        )
                    );
                }
            } else {
                throw new NotConnectedException();
            }

            $this->calls = [];
        }
    }
}
