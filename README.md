# Модуль ZenCart для Украины

## Чеклист интеграции:
- [x] Установить модуль.
- [x] Передать тех поддержке PSP Platon  ссылку для коллбеков.
- [x] Провести оплату используя тестовые реквизиты.

## Установка:

* Распаковать архив в корень сайта.

* В админ панеле перейти в  Modules → Payment.

* Выберите “Platon” и нажмите "Install Module".

* В настройках модуля введите ключ и пароль.

* Переведите "Enable Platon Gateway" в значение “True”.

## Иностранные валюты:
Готовые CMS модули PSP Platon по умолчанию поддерживают только оплату в UAH.

Если необходимы иностранные валюты необходимо провести правки модуля вашими программистами согласно раздела [документации](https://platon.atlassian.net/wiki/spaces/docs/pages/1810235393).

## Ссылка для коллбеков:
https://ВАШ_САЙТ/process_platon.php

## Тестирование:
В целях тестирования используйте наши тестовые реквизиты.

| Номер карты  | Месяц / Год | CVV2 | Описание результата |
| :---:  | :---:  | :---:  | --- |
| 4111  1111  1111  1111 | 02 / 2022 | Любые три цифры | Не успешная оплата без 3DS проверки |
| 4111  1111  1111  1111 | 06 / 2022 | Любые три цифры | Не успешная оплата с 3DS проверкой |
| 4111  1111  1111  1111 | 01 / 2022 | Любые три цифры | Успешная оплата без 3DS проверки |
| 4111  1111  1111  1111 | 05 / 2022 | Любые три цифры | Успешная оплата с 3DS проверкой |
