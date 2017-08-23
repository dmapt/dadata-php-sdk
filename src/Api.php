<?php

namespace dmapt\DaData;

class Api {

    /**
     * SDK version
     */
    const VERSION = '1.0';

    /**
     * DaData api URL.
     * @var string
     */
    protected $url = 'https://dadata.ru/api/v2';

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
     * @param string $token
     * @param string $secret
     * @param string $url
     */
    public function __construct($token, $secret, $url = false)
    {
        $this->token = $token;
        $this->secret = $secret;

        if ($url)
            $this->url = $url;
    }

    /**
     * Execute a request API to DaData using cURL
     * @param string $url
     * @param array $data
     * @return mixed
     * @throws DaDataException
     */
    protected function request($url, array $data)
    {
        $curlOptions = array(
            CURLOPT_RETURNTRANSFER => true,
            CURLINFO_HEADER_OUT => true,
            CURLOPT_VERBOSE => true,
            CURLOPT_HEADER => false,
            CURLOPT_CONNECTTIMEOUT => 5,
            CURLOPT_TIMEOUT        => 5,
            CURLOPT_USERAGENT => strtolower(__CLASS__.'-PHP-SDK/v'.self::VERSION),
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => json_encode($data),
            CURLOPT_URL => $url,
            CURLOPT_HTTPHEADER => array(
                'Content-Type: application/json',
                'Authorization: Token '.$this->token,
                'X-Secret: '. $this->secret,
            )
        );

        $curl = curl_init();
        curl_setopt_array($curl, $curlOptions);
        $curlResult = curl_exec($curl);
        $curlErrorNumber = curl_errno($curl);

        if($curlErrorNumber > 0) {
            $errorMsg = 'CURL Error: '.curl_error($curl).' cURL error code: '.$curlErrorNumber;
            curl_close($curl);
            throw new DaDataException($errorMsg);
        } else
            curl_close($curl);

        $jsonResult = json_decode($curlResult, true);
        unset($curlResult);
        $jsonErrorCode = json_last_error();
        if(is_null($jsonResult) && ($jsonErrorCode != JSON_ERROR_NONE)) {
            $errorMsg = 'JSON Error: '.json_last_error_msg().' Error code: ' . $jsonErrorCode;
            throw new DaDataException($errorMsg);
        }

        return $jsonResult;
    }

    /**
     * Prepare and execute clean command. Parse result.
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

        $url = $this->url.'/clean/'.$type;
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
                print_r($data);
                if (isset($data['series']) && isset($data['number']))
                    return $data['series'] . ' ' . $data['number'];

                return false;
            };
        return $this->clean('passport', $data, $field, $response);
    }

    /**
     * Clean email.
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
        $url = $this->url.'/clean';

        $data = array(
            'structure' => $structure,
            'data' => $data
        );

        return $this->request($url, $data);

    }

}