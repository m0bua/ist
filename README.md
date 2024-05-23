Site status checker with TG bot.

## Configs examples
### Telegram API (tg.json file):
```json
{
    "id":"id",
    "key":"api_key"
}
```
### Direct worker conf (direct_test.json file):
```json
{
    "server":"google.com",
    "tgChat":1234567890,
    "msgPattern":"{status:\ud83d\udd34|\ud83d\udfe2} Test {status:power off|power on}{after: after #}!",
    "current":true,
    "0":"2000-01-01T00:00:00+00:00",
    "1":"2000-12-31T23:59:59+00:00"
}
```

## Usage:
### Test run or config init:
```bash
php direct.php test google.com {telegram_chat_id} "{status:🔴|🟢} Test {status:power off|power on}{after: after #}!"
```

### Crontab:
```
*/1 * * * * php /path/to/project/direct.php test
```

## License
The repo is released under the [MIT](https://github.com/m0b-ua/nde/blob/master/LICENSE) license.
