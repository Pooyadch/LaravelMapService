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
        if (Cache::get('cedarStatusCode') == 200) {
            $lastResponseCedarMaps = $this->cedarMapFindAddress();
            if ($lastResponseCedarMaps == null) {
                $lastResponseMapBox = $this->mapBoxFindAddress();
                $lastResponse = ['address' => $lastResponseMapBox];
            } else {
                $lastResponse = ['address' => $lastResponseCedarMaps];
            }
        } else {
            $lastResponseMapBox = $this->mapBoxFindAddress();
            $lastResponse = ['address' => $lastResponseMapBox];
        }
        return $lastResponse;
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

    public function mapBoxFindAddress()
    {
        try {
            $client = new Client();
            $accessTokenMapBox = "pk.eyJ1IjoicG9veWFkY2giLCJhIjoiY2pqZHlpNzU5NDlpbTNydW95bTJhamd3MSJ9.HBVEz296uNyAu508i4QHoQ";
            $getUrlMapBox = "https://api.mapbox.com/geocoding/v5/mapbox.places/"
                . Input::get('lon') . "," . Input::get('lat')
                . ".json" . "?limit=1&access_token=" . $accessTokenMapBox;
            $responseMapBox = $client->get($getUrlMapBox);
            $responseMapBox = (json_decode($responseMapBox->getBody(), true));
            $lastResponseMapBox = $responseMapBox['features'][0]['place_name'];
            return $lastResponseMapBox;

        } catch (Exception $e) {
            return $e->getMessage();
        }
    }


}