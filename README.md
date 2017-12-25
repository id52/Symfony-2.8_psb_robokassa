# Использование PaymentBundle

- [Установка](#Установка)
- [Маршруты](#Маршруты)
- [Создание своих маршрутов](#Создание-своих-маршрутов)
- [Сущности для логирования оплат](#Сущности-для-логирования-оплат)
- [Использование бандла для оплаты через Робокассу](#Использование-бандла-для-оплаты-через-Робокассу)
    - [Настройки](#Настройки-для-использования-с-робокассой)
    - [Использование](#Использование)
- [Оплата через PSB](#Оплата-через-psb)
- [Пример полной конфигурации бандла](#Пример-полной-конфигурации-бандла)


## Установка

Прописываем путь к бандлу в **composer.json** в блоке **require**

```
"require": {
    ...

    "krealab-services/payment-bundle": "*",

    ...
}
```

в блоке **repositories**

```
"repositories": [
 ...
        {
            "type": "git",
            "url": "git@gitlab.krealab.ru:krealab-services/payment-bundle.git"
        },
...
```

и пишем в командной строке

```bash
composer require krealab-services/payment-bundle
```

После чего происходит скачивание бандла, по окончании не забываем регистрировать его в **AppKernel**

```
        $bundles = array(
          ...

            new KreaLab\PaymentBundle\PaymentBundle(),

          ...
        );
```

После подключения создаем сущность для логирования действий, наследуещей от _Log.orm.yml_ из PaymentBundle.

Все Бандл готов к использованию.

######  Маршруты

Для начала работы необходимо подлючить маршруты для PaymentBundle или же создать свои на его основе, как это делать мы рассмотрим ниже,
пока же будем использовать все настройки по умолчанию.

Подключение маршрутов будем производить в _routing.yml_, находящеся по адресу _./app/config/_.
Пример подключения маршрутов:

```
_payments_robokassa_ticket_user:
    resource: "@KrealabPaymentBundle/Resources/config/routing_robokassa.yml"
    host: "ticket.%domain%"
    prefix: "/payments/robokassa/"
    defaults: { _bundle: 'TicketUser' }

_payments_psb_ticket_user:
    resource: "@KrealabPaymentBundle/Resources/config/routing_psb.yml"
    host: "ticket.%domain%"
    prefix: "/payments/psb/"
    defaults: { _bundle: 'TicketUser' }
```

Указывая **_defaults: { _bundle: 'TicketUser' }_**, мы говорим бандлу что ему необходимо искать настройки в блоке конфигурации с именем **_TicketUser_**,

###### Создание своих маршрутов

Для использования своих маршрутов, например если нам необходимо в использовать оплаты не только для нескольких типов пользователей, каждый со своего аккаунта в Робокассе,
необходимо создать контроллер наслудующий от **_KreaLab\PaymentBundle\Controller\RobokassaController.php_** и при необходимости переопределив там методы прописать к ним маршруты. Потом подключаем
вновь созданный файл через _routing.yml_, меняя для каждого префикс.

Пример подключения файлов с маршрутами:
```
_payments_robokassa_ticket_user:
    resource: "@TicketUserBundle/Resources/config/payment_routing.yml"
    host: "%domain%"
    prefix: "/ticket/payments/robokassa/"
    defaults: { _bundle: 'TicketUser' }
```

```
_payments_robokassa_ticket_user:
    resource: "@ManagerUserBundle/Resources/config/payment_routing.yml"
    host: "%domain%"
    prefix: "/manager/payments/robokassa/"
    defaults: { _bundle: 'ManagerUser' }
```

В этом примере мы видим две конфигурации которые ипользуют различные настройки для доступа к Робокассе и поиск параметров будет производится в блоках с соответствующими именами
в _**config.yml**_. А ниже пример самого файла с маршрутами для бандла _TicketUser_:

```
ticket_user_payments_robokassa_query:
    path: /query-{id}/
    defaults: { _controller: TicketUserBundle:PaymentRobokassa:query }
    requirements:
        id: \d+

ticket_user_payments_robokassa_result:
    path: /result/
    defaults: { _controller: TicketUserBundle:PaymentRobokassa:result }

ticket_user_payments_robokassa_success:
    path: /success/
    defaults: { _controller: KrealabPaymentBundle:Robokassa:renderSuccess }

ticket_user_payments_robokassa_fail:
    path: /fail/
    defaults: { _controller: KrealabPaymentBundle:Robokassa:renderFail }

```
В этом примере мы видем создание своих маршрутов для использования оплат через робокассу, причем два маршрута ссылаються на новые экшены
в классе наследнике от контроллера Робокассы, а два на стрые места внутри KrealabPaymentBundle.

###### Сущности для логирования оплат

Для того чтобы вести логирования необходимо либо создать новую сущность, наследника класса `\KreaLab\PaymentBundle\Entity\Log`, либо использовать существующюю и создав экземпляр
класса заполнить необходимые данные.

```
$log = new PaymentLog();
$log->setSType($type);
$log->setSum($sum);
$log->setInfo($ticketsPaidData);

$em->persist($log);
$em->flush();
```

Список свойств использующихся при логировании оплты

| Название      | Тип      | Описание                                                             | Может быть NULL |
|:--------------|:---------|:---------------------------------------------------------------------|:----------------|
| s_type        | string   | Тип сервиса для оплаты. (предпологается psb или robokassa)           | Да              |
| s_id          | string   | Номер транзакции. Задается банком.                                   | Да              |
| sum           | integer  | Сумма которую мы оплачиваем                                          | Нет             |
| paid          | boolean  | Статус платежа, завершен или нет                                     | Нет             |
| info          | array    | Массив для хранения каких либо дополнительных данных                 | Нет             |
| revert_log_id | integer  | Id лога возврата платежа (используется только в PSB)                 | Да              |
| paid_log_id   | integer  | Id лога  по которому был возврат платежа (используется только в PSB) | Да              |
| created_at    | datetime | Время создания лога (задается автоматически)                         | Нет             |
| updated_at    | datetime | Время изменения лога (задается автоматически)                        | Нет             |


## Использование бандла для оплаты через Робокассу

######  Настройки для использования с Робокассой

Все доступные настройки можно указывать в файле **_config.yml_**, в блоке **_payment_**, если этот блок не указывать то принимаються настройки по умолчанию(_при подключении маршрутов
необходимо указать бандл Payment и поиск layot будет осущствлен в App бандле_). Если же мы не хотим использовать настройки по умолчанию то их необходимо указывать для каждого бандла в
которм будут осуществляться платежи.
 Пример полного блока настроек Робокассы для TicketUserBundle и ManagerUserBundle:

```
krealab_payment:
    ticket_user:
        robokassa:
            handler_parse_parameters: krealab_payment.service.parameters
            handlers:
                success: common.payment.ticket_user:success
                render_success: common.payment.ticket_user:renderRobokassaSuccess
                fail: krealab_payment.service.robokassa:fail
                render_fail: krealab_payment.service.robokassa:renderFail
            payments:
                url: '%robokassa_url%'
                login: '%ticket_user_robokassa_login%'
                pass1: '%ticket_user_robokassa_pass1%'
                pass2: '%ticket_user_robokassa_pass2%'
            routes:
                callback: ticket_user_homepage
            templates:
                view_success: '@TicketUser/Payments/Robokassa/success.html.twig'
                view_fail: '@TicketUser/Payments/Robokassa/fail.html.twig'
    manager_user:
        robokassa:
            handler_parse_parameters: krealab_payment.service.parameters
            handlers:
                success: common.payment.manger_user:success
                render_success: common.payment.manger_user:renderRobokassaSuccess
                fail: common.payment.manger_user:fail
                render_fail: common.payment.manger_user:renderFail
            payments:
                url: '%robokassa_url%'
                login: '%manager_user_robokassa_login%'
                pass1: '%manager_user_robokassa_pass1%'
                pass2: '%manager_user_robokassa_pass2%'
            routes:
                callback: manager_user_homepage
            templates:
                view_success: '@ManagerUser/Payments/Robokassa/success.html.twig'
                view_fail: '@ManagerUser/Payments/Robokassa/fail.html.twig'
```
Каждая из этих настроек опциональна и если она не указанна, то будет использованно значение по умолчанию.
Для совершения каких либо действий после прохождения оплаты или же после ее неудачного завершения, ровно как и для отрисовки страниц информации, используются сервисы
и если мы хотим поставить свои обработчики - необходимо создать свой сервис, наследуемый от **_KreaLab\PaymentBundle\Service\PaymentServiceRobokassa_** и переопределить
в нем нужные нам методы.

Краткое описание блоков настройки:

| Название блока           | Название параметра | Значение по умолчанию                           | Описание                                                                                    |
|:-------------------------|:-------------------|:------------------------------------------------|:--------------------------------------------------------------------------------------------|
| handler_parse_parameters | ---                | krealab_payment.service.parameters              | Название Сервиса, который будет обрабатывать конфигурацию из файла _config.yml_             |
| **handlers**             |                    |                                                 |                                                                                             |
|                          | success            | krealab_payment.service.robokassa:success       | Обработчик положительного результата оплаты (вызывается после оплаты)                       |
|                          | render_success     | krealab_payment.service.robokassa:render_sucess | Отрисовка страницы с сообщением о положительной оплате                                      |
|                          | fail               | krealab_payment.service.robokassa:fail          | Обработчик отрицательного результата оплаты                                                 |
|                          | render_fail        | krealab_payment.service.robokassa:render_fail   | Отрисовка страницы с отказом или ошибкой оплаты                                             |
| **payments**             |                    |                                                 |                                                                                             |
|                          | url                | %robokassa_url%                                 | Адрес сайта Робокассы                                                                       |
|                          | login              | %robokassa_login%                               | Логин для Робокассы                                                                         |
|                          | pass1              | %robokassa_pass1%                               | Пароль 1                                                                                    |
|                          | pass2              | %robokassa_pass2%                               | Пароль 2                                                                                    |
| **routes**               |                    |                                                 |                                                                                             |
|                          | callback           | manager_user_homepage                           | Адрес на который будет осуществлен переход, после отображения страницы с результатом оплаты |
| **templates**            |                    |                                                 |                                                                                             |
|                          | view_success       | @Payment/Robokassa/success.html.twig            | Шаблон для отображения положительной оплаты                                                 |
|                          | view_fail          | @Payment/Robokassa/fail.html.twig               | Шаблон для отображения ошибки оплаты                                                        |

Значения находящиеся между знаками '%' - наименование параметров, котроые будут подставляться из файла _**parameters.yml**_
 Для использования одной учетки Робокассы необходимо в файле с параметрами указать данные учетной записи:
 ```
     robokassa_url: 'http:\\robokassa.com'
     robokassa_login: ticket_user_pvart
     robokassa_pass1: pass1
     robokassa_pass2: pass2
 ```
 для использования значений по умолчанию, либо изменить название и уже обязательно указать измененные названия в _config.yml_

 Если мы хотим использовать несколько учетных записей для Робокассы, для разных контроллеров, необходимо их прописать в _parameters.yml_ и указать название в блоке конфигурации _payments_.
 В примере именно так и сделано, для каждого бандла указанны свои параметры.


Настройки для Робокасыы по умолчанию:
```
krealab_payment:
    TicketUser: ~
```

Теперь есть настройки по умолчанию для бандла _TicketUser_, как для Робокассы так и для PSB, последние настройки нам пока не интересны, мы рассмотрим их ниже.


######  Использование

Для совершения платежа необходимо создать лог оплаты, указав в нем суммы и выполнить перенаправление по маршруту на **_queryAction_**, с передачей в параметрах _ID_
созданного лога.
Пример:

```
...
$log = new PaymentLog();
$log->setSType('robokassa');
$log->setSum($sum);
$log->setInfo($ticketsPaidData);

$em->persist($log);
$em->flush();
...

return $this->redirectToRoute('ticket_user_payments_robokassa_query', ['id' => $log->getId()]);
```

В этом примере мы создали лог, указали используемый тип (Робокасса), сумму для оплаты и выполнили перенапрваление на роути с запросом на оплату. Все, при правильных прочих настройках
мы получим сообщение о удачном или же не удачном завершении платежа.


## Оплата через PSB
_Для возможности отмены платежа, пользователь, инициировавший возврат должен имет роль_ **_"ROLE_PSB_REVERT"_**
Для оплаты через сервис PSB все тоже самое, с небольшими отличаями, а именно чуть больше настроек, за счет того что можно делать возврат платежей,
а так же, при формировании запроса необходимо указвать тип операции. В прочем последнее верно только при запрросе на возврат денег, при оплате формироваие
запроса ничем не отличается от запроса через робокассу.

Таблицы настроек для PSB практически анологичны блоку отвечающему за Робокассу:

```
krealab_payment:
    TicketUser:
        psb:
            is_ajax_check_paid: true
            handler_parse_parameters: krealab_payment.service.parameters
            handlers:
                check_status: krealab_payment.service.psb:getStatusAjax
                success: common.payment.ticket_user:success
                fail: krealab_payment.service.psb:fail
                success_revert: krealab_payment.service.psb:successRevert
                fail_revert: krealab_payment.service.psb:failRevert
                render_info_payment: common.payment.ticket_user:renderPsbInfoPayment
                render_info_revert: krealab_payment.service.psb:renderInfoRevert
            payments:
                key: '%psb_key%'
                terminal_id: '%psb_terminal_id%'
                merchant_id: '%psb_merchant_id%'
                merchant_name: '%psb_merchant_name%'
                merchant_email: '%psb_merchant_email%'
                url: '%psb_url%'
            routes:
                info: payments_psb_info
                info_revert: payments_psb_info_revert
                check_status: payments_psb_ajax_status
                callback: ticket_user_homepage
            templates:
                view_info_payment: '@KrealabPayment/Psb/info_payment.html.twig'
                view_info_revert: '@KrealabPayment/Psb/info_revert.html.twig'
```

Краткое описание блоков настройки:

| Название блока           | Название параметра  | Значение по умолчанию                            | Описание                                                                                    |
|:-------------------------|:--------------------|:-------------------------------------------------|:--------------------------------------------------------------------------------------------|
| is_ajax_check_paid       | ---                 | true                                             | Будет ли осуществлятся проверка состяния платежа                                            |
| handler_parse_parameters | ---                 | krealab_payment.service.parameters               | Название Сервиса, который будет обрабатывать конфигурацию из файла _config.yml_             |
| **handlers**             |                     |                                                  |                                                                                             |
|                          | check_status        | krealab_payment.service.psb:getStatusAjax        | Обработчик осуществляющий _ajax_ проверку состояния платежа                                 |
|                          | success             | krealab_payment.service.psb:success              | Отрисовка положительного результата оплаты                                                  |
|                          | fail                | krealab_payment.service.psb:fail                 | Обработчик отрицательного результата оплаты                                                 |
|                          | success_revert      | krealab_payment.service.psb:successRevert        | Обработчик положительного завершения возврата денег                                         |
|                          | fail_revert         | krealab_payment.service.psb:failRevert           | Обработчик отрицательного завершения возврата денег                                         |
|                          | render_info_payment | krealab_payment.service.psb:renderPsbInfoPayment | Отрисовка страницы инвормации после совершения платежа                                      |
|                          | render_info_revert  | krealab_payment.service.psb:renderInfoRevert     | Отрисовка страницы инвормации после совершения возврата денег                               |
| **payments**             |                     |                                                  |                                                                                             |
|                          | key                 | %psb_key%                                        | Ключь кодирования для PSB                                                                   |
|                          | terminal_id         | %psb_terminal_id%                                | Id                                                                                          |
|                          | merchant_id         | %psb_merchant_id%                                | Id                                                                                          |
|                          | merchant_name       | %psb_merchant_name%                              | имя                                                                                         |
|                          | merchant_email      | %psb_merchant_email%                             | Email потребителя (сайта)                                                                   |
|                          | url                 | %psb_url%                                        | Адрес расположения PSB                                                                      |
| **routes**               |                     |                                                  |                                                                                             |
|                          | info                | payments_psb_info                                | Адрес для отображения состояния платежа                                                     |
|                          | info_revert         | payments_psb_info_revert                         | Адрес для отображения информации о возврате средств                                         |
|                          | check_status        | payments_psb_ajax_status                         | Адрес для проверки _ajax_ статуса платежа                                                   |
|                          | callback            | homepage                                         | Адрес на который будет осуществлен переход, после отображения страницы с результатом оплаты |
| **templates**            |                     |                                                  |                                                                                             |
|                          | view_info_payment   | @KrealabPayment/Psb/info_payment.html.twig       | Шаблон для отображения положительной оплаты                                                 |
|                          | view_info_revert    | @KrealabPayment/Psb/info_revert.html.twig        | Шаблон для отображения ошибки оплаты                                                        |

Так же как и в конфигурации для Робокассы, значения находящиеся между знаками '%' - наименование параметров, котроые будут подставляться из файла _**parameters.yml**_

В связи с тем что поведение _PSB_ несколько отличается от поведения Робокассы, здесь используется дополнительная проверка состояния платежа. Тоесть
при завершении операции мы попадаем на страницу информации, с которой делаем запрос в нашу базу данных и ищем там лог оплаты (передается в параметрах),
и с помощьюсмотрим было ли завершение оперции положительным или же нет.

Пример запроса на оплату и возварт денег при использовании PSB:

```
Оплата:

...
$log = new PaymentLog();
$log->setSType($type);
$log->setSum($sum);
$log->setInfo($ticketsPaidData);

$em->persist($log);
$em->flush();
...

return $this->redirectToRoute('ticket_user_payments_psb_query', ['id' => $log->getId()]);


Возврат:

...
return $this->redirectToRoute('ticket_user_payments_psb_query', ['id' => $log->getId(), 'trtype' => 'revert']);
...
```
Как видим, оплата полностью индентична оплате через робокассу, а вот при возврате мы не создаем нового лога, а лиш указываем ID лога оплаты, который мы хотим отменить. При этом
у нас появился новый параметр **_'trtype' => 'revert'_**, тем самым мы указываем что хотим выполнить возврат средств. После возврата платежа, в таблице логов появится новый лог у которого будет указан
в связях лог, по которому был произведен платеж, сумма будет равна сумме платежа, но со знаком минус и у оригинального лога оплаты будет добавлен id лога возврата в поле _revert_log_id_


## Пример полной конфигурации бандла

```
krealab_payment:
    ticket_user:
        robokassa:
            handler_parse_parameters: krealab_payment.service.parameters
            handlers:
                success: common.payment.ticket_user:success
                render_success: common.payment.ticket_user:renderRobokassaSuccess
                fail: krealab_payment.service.robokassa:fail
                render_fail: krealab_payment.service.robokassa:renderFail
            payments:
                url: '%robokassa_url%'
                login: '%ticket_user_robokassa_login%'
                pass1: '%ticket_user_robokassa_pass1%'
                pass2: '%ticket_user_robokassa_pass2%'
            routes:
                callback: ticket_user_homepage
            templates:
                view_success: '@TicketUser/Payments/Robokassa/success.html.twig'
                view_fail: '@TicketUser/Payments/Robokassa/fail.html.twig'
        psb:
            is_ajax_check_paid: true
            handler_parse_parameters: krealab_payment.service.parameters
            handlers:
                check_status: krealab_payment.service.psb:getStatusAjax
                success: common.payment.ticket_user:success
                fail: krealab_payment.service.psb:fail
                success_revert: krealab_payment.service.psb:successRevert
                fail_revert: krealab_payment.service.psb:failRevert
                render_info_payment: common.payment.ticket_user:renderPsbInfoPayment
                render_info_revert: krealab_payment.service.psb:renderInfoRevert
            payments:
                key: '%psb_key%'
                terminal_id: '%psb_terminal_id%'
                merchant_id: '%psb_merchant_id%'
                merchant_name: '%psb_merchant_name%'
                merchant_email: '%psb_merchant_email%'
                url: '%psb_url%'
            routes:
                info: payments_psb_info
                info_revert: payments_psb_info_revert
                check_status: payments_psb_ajax_status
                callback: ticket_user_homepage
            templates:
                view_info_payment: '@KrealabPayment/Psb/info_payment.html.twig'
                view_info_revert: '@KrealabPayment/Psb/info_revert.html.twig'
```

