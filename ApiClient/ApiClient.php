<?php

require_once('../config.php');
require_once('ApiClientException.php');

class ApiClient
{

    /*
     * url that we use for api call
     */
    private $apiUrl;

    /*
     * date in unix timestamp format
     */
    private $time;

    /*
     * curl resource
     */
    private $ch;

    /*
     * export file extension
     */
    private $type;

    /*
     * temperature value
     */
    private $temperature;

    /*
     * temperature units C/F
     */
    private $temperatureConversion;

    private $requestResult;

    const API_BASE_URL = 'https://api.darksky.net/forecast';

    public function __construct($date = null)
    {
        if (isset($date)) {
            if (DateTime::createFromFormat('Y-m-d', $date) === false) {
                throw new ApiClientException("Date parameter must have 'Y-m-d' format");
            } else {
                $this->time = strtotime($date);
            }
        } else {
            $this->time = time();
        }

        $this->generateApiUrl();
    }

    private function generateApiUrl()
    {
        $this->apiUrl = self::API_BASE_URL . '/' . API_KEY . '/' . LATITUDE . ',' . LONGITUDE . ',' . $this->time . '?units=si';
    }

    public function call()
    {
        $this->ch = curl_init();
        curl_setopt_array($this->ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_URL => $this->apiUrl
        ]);
        $requestResult = curl_exec($this->ch);
        curl_close($this->ch);

        if ($requestResult === false) {
            throw new ApiClientException('Error during api request');
        } elseif (!is_object(json_decode($requestResult))) {
            throw new ApiClientException($requestResult);
        } else {
            $this->requestResult = json_decode($requestResult);
        }
    }

    public function exportToFile()
    {
        $this->getTemperature();
        if (isset($this->temperatureConversion) && $this->temperatureConversion == 'F') {
            $this->convertTemperatureToFahrenheit();
        }

        if (!isset($this->type) || $this->type == 'json') {
            $this->saveAsJSON();
        } elseif ($this->type == 'xml') {
            $this->saveAsXML();
        }
    }

    private function saveAsJSON()
    {
        header('Content-disposition: attachment; filename="api_response.json"');
        header('Content-type: application/json');
        echo json_encode([
            'date' => date('Y-m-d', $this->requestResult->daily->data[0]->time),
            'temperature' => sprintf('%+g', $this->temperature),
            'temperature_conversion' => isset($this->temperatureConversion) ? $this->temperatureConversion : 'C',
            'windSpeed' => $this->requestResult->daily->data[0]->windSpeed,
            'pressure' => $this->requestResult->daily->data[0]->pressure
        ]);
    }

    private function saveAsXML()
    {
        $xml = new SimpleXMLElement('<xml/>');

        $xml->addChild('windSpeed', $this->requestResult->daily->data[0]->windSpeed);
        $xml->addChild('temperature', sprintf('%+g', $this->temperature));
        $xml->addChild('temperature_conversion', isset($this->temperatureConversion) ? $this->temperatureConversion : 'C');
        $xml->addChild('pressure', $this->requestResult->daily->data[0]->pressure);
        $xml->addChild('date', date('Y-m-d', $this->requestResult->daily->data[0]->time));

        header('Content-disposition: attachment; filename="api_response.xml"');
        header('Content-type: text/xml');
        echo $xml->asXML();
    }

    public function setType($type)
    {
        if ($type == 'json' || $type == 'xml') {
            $this->type = $type;
        } else {
            throw new ApiClientException("Type parameter must be either 'json' or 'xml'");
        }
    }

    public function setTemperatureConversion($units)
    {
        if ($units == 'C' || $units == 'F') {
            $this->temperatureConversion = $units;
        } else {
            throw new ApiClientException("Temperature conversion parameter must be either 'C' or 'F'");
        }
    }

    /*
     * counts average daily temperature
     */
    private function getTemperature()
    {
        $this->temperature = ($this->requestResult->daily->data[0]->temperatureMin + $this->requestResult->daily->data[0]->temperatureMax) / 2;
    }

    private function convertTemperatureToFahrenheit()
    {
        $this->temperature = $this->temperature * 9 / 5 + 32;
    }

}

?>