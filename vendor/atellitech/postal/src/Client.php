<?php

namespace AtelliTech\Postal;

use Exception;
use StdClass;
use WpOrg\Requests\Requests;

/**
 * http client of Postal
 */
class Client
{
    /**
     * construct
     *
     * @param string $host
     * @param string $serverKey
     */
    public function __construct(private string $host, private string $serverKey)
    {}

    /**
     * make request
     *
     * @param string $controller
     * @param string $action
     * @param array<string, mixed> $parameters
     * @return StdClass
     */
    public function makeRequest(string $controller, string $action, array $parameters): StdClass
    {
        $url = sprintf('%s/api/v1/%s/%s', $this->host, $controller, $action);

        // Headers
        $headers = [
            'x-server-api-key' => $this->serverKey,
            'content-type' => 'application/json',
        ];

        // Make the body
        $json = json_encode($parameters);

        // Make the request
        $response = Requests::post($url, $headers, $json);

        if ($response->status_code === 200) {
            $json = json_decode($response->body);

            if ($json->status == 'success') {
                return $json->data;
            } else {
                if (isset($json->data->code)) {
                    throw new Exception(sprintf('[%s] %s', $json->data->code, $json->data->message));
                } else {
                    throw new Exception($json->data->message);
                }
            }
        }

        throw new Exception('Couldn’t send message to API');
    }
}
