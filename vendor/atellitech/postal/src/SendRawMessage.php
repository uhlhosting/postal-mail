<?php

namespace AtelliTech\Postal;

use Exception;

/**
 * This class represets a send message of raw.
 */
class SendRawMessage
{
    /**
     * construct
     *
     * @param array<string, mixed> $attributes {rcpt_to, mail_from, data}
     */
    public function __construct(private array $attributes = [])
    {}

    /**
     * set attribute
     *
     * @param string $name
     * @param array<string>|string $value
     * @return void
     */
    public function setAttribute(string $name, array|string $value)
    {
        if (preg_match('/^(rcpt\_to|mail\_from|data)$/', $name) === false)
            throw new Exception("Not support this attribute $name");

        $this->attributes[$name] = $value;
    }

    /**
     * get attributes
     *
     * @return array<string, mixed>
     */
    public function getAttributes(): array
    {
        return $this->attributes;
    }

    /**
     * set an address into mailFrom
     *
     * @param string $address
     * @return void
     */
    public function mailFrom($address)
    {
        $this->attributes['mail_from'] = $address;
    }

    /**
     * add an address into rcptTo
     *
     * @param string $address
     * @return void
     */
    public function rcptTo(string $address)
    {
        $this->attributes['rcpt_to'][] = $address;
    }

    /**
     * set data
     *
     * @param string $data
     * @return void
     */
    public function data(string $data)
    {
        $this->attributes['data'] = base64_encode($data);
    }

    /**
     * Send
     *
     * @param Client $client
     * @return SendResult
     */
    public function send(Client $client)
    {
        $result = $client->makeRequest('send', 'raw', $this->attributes);
        return new SendResult($client, $result);
    }
}
