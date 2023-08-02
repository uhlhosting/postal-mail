<?php

namespace AtelliTech\Postal;

use Exception;

/**
 * This class represets a send message.
 */
class SendMessage
{
    /**
     * construct
     *
     * @param array<string, mixed> $attributes {to, cc, bcc, headers, attachments, from, sender, subject, tag, reply_to, plain_body, html_body}
     */
    public function __construct(private array $attributes = [])
    {}

    /**
     * set attribute
     *
     * @param string $name
     * @param array<mixed>|string $value
     * @return void
     */
    public function setAttribute(string $name, array|string $value)
    {
        if (preg_match('/^(to|cc|bcc|headers|attachments|from|sender|subject|tag|reply\_to|plain\_body|html\_body)$/', $name) === false)
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
     * add an address into to
     *
     * @param string $address
     * @return void
     */
    public function to(string $address)
    {
        $this->attributes['to'][] = $address;
    }

    /**
     * add an address into cc
     *
     * @param string $address
     * @return void
     */
    public function cc(string $address)
    {
        $this->attributes['cc'][] = $address;
    }

    /**
     * add an address into bcc
     *
     * @param string $address
     * @return void
     */
    public function bcc($address)
    {
        $this->attributes['bcc'][] = $address;
    }

    /**
     * set from
     *
     * @param string $address
     * @return void
     */
    public function from($address)
    {
        $this->attributes['from'] = $address;
    }

    /**
     * set sender
     *
     * @param string $address
     * @return void
     */
    public function sender(string $address)
    {
        $this->attributes['sender'] = $address;
    }

    /**
     * set subject
     *
     * @param string $subject
     * @return void
     */
    public function subject($subject)
    {
        $this->attributes['subject'] = $subject;
    }

    /**
     * set tag
     *
     * @param string $tag
     * @return void
     */
    public function tag(string $tag)
    {
        $this->attributes['tag'] = $tag;
    }

    /**
     * set reply to
     *
     * @param string $replyTo
     * @return void
     */
    public function replyTo(string $replyTo)
    {
        $this->attributes['reply_to'] = $replyTo;
    }

    /**
     * set plain text body
     *
     * @param string $content
     * @return void
     */
    public function plainBody(string $content)
    {
        $this->attributes['plain_body'] = $content;
    }

    /**
     * set html body
     *
     * @param string $content
     * @return void
     */
    public function htmlBody(string $content)
    {
        $this->attributes['html_body'] = $content;
    }

    /**
     * set header
     *
     * @param string $key
     * @param string $value;
     * @return void
     */
    public function header(string $key, string $value)
    {
        $this->attributes['headers'][$key] = $value;
    }

    /**
     * attach file
     *
     * @param string $filename
     * @param string $contentType
     * @param string $data
     * @return void
     */
    public function attach(string $filename, string $contentType, string $data)
    {
        $attachment = [
            'name' => $filename,
            'content_type' => $contentType,
            'data' => base64_encode($data),
        ];

        $this->attributes['attachments'][] = $attachment;
    }

    /**
     * Send
     *
     * @param Client $client
     * @return SendResult
     */
    public function send(Client $client)
    {
        $result = $client->makeRequest('send', 'message', $this->attributes);
        return new SendResult($client, $result);
    }
}
