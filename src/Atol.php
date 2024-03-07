<?php

namespace Unetway\Atol;

use Exception;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\ClientException;

class Atol
{
    /**
     * @var string
     */
    private string $baseUri = 'https://online.atol.ru';

    /**
     * @var string
     */
    private string $testUri = 'https://testonline.atol.ru';

    /**
     * @var string
     */
    private $login;

    /**
     * @var string
     */
    private $password;

    /**
     * @var string
     */
    private $groupCode;

    /**
     * @var string
     */
    private string $version = 'v4';

    /**
     * @var Client $client
     */
    private Client $client;

    /**
     * @var string
     */
    private string $callbackUrl;

    /**
     * @var string
     */
    private string $companyEmail;

    /**
     * @var string
     */
    private string $sno;

    /**
     * @var string
     */
    private string $inn;

    /**
     * @var string
     */
    private string $paymentAddress;

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
    private string $paymentMethod = 'full_payment';

    /**
     * @var string
     */
    private string $paymentObject = 'service';

    /**
     * Atol constructor.
     * @param $params
     * @throws Exception
     */
    public function __construct($params)
    {
        $this->validateParams($params);

        if (!empty($params['is_test']) && $params['is_test'] === true) {
            $this->baseUri = $this->testUri;
        }

        $this->login = $params['login'];
        $this->password = $params['password'];
        $this->groupCode = $params['group_code'];
        $this->companyEmail = $params['company_email'];
        $this->sno = $params['sno'];
        $this->inn = $params['inn'];
        $this->paymentAddress = $params['payment_address'];
        $this->callbackUrl = $params['callback_url'];

        if (!empty($params['vat'])) {
            $this->vat = $params['vat'];
        }

        $this->client = new Client([
            'base_uri' => $this->baseUri . '/possystem/' . $this->getVersion() . '/',
            'headers' => [
                'Content-Type' => 'application/json; charset=utf-8',
                'Token' => $this->getToken(),
            ]
        ]);
    }

    /**
     * @param array $params
     * @throws Exception
     */
    private function validateParams(array $params): void
    {
        $requiredParams = [
            'login',
            'password',
            'group_code',
            'company_email',
            'sno',
            'inn',
            'payment_address',
            'callback_url',
        ];

        foreach ($requiredParams as $param) {
            if (empty($params[$param])) {
                throw new Exception("Param $param is not defined");
            }
        }
    }

    /**
     * @return string
     */
    private function getVersion(): string
    {
        return $this->version;
    }

    /**
     * @return string
     * @throws Exception
     */
    private function getToken(): string
    {
        $client = new Client([
            'base_uri' => $this->baseUri . '/possystem/' . $this->getVersion() . '/',
        ]);

        try {
            $response = $client->post('getToken', [
                'json' => [
                    'login' => $this->login,
                    'pass' => $this->password,
                ]
            ]);

            $content = $response->getBody()->getContents();
            $response = json_decode($content, true);

            return $response['token'];

        } catch (ClientException $exception) {
            throw new Exception($exception);
        }
    }

    /**
     * @param string $name
     * @param float $price
     * @param string $email
     * @return array
     * @throws Exception
     */
    public function receipt(string $name, float $price, string $email): array
    {
        try {
            $url = $this->getGroupCode() . '/sell';

            $response = $this->client->post($url, [
                'json' => $this->getParams($name, $price, $email)
            ]);

            $content = $response->getBody()->getContents();

            return json_decode($content, true);
        } catch (ClientException $exception) {
            throw new Exception($exception);
        }
    }

    /**
     * @return string
     */
    private function getGroupCode(): string
    {
        return $this->groupCode;
    }

    /**
     * @param string $name
     * @param float $price
     * @param string $email
     * @return array
     */
    private function getParams(string $name, float $price, string $email): array
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
     * @return string
     */
    private function getCompanyEmail(): string
    {
        return $this->companyEmail;
    }

    /**
     * @return string
     */
    private function getSno(): string
    {
        return $this->sno;
    }

    /**
     * @return string
     */
    private function getInn(): string
    {
        return $this->inn;
    }

    /**
     * @return string
     */
    private function getPaymentAddress(): string
    {
        return $this->paymentAddress;
    }

    /**
     * @return int
     */
    private function getQuantity(): int
    {
        return $this->quantity;
    }

    /**
     * @return string
     */
    private function getPaymentMethod(): string
    {
        return $this->paymentMethod;
    }

    /**
     * @return string
     */
    private function getPaymentObject(): string
    {
        return $this->paymentObject;
    }

    /**
     * @return string
     */
    private function getVat(): string
    {
        return $this->vat;
    }

    /**
     * @return string
     */
    private function getCallbackUrl(): string
    {
        return $this->callbackUrl;
    }

    /**
     * @param string $uuid
     * @return array
     * @throws Exception
     */
    public function report(string $uuid): array
    {
        try {
            $url = $this->getGroupCode() . '/report/' . $uuid;
            $response = $this->client->get($url);

            $content = $response->getBody()->getContents();

            return json_decode($content, true);
        } catch (ClientException $exception) {
            throw new Exception($exception);
        }
    }

}