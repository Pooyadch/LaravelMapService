<?php
/**
 * Created by PhpStorm.
 * User: magia
 * Date: 9/26/18
 * Time: 11:08 AM
 */

namespace Pooyadch\LaravelMapService\Http\Controllers;


use Alive2212\LaravelSmartResponse\ResponseModel;
use Alive2212\LaravelSmartResponse\SmartResponse\SmartResponse;
use Exception;
use GuzzleHttp\Client;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Input;

class LaravelMapSearchAddressController
{

    /**
     * @return mixed
     */
    public function searchAddress()
    {
        if (Cache::get('openStreetStatusCode') == 200 || !Cache::has('openStreetStatusCode')) {
            list ($responseOpenStreet, $openStreetStatus) = $this->openStreetSearchAddress();
            if (!($openStreetStatus == 'OK')) {
                $responseCedarMaps = $this->cedarMapSearchAddress();
                return SmartResponse::response($responseCedarMaps);
            } else {
                return SmartResponse::response($responseOpenStreet);
            }
        } else {
            $responseCedarMaps = $this->cedarMapSearchAddress();
            return SmartResponse::response($responseCedarMaps);

        }
    }

    /**
     * @return mixed|\Psr\Http\Message\ResponseInterface
     */
    public function cedarMapSearchAddress()
    {
        $responseCedarMaps = $this->cedarMapSearchAddressRequest();
        $lastResponse = [];
        if (!array_key_exists("status", $responseCedarMaps)) {
            $response = new ResponseModel();
            $response->setMessage('no response');
            $response->setStatus(false);
            $response->setData(collect($responseCedarMaps));
            return $response;
        }
        if ($responseCedarMaps['status'] !== 'OK') {
            $response = new ResponseModel();
            $response->setMessage('no response');
            $response->setStatus(false);
            $response->setData(collect($responseCedarMaps));
            return $response;
        }
        if ($responseCedarMaps['status'] == 'OK') {
            $lastResponse = $this->cedarMapSearchAddressResponse($responseCedarMaps);

        }
        $response = new ResponseModel();
        $response->setMessage('no response');
        $response->setStatus(false);
        $response->setData(collect($lastResponse));
        return $response;
    }

    /**
     * @return mixed|\Psr\Http\Message\ResponseInterface
     */
    public function openStreetSearchAddress()
    {
        $responseOpenStreet = $this->openStreetSearchAddressRequest();
        $lastResponse = [];
        $openStreetStatus = [];

        if (isset($responseOpenStreet[0]['place_id'])) {
            list($lastResponse,$openStreetStatus) = $this->openStreetSearchAddressResponse($responseOpenStreet);
            return [$lastResponse, $openStreetStatus];
        }
        return [$lastResponse, $openStreetStatus];

    }

    /**
     * @return array
     */
    public function cedarMapSearchAddressRequest()
    {
        try {
            $client = new Client();
            $getUrlCedarMaps = $this->cedarMapSearchAddressRequestJson();
            $responseCedarMaps = $client->get($getUrlCedarMaps);
            $responseStatusCode = $responseCedarMaps->getStatusCode();
            Cache::put('openStreetStatusCode', $responseStatusCode, 1);
            $responseCedarMaps = (json_decode($responseCedarMaps->getBody(), true));
            return $responseCedarMaps;
        } catch (Exception $e) {
            Cache::put('openStreetStatusCode', 500, 1);
            $responseCedarMaps = [];
            return $responseCedarMaps;
        }
    }

    /**
     * @param $responseCedarMaps
     * @return mixed
     */
    public function cedarMapSearchAddressResponse($responseCedarMaps)
    {
        $lastResponse = [];
        foreach ($responseCedarMaps['results'] as $responseCedarMap) {
            $coordinates = $responseCedarMap['location']['center'];
            $latitude = floatval(explode(",",$coordinates)[0]);
            $longitude = floatval(explode(",",$coordinates)[1]);
            if (array_key_exists(0, $responseCedarMap['components']['districts'])) {
                $districts = $responseCedarMap['components']['districts'][0];
            } else {
                $districts = "";
            }
            $city = $responseCedarMap['components']['city'];
            $province = $responseCedarMap['components']['province'];
            $country = $responseCedarMap['components']['country'];
            $text = $responseCedarMap['name'];
            $address = $text."," . $districts . ", " . $city . ", " . $province . ", " . $country;
            $response = [
                'keyword' => $text,
                'address' => $address,
                'coordinates' => $coordinates,
                'latitude' => $latitude,
                'longitude' => $longitude
            ];
            array_push($lastResponse, $response);
        }
        return $lastResponse;
    }

    /**
     * @return mixed
     */
    public function openStreetSearchAddressRequest()
    {
        try {
            $client = new Client();
            $getUrlOpenStreet = $this->openStreetSearchAddressRequestJson();
            $responseOpenStreet = $client->get($getUrlOpenStreet);
            $responseStatusCode = $responseOpenStreet->getStatusCode();
            Cache::put('cedarStatusCode', $responseStatusCode, 1);
            $responseOpenStreet = (json_decode($responseOpenStreet->getBody(), true));
            return $responseOpenStreet;
        } catch (Exception $e) {
            Cache::put('cedarStatusCode', 500, 1);
            $exceptionResponse = ['error_message' => $e->getMessage()];
            return $exceptionResponse;
        }
    }

    /**
     * @param $responseOpenStreet
     * @return ResponseModel|array
     */
    public function openStreetSearchAddressResponse($responseOpenStreet)
    {
        if (!isset($responseOpenStreet[0])) {
            $response = new ResponseModel();
            $response->setMessage('no response');
            $response->setStatus(false);
            $response->setData(collect($responseOpenStreet));
            $openStreetStatus = 'NOT OK';
            return [$response,$openStreetStatus];
        }
        if (!array_key_exists("place_id", $responseOpenStreet[0])){
            $response = new ResponseModel();
            $response->setMessage('not ok');
            $response->setStatus(false);
            $response->setData(collect($responseOpenStreet));
            $openStreetStatus = 'NOT OK';
            return [$response,$openStreetStatus];
        }

        $lastResponse = [];
        foreach ($responseOpenStreet as $responseOpen) {
            $latitude = $responseOpen['lat'];
            $longitude = $responseOpen['lon'];
            $address = $responseOpen['display_name'];
            $text =Input::get('keyword');
            $response = [
                'keyword' => $text,
                'address' => $address,
                'coordinates' => $latitude.",". $longitude,
                'latitude' => $latitude,
                'longitude' => $longitude
            ];
            array_push($lastResponse, $response);
        }
        $response = new ResponseModel();
        $response->setMessage('ok');
        $response->setStatus(true);
        $response->setData(collect($lastResponse));
        $openStreetStatus = 'OK';
        return [$response,$openStreetStatus];
    }

    /**
     * @return string
     */
    public function cedarMapSearchAddressRequestJson()
    {
        $accessTokenCedarMaps = "89640b6dd24953f4df81742e2bd0dffe3070c0b5";
        $searchInput = Input::get('keyword');
        $searchLimit = Input::get('Limit');
        $searchLat = Input::get('lat');
        $searchLon = Input::get('lon');
        if (is_null($searchLimit)) {
            $searchLimit = 5;
        }
        if (is_null($searchLat)) {
            $searchLat = 35.70939;
            $searchLon = 51.37743;
        }
        $getUrlCedarMaps = "https://api.cedarmaps.com/v1/geocode/cedarmaps.streets/"
            . $searchInput
            . "?location=$searchLat,$searchLon&distance=25&limit="
            . $searchLimit
            . "&access_token=$accessTokenCedarMaps";
        return $getUrlCedarMaps;
    }

    /**
     * @return string
     */
    public function openStreetSearchAddressRequestJson()
    {
        $searchInput = Input::get('keyword');
        $searchLimit = Input::get('Limit');
        if (is_null($searchLimit)) {
            $searchLimit = 5;
        }
        $getUrlOpenStreet = "https://nominatim.openstreetmap.org/search?q="
            . $searchInput . "," . "استان تهران"
            . "&format=json&addressdetails=0&limit="
            . $searchLimit
            . "&countrycodes=ir&accept-language=fa";
        return $getUrlOpenStreet;
    }


}