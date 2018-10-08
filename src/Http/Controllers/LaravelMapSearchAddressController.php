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
        if (Cache::get('cedarStatusCode') == 200 || Cache::get('cedarStatusCode') == null) {
            list($responseCedarMaps, $cedarMapStatus) = $this->cedarMapSearchAddress();
            if (!($cedarMapStatus == 'OK')) {
                $responseMapBox = $this->mapBoxSearchAddress();
                return SmartResponse::response($responseMapBox);
            } else {
                $response = new ResponseModel();
                $response->setMessage('ok');
                $response->setStatus(true);
                $response->setData(collect($responseCedarMaps));
                return SmartResponse::response($response);
            }
        } else {
            $responseMapBox = $this->mapBoxSearchAddress();
            return SmartResponse::response($responseMapBox);
        }
    }

    /**
     * @return mixed|\Psr\Http\Message\ResponseInterface
     */
    public function cedarMapSearchAddress()
    {
        $responseCedarMaps = $this->cedarMapSearchAddressRequest();
        $lastResponse = [];
        $cedarMapStatus = [];
        if (!array_key_exists("status", $responseCedarMaps)) {
            return [$lastResponse, $cedarMapStatus];
        }
        if ($responseCedarMaps['status'] !== 'OK') {
            return [$lastResponse, $cedarMapStatus];
        }
        if ($responseCedarMaps['status'] == 'OK') {
            $cedarMapStatus = $responseCedarMaps['status'];
            $lastResponse = $this->cedarMapSearchAddressResponse($responseCedarMaps);
        }
        return [$lastResponse, $cedarMapStatus];
    }

    /**
     * @return mixed|\Psr\Http\Message\ResponseInterface
     */
    public function mapBoxSearchAddress()
    {
        $responseMapBoxes = $this->mapBoxSearchAddressRequest();
        $response = $this->mapBoxSearchAddressResponse($responseMapBoxes);
        return $response;
    }

    /**
     * @return array
     */
    public function cedarMapSearchAddressRequest()
    {
        try {
//            dd(123);
            $client = new Client();
            $getUrlCedarMaps = $this->cedarMapSearchAddressRequestJson();
            $responseCedarMaps = $client->get($getUrlCedarMaps);
            $responseStatusCode = $responseCedarMaps->getStatusCode();
            Cache::put('cedarStatusCode', $responseStatusCode, 1);
            Cache::put('cedarStatusCode', 500, 1);
            $responseCedarMaps = (json_decode($responseCedarMaps->getBody(), true));
            return $responseCedarMaps;
        } catch (Exception $e) {
            Cache::put('cedarStatusCode', 500, 1);
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
            $longitude = floatval(explode(",",$coordinates)[0]);
            if (array_key_exists(0, $responseCedarMap['components']['districts'])) {
                $districts = $responseCedarMap['components']['districts'][0];
            } else {
                $districts = "";
            }
            $city = $responseCedarMap['components']['city'];
            $province = $responseCedarMap['components']['province'];
            $country = $responseCedarMap['components']['country'];
            $address = $country . ", " . $province . ", " . $city . ", " . $districts;
            $text = $responseCedarMap['name'];
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
    public function mapBoxSearchAddressRequest()
    {
        try {
            $client = new Client();
            $accessTokenMapBox = "pk.eyJ1IjoicG9veWFkY2giLCJhIjoiY2pqZHlpNzU5NDlpbTNydW95bTJhamd3MSJ9.HBVEz296uNyAu508i4QHoQ";
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
            $getUrlMapBox = "https://api.mapbox.com/geocoding/v5/mapbox.places/"
                . $searchInput
                . ".json?country=IR&proximity=$searchLon,$searchLat&limit="
                . $searchLimit
                . "&access_token="
                . $accessTokenMapBox;
            $responseMapBox = $client->get($getUrlMapBox);
            $responseMapBoxes = (json_decode($responseMapBox->getBody(), true));
            return $responseMapBoxes;
        } catch (Exception $e) {
            $exceptionResponse = ['error_message' => $e->getMessage()];
            return $exceptionResponse;
        }
    }

    /**
     * @param $responseMapBoxes
     * @return ResponseModel|array
     */
    public function mapBoxSearchAddressResponse($responseMapBoxes)
    {
        if (!array_key_exists("features", $responseMapBoxes)){
            $response = new ResponseModel();
            $response->setMessage('not ok');
            $response->setStatus(false);
            $response->setData(collect($responseMapBoxes));
            return $response;
        }
        $lastResponse = [];
        foreach ($responseMapBoxes['features'] as $responseMap) {
            $coordinates = implode(',', $responseMap['center']);
            $latitude = $responseMap['center'][0];
            $longitude = $responseMap['center'][1];
            $address = $responseMap['place_name'];
            $text = $responseMap['text'];
            $response = [
                'keyword' => $text,
                'address' => $address,
                'coordinates' => $coordinates,
                'latitude' => $latitude,
                'longitude' => $longitude
            ];
            array_push($lastResponse, $response);
        }
        $response = new ResponseModel();
        $response->setMessage('ok');
        $response->setStatus(true);
        $response->setData(collect($lastResponse));
        return $response;
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

}