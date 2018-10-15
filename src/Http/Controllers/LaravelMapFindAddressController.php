<?php

namespace Pooyadch\LaravelMapService\Http\Controllers;

use Alive2212\LaravelSmartResponse\ResponseModel;
use Alive2212\LaravelSmartResponse\SmartResponse\SmartResponse;
use Exception;
use GuzzleHttp\Client;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Input;

class LaravelMapFindAddressController
{
    /**
     * @return \Illuminate\Http\JsonResponse
     */
    public function findAddress()
    {
        $lastResponse = $this->FindAddressLastResponse();
        $response = new ResponseModel();
        $response->setMessage('ok');
        $response->setStatus(true);
        $response->setData(collect($lastResponse));
        return SmartResponse::response($response);
    }

    /**
     * @return array
     */
    public function FindAddressLastResponse()
    {
        if (Cache::get('openStreetStatusCode') == 200 || !Cache::has('openStreetStatusCode')) {
            list ($responseOpenStreet, $openStreetStatus) = $this->openStreetFindAddress();
            if (!($openStreetStatus == 'OK')) {
                $responseCedarMaps = $this->cedarMapFindAddress();
                $lastResponse = ["address" => $responseCedarMaps];
                return $lastResponse;
            } else {
                $lastResponse = ["address" => $responseOpenStreet];
                return $lastResponse;
            }
        } else {
            $responseCedarMaps = $this->cedarMapFindAddress();
            $lastResponse = ["address" => $responseCedarMaps];
            return $lastResponse;

        }
    }


    /**
     * @return string
     */
    public function cedarMapFindAddress()
    {
        $responseCedarMaps = $this->cedarMapFindAddressRequest();
        if (!array_key_exists("status", $responseCedarMaps)) {
            return null;
        }
        if ($responseCedarMaps['status'] !== 'OK') {
            return null;
        }
        $address = $responseCedarMaps['result']['address'];
        $locality = $responseCedarMaps['result']['locality'];
        $district = $responseCedarMaps['result']['district'];
        $place = $responseCedarMaps['result']['place'];
        $city = $responseCedarMaps['result']['city'];
        $province = $responseCedarMaps['result']['province'];
        $country = $responseCedarMaps['result']['country'];
        $lastResponseCedarMaps = $country . ", " . $province . ", " . $city . ", " . $place . ", " . $district . ", " . $locality . ", " . $address;
        return $lastResponseCedarMaps;

    }

    /**
     * @return mixed|\Psr\Http\Message\ResponseInterface
     */
    public function cedarMapFindAddressRequest()
    {
        try {
            $client = new Client();
            $accessTokenCedarMaps = "89640b6dd24953f4df81742e2bd0dffe3070c0b5";
            $getUrlCedarMaps = "https://api.cedarmaps.com/v1/geocode/cedarmaps.streets/"
                . Input::get('lat') . ","
                . Input::get('lon') . ".json?access_token=$accessTokenCedarMaps";
            $responseCedarMaps = $client->get($getUrlCedarMaps);
            $responseStatusCode = $responseCedarMaps->getStatusCode();
            Cache::put('cedarStatusCode', $responseStatusCode, 1);
            $responseCedarMaps = (json_decode($responseCedarMaps->getBody(), true));
            return $responseCedarMaps;
        } catch (Exception $e) {
            Cache::put('cedarStatusCode', 500, 1);
            $responseCedarMaps = [];
            return $responseCedarMaps;

        }
    }

    /**
     * @return array
     */
    public function openStreetFindAddress()
    {
        try {
            $client = new Client();
            $getUrlOpenStreet = "https://nominatim.openstreetmap.org/reverse?format=json&lat="
                . Input::get('lat') . "&lon=" . Input::get('lon')
                . "&zoom=18&addressdetails=1&accept-language=fa";
            $responseOpenStreets = $client->get($getUrlOpenStreet);
            $responseStatusCode = $responseOpenStreets->getStatusCode();
            $responseOpenStreets = (json_decode($responseOpenStreets->getBody(), true));
            if (isset($responseOpenStreets['display_name'])) {
                list($openStreetStatus, $lastResponseOpenStreet) = $this->openStreetFindAddressResponse($responseStatusCode, $responseOpenStreets);
                return [$lastResponseOpenStreet, $openStreetStatus];
            }
            return [$lastResponseOpenStreet = null, $openStreetStatus = null];

        } catch (Exception $e) {
            Cache::put('openStreetStatusCode', 500, 1);
            return [$lastResponseOpenStreet = null, $openStreetStatus = null];
        }
    }

    /**
     * @param $responseStatusCode
     * @param $responseOpenStreets
     * @return array
     */
    public function openStreetFindAddressResponse($responseStatusCode, $responseOpenStreets)
    {
        Cache::put('openStreetStatusCode', $responseStatusCode, 1);
        $openStreetStatus = 'OK';
        $lastResponseOpenStreet = null;
        $responseOpenStreets = $responseOpenStreets['address'];
        $offsetKey = 'city';
        $n = array_keys($responseOpenStreets);
        $count = array_search($offsetKey, $n);
        $responseOpenStreets = array_slice($responseOpenStreets, 0, $count + 1, true);
        foreach ($responseOpenStreets as $responseOpenStreet) {
            $lastResponseOpenStreet = $lastResponseOpenStreet . "," . $responseOpenStreet;
        }
        return array($openStreetStatus, $lastResponseOpenStreet);
    }


}