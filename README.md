<h3 align="center">TS-website - free website for your TeamSpeak 3 server</h3>
<br>

[![Website screenshot](https://i.imgur.com/FuDJyGU.png?2)](https://imgur.com/a/3vfIPJQ)

<p align="center">
    <a href="https://imgur.com/a/3vfIPJQ" target="_blank">View more screenshots</a> |
    <a href="https://github.com/Wruczek/ts-website/wiki/%5BDE%5D-Readme-%7C-Liesmich-%5B2.x%5D" target="_blank">🇩🇪 Deutsches Readme</a>
</p>

<p align="center">
<b>Need help? <a href="https://t.me/tswebsite">Join our telegram group</a></b> for news, announcements, help and general chat about ts-website.
</p>

<hr>

#### Useful links
- [Demo](https://ts.wruczek.tech/)
- [Installation](https://github.com/Wruczek/ts-website/wiki/%5BEN%5D-Website-Installation)
- [Wiki](https://github.com/Wruczek/ts-website/wiki)
- [Report Issues / Suggestions](https://github.com/Wruczek/ts-website/issues/new)
- [Translate ts-website](https://wruczek.oneskyapp.com/collaboration/project/325562)
- **[ts-website Telegram group](https://t.me/tswebsite) - support, announcements, and general chat**

#### Main Features
- News page, dynamic server status, customizable admin status, server viewer, group assigner, ban list, rules, FAQ, impressum
- Ability for users to login via TeamSpeak
- Multiple languages with auto-detection for default language
- Modern and responsive design
- Caching
- Free and Open source, under GPL-3.0

#### TeamSpeak 6 support
TeamSpeak 6 servers have no raw ServerQuery (port 10011) anymore, only SSH and HTTP(S) query.
TS-website can connect using any of them - pick the **query mode** in the installer:

| Mode    | Default port | Login                          |
|---------|--------------|--------------------------------|
| `raw`   | 10011        | query username + password (TS3 only) |
| `ssh`   | 10022        | query username + password      |
| `http`  | 10080        | API key (entered as password)  |
| `https` | 10443        | API key (entered as password)  |

- SSH: enable it with `--query-ssh-enable` / `TSSERVER_QUERY_SSH_ENABLED=1`. Uses phpseclib, no PHP extension needed - run `composer install` in `src`.
- HTTP: enable it with `--query-http-enable` / `TSSERVER_QUERY_HTTP_ENABLED=1`, the API key is set with `TSSERVER_QUERY_ADMIN_API_KEY`.
  Server/channel icons are not available over WebQuery (file transfer commands are out of the API key scope), the website works without them.
- **Add the website server IP to `query_ip_allowlist.txt`**, otherwise TS flood protection will temporarily ban it. If TS-website runs on the same host as a TS server in Docker, add the Docker network gateway (e.g. `172.18.0.0/16`), not only `127.0.0.1`. TS6 reloads this file without a restart.

Already installed? Switch an existing install in the database (add your table prefix to `config` if you set one):
```sql
INSERT INTO config (identifier, type, value) VALUES ('query_mode', 'STRING', 'http');
UPDATE config SET value = '10080' WHERE identifier = 'query_port';
UPDATE config SET value = 'YOUR_API_KEY' WHERE identifier = 'query_password';
```

### Other stuff
I am happy to take any programming-related requests, add additional features or modify the code to suit your needs for a small donation :) I am experienced at Java, PHP, HTML, CSS, Javascript, SQL, server configurations etc.

For business enquiries only: **wruczekk** at **gmail.com**, for anything else please join our [Telegram group](https://t.me/ts-website).

#### Russian translation
The Russian translation has been reviewed and completed. New installations get it automatically. To update an existing installation, run `src/installer/upgrade/ru-translations.sql` against the website database, replacing `DBPREFIX` with your table prefix (or removing it if you have none):
```sh
sed 's/DBPREFIX/tsw_/g' src/installer/upgrade/ru-translations.sql | mysql -u USER -p DATABASE
```
The script is safe to run more than once: it updates the existing Russian rows, adds the missing ones and also applies a few English grammar fixes. Translations are cached for up to 5 minutes (`cache_languages`), delete `src/private/cache/*.cache.php` to see the changes right away.
