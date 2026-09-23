<?php
/*
 * TeamSpeak login modal (templates/utils/modal-login.latte, js/login.js).
 * Translations stored in the database always win, see core.php.
 */
return [
    "LOGIN_TITLE" => "Вход через TeamSpeak",
    "LOGIN_LOADING" => "Загрузка",

    "LOGIN_PROGRESS" => "Шаги входа",
    "LOGIN_STEP_CONNECT" => "Подключение",
    "LOGIN_STEP_ACCOUNT" => "Выбор аккаунта",
    "LOGIN_STEP_CODE" => "Ввод кода",
    "LOGIN_STEP_DONE" => "готово",

    "LOGIN_NOT_CONNECTED" => "Вы ещё не на сервере",
    "LOGIN_NOT_CONNECTED_TEXT" => "Подключитесь к серверу с этого устройства. Окно само перейдёт дальше, как только сервер вас увидит.",
    "LOGIN_WAITING" => "Ждём подключения",
    "LOGIN_DEBUG_IP" => "IP-адрес, который видит сайт",

    "LOGIN_SELECT_ACCOUNT" => "Выберите аккаунт",
    "LOGIN_SELECT_ACCOUNT_TEXT" => "С вашего адреса подключено несколько клиентов TeamSpeak.",
    "LOGIN_ACCOUNT_ID" => "ID {0}",

    "LOGIN_ENTER_CODE" => "Введите код",
    "LOGIN_CODE_SENT_POKE" => "Мы отправили вам код подтверждения в TeamSpeak всплывающим сообщением.",
    "LOGIN_CODE_SENT_MESSAGE" => "Мы отправили вам код подтверждения в TeamSpeak личным сообщением.",
    "LOGIN_CODE_LABEL" => "Код подтверждения",
    "LOGIN_CODE_VALID" => "Коды действуют {0}",
    "LOGIN_DURATION_MIN" => "{0}\u{00A0}мин",
    "LOGIN_DURATION_SEC" => "{0}\u{00A0}с",
    "LOGIN_CODE_INVALID" => "Код не подошёл",
    "LOGIN_CODE_RESEND" => "Прислать новый код",
    "LOGIN_CODE_RESENT" => "Отправили новый код",
    "LOGIN_CODE_STILL_VALID" => "Прежний код ещё действует",
    "LOGIN_SUBMIT" => "Войти",
    "LOGIN_OTHER_ACCOUNT" => "Другой аккаунт",

    "LOGIN_ERROR_LOAD" => "Не удалось загрузить данные для входа. Возможно, сервер TeamSpeak недоступен.",
    "LOGIN_ERROR_SEND" => "Не удалось отправить код. Проверьте, что вы всё ещё на сервере и бот может присылать вам сообщения.",
    "LOGIN_RETRY" => "Попробовать ещё раз",
];
