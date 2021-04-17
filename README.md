# Атол Онлайн

Пакет позволяет отправить чек и получить его статус в [Атол Онлайн](https://online.atol.ru/) 

## Установка

````
$ composer require unetway/atol
````

## Использование

### Отправка чека в Атол:

````
use Unetway\Atol\Atol;

$atol = new Atol([
    'login' => '',
    'password' => '',
    'group_code' => '',
    'company_email' => '',
    'sno' => '',
    'inn' => '',
    'payment_address' => '',
    'is_test' => false,
    'callback_url' => '',
    'vat' => 'vat20',
]);

$name = 'Имя';
$email = 'email@user.com';
$price = 700;

return $atol->receipt($name, $price, $email);

````

**Параметры:**

- name - имя клиента
- price - цена
- email - почта клиента

### Получение статуса чека:

````
use Unetway\Atol\Atol;

$atol = new Atol([
    'login' => '',
    'password' => '',
    'group_code' => '',
    'company_email' => '',
    'sno' => '',
    'inn' => '',
    'payment_address' => '',
    'is_test' => false,
    'callback_url' => '',
    'vat' => 'vat20',
]);
$uuid = '';

return $atol->report($uuid);

````

Пример получения ответа на указанный callback адрес:

````
$request = file_get_contents('php://input');
$res = json_decode($request, true);
$uuid = $res['uuid'];

if ($res['status'] !== 'done') {
    
}
````
