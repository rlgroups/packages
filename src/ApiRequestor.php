<?php

namespace Ecomws;

use FluidXml\FluidXml;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;

trait ApiRequestor {


    // getResponse

    // getRequest (bulidXml, toXml, toArray)

    // request {
    // $xml = $this->getRequest
    // retrn getResponse($xml)
    // }
    public function request()
    {
        return $this->getRequest($this);
    }

    public function getRequest($params)
    {
        $action = $this->endPoint;

        $host = static::$apiHost;

        $apiBase = static::$apiBase;

        $baseUrl = "https://{$host}/{$apiBase}/{$this->endPoint}";
        
        $xmlRequest = $this->bulidXml($action, $params->jsonSerialize());

        return $this->getResponse($xmlRequest);
    }

    public function getResponse($xmlRequest)
    {
        $http = new Client([
            // 'curl' => [CURLOPT_SSL_VERIFYPEER => false],
            // 'verify' => false,
            // 'timeout' => 10000,
            // 'proxy' => '127.0.0.1:8888',
        ]);

        $host = static::$apiHost;

        $apiBase = static::$apiBase;

        $baseUrl = "http://{$host}/{$apiBase}?op={$this->endPoint}";

        $status = 1;

        try {
            $responseData = $http->request('POST', $baseUrl, [
                'body' => $xmlRequest,
                'headers' => [
                    "Content-Type" => "application/soap+xml",
                    // "Content-Length" => "length",
                    "Host" => $host,
                ]
            ]);

         } catch (RequestException $e) {
            $status = 0;
            //dd($xmlRequest);
            $responseData = [
                'RequestException' => $e->getRequest()
            ];
        }

        if ($responseData && $status) {
            $xmlResponse = (string) $responseData->getBody();

            // log
            // return dd([
            //     $xmlRequest, $xmlResponse
            // ]);

            // print_r($xmlRequest);
            // exit;

            // if (static::$log || 1) {
            //     return [
            //         $xmlRequest, $xmlResponse
            //     ];
            // }

            return $this->mapResponse(
                $this->xmlToArray($xmlResponse)
            );
        }

        return [
            'status' => '0'
        ];
    }

    public function mapResponse($array)
    {
        if (
            isset($array['soap:Body'])
            && isset($array['soap:Body']["{$this->endPoint}Response"])
            && isset($array['soap:Body']["{$this->endPoint}Response"]["{$this->endPoint}Result"])
        ) {
            $data = $array['soap:Body']["{$this->endPoint}Response"]["{$this->endPoint}Result"];

            $status = isset($data['Status']) ? $data['Status'] : '3';
            
            return [
                'status' => $status,
                'data' => $array['soap:Body']["{$this->endPoint}Response"]["{$this->endPoint}Result"],
                'all_data' => $array['soap:Body']
            ];
        } else {
            return [
                'status' => '2'
            ];
        }
    }

    public function getErrorReponse($response)
    {
        $error = $response['@attributes'];
        $error['error'] = $response['error']['@content'];
        $error['error_code'] = $response['error']['@attributes']['code'];

        if (isset($error['booking-options'])) {
            $error['booking-options'] = $response['booking-options'] ?? null;
        }

        return $error;
    }

    public function toXml()
    {
        $xml = new FluidXml('request');

        $xml->attr(['version' => self::$apiVersion])->add($this->toArray());


        return $xml;
    }

    /**
     * Convert the instance to JSON.
     *
     * @param  int  $options
     * @return string
     */
    public function toJson($options = 0)
    {
        $json = json_encode($this->jsonSerialize(), $options);

        return $json;
    }

    /**
     * Convert the object into something JSON serializable.
     *
     * @return array
     */
    public function jsonSerialize()
    {
        //dd($this->toArray());
        return $this->toArray();

    }

    public function outputLog($xmlRequest, $xmlResponse)
    {
        $log = [
            'uuid' => self::$uuid ?? null,
            'called' => get_called_class(),
            'request' => (string) $xmlRequest,
            'response' => (string) $xmlResponse
        ];

        // Log::create($log);
    }

    function xmlToArray($xml) {
        $doc = new \DOMDocument();
        $doc->loadXML($xml);
        $root = $doc->documentElement;
        $output = $this->nodeToArray($root);
        $output['@root'] = $root->tagName;

        return $output;
    }

    function nodeToArray($node) {
        $output = [];

        switch ($node->nodeType) {
            case XML_CDATA_SECTION_NODE:
            case XML_TEXT_NODE:
                $output = trim($node->textContent);
                break;

            case XML_ELEMENT_NODE:
                for ($i=0, $m=$node->childNodes->length; $i<$m; $i++) {
                    $child = $node->childNodes->item($i);
                    $v = $this->nodeToArray($child);

                    if(isset($child->tagName)) {
                        $t = $child->tagName;

                        if(!isset($output[$t])) {
                            $output[$t] = [];
                        }

                        $output[$t][] = $v;
                    } elseif($v || $v === '0') {
                        $output = (string) $v;
                    }
                }

                if($node->attributes->length && !is_array($output)) { //Has attributes but isn't an array
                    $output = array('@content'=>$output); //Change output into an array.
                }

                if(is_array($output)) {
                    if($node->attributes->length) {
                        $a = [];

                        foreach($node->attributes as $attrName => $attrNode) {
                            $a[$attrName] = (string) $attrNode->value;
                        }

                        $output['@attributes'] = $a;
                    }

                    foreach ($output as $t => $v) {
                        if(is_array($v) && count($v)==1 && $t!='@attributes') {
                            $output[$t] = $v[0];
                        }
                    }
                }
                break;
        }

        return $output;
    }

    public function bulidXml($action, $params) {
        return "<?xml version=\"1.0\" encoding=\"utf-8\"?>
<soap12:Envelope xmlns:xsi=\"http://www.w3.org/2001/XMLSchema-instance\" xmlns:xsd=\"http://www.w3.org/2001/XMLSchema\" xmlns:soap12=\"http://www.w3.org/2003/05/soap-envelope\">
    <soap12:Body>
        <$action xmlns=\"http://tempuri.org/\">
            ".
            $this->splitArrayToNodes($params)
            ."
        </$action>
    </soap12:Body>
</soap12:Envelope>
        ";
    }

    public function splitArrayToNodes($array, $r = 1)
    {
        if (is_array($array)) {
            return implode('', collect($array)->map(function ($v, $k) use ($r) {
                $node = is_numeric($k) ? $k[0] : $k;
                if ($node != '') {
                    return "<$node>{$this->splitArrayToNodes($v, $r+1)}</$node>";
                } else {
                    return "{$this->splitArrayToNodes($v, $r+1)}";
                }
            })->toArray());
        }

        return $array;
    }

    public function logDb($xmlRequest, $response)
    {
        //
    }
}
