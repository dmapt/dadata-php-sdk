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
     * @param array $response
     * @return array|bool
     * @throws DaDataException
     */
    public function clean($type, $data, $resultAttr, &$response)
    {
        $keys = false;
        if (!is_array($data))
            $data = array($data);
        else {
            $keys = array_keys($data);
            $data = array_values($data);
        }

        $url = $this->url.'/clean/'.$type;
        $response = $this->request($url, $data);

        if (is_array($keys)) {
            $return = array();
            $i = 0;
            foreach($keys as $key) {
                $return[$key] = false;
                if (is_callable($resultAttr))
                    $return[$key] = call_user_func($resultAttr, $response);
                elseif (isset($response[$i][$resultAttr]))
                    $return[$key] = $response[$i][$resultAttr];

                $i++;
            }

            return $return;
        } else
            if (isset($response[$resultAttr]))
                return $response[$resultAttr];

        return false;
    }

    /**
     * Clean name.
     * @param string|mixed $data
     * @param array $response
     * @return string|bool
     * @throws DaDataException
     */
    public function cleanName($data, &$response = false)
    {
        return $this->clean('name', $data, 'result', $response);
    }

    /**
     * Clean phone.
     * @param string|mixed $data
     * @param array $response
     * @return string|bool
     * @throws DaDataException
     */
    public function cleanPhone($data, &$response = false)
    {
        return $this->clean('phone', $data, 'phone', $response);
    }

    /**
     * Clean passport.
     * @param string|mixed $data
     * @param array $response
     * @return string|bool
     * @throws DaDataException
     */
    public function cleanPassport($data, &$response = false)
    {
        /*return $this->clean('passport', $data, function($response) {
            if (isset($response['series']) && isset($response['number']))
                return $response['series'].' '.$response['number'];

            return false;
        }, $response);*/
        return $this->clean('passport', $data, 'series', $response);
    }

    /**
     * Clean email.
     * @param string|mixed $data
     * @param array $response
     * @return string|bool
     * @throws DaDataException
     */
    public function cleanEmail($data, &$response = false)
    {
        return $this->clean('email', $data, 'email', $response);
    }

    /**
     * Clean birthdate.
     * @param string|mixed $data
     * @param array $response
     * @return string|bool
     * @throws DaDataException
     */
    public function cleanBirthdate($data, &$response = false)
    {
        return $this->clean('birthdate', $data, 'birthdate', $response);
    }

    /**
     * Clean vehicle.
     * @param string|mixed $data
     * @param array $response
     * @return string|bool
     * @throws DaDataException
     */
    public function cleanVehicle($data, &$response = false)
    {
        return $this->clean('vehicle', $data, 'vehicle', $response);
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