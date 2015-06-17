<?php

namespace Pim\Bundle\PrestashopConnectorBundle\Webservice;

/**
 * @package PrestaShopWebservice
 */
class PrestaShopWebservice
{

    /** @var string Shop URL */
    protected $url;

    /** @var string Authentification key */
    protected $key;

    /** @var boolean is debug activated */
    protected $debug;

    /** @var string PS version */
    protected $version;

    /** @var array compatible versions of PrestaShop Webservice */
    const psCompatibleVersionsMin = '1.4.0.0';
    const psCompatibleVersionsMax = '1.6.0.14';


    function __construct($url, $key, $debug = true)
    {
        if (!extension_loaded('curl')) {
            throw new PrestaShopWebserviceException(
                'Please activate the PHP extension \'curl\' to allow use of PrestaShop webservice library'
            );
        }
        $this->url = $url;
        $this->key = $key;
        $this->debug = $debug;
        $this->version = 'unknown';
    }

    protected function checkStatusCode($status_code)
    {
        $error_label = 'This call to PrestaShop Web Services failed and returned an HTTP status of %d. That means: %s.';
        switch ($status_code) {
            case 200:
            case 201:
                break;
            case 204:
                throw new PrestaShopWebserviceException(sprintf($error_label, $status_code, 'No content'));
                break;
            case 400:
                throw new PrestaShopWebserviceException(sprintf($error_label, $status_code, 'Bad Request'));
                break;
            case 401:
                throw new PrestaShopWebserviceException(sprintf($error_label, $status_code, 'Unauthorized'));
                break;
            case 404:
                throw new PrestaShopWebserviceException(sprintf($error_label, $status_code, 'Not Found'));
                break;
            case 405:
                throw new PrestaShopWebserviceException(sprintf($error_label, $status_code, 'Method Not Allowed'));
                break;
            case 500:
                throw new PrestaShopWebserviceException(sprintf($error_label, $status_code, 'Internal Server Error'));
                break;
            default:
                throw new PrestaShopWebserviceException(
                    'This call to PrestaShop Web Services returned an unexpected HTTP status of:'.$status_code
                );
        }
    }

    protected function executeRequest($url, $curl_params = array())
    {
        $defaultParams = array(
            CURLOPT_HEADER => true,
            CURLOPT_RETURNTRANSFER => true,
            CURLINFO_HEADER_OUT => true,
            CURLOPT_HTTPAUTH => CURLAUTH_BASIC,
            CURLOPT_USERPWD => $this->key.':',
            CURLOPT_HTTPHEADER => array('Expect:')
        );

        $session = curl_init($url);

        $curl_options = array();
        foreach ($defaultParams as $defkey => $defval) {
            if (isset($curl_params[$defkey])) {
                $curl_options[$defkey] = $curl_params[$defkey];
            } else {
                $curl_options[$defkey] = $defaultParams[$defkey];
            }
        }
        foreach ($curl_params as $defkey => $defval) {
            if (!isset($curl_options[$defkey])) {
                $curl_options[$defkey] = $curl_params[$defkey];
            }
        }

        curl_setopt_array($session, $curl_options);
        $response = curl_exec($session);

        $index = strpos($response, "\r\n\r\n");
        if ($index === false && $curl_params[CURLOPT_CUSTOMREQUEST] != 'HEAD') {
            throw new PrestaShopWebserviceException('Bad HTTP response');
        }

        $header = substr($response, 0, $index);
        $body = substr($response, $index + 4);

        $headerArrayTmp = explode("\n", $header);

        $headerArray = array();
        foreach ($headerArrayTmp as &$headerItem) {
            $tmp = explode(':', $headerItem);
            $tmp = array_map('trim', $tmp);
            if (count($tmp) == 2) {
                $headerArray[$tmp[0]] = $tmp[1];
            }
        }

        if (array_key_exists('PSWS-Version', $headerArray)) {
            $this->version = $headerArray['PSWS-Version'];
            if (
                version_compare(PrestaShopWebservice::psCompatibleVersionsMin, $headerArray['PSWS-Version']) == 1 ||
                version_compare(PrestaShopWebservice::psCompatibleVersionsMax, $headerArray['PSWS-Version']) == -1
            ) {
                throw new PrestaShopWebserviceException(
                    'This library is not compatible with this version of PrestaShop. Please upgrade/downgrade this library'
                );
            }
        }

        if ($this->debug) {
            $this->printDebug('HTTP REQUEST HEADER', curl_getinfo($session, CURLINFO_HEADER_OUT));
            $this->printDebug('HTTP RESPONSE HEADER', $header);

        }
        $status_code = curl_getinfo($session, CURLINFO_HTTP_CODE);
        if ($status_code === 0) {
            throw new PrestaShopWebserviceException('CURL Error: '.curl_error($session));
        }
        curl_close($session);
        if ($this->debug) {
            if ($curl_params[CURLOPT_CUSTOMREQUEST] == 'PUT' || $curl_params[CURLOPT_CUSTOMREQUEST] == 'POST') {
                $this->printDebug('XML SENT', urldecode($curl_params[CURLOPT_POSTFIELDS]));
            }
            if ($curl_params[CURLOPT_CUSTOMREQUEST] != 'DELETE' && $curl_params[CURLOPT_CUSTOMREQUEST] != 'HEAD') {
                $this->printDebug('RETURN HTTP BODY', $body);
            }
        }

        return array('status_code' => $status_code, 'response' => $body, 'header' => $header);
    }

    public function printDebug($title, $content)
    {
        echo '<div style="display:table;background:#CCC;font-size:8pt;padding:7px"><h6 style="font-size:9pt;margin:0">'.$title.'</h6><pre>'.htmlentities(
                $content
            ).'</pre></div>';
    }

    public function getVersion()
    {
        return $this->version;
    }

    protected function parseXML($response)
    {
        if ($response != '') {
            libxml_clear_errors();
            libxml_use_internal_errors(true);
            $xml = simplexml_load_string($response, 'SimpleXMLElement', LIBXML_NOCDATA);
            if (libxml_get_errors()) {
                $msg = var_export(libxml_get_errors(), true);
                libxml_clear_errors();
                throw new PrestaShopWebserviceException('HTTP XML response is not parsable: '.$msg);
            }

            return $xml;
        } else {
            throw new PrestaShopWebserviceException('HTTP response is empty');
        }
    }

    public function add($options)
    {
        $xml = '';

        if (isset($options['resource'], $options['postXml']) || isset($options['url'], $options['postXml'])) {
            $url = (isset($options['resource']) ? $this->url.'/api/'.$options['resource'] : $options['url']);
            $xml = $options['postXml'];
            if (isset($options['id_shop'])) {
                $url .= '&id_shop='.$options['id_shop'];
            }
            if (isset($options['id_group_shop'])) {
                $url .= '&id_group_shop='.$options['id_group_shop'];
            }
        } else {
            throw new PrestaShopWebserviceException('Bad parameters given');
        }
        $request = self::executeRequest($url, array(CURLOPT_CUSTOMREQUEST => 'POST', CURLOPT_POSTFIELDS => $xml));

        self::checkStatusCode($request['status_code']);

        return self::parseXML($request['response']);
    }

    public function get($options)
    {
        if (isset($options['url'])) {
            $url = $options['url'];
        } elseif (isset($options['resource'])) {
            $url = $this->url.'/api/'.$options['resource'];
            $url_params = array();
            if (isset($options['id'])) {
                $url .= '/'.$options['id'];
            }

            $params = array('filter', 'display', 'sort', 'limit', 'id_shop', 'id_group_shop');
            foreach ($params as $p) {
                foreach ($options as $k => $o) {
                    if (strpos($k, $p) !== false) {
                        $url_params[$k] = $options[$k];
                    }
                }
            }
            if (count($url_params) > 0) {
                $url .= '?'.http_build_query($url_params);
            }
        } else {
            throw new PrestaShopWebserviceException('Bad parameters given');
        }

        $request = self::executeRequest($url, array(CURLOPT_CUSTOMREQUEST => 'GET'));

        self::checkStatusCode($request['status_code']);// check the response validity
        return self::parseXML($request['response']);
    }

    public function head($options)
    {
        if (isset($options['url'])) {
            $url = $options['url'];
        } elseif (isset($options['resource'])) {
            $url = $this->url.'/api/'.$options['resource'];
            $url_params = array();
            if (isset($options['id'])) {
                $url .= '/'.$options['id'];
            }

            $params = array('filter', 'display', 'sort', 'limit');
            foreach ($params as $p) {
                foreach ($options as $k => $o) {
                    if (strpos($k, $p) !== false) {
                        $url_params[$k] = $options[$k];
                    }
                }
            }
            if (count($url_params) > 0) {
                $url .= '?'.http_build_query($url_params);
            }
        } else {
            throw new PrestaShopWebserviceException('Bad parameters given');
        }
        $request = self::executeRequest($url, array(CURLOPT_CUSTOMREQUEST => 'HEAD', CURLOPT_NOBODY => true));
        self::checkStatusCode($request['status_code']);// check the response validity
        return $request['header'];
    }

    public function edit($options)
    {
        $xml = '';
        if (isset($options['url'])) {
            $url = $options['url'];
        } elseif ((isset($options['resource'], $options['id']) || isset($options['url'])) && $options['putXml']) {
            $url = (isset($options['url']) ? $options['url'] : $this->url.'/api/'.$options['resource'].'/'.$options['id']);
            $xml = $options['putXml'];
            if (isset($options['id_shop'])) {
                $url .= '&id_shop='.$options['id_shop'];
            }
            if (isset($options['id_group_shop'])) {
                $url .= '&id_group_shop='.$options['id_group_shop'];
            }
        } else {
            throw new PrestaShopWebserviceException('Bad parameters given');
        }

        $request = self::executeRequest($url, array(CURLOPT_CUSTOMREQUEST => 'PUT', CURLOPT_POSTFIELDS => $xml));
        self::checkStatusCode($request['status_code']);// check the response validity
        return self::parseXML($request['response']);
    }

    public function delete($options)
    {
        if (isset($options['url'])) {
            $url = $options['url'];
        } elseif (isset($options['resource']) && isset($options['id'])) {
            if (is_array($options['id'])) {
                $url = $this->url.'/api/'.$options['resource'].'/?id=['.implode(',', $options['id']).']';
            } else {
                $url = $this->url.'/api/'.$options['resource'].'/'.$options['id'];
            }
        }
        if (isset($options['id_shop'])) {
            $url .= '&id_shop='.$options['id_shop'];
        }
        if (isset($options['id_group_shop'])) {
            $url .= '&id_group_shop='.$options['id_group_shop'];
        }
        $request = self::executeRequest($url, array(CURLOPT_CUSTOMREQUEST => 'DELETE'));
        self::checkStatusCode($request['status_code']);// check the response validity
        return true;
    }


}

/**
 * @package PrestaShopWebservice
 */
class PrestaShopWebserviceException extends \Exception
{
}

