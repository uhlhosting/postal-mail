<?php

namespace AtelliTech\Postal;

use StdClass;

/**
 * A message object
 */
class Message
{
    /**
     * construct
     *
     * @param Client $client
     * @param StdClass $attributes
     */
    public function __construct(private Client $client, private StdClass $attributes)
    {}

    /**
     * get id
     *
     * @return string
     */
    public function id(): string
    {
        return $this->attributes->id;
    }

    /**
     * get token
     *
     * @return string
     */
    public function token(): string
    {
        return $this->attributes->token;
    }
}
