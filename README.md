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
TeamSpeak 6 servers have no raw ServerQuery (port 10011) anymore, only SSH and HTTP query.
TS-website connects to them over **SSH query** (default port `10022`, powered by phpseclib - no PHP extension needed).

1. Enable SSH query on the TS6 server: `--query-ssh-enable` or `TSSERVER_QUERY_SSH_ENABLED=1`
2. Run `composer install` in `src` (new dependency: `phpseclib/phpseclib`)
3. In the installer, enter query port `10022` and tick **"Use SSH query"**

Already installed? Switch an existing install to SSH query in the database (add your table prefix to `config` if you set one):
```sql
INSERT INTO config (identifier, type, value) VALUES ('query_ssh', 'BOOL', 'true');
UPDATE config SET value = '10022' WHERE identifier = 'query_port';
```

### Other stuff
I am happy to take any programming-related requests, add additional features or modify the code to suit your needs for a small donation :) I am experienced at Java, PHP, HTML, CSS, Javascript, SQL, server configurations etc.

For business enquiries only: **wruczekk** at **gmail.com**, for anything else please join our [Telegram group](https://t.me/ts-website).
