<?php

namespace Unetway\Atol;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\ClientException;
use Exception;

class Atol
{
    /**
     * @var string
     */
    private string $base_uri = 'https://online.atol.ru';

    /**
     * @var string
     */
    private string $test_uri = 'https://testonline.atol.ru';

    /**
     * @var mixed|null
     */
    private $login;

    /**
     * @var mixed|null
     */
    private $password;

    /**
     * @var mixed|null
     */
    private $group_code;

    /**
     * @var string
     */
    private string $version = 'v4';

    /**
     * @var Client
     */
    private Client $client;

    /**
     * @var string
     */
    private string $callback_url;

    /**
     * @var string
     */
    private $company_email;

    /**
     * @var string
     */
    private $sno;

    /**
     * @var string
     */
    private $inn;

    /**
     * @var string
     */
    private $payment_address;

    /**
     * @var int
     */
    private int $quantity = 1;

    /**
     * @var string
     */
    private string $vat = 'vat20';

    /**
     * @var string
     */
    private string $payment_method = 'full_payment';

    /**
     * @var string
     */
    private string $payment_object = 'service';

    /**
     * Atol constructor.
     * @param $params
     * @throws Exception
     */
    public function __construct($params)
    {
        if (empty($params)) {
            throw new Exception('Config is not defined');
        }

        if (!empty($params['is_test']) && $params['is_test'] === true) {
            $this->base_uri = $this->test_uri;
        }

        if (empty($params['login'])) {
            throw new Exception('Param login is not defined');
        }

        if (empty($params['password'])) {
            throw new Exception('Param password is not defined');
        }

        if (empty($params['group_code'])) {
            throw new Exception('Param group_code is not defined');
        }

        if (empty($params['company_email'])) {
            throw new Exception('Param company_email is not defined');
        }

        if (empty($params['sno'])) {
            throw new Exception('Param sno is not defined');
        }

        if (empty($params['inn'])) {
            throw new Exception('Param inn is not defined');
        }

        if (empty($params['payment_address'])) {
            throw new Exception('Param payment_address is not defined');
        }

        if (empty($params['callback_url'])) {
            throw new Exception('Param callback_url is not defined');
        }

        $this->login = $params['login'];
        $this->password = $params['password'];
        $this->group_code = $params['group_code'];
        $this->company_email = $params['company_email'];
        $this->sno = $params['sno'];
        $this->inn = $params['inn'];
        $this->payment_address = $params['payment_address'];
        $this->callback_url = $params['callback_url'];

        if (!empty($params['vat'])) {
            $this->vat = $params['vat'];
        }

        $this->client = new Client([
            'base_uri' => $this->base_uri . '/possystem/' . $this->getVersion() . '/',
            'headers' => [
                'Content-Type' => 'application/json; charset=utf-8',
                'Token' => $this->getToken(),
            ]
        ]);
    }

    /**
     * @return mixed|null
     */
    private function getToken()
    {
        $client = new Client([
            'base_uri' => $this->base_uri . '/possystem/' . $this->getVersion() . '/',
        ]);

        try {
            $response = $client->post('getToken', [
                'json' => [
                    'login' => $this->login,
                    'pass' => $this->password,
                ]
            ]);

            if ($response->getStatusCode() === 200) {
                $content = $response->getBody()->getContents();
                $res = json_decode($content, true);

                if (is_null($res['error'])) {
                    return $res['token'];
                }
            }
        } catch (ClientException $exception) {
        }

        return null;
    }

    /**
     * @param string $name
     * @param float $price
     * @param string $email
     * @return mixed
     * @throws Exception
     */
    public function receipt(string $name, float $price, string $email)
    {
        if (empty($name)) {
            throw new Exception('Param name is not defined');
        }

        if (empty($price)) {
            throw new Exception('Param price is not defined');
        }

        if (empty($email)) {
            throw new Exception('Param email is not defined');
        }

        try {
            $url = $this->getGroupCode() . '/sell';

            $response = $this->client->post($url, [
                'json' => $this->getParams($name, $price, $email)
            ]);

            if ($response->getStatusCode() === 200) {
                $content = $response->getBody()->getContents();
                return json_decode($content, true);
            }
        } catch (ClientException $exception) {
            $response = $exception->getResponse();
            $content = $response->getBody()->getContents();
            return json_decode($content, true);
        }
    }

    /**
     * @param string $uuid
     * @return mixed
     */
    public function report(string $uuid)
    {
        try {
            $url = $this->getGroupCode() . '/report/' . $uuid;
            $response = $this->client->get($url);

            if ($response->getStatusCode() === 200) {
                $content = $response->getBody()->getContents();
                return json_decode($content, true);
            }
        } catch (ClientException $exception) {
            $response = $exception->getResponse();
            $content = $response->getBody()->getContents();
            return json_decode($content, true);
        }
    }

    /**
     * @param $name
     * @param $price
     * @param $email
     * @return array
     */
    private function getParams($name, $price, $email): array
    {
        return [
            'external_id' => $this->getUuid(),
            'receipt' => [
                'client' => [
                    'email' => $email
                ],
                'company' => [
                    'email' => $this->getCompanyEmail(),
                    'sno' => $this->getSno(),
                    'inn' => $this->getInn(),
                    'payment_address' => $this->getPaymentAddress()
                ],
                'items' => [
                    [
                        'name' => $name,
                        'price' => $price,
                        'quantity' => $this->getQuantity(),
                        'sum' => $price,
                        'payment_method' => $this->getPaymentMethod(),
                        'payment_object' => $this->getPaymentObject(),
                        'vat' => [
                            'type' => $this->getVat()
                        ]
                    ],
                ],
                'payments' => [
                    [
                        'type' => 1,
                        'sum' => $price
                    ]
                ],
                'vats' => [
                    [
                        'type' => $this->getVat(),
                        'sum' => $price
                    ]
                ],
                'total' => $price
            ],
            'service' => [
                'callback_url' => $this->getCallbackUrl()
            ],
            'timestamp' => date('d.m.Y H:i:s')
        ];
    }

    /**
     * @return string
     */
    private function getVersion(): string
    {
        return $this->version;
    }

    /**
     * @return mixed|null
     */
    private function getGroupCode()
    {
        return $this->group_code;
    }

    /**
     * @return string
     */
    private function getCallbackUrl(): string
    {
        return $this->callback_url;
    }

    /**
     * @return string
     */
    private function getUuid(): string
    {
        return sprintf('%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
            // 32 bits for "time_low"
            mt_rand(0, 0xffff), mt_rand(0, 0xffff),
            // 16 bits for "time_mid"
            mt_rand(0, 0xffff),
            // 16 bits for "time_hi_and_version",
            // four most significant bits holds version number 4
            mt_rand(0, 0x0fff) | 0x4000,
            // 16 bits, 8 bits for "clk_seq_hi_res",
            // 8 bits for "clk_seq_low",
            // two most significant bits holds zero and one for variant DCE1.1
            mt_rand(0, 0x3fff) | 0x8000,
            // 48 bits for "node"
            mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff)
        );
    }

    /**
     * @return mixed
     */
    private function getCompanyEmail(): string
    {
        return $this->company_email;
    }

    /**
     * @return mixed
     */
    private function getSno(): string
    {
        return $this->sno;
    }

    /**
     * @return mixed
     */
    private function getInn(): string
    {
        return $this->inn;
    }

    /**
     * @return mixed
     */
    private function getPaymentAddress(): string
    {
        return $this->payment_address;
    }

    /**
     * @return mixed
     */
    private function getQuantity(): int
    {
        return $this->quantity;
    }

    /**
     * @return mixed
     */
    private function getVat(): string
    {
        return $this->vat;
    }

    /**
     * @return string
     */
    private function getPaymentObject(): string
    {
        return $this->payment_object;
    }

    /**
     * @return string
     */
    private function getPaymentMethod(): string
    {
        return $this->payment_method;
    }

}