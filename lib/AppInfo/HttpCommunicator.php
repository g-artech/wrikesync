<?php


namespace OCA\WrikeSync\AppInfo;


class HttpCommunicator
{

    public static function getHttpResponseCode($http_response_header) {
        if(is_array($http_response_header))
        {
            $parts=explode(' ',$http_response_header[0]);
            if(count($parts)>1) //HTTP/1.0 <code> <text>
                return intval($parts[1]); //Get code
        }

        return 0;
    }

    public static function doHttpRequest(string $url, $context) {
        $retryCount = 0;
        $statusCode = 0;

        do {
            $retryCount++;

            $rawJson = file_get_contents($url, false, $context);
            $statusCode = self::getHttpResponseCode($http_response_header);

            if ($statusCode == 200) {
                return json_decode($rawJson);
            } else {
                //If we have more than 200 requests a minute we get a HTTP 429
                if ($statusCode == 429) {
                    //Sleep 10 seconds if any error occurs.
                    sleep(10);
                }
            }
        } while ($retryCount < 5 && $statusCode != 200);

        return null;
    }

}