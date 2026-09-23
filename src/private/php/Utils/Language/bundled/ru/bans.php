<?php
/*
 * Ban list (bans.php). Translations stored in the database always win.
 */
return [
    "BANS_INTRO" => "Кто заблокирован на сервере, за что и до какого срока.",
    "BANS_LOADING" => "Загружаем список банов",
    "BANS_EMPTY_HINT" => "Сейчас на сервере никто не забанен.",
    "BANS_TARGET_UNKNOWN" => "Неизвестно",

    // Забанен IP самого посетителя
    "BANS_BANNED_ALERT_EXPIRES" => "Бан закончится {0}.",
    "BANS_BANNED_ALERT_PERMANENT" => "Бан бессрочный.",

    // Цифры по всему списку над таблицей
    "BANS_FACTS_LABEL" => "Баны в цифрах",
    "BANS_FACT_TOTAL" => "Всего банов",
    "BANS_FACT_PERMANENT" => "Бессрочные",
    "BANS_FACT_TEMPORARY" => "Временных: {0}",
    "BANS_FACT_EXPIRING" => "Истекут за 7 дней",
    "BANS_FACT_NEXT" => "Ближайший {0}",
    "BANS_FACT_LATEST" => "Последний бан",

    // Кто выдал больше всего банов из тех, что ещё в списке (снятые баны из него уходят), рядом с цифрами
    "BANS_STAFF_TITLE" => "Кто выдал больше всего банов из списка",
    "BANS_STAFF_COUNT" => "Выдано банов: {0}",
    "BANS_STAFF_TIED" => "И ещё {0} с тем же числом банов",

    // Временный бан, срок которого вышел, но сервер его ещё не снял
    "BANS_EXPIRED" => "Истёк {0}",

    // Типы банов. IP, UID и MyTSID одинаковы во всех языках
    "BANS_TYPE_NAME" => "Ник",
    "BANS_FILTER_LABEL" => "Тип бана",
    "BANS_FILTER_ALL" => "Все",

    "BANS_SEARCH_LABEL" => "Поиск по банам",
    "BANS_SEARCH_CLEAR" => "Очистить поиск",
    "BANS_SEARCH_EMPTY" => "По запросу «{0}» ничего не найдено",
    "BANS_SEARCH_EMPTY_HINT" => "Поиск идёт по никам, причинам и тем, кто выдал бан.",
    "BANS_SEARCH_EMPTY_FILTERED" => "Искали только среди банов типа «{0}».",
    "BANS_FILTER_RESET" => "Искать среди всех",
    "BANS_DETAILS" => "Подробности",
    "BANS_SHOW_DETAILS_FOR" => "Подробности бана: {0}",

    // Строки DataTables. Для русского они заменяют перевод DataTables с его CDN
    // Не перевод, а код языка этого файла: по нему страница понимает, что строки свои
    "BANS_TABLE_LANGUAGE" => "ru",
    "BANS_TABLE_INFO" => "Показаны с _START_ по _END_ из _TOTAL_",
    "BANS_TABLE_INFO_ALL" => "Всего банов: _TOTAL_",
    "BANS_TABLE_INFO_MATCHES" => "Найдено: _TOTAL_ из _MAX_",
    "BANS_TABLE_INFO_EMPTY" => "Нечего показать",
    "BANS_TABLE_INFO_FILTERED" => "(всего _MAX_)",
    // Неразрывный пробел между разрядами: 1 234
    "BANS_TABLE_THOUSANDS" => "\u{00A0}",
    "BANS_TABLE_PREVIOUS" => "Предыдущая страница",
    "BANS_TABLE_NEXT" => "Следующая страница",
    "BANS_TABLE_SORT_ASC" => ": сортировать по возрастанию",
    "BANS_TABLE_SORT_DESC" => ": сортировать по убыванию",
];
