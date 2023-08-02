<?php

namespace AtelliTech\Postal;

use StdClass;

/**
 * This is a result object of sent message.
 */
class SendResult
{
    /**
     * @var Message[] $recipients
     */
    protected $recipients;

    public function __construct(private Client $client, private StdClass $result)
    {}

    /**
     * get recipients
     *
     * @return Message[]
     */
    public function recipients()
    {
        if ($this->recipients != null) {
            return $this->recipients;
        } else {
            $this->recipients = [];

            foreach ($this->result->messages as $key => $value) {
                $this->recipients[strtolower($key)] = new Message($this->client, $value);
            }

            return $this->recipients;
        }
    }

    /**
     * get recipients size
     *
     * @return int
     */
    public function size()
    {
        return count($this->recipients());
    }
}
