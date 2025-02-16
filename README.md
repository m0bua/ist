Site status checker with TG bot.

## Usage:
### Run:
```bash
php run
php run test
```

### Income query:
```bash
curl -i "https://site.domain/?d=test&u=usename&p=password&m=extra%20message"
```

### Crontab:
```
*/1 * * * * php /path/to/project/run
```

## License
The repo is released under the [MIT](https://github.com/m0b-ua/nde/blob/master/LICENSE) license.
