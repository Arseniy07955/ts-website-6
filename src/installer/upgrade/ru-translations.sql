-- TS-website: improved Russian translation (and a few English grammar fixes)
--
-- Brings an EXISTING installation up to date with the Russian strings shipped in
-- installer/dbinstall_mysql_lang.sql. New installations already have them.
-- MySQL / MariaDB only. Safe to run more than once: existing rows are updated,
-- missing rows are inserted, nothing is deleted.
--
-- Before running, replace every DBPREFIX in this file with the table prefix of
-- your installation (the "prefix" value in private/dbconfig.php, for example
-- tsw_), or remove DBPREFIX entirely if the prefix is empty. For example:
--
--   sed 's/DBPREFIX/tsw_/g' ru-translations.sql | mysql -u USER -p DATABASE
--
-- Languages are looked up by langcode ('ru' and 'en'), not by langid. If a
-- language does not exist in the languages table, its part is skipped.
--
-- The website caches translations for up to 5 minutes (config key
-- cache_languages, 300 seconds by default). To see the changes immediately,
-- delete the cache files: private/cache/*.cache.php (the translations cache
-- alone is private/cache/2ed18fafe4199c7cfc187fdb2a5b1a66.cache.php).

SET NAMES utf8mb4;
START TRANSACTION;

SET @ru := (SELECT `langid` FROM `DBPREFIXlanguages` WHERE `langcode` = 'ru' ORDER BY `langid` LIMIT 1);
SET @en := (SELECT `langid` FROM `DBPREFIXlanguages` WHERE `langcode` = 'en' ORDER BY `langid` LIMIT 1);

-- Russian

UPDATE `DBPREFIXtranslations` SET `value` = '<b>Мы используем куки.</b> Они нужны, чтобы сайтом было удобно пользоваться. <a href="https://support.mozilla.org/ru/kb/kuki-informaciya-kotoruyu-veb-sajty-hranyat-na-vas" target="_blank">Подробнее</a>'
  WHERE `langid` = @ru AND `identifier` = 'COOKIEALERT_MESSAGE';
INSERT INTO `DBPREFIXtranslations` (`langid`, `identifier`, `value`, `comment`)
  SELECT @ru, 'COOKIEALERT_MESSAGE', '<b>Мы используем куки.</b> Они нужны, чтобы сайтом было удобно пользоваться. <a href="https://support.mozilla.org/ru/kb/kuki-informaciya-kotoruyu-veb-sajty-hranyat-na-vas" target="_blank">Подробнее</a>', NULL FROM DUAL
  WHERE @ru IS NOT NULL AND NOT EXISTS (SELECT 1 FROM `DBPREFIXtranslations` WHERE `langid` = @ru AND `identifier` = 'COOKIEALERT_MESSAGE');

UPDATE `DBPREFIXtranslations` SET `value` = 'Принимаю'
  WHERE `langid` = @ru AND `identifier` = 'COOKIEALERT_AGREE';
INSERT INTO `DBPREFIXtranslations` (`langid`, `identifier`, `value`, `comment`)
  SELECT @ru, 'COOKIEALERT_AGREE', 'Принимаю', NULL FROM DUAL
  WHERE @ru IS NOT NULL AND NOT EXISTS (SELECT 1 FROM `DBPREFIXtranslations` WHERE `langid` = @ru AND `identifier` = 'COOKIEALERT_AGREE');

UPDATE `DBPREFIXtranslations` SET `value` = '<b>Внимание!</b> Часть данных сейчас недоступна. Показаны данные, полученные {0}.'
  WHERE `langid` = @ru AND `identifier` = 'OUTDATED_DATA';
INSERT INTO `DBPREFIXtranslations` (`langid`, `identifier`, `value`, `comment`)
  SELECT @ru, 'OUTDATED_DATA', '<b>Внимание!</b> Часть данных сейчас недоступна. Показаны данные, полученные {0}.', NULL FROM DUAL
  WHERE @ru IS NOT NULL AND NOT EXISTS (SELECT 1 FROM `DBPREFIXtranslations` WHERE `langid` = @ru AND `identifier` = 'OUTDATED_DATA');

UPDATE `DBPREFIXtranslations` SET `value` = 'Показать ошибки'
  WHERE `langid` = @ru AND `identifier` = 'SHOW_PROBLEMS';
INSERT INTO `DBPREFIXtranslations` (`langid`, `identifier`, `value`, `comment`)
  SELECT @ru, 'SHOW_PROBLEMS', 'Показать ошибки', NULL FROM DUAL
  WHERE @ru IS NOT NULL AND NOT EXISTS (SELECT 1 FROM `DBPREFIXtranslations` WHERE `langid` = @ru AND `identifier` = 'SHOW_PROBLEMS');

UPDATE `DBPREFIXtranslations` SET `value` = 'Для работы сайта нужно <a href="https://www.enable-javascript.com/ru/" target="_blank">включить JavaScript</a>.'
  WHERE `langid` = @ru AND `identifier` = 'NO_JAVASCRIPT_ENABLED';
INSERT INTO `DBPREFIXtranslations` (`langid`, `identifier`, `value`, `comment`)
  SELECT @ru, 'NO_JAVASCRIPT_ENABLED', 'Для работы сайта нужно <a href="https://www.enable-javascript.com/ru/" target="_blank">включить JavaScript</a>.', NULL FROM DUAL
  WHERE @ru IS NOT NULL AND NOT EXISTS (SELECT 1 FROM `DBPREFIXtranslations` WHERE `langid` = @ru AND `identifier` = 'NO_JAVASCRIPT_ENABLED');

UPDATE `DBPREFIXtranslations` SET `value` = 'Не удалось загрузить данные модуля «{0}». Пожалуйста, сообщите об этом владельцу сайта.'
  WHERE `langid` = @ru AND `identifier` = 'CANNOT_GET_DATA';
INSERT INTO `DBPREFIXtranslations` (`langid`, `identifier`, `value`, `comment`)
  SELECT @ru, 'CANNOT_GET_DATA', 'Не удалось загрузить данные модуля «{0}». Пожалуйста, сообщите об этом владельцу сайта.', NULL FROM DUAL
  WHERE @ru IS NOT NULL AND NOT EXISTS (SELECT 1 FROM `DBPREFIXtranslations` WHERE `langid` = @ru AND `identifier` = 'CANNOT_GET_DATA');

UPDATE `DBPREFIXtranslations` SET `value` = 'Кем выдан'
  WHERE `langid` = @ru AND `identifier` = 'BANS_HEADER_INVOKER';
INSERT INTO `DBPREFIXtranslations` (`langid`, `identifier`, `value`, `comment`)
  SELECT @ru, 'BANS_HEADER_INVOKER', 'Кем выдан', NULL FROM DUAL
  WHERE @ru IS NOT NULL AND NOT EXISTS (SELECT 1 FROM `DBPREFIXtranslations` WHERE `langid` = @ru AND `identifier` = 'BANS_HEADER_INVOKER');

UPDATE `DBPREFIXtranslations` SET `value` = 'Дата бана'
  WHERE `langid` = @ru AND `identifier` = 'BANS_HEADER_BANDATE';
INSERT INTO `DBPREFIXtranslations` (`langid`, `identifier`, `value`, `comment`)
  SELECT @ru, 'BANS_HEADER_BANDATE', 'Дата бана', NULL FROM DUAL
  WHERE @ru IS NOT NULL AND NOT EXISTS (SELECT 1 FROM `DBPREFIXtranslations` WHERE `langid` = @ru AND `identifier` = 'BANS_HEADER_BANDATE');

UPDATE `DBPREFIXtranslations` SET `value` = '{0} в резерве'
  WHERE `langid` = @ru AND `identifier` = 'STATUS_RESERVED_SLOTS';
INSERT INTO `DBPREFIXtranslations` (`langid`, `identifier`, `value`, `comment`)
  SELECT @ru, 'STATUS_RESERVED_SLOTS', '{0} в резерве', NULL FROM DUAL
  WHERE @ru IS NOT NULL AND NOT EXISTS (SELECT 1 FROM `DBPREFIXtranslations` WHERE `langid` = @ru AND `identifier` = 'STATUS_RESERVED_SLOTS');

UPDATE `DBPREFIXtranslations` SET `value` = 'Рекорд онлайна:'
  WHERE `langid` = @ru AND `identifier` = 'STATUS_TOP_ONLINE';
INSERT INTO `DBPREFIXtranslations` (`langid`, `identifier`, `value`, `comment`)
  SELECT @ru, 'STATUS_TOP_ONLINE', 'Рекорд онлайна:', NULL FROM DUAL
  WHERE @ru IS NOT NULL AND NOT EXISTS (SELECT 1 FROM `DBPREFIXtranslations` WHERE `langid` = @ru AND `identifier` = 'STATUS_TOP_ONLINE');

UPDATE `DBPREFIXtranslations` SET `value` = 'Рекорд установлен {0}'
  WHERE `langid` = @ru AND `identifier` = 'STATUS_TOP_ONLINE_DESC';
INSERT INTO `DBPREFIXtranslations` (`langid`, `identifier`, `value`, `comment`)
  SELECT @ru, 'STATUS_TOP_ONLINE_DESC', 'Рекорд установлен {0}', NULL FROM DUAL
  WHERE @ru IS NOT NULL AND NOT EXISTS (SELECT 1 FROM `DBPREFIXtranslations` WHERE `langid` = @ru AND `identifier` = 'STATUS_TOP_ONLINE_DESC');

UPDATE `DBPREFIXtranslations` SET `value` = 'Время работы:'
  WHERE `langid` = @ru AND `identifier` = 'STATUS_UPTIME';
INSERT INTO `DBPREFIXtranslations` (`langid`, `identifier`, `value`, `comment`)
  SELECT @ru, 'STATUS_UPTIME', 'Время работы:', NULL FROM DUAL
  WHERE @ru IS NOT NULL AND NOT EXISTS (SELECT 1 FROM `DBPREFIXtranslations` WHERE `langid` = @ru AND `identifier` = 'STATUS_UPTIME');

UPDATE `DBPREFIXtranslations` SET `value` = 'Пинг:'
  WHERE `langid` = @ru AND `identifier` = 'STATUS_PING';
INSERT INTO `DBPREFIXtranslations` (`langid`, `identifier`, `value`, `comment`)
  SELECT @ru, 'STATUS_PING', 'Пинг:', NULL FROM DUAL
  WHERE @ru IS NOT NULL AND NOT EXISTS (SELECT 1 FROM `DBPREFIXtranslations` WHERE `langid` = @ru AND `identifier` = 'STATUS_PING');

UPDATE `DBPREFIXtranslations` SET `value` = 'Потеря пакетов:'
  WHERE `langid` = @ru AND `identifier` = 'STATUS_PACKETLOSS';
INSERT INTO `DBPREFIXtranslations` (`langid`, `identifier`, `value`, `comment`)
  SELECT @ru, 'STATUS_PACKETLOSS', 'Потеря пакетов:', NULL FROM DUAL
  WHERE @ru IS NOT NULL AND NOT EXISTS (SELECT 1 FROM `DBPREFIXtranslations` WHERE `langid` = @ru AND `identifier` = 'STATUS_PACKETLOSS');

UPDATE `DBPREFIXtranslations` SET `value` = 'Не удалось получить статус сервера'
  WHERE `langid` = @ru AND `identifier` = 'STATUS_ERROR';
INSERT INTO `DBPREFIXtranslations` (`langid`, `identifier`, `value`, `comment`)
  SELECT @ru, 'STATUS_ERROR', 'Не удалось получить статус сервера', NULL FROM DUAL
  WHERE @ru IS NOT NULL AND NOT EXISTS (SELECT 1 FROM `DBPREFIXtranslations` WHERE `langid` = @ru AND `identifier` = 'STATUS_ERROR');

UPDATE `DBPREFIXtranslations` SET `value` = 'Привет! Ваш код для входа на сайт: [b]{0}[/b]'
  WHERE `langid` = @ru AND `identifier` = 'LOGIN_CONFIRMATION_CODE';
INSERT INTO `DBPREFIXtranslations` (`langid`, `identifier`, `value`, `comment`)
  SELECT @ru, 'LOGIN_CONFIRMATION_CODE', 'Привет! Ваш код для входа на сайт: [b]{0}[/b]', NULL FROM DUAL
  WHERE @ru IS NOT NULL AND NOT EXISTS (SELECT 1 FROM `DBPREFIXtranslations` WHERE `langid` = @ru AND `identifier` = 'LOGIN_CONFIRMATION_CODE');

UPDATE `DBPREFIXtranslations` SET `value` = 'Ваш браузер не поддерживается. Чтобы пользоваться сайтом, установите последнюю версию Chrome, Firefox, Safari или Edge.'
  WHERE `langid` = @ru AND `identifier` = 'UNSUPPORTED_BROWSER';
INSERT INTO `DBPREFIXtranslations` (`langid`, `identifier`, `value`, `comment`)
  SELECT @ru, 'UNSUPPORTED_BROWSER', 'Ваш браузер не поддерживается. Чтобы пользоваться сайтом, установите последнюю версию Chrome, Firefox, Safari или Edge.', NULL FROM DUAL
  WHERE @ru IS NOT NULL AND NOT EXISTS (SELECT 1 FROM `DBPREFIXtranslations` WHERE `langid` = @ru AND `identifier` = 'UNSUPPORTED_BROWSER');

UPDATE `DBPREFIXtranslations` SET `value` = ' | TS-website на русском'
  WHERE `langid` = @ru AND `identifier` = 'WEBSITE_TITLE';
INSERT INTO `DBPREFIXtranslations` (`langid`, `identifier`, `value`, `comment`)
  SELECT @ru, 'WEBSITE_TITLE', ' | TS-website на русском', NULL FROM DUAL
  WHERE @ru IS NOT NULL AND NOT EXISTS (SELECT 1 FROM `DBPREFIXtranslations` WHERE `langid` = @ru AND `identifier` = 'WEBSITE_TITLE');

UPDATE `DBPREFIXtranslations` SET `value` = 'В сети'
  WHERE `langid` = @ru AND `identifier` = 'ADMIN_STATUS_ONLINE';
INSERT INTO `DBPREFIXtranslations` (`langid`, `identifier`, `value`, `comment`)
  SELECT @ru, 'ADMIN_STATUS_ONLINE', 'В сети', NULL FROM DUAL
  WHERE @ru IS NOT NULL AND NOT EXISTS (SELECT 1 FROM `DBPREFIXtranslations` WHERE `langid` = @ru AND `identifier` = 'ADMIN_STATUS_ONLINE');

UPDATE `DBPREFIXtranslations` SET `value` = 'Нет на месте'
  WHERE `langid` = @ru AND `identifier` = 'ADMIN_STATUS_AWAY';
INSERT INTO `DBPREFIXtranslations` (`langid`, `identifier`, `value`, `comment`)
  SELECT @ru, 'ADMIN_STATUS_AWAY', 'Нет на месте', NULL FROM DUAL
  WHERE @ru IS NOT NULL AND NOT EXISTS (SELECT 1 FROM `DBPREFIXtranslations` WHERE `langid` = @ru AND `identifier` = 'ADMIN_STATUS_AWAY');

UPDATE `DBPREFIXtranslations` SET `value` = 'Не в сети'
  WHERE `langid` = @ru AND `identifier` = 'ADMIN_STATUS_OFFLINE';
INSERT INTO `DBPREFIXtranslations` (`langid`, `identifier`, `value`, `comment`)
  SELECT @ru, 'ADMIN_STATUS_OFFLINE', 'Не в сети', NULL FROM DUAL
  WHERE @ru IS NOT NULL AND NOT EXISTS (SELECT 1 FROM `DBPREFIXtranslations` WHERE `langid` = @ru AND `identifier` = 'ADMIN_STATUS_OFFLINE');

UPDATE `DBPREFIXtranslations` SET `value` = 'Никого нет'
  WHERE `langid` = @ru AND `identifier` = 'ADMIN_STATUS_EMPTY_GROUP';
INSERT INTO `DBPREFIXtranslations` (`langid`, `identifier`, `value`, `comment`)
  SELECT @ru, 'ADMIN_STATUS_EMPTY_GROUP', 'Никого нет', NULL FROM DUAL
  WHERE @ru IS NOT NULL AND NOT EXISTS (SELECT 1 FROM `DBPREFIXtranslations` WHERE `langid` = @ru AND `identifier` = 'ADMIN_STATUS_EMPTY_GROUP');

UPDATE `DBPREFIXtranslations` SET `value` = 'Список администрации пуст'
  WHERE `langid` = @ru AND `identifier` = 'ADMIN_STATUS_EMPTY_STATUS';
INSERT INTO `DBPREFIXtranslations` (`langid`, `identifier`, `value`, `comment`)
  SELECT @ru, 'ADMIN_STATUS_EMPTY_STATUS', 'Список администрации пуст', NULL FROM DUAL
  WHERE @ru IS NOT NULL AND NOT EXISTS (SELECT 1 FROM `DBPREFIXtranslations` WHERE `langid` = @ru AND `identifier` = 'ADMIN_STATUS_EMPTY_STATUS');

UPDATE `DBPREFIXtranslations` SET `value` = 'Группы'
  WHERE `langid` = @ru AND `identifier` = 'ASSIGNER_TITLE';
INSERT INTO `DBPREFIXtranslations` (`langid`, `identifier`, `value`, `comment`)
  SELECT @ru, 'ASSIGNER_TITLE', 'Группы', NULL FROM DUAL
  WHERE @ru IS NOT NULL AND NOT EXISTS (SELECT 1 FROM `DBPREFIXtranslations` WHERE `langid` = @ru AND `identifier` = 'ASSIGNER_TITLE');

UPDATE `DBPREFIXtranslations` SET `value` = 'Нажмите на строку, чтобы увидеть подробности бана'
  WHERE `langid` = @ru AND `identifier` = 'BANS_VIEW_MORE_TIP';
INSERT INTO `DBPREFIXtranslations` (`langid`, `identifier`, `value`, `comment`)
  SELECT @ru, 'BANS_VIEW_MORE_TIP', 'Нажмите на строку, чтобы увидеть подробности бана', NULL FROM DUAL
  WHERE @ru IS NOT NULL AND NOT EXISTS (SELECT 1 FROM `DBPREFIXtranslations` WHERE `langid` = @ru AND `identifier` = 'BANS_VIEW_MORE_TIP');

UPDATE `DBPREFIXtranslations` SET `value` = 'Новостей пока нет'
  WHERE `langid` = @ru AND `identifier` = 'HOME_EMPTY';
INSERT INTO `DBPREFIXtranslations` (`langid`, `identifier`, `value`, `comment`)
  SELECT @ru, 'HOME_EMPTY', 'Новостей пока нет', NULL FROM DUAL
  WHERE @ru IS NOT NULL AND NOT EXISTS (SELECT 1 FROM `DBPREFIXtranslations` WHERE `langid` = @ru AND `identifier` = 'HOME_EMPTY');

UPDATE `DBPREFIXtranslations` SET `value` = 'Изменено: {0}'
  WHERE `langid` = @ru AND `identifier` = 'HOME_NEWS_EDITED';
INSERT INTO `DBPREFIXtranslations` (`langid`, `identifier`, `value`, `comment`)
  SELECT @ru, 'HOME_NEWS_EDITED', 'Изменено: {0}', NULL FROM DUAL
  WHERE @ru IS NOT NULL AND NOT EXISTS (SELECT 1 FROM `DBPREFIXtranslations` WHERE `langid` = @ru AND `identifier` = 'HOME_NEWS_EDITED');

UPDATE `DBPREFIXtranslations` SET `value` = 'Администрация'
  WHERE `langid` = @ru AND `identifier` = 'ADMIN_STATUS_PANEL_TITLE';
INSERT INTO `DBPREFIXtranslations` (`langid`, `identifier`, `value`, `comment`)
  SELECT @ru, 'ADMIN_STATUS_PANEL_TITLE', 'Администрация', NULL FROM DUAL
  WHERE @ru IS NOT NULL AND NOT EXISTS (SELECT 1 FROM `DBPREFIXtranslations` WHERE `langid` = @ru AND `identifier` = 'ADMIN_STATUS_PANEL_TITLE');

UPDATE `DBPREFIXtranslations` SET `value` = 'Скрыть тех, кто не в сети'
  WHERE `langid` = @ru AND `identifier` = 'ADMIN_STATUS_HIDE_OFFLINE_TIP';
INSERT INTO `DBPREFIXtranslations` (`langid`, `identifier`, `value`, `comment`)
  SELECT @ru, 'ADMIN_STATUS_HIDE_OFFLINE_TIP', 'Скрыть тех, кто не в сети', NULL FROM DUAL
  WHERE @ru IS NOT NULL AND NOT EXISTS (SELECT 1 FROM `DBPREFIXtranslations` WHERE `langid` = @ru AND `identifier` = 'ADMIN_STATUS_HIDE_OFFLINE_TIP');

UPDATE `DBPREFIXtranslations` SET `value` = 'Показать тех, кто не в сети'
  WHERE `langid` = @ru AND `identifier` = 'ADMIN_STATUS_SHOW_OFFLINE_TIP';
INSERT INTO `DBPREFIXtranslations` (`langid`, `identifier`, `value`, `comment`)
  SELECT @ru, 'ADMIN_STATUS_SHOW_OFFLINE_TIP', 'Показать тех, кто не в сети', NULL FROM DUAL
  WHERE @ru IS NOT NULL AND NOT EXISTS (SELECT 1 FROM `DBPREFIXtranslations` WHERE `langid` = @ru AND `identifier` = 'ADMIN_STATUS_SHOW_OFFLINE_TIP');

UPDATE `DBPREFIXtranslations` SET `value` = 'Не удалось загрузить список администрации'
  WHERE `langid` = @ru AND `identifier` = 'ADMIN_STATUS_ERROR';
INSERT INTO `DBPREFIXtranslations` (`langid`, `identifier`, `value`, `comment`)
  SELECT @ru, 'ADMIN_STATUS_ERROR', 'Не удалось загрузить список администрации', NULL FROM DUAL
  WHERE @ru IS NOT NULL AND NOT EXISTS (SELECT 1 FROM `DBPREFIXtranslations` WHERE `langid` = @ru AND `identifier` = 'ADMIN_STATUS_ERROR');

UPDATE `DBPREFIXtranslations` SET `value` = 'Меню'
  WHERE `langid` = @ru AND `identifier` = 'NAV_TOGGLE';
INSERT INTO `DBPREFIXtranslations` (`langid`, `identifier`, `value`, `comment`)
  SELECT @ru, 'NAV_TOGGLE', 'Меню', NULL FROM DUAL
  WHERE @ru IS NOT NULL AND NOT EXISTS (SELECT 1 FROM `DBPREFIXtranslations` WHERE `langid` = @ru AND `identifier` = 'NAV_TOGGLE');

UPDATE `DBPREFIXtranslations` SET `value` = 'Каналы'
  WHERE `langid` = @ru AND `identifier` = 'NAV_VIEWER';
INSERT INTO `DBPREFIXtranslations` (`langid`, `identifier`, `value`, `comment`)
  SELECT @ru, 'NAV_VIEWER', 'Каналы', NULL FROM DUAL
  WHERE @ru IS NOT NULL AND NOT EXISTS (SELECT 1 FROM `DBPREFIXtranslations` WHERE `langid` = @ru AND `identifier` = 'NAV_VIEWER');

UPDATE `DBPREFIXtranslations` SET `value` = 'Баны'
  WHERE `langid` = @ru AND `identifier` = 'NAV_BANS';
INSERT INTO `DBPREFIXtranslations` (`langid`, `identifier`, `value`, `comment`)
  SELECT @ru, 'NAV_BANS', 'Баны', NULL FROM DUAL
  WHERE @ru IS NOT NULL AND NOT EXISTS (SELECT 1 FROM `DBPREFIXtranslations` WHERE `langid` = @ru AND `identifier` = 'NAV_BANS');

UPDATE `DBPREFIXtranslations` SET `value` = 'Каналы'
  WHERE `langid` = @ru AND `identifier` = 'VIEWER_TITLE';
INSERT INTO `DBPREFIXtranslations` (`langid`, `identifier`, `value`, `comment`)
  SELECT @ru, 'VIEWER_TITLE', 'Каналы', NULL FROM DUAL
  WHERE @ru IS NOT NULL AND NOT EXISTS (SELECT 1 FROM `DBPREFIXtranslations` WHERE `langid` = @ru AND `identifier` = 'VIEWER_TITLE');

UPDATE `DBPREFIXtranslations` SET `value` = 'Каналы'
  WHERE `langid` = @ru AND `identifier` = 'VIEWER_PANEL_TITLE';
INSERT INTO `DBPREFIXtranslations` (`langid`, `identifier`, `value`, `comment`)
  SELECT @ru, 'VIEWER_PANEL_TITLE', 'Каналы', NULL FROM DUAL
  WHERE @ru IS NOT NULL AND NOT EXISTS (SELECT 1 FROM `DBPREFIXtranslations` WHERE `langid` = @ru AND `identifier` = 'VIEWER_PANEL_TITLE');

UPDATE `DBPREFIXtranslations` SET `value` = 'Нажмите на канал, чтобы зайти в него. Наведите курсор на пользователя, чтобы увидеть подробности'
  WHERE `langid` = @ru AND `identifier` = 'VIEWER_TIP_ALERT';
INSERT INTO `DBPREFIXtranslations` (`langid`, `identifier`, `value`, `comment`)
  SELECT @ru, 'VIEWER_TIP_ALERT', 'Нажмите на канал, чтобы зайти в него. Наведите курсор на пользователя, чтобы увидеть подробности', NULL FROM DUAL
  WHERE @ru IS NOT NULL AND NOT EXISTS (SELECT 1 FROM `DBPREFIXtranslations` WHERE `langid` = @ru AND `identifier` = 'VIEWER_TIP_ALERT');

UPDATE `DBPREFIXtranslations` SET `value` = 'Не удалось загрузить данные'
  WHERE `langid` = @ru AND `identifier` = 'VIEWER_ERROR';
INSERT INTO `DBPREFIXtranslations` (`langid`, `identifier`, `value`, `comment`)
  SELECT @ru, 'VIEWER_ERROR', 'Не удалось загрузить данные', NULL FROM DUAL
  WHERE @ru IS NOT NULL AND NOT EXISTS (SELECT 1 FROM `DBPREFIXtranslations` WHERE `langid` = @ru AND `identifier` = 'VIEWER_ERROR');

UPDATE `DBPREFIXtranslations` SET `value` = 'Подключиться к этому каналу?'
  WHERE `langid` = @ru AND `identifier` = 'VIEWER_CONNECTION_CONFIRMATION';
INSERT INTO `DBPREFIXtranslations` (`langid`, `identifier`, `value`, `comment`)
  SELECT @ru, 'VIEWER_CONNECTION_CONFIRMATION', 'Подключиться к этому каналу?', NULL FROM DUAL
  WHERE @ru IS NOT NULL AND NOT EXISTS (SELECT 1 FROM `DBPREFIXtranslations` WHERE `langid` = @ru AND `identifier` = 'VIEWER_CONNECTION_CONFIRMATION');

UPDATE `DBPREFIXtranslations` SET `value` = 'Последняя активность:'
  WHERE `langid` = @ru AND `identifier` = 'VIEWER_CLIENT_LASTACTIVE';
INSERT INTO `DBPREFIXtranslations` (`langid`, `identifier`, `value`, `comment`)
  SELECT @ru, 'VIEWER_CLIENT_LASTACTIVE', 'Последняя активность:', NULL FROM DUAL
  WHERE @ru IS NOT NULL AND NOT EXISTS (SELECT 1 FROM `DBPREFIXtranslations` WHERE `langid` = @ru AND `identifier` = 'VIEWER_CLIENT_LASTACTIVE');

UPDATE `DBPREFIXtranslations` SET `value` = 'Время в сети:'
  WHERE `langid` = @ru AND `identifier` = 'VIEWER_CLIENT_ONLINE';
INSERT INTO `DBPREFIXtranslations` (`langid`, `identifier`, `value`, `comment`)
  SELECT @ru, 'VIEWER_CLIENT_ONLINE', 'Время в сети:', NULL FROM DUAL
  WHERE @ru IS NOT NULL AND NOT EXISTS (SELECT 1 FROM `DBPREFIXtranslations` WHERE `langid` = @ru AND `identifier` = 'VIEWER_CLIENT_ONLINE');

UPDATE `DBPREFIXtranslations` SET `value` = 'Информация о пользователе'
  WHERE `langid` = @ru AND `identifier` = 'VIEWER_CLIENT_TITLE';
INSERT INTO `DBPREFIXtranslations` (`langid`, `identifier`, `value`, `comment`)
  SELECT @ru, 'VIEWER_CLIENT_TITLE', 'Информация о пользователе', NULL FROM DUAL
  WHERE @ru IS NOT NULL AND NOT EXISTS (SELECT 1 FROM `DBPREFIXtranslations` WHERE `langid` = @ru AND `identifier` = 'VIEWER_CLIENT_TITLE');

UPDATE `DBPREFIXtranslations` SET `value` = 'Канал по умолчанию'
  WHERE `langid` = @ru AND `identifier` = 'VIEWER_DEFAULT_CHANNEL';
INSERT INTO `DBPREFIXtranslations` (`langid`, `identifier`, `value`, `comment`)
  SELECT @ru, 'VIEWER_DEFAULT_CHANNEL', 'Канал по умолчанию', NULL FROM DUAL
  WHERE @ru IS NOT NULL AND NOT EXISTS (SELECT 1 FROM `DBPREFIXtranslations` WHERE `langid` = @ru AND `identifier` = 'VIEWER_DEFAULT_CHANNEL');

UPDATE `DBPREFIXtranslations` SET `value` = 'Защищён паролем'
  WHERE `langid` = @ru AND `identifier` = 'VIEWER_CHANNEL_PASSWORD';
INSERT INTO `DBPREFIXtranslations` (`langid`, `identifier`, `value`, `comment`)
  SELECT @ru, 'VIEWER_CHANNEL_PASSWORD', 'Защищён паролем', NULL FROM DUAL
  WHERE @ru IS NOT NULL AND NOT EXISTS (SELECT 1 FROM `DBPREFIXtranslations` WHERE `langid` = @ru AND `identifier` = 'VIEWER_CHANNEL_PASSWORD');

UPDATE `DBPREFIXtranslations` SET `value` = 'Музыкальный кодек'
  WHERE `langid` = @ru AND `identifier` = 'VIEWER_CHANNEL_MUSIC_CODED';
INSERT INTO `DBPREFIXtranslations` (`langid`, `identifier`, `value`, `comment`)
  SELECT @ru, 'VIEWER_CHANNEL_MUSIC_CODED', 'Музыкальный кодек', NULL FROM DUAL
  WHERE @ru IS NOT NULL AND NOT EXISTS (SELECT 1 FROM `DBPREFIXtranslations` WHERE `langid` = @ru AND `identifier` = 'VIEWER_CHANNEL_MUSIC_CODED');

UPDATE `DBPREFIXtranslations` SET `value` = 'Нет на месте'
  WHERE `langid` = @ru AND `identifier` = 'VIEWER_CLIENT_AWAY';
INSERT INTO `DBPREFIXtranslations` (`langid`, `identifier`, `value`, `comment`)
  SELECT @ru, 'VIEWER_CLIENT_AWAY', 'Нет на месте', NULL FROM DUAL
  WHERE @ru IS NOT NULL AND NOT EXISTS (SELECT 1 FROM `DBPREFIXtranslations` WHERE `langid` = @ru AND `identifier` = 'VIEWER_CLIENT_AWAY');

UPDATE `DBPREFIXtranslations` SET `value` = 'Звук выключен'
  WHERE `langid` = @ru AND `identifier` = 'VIEWER_CLIENT_OUTPUT_MUTED';
INSERT INTO `DBPREFIXtranslations` (`langid`, `identifier`, `value`, `comment`)
  SELECT @ru, 'VIEWER_CLIENT_OUTPUT_MUTED', 'Звук выключен', NULL FROM DUAL
  WHERE @ru IS NOT NULL AND NOT EXISTS (SELECT 1 FROM `DBPREFIXtranslations` WHERE `langid` = @ru AND `identifier` = 'VIEWER_CLIENT_OUTPUT_MUTED');

UPDATE `DBPREFIXtranslations` SET `value` = 'Микрофон выключен'
  WHERE `langid` = @ru AND `identifier` = 'VIEWER_CLIENT_MIC_MUTED';
INSERT INTO `DBPREFIXtranslations` (`langid`, `identifier`, `value`, `comment`)
  SELECT @ru, 'VIEWER_CLIENT_MIC_MUTED', 'Микрофон выключен', NULL FROM DUAL
  WHERE @ru IS NOT NULL AND NOT EXISTS (SELECT 1 FROM `DBPREFIXtranslations` WHERE `langid` = @ru AND `identifier` = 'VIEWER_CLIENT_MIC_MUTED');

UPDATE `DBPREFIXtranslations` SET `value` = 'Иконка пользователя'
  WHERE `langid` = @ru AND `identifier` = 'VIEWER_CLIENT_ICON';
INSERT INTO `DBPREFIXtranslations` (`langid`, `identifier`, `value`, `comment`)
  SELECT @ru, 'VIEWER_CLIENT_ICON', 'Иконка пользователя', NULL FROM DUAL
  WHERE @ru IS NOT NULL AND NOT EXISTS (SELECT 1 FROM `DBPREFIXtranslations` WHERE `langid` = @ru AND `identifier` = 'VIEWER_CLIENT_ICON');

UPDATE `DBPREFIXtranslations` SET `value` = 'Нет права голоса'
  WHERE `langid` = @ru AND `identifier` = 'VIEWER_CLIENT_TALK_POWER_INSUFFICIENT';
INSERT INTO `DBPREFIXtranslations` (`langid`, `identifier`, `value`, `comment`)
  SELECT @ru, 'VIEWER_CLIENT_TALK_POWER_INSUFFICIENT', 'Нет права голоса', NULL FROM DUAL
  WHERE @ru IS NOT NULL AND NOT EXISTS (SELECT 1 FROM `DBPREFIXtranslations` WHERE `langid` = @ru AND `identifier` = 'VIEWER_CLIENT_TALK_POWER_INSUFFICIENT');

UPDATE `DBPREFIXtranslations` SET `value` = 'Войдите, чтобы выбрать группы'
  WHERE `langid` = @ru AND `identifier` = 'ASSIGNER_NOT_LOGGED_IN';
INSERT INTO `DBPREFIXtranslations` (`langid`, `identifier`, `value`, `comment`)
  SELECT @ru, 'ASSIGNER_NOT_LOGGED_IN', 'Войдите, чтобы выбрать группы', NULL FROM DUAL
  WHERE @ru IS NOT NULL AND NOT EXISTS (SELECT 1 FROM `DBPREFIXtranslations` WHERE `langid` = @ru AND `identifier` = 'ASSIGNER_NOT_LOGGED_IN');

UPDATE `DBPREFIXtranslations` SET `value` = 'Такой набор групп выбрать нельзя'
  WHERE `langid` = @ru AND `identifier` = 'ASSIGNER_INVALID_GROUPS';
INSERT INTO `DBPREFIXtranslations` (`langid`, `identifier`, `value`, `comment`)
  SELECT @ru, 'ASSIGNER_INVALID_GROUPS', 'Такой набор групп выбрать нельзя', NULL FROM DUAL
  WHERE @ru IS NOT NULL AND NOT EXISTS (SELECT 1 FROM `DBPREFIXtranslations` WHERE `langid` = @ru AND `identifier` = 'ASSIGNER_INVALID_GROUPS');

UPDATE `DBPREFIXtranslations` SET `value` = 'Администратор сайта ещё не настроил выбор групп'
  WHERE `langid` = @ru AND `identifier` = 'ASSIGNER_NOT_CONFIGURED';
INSERT INTO `DBPREFIXtranslations` (`langid`, `identifier`, `value`, `comment`)
  SELECT @ru, 'ASSIGNER_NOT_CONFIGURED', 'Администратор сайта ещё не настроил выбор групп', NULL FROM DUAL
  WHERE @ru IS NOT NULL AND NOT EXISTS (SELECT 1 FROM `DBPREFIXtranslations` WHERE `langid` = @ru AND `identifier` = 'ASSIGNER_NOT_CONFIGURED');

UPDATE `DBPREFIXtranslations` SET `value` = 'Ваши группы обновлены'
  WHERE `langid` = @ru AND `identifier` = 'ASSIGNER_SAVE_SUCCESS';
INSERT INTO `DBPREFIXtranslations` (`langid`, `identifier`, `value`, `comment`)
  SELECT @ru, 'ASSIGNER_SAVE_SUCCESS', 'Ваши группы обновлены', NULL FROM DUAL
  WHERE @ru IS NOT NULL AND NOT EXISTS (SELECT 1 FROM `DBPREFIXtranslations` WHERE `langid` = @ru AND `identifier` = 'ASSIGNER_SAVE_SUCCESS');

UPDATE `DBPREFIXtranslations` SET `value` = 'Не удалось изменить группы'
  WHERE `langid` = @ru AND `identifier` = 'ASSIGNER_SAVE_ERROR';
INSERT INTO `DBPREFIXtranslations` (`langid`, `identifier`, `value`, `comment`)
  SELECT @ru, 'ASSIGNER_SAVE_ERROR', 'Не удалось изменить группы', NULL FROM DUAL
  WHERE @ru IS NOT NULL AND NOT EXISTS (SELECT 1 FROM `DBPREFIXtranslations` WHERE `langid` = @ru AND `identifier` = 'ASSIGNER_SAVE_ERROR');

UPDATE `DBPREFIXtranslations` SET `value` = 'Вы ничего не изменили'
  WHERE `langid` = @ru AND `identifier` = 'ASSIGNER_SAVE_NO_CHANGE';
INSERT INTO `DBPREFIXtranslations` (`langid`, `identifier`, `value`, `comment`)
  SELECT @ru, 'ASSIGNER_SAVE_NO_CHANGE', 'Вы ничего не изменили', NULL FROM DUAL
  WHERE @ru IS NOT NULL AND NOT EXISTS (SELECT 1 FROM `DBPREFIXtranslations` WHERE `langid` = @ru AND `identifier` = 'ASSIGNER_SAVE_NO_CHANGE');

UPDATE `DBPREFIXtranslations` SET `value` = 'У вас нет ни одной из нужных групп, поэтому выбор групп недоступен.'
  WHERE `langid` = @ru AND `identifier` = 'ASSIGNER_NO_REQUIRED_GROUPS';
INSERT INTO `DBPREFIXtranslations` (`langid`, `identifier`, `value`, `comment`)
  SELECT @ru, 'ASSIGNER_NO_REQUIRED_GROUPS', 'У вас нет ни одной из нужных групп, поэтому выбор групп недоступен.', NULL FROM DUAL
  WHERE @ru IS NOT NULL AND NOT EXISTS (SELECT 1 FROM `DBPREFIXtranslations` WHERE `langid` = @ru AND `identifier` = 'ASSIGNER_NO_REQUIRED_GROUPS');

UPDATE `DBPREFIXtranslations` SET `value` = 'Группы снова можно будет изменить через {0}.'
  WHERE `langid` = @ru AND `identifier` = 'ASSIGNER_COOLDOWN_MESSAGE';
INSERT INTO `DBPREFIXtranslations` (`langid`, `identifier`, `value`, `comment`)
  SELECT @ru, 'ASSIGNER_COOLDOWN_MESSAGE', 'Группы снова можно будет изменить через {0}.', NULL FROM DUAL
  WHERE @ru IS NOT NULL AND NOT EXISTS (SELECT 1 FROM `DBPREFIXtranslations` WHERE `langid` = @ru AND `identifier` = 'ASSIGNER_COOLDOWN_MESSAGE');

-- English grammar fixes

UPDATE `DBPREFIXtranslations` SET `value` = '<b>Do you like cookies?</b> We use cookies to ensure you get the best experience on our website. <a href="http://cookiesandyou.com/" target="_blank">Learn more</a>'
  WHERE `langid` = @en AND `identifier` = 'COOKIEALERT_MESSAGE';
INSERT INTO `DBPREFIXtranslations` (`langid`, `identifier`, `value`, `comment`)
  SELECT @en, 'COOKIEALERT_MESSAGE', '<b>Do you like cookies?</b> We use cookies to ensure you get the best experience on our website. <a href="http://cookiesandyou.com/" target="_blank">Learn more</a>', NULL FROM DUAL
  WHERE @en IS NOT NULL AND NOT EXISTS (SELECT 1 FROM `DBPREFIXtranslations` WHERE `langid` = @en AND `identifier` = 'COOKIEALERT_MESSAGE');

UPDATE `DBPREFIXtranslations` SET `value` = 'Cannot get data for "{0}"! Please contact the website owner.'
  WHERE `langid` = @en AND `identifier` = 'CANNOT_GET_DATA';
INSERT INTO `DBPREFIXtranslations` (`langid`, `identifier`, `value`, `comment`)
  SELECT @en, 'CANNOT_GET_DATA', 'Cannot get data for "{0}"! Please contact the website owner.', NULL FROM DUAL
  WHERE @en IS NOT NULL AND NOT EXISTS (SELECT 1 FROM `DBPREFIXtranslations` WHERE `langid` = @en AND `identifier` = 'CANNOT_GET_DATA');

UPDATE `DBPREFIXtranslations` SET `value` = 'Hi, here''s your confirmation code to log in: [b]{0}[/b]'
  WHERE `langid` = @en AND `identifier` = 'LOGIN_CONFIRMATION_CODE';
INSERT INTO `DBPREFIXtranslations` (`langid`, `identifier`, `value`, `comment`)
  SELECT @en, 'LOGIN_CONFIRMATION_CODE', 'Hi, here''s your confirmation code to log in: [b]{0}[/b]', NULL FROM DUAL
  WHERE @en IS NOT NULL AND NOT EXISTS (SELECT 1 FROM `DBPREFIXtranslations` WHERE `langid` = @en AND `identifier` = 'LOGIN_CONFIRMATION_CODE');

UPDATE `DBPREFIXtranslations` SET `value` = 'Log in before using the group assigner'
  WHERE `langid` = @en AND `identifier` = 'ASSIGNER_NOT_LOGGED_IN';
INSERT INTO `DBPREFIXtranslations` (`langid`, `identifier`, `value`, `comment`)
  SELECT @en, 'ASSIGNER_NOT_LOGGED_IN', 'Log in before using the group assigner', NULL FROM DUAL
  WHERE @en IS NOT NULL AND NOT EXISTS (SELECT 1 FROM `DBPREFIXtranslations` WHERE `langid` = @en AND `identifier` = 'ASSIGNER_NOT_LOGGED_IN');

UPDATE `DBPREFIXtranslations` SET `value` = 'The group assigner is not configured by the website administrator'
  WHERE `langid` = @en AND `identifier` = 'ASSIGNER_NOT_CONFIGURED';
INSERT INTO `DBPREFIXtranslations` (`langid`, `identifier`, `value`, `comment`)
  SELECT @en, 'ASSIGNER_NOT_CONFIGURED', 'The group assigner is not configured by the website administrator', NULL FROM DUAL
  WHERE @en IS NOT NULL AND NOT EXISTS (SELECT 1 FROM `DBPREFIXtranslations` WHERE `langid` = @en AND `identifier` = 'ASSIGNER_NOT_CONFIGURED');

UPDATE `DBPREFIXtranslations` SET `value` = 'No changes were made'
  WHERE `langid` = @en AND `identifier` = 'ASSIGNER_SAVE_NO_CHANGE';
INSERT INTO `DBPREFIXtranslations` (`langid`, `identifier`, `value`, `comment`)
  SELECT @en, 'ASSIGNER_SAVE_NO_CHANGE', 'No changes were made', NULL FROM DUAL
  WHERE @en IS NOT NULL AND NOT EXISTS (SELECT 1 FROM `DBPREFIXtranslations` WHERE `langid` = @en AND `identifier` = 'ASSIGNER_SAVE_NO_CHANGE');

COMMIT;
