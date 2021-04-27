<?php
/**
 * Created by PhpStorm.
 * Date: 06.05.20
 * Time: 22:24
 * @package rockbot
 * @author  Roman Lihatskiy <rlihatskiy@determine.com>
 */

use Telegram\Bot\Api;
use Telegram\Bot\Objects\Message;
use Telegram\Bot\TelegramResponse;

class MyApi extends Api
{

    /**
     * Sends a POST request to Telegram Bot API and returns the result.
     *
     * @param string $endpoint
     * @param array  $params
     *
     * @return Message
     */
    public function sendAnyRequest($endpoint, array $params = [])
    {
        $response = $this->post($endpoint, $params);
        return new Message($response->getDecodedBody());
    }

}
