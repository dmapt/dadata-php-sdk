<?php

namespace dmapt\DaData;

class Api {

    /**
     * SDK version
     */
    const VERSION = '2.0';

    /**
     * DaData clean URL.
     * @var string
     */
    protected $apiUrl = 'https://dadata.ru/api/v2';

    /**
     * DaData suggestions URL
     * @var string
     */
    protected $suggestUrl = 'https://suggestions.dadata.ru/suggestions/api/4_1/rs';

    /**
     * Access token
     * @var string
     */
    protected $token = '';

    /**
     * Secret
     * @var string
     */
    protected $secret = '';

    /**
     * Curl instance
     * @var resource|null
     */
    protected static $curl = null;


    /**
     * @param string $token
     * @param string $secret
     */
    public function __construct($token, $secret = '')
    {
        $this->token = $token;
        $this->secret = $secret;
    }

    public function __destruct()
    {
        if (self::$curl)
            curl_close(self::$curl);
    }

    /**
     * Create CURL instance if not exists
     * @return resource
     */
    protected function getCurl()
    {
        if (self::$curl === null) {
            self::$curl = curl_init();
            curl_setopt_array(self::$curl, array(
                CURLOPT_RETURNTRANSFER => true,
                CURLINFO_HEADER_OUT => false,
                CURLOPT_VERBOSE => false,
                CURLOPT_HEADER => false,
                CURLOPT_CONNECTTIMEOUT => 5,
                CURLOPT_TIMEOUT        => 5,
                CURLOPT_USERAGENT => strtolower(__CLASS__.'-PHP-SDK/v'.self::VERSION),
            ));
        }
        return self::$curl;
    }

    /**
     * Execute a request API to DaData using cURL
     *
     * @param string $url
     * @param $data
     * @return mixed
     * @throws DaDataException
     */
    protected function request($url, $data = null)
    {
        $curl = $this->getCurl();
        curl_setopt_array($curl, array(
            CURLOPT_URL => $url,
            CURLOPT_HTTPHEADER => array(
                'Content-Type: application/json',
                'Accept: application/json',
                'Authorization: Token '.$this->token,
                'X-Secret: '. $this->secret,
            )
        ));
        if ($data === null) {
            curl_setopt_array($curl, array(
                CURLOPT_POST => false,
            ));
        } elseif (is_array($data)) {
            curl_setopt_array($curl, array(
                CURLOPT_POST => true,
                CURLOPT_POSTFIELDS => json_encode($data),
            ));
        }

        $curlResult = curl_exec($curl);
        $curlErrorNumber = curl_errno($curl);

        if ($curlErrorNumber != 0) {
            throw new DaDataException('CURL Error: ' . curl_error($curl) . '. cURL error code: ' . $curlErrorNumber);
        }

        $jsonResult = json_decode($curlResult, true);
        unset($curlResult);

        $jsonErrorCode = json_last_error();
        if ($jsonResult === null && ($jsonErrorCode != JSON_ERROR_NONE)) {
            throw new DaDataException('JSON Error: ' . json_last_error_msg() . '. Error code: ' . $jsonErrorCode);
        }

        $httpCode = curl_getinfo($curl, CURLINFO_RESPONSE_CODE);
        if ($httpCode != 200) {
            $message = 'Wrong HTTP Code: ' . $httpCode . '.';
            if (isset($jsonResult['detail']))
                $message .= ' Error: ' . $jsonResult['detail'];
            throw new DaDataException($message);
        }

        return $jsonResult;
    }

    /**
     * Prepare and execute clean command. Parse result.
     *
     * @param string $type
     * @param mixed $data
     * @param mixed $resultAttr
     * @param mixed $response
     * @return array|bool
     * @throws DaDataException
     */
    public function clean($type, $data, $resultAttr, &$response = null)
    {
        $single = false;
        if (!is_array($data)) {
            $data = array($data);
            $single = true;
        }

        $keys = array_keys($data);
        $data = array_values($data);

        $url = $this->apiUrl . '/clean/' . $type;
        $response = $this->request($url, $data);

        if (is_array($keys)) {
            $return = array();
            $i = 0;
            foreach($keys as $key) {
                $return[$key] = false;
                if (is_callable($resultAttr))
                    $return[$key] = call_user_func($resultAttr, $response[$i]);
                elseif (isset($response[$i][$resultAttr]))
                    $return[$key] = $response[$i][$resultAttr];

                if ($single)
                    return $return[$key];

                $i++;
            }

            return $return;
        }

        return false;
    }

    /**
     * Clean name.
     *
     * @param string|mixed $data
     * @param mixed $field
     * @param array $response
     * @return string|bool
     * @throws DaDataException
     */
    public function cleanName($data, $field = 'result', &$response = false)
    {
        return $this->clean('name', $data, $field, $response);
    }

    /**
     * Clean phone.
     *
     * @param string|mixed $data
     * @param mixed $field
     * @param array $response
     * @return string|bool
     * @throws DaDataException
     */
    public function cleanPhone($data, $field = 'phone', &$response = false)
    {
        return $this->clean('phone', $data, $field, $response);
    }

    /**
     * Clean passport.
     *
     * @param string|mixed $data
     * @param mixed $field
     * @param array $response
     * @return string|bool
     * @throws DaDataException
     */
    public function cleanPassport($data, $field = null, &$response = false)
    {
        if ($field === null)
            $field = function($data) {
                if (isset($data['series']) && isset($data['number']))
                    return $data['series'] . ' ' . $data['number'];

                return false;
            };
        return $this->clean('passport', $data, $field, $response);
    }

    /**
     * Clean email.
     *
     * @param string|mixed $data
     * @param mixed $field
     * @param array $response
     * @return string|bool
     * @throws DaDataException
     */
    public function cleanEmail($data, $field = 'email', &$response = false)
    {
        return $this->clean('email', $data, $field, $response);
    }

    /**
     * Clean birthdate.
     *
     * @param string|mixed $data
     * @param mixed $field
     * @param array $response
     * @return string|bool
     * @throws DaDataException
     */
    public function cleanBirthdate($data, $field = 'birthdate', &$response = false)
    {
        return $this->clean('birthdate', $data, $field, $response);
    }

    /**
     * Clean vehicle.
     *
     * @param string|mixed $data
     * @param mixed $field
     * @param array $response
     * @return string|bool
     * @throws DaDataException
     */
    public function cleanVehicle($data, $field = 'result', &$response = false)
    {
        return $this->clean('vehicle', $data, $field, $response);
    }

    /**
     * Clean address.
     *
     * @param string|mixed $data
     * @param mixed $field
     * @param array $response
     * @return string|bool
     * @throws DaDataException
     */
    public function cleanAddress($data, $field = 'result', &$response = false)
    {
        return $this->clean('address', $data, $field, $response);
    }

    /**
     * Clean structure
     *
     * @param $config
     * @param $data
     * @return mixed
     * @throws DaDataException
     */
    public function cleanStructure($structure, $data)
    {
        $data = array(
            'structure' => $structure,
            'data' => $data
        );

        return $this->request($this->apiUrl . '/clean', $data);
    }

    /**
     * Build suggestions post params array
     *
     * @param $query
     * @param array $params
     * @return array
     */
    protected function buildSuggestParams($query, array $params = array())
    {
        if (!is_array($params))
            $params = array();
        $params['query'] = $query;

        return $params;
    }

    /**
     * Send suggestions request
     *
     * @param string $url
     * @param array $data
     * @return array
     * @throws DaDataException
     */
    protected function suggestionsRequest($url, array $data)
    {
        $url = $this->suggestUrl . '/' . $url;

        $response =  $this->request($url, $data);

        if (isset($response['suggestions']) && is_array($response['suggestions']))
            return $response['suggestions'];
        else
            throw new DaDataException('Unexpected DaData answer.');
    }

    /**
     * Suggest query
     *
     * @param string $type
     * @param array $data
     * @return array
     * @throws DaDataException
     */
    public function suggest($type, array $data)
    {
        $url = 'suggest/' . $type;

        return $this->suggestionsRequest($url, $data);
    }

    /**
     * Suggests FIO
     *
     * @param string $query
     * @param array $params
     * @return array
     */
    public function suggestFio($query, array $params = array())
    {
        return $this->suggest('fio', $this->buildSuggestParams($query, $params));
    }

    /**
     * Suggests FIO. Alias for suggestFIO
     *
     * @param string $query
     * @param array $params
     * @return array
     */
    public function suggestName($query, array $params = array())
    {
        return $this->suggestFio($query, $params);
    }

    /**
     * Suggests Address
     *
     * @param string $query
     * @param array $params
     * @return array
     */
    public function suggestAddress($query, array $params = array())
    {
        return $this->suggest('address', $this->buildSuggestParams($query, $params));
    }

    /**
     * Suggests Party
     *
     * @param string $query
     * @param array $params
     * @return array
     */
    public function suggestParty($query, array $params = array())
    {
        return $this->suggest('party', $this->buildSuggestParams($query, $params));
    }

    /**
     * Suggests organization. Alias for suggestParty
     *
     * @param string $query
     * @param array $params
     * @return array
     */
    public function suggestOrganization($query, array $params = array())
    {
        return $this->suggestParty($query, $params);
    }

    /**
     * Suggests Bank
     *
     * @param $query
     * @param array $params
     * @return array
     */
    public function suggestBank($query, array $params = array())
    {
        return $this->suggest('bank', $this->buildSuggestParams($query, $params));
    }

    /**
     * Suggests Email
     *
     * @param string $query
     * @param array $params
     * @return array
     */
    public function suggestEmail($query, array $params = array())
    {
        return $this->suggest('email', $this->buildSuggestParams($query, $params));
    }

    /**
     * Detect address by IP
     *
     * @param null|string $ip
     * @return array
     * @throws DaDataException
     */
    public function detectAddressByIp($ip = null)
    {
        $url = $this->suggestUrl . '/detectAddressByIp';
        if ($ip !== null) {
            $url .= '?' . http_build_query(array('ip' => $ip));
        }

        $response = $this->request($url, null);

        if (isset($response['location']))
            return $response['location'];
        else
            throw new DaDataException('Unexpected DaData answer.');
    }

    /**
     * Find address by FIAS or KLADR
     *
     * @param string $query
     * @return array
     */
    public function findAddress($query)
    {
        return $this->suggestionsRequest('findById/address', $this->buildSuggestParams($query));
    }

    /**
     * Find delivery company id by FIAS or KLADR
     *
     * @param string $query
     * @return array
     */
    public function findDelivery($query)
    {
        return $this->suggestionsRequest('findById/delivery', $this->buildSuggestParams($query));
    }

    /**
     * Find organization by INN or OGRN
     *
     * @param string $query
     * @param array $params
     * @return array
     */
    public function findParty($query, $params = array())
    {
        return $this->suggestionsRequest('findById/party', $this->buildSuggestParams($query, $params));
    }

    /**
     * Find organization by INN or OGRN. Alias For findParty
     *
     * @param string $query
     * @param array $params
     * @return array
     */
    public function findOrganization($query, $params = array())
    {
        return $this->findParty($query, $params);
    }

    /**
     * Get current balance
     *
     * @return float
     * @throws DaDataException
     */
    public function balance()
    {
        $url = $this->apiUrl . '/profile/balance';

        $response = $this->request($url);
        if (isset($response['balance']))
            return (float)$response['balance'];
        else
            throw new DaDataException('Unexpected DaData answer.');
    }

}