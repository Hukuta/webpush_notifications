Production-ready example of using [web-push-php](https://github.com/web-push-libs/web-push-php)

![Screen](https://github.com/Hukuta/webpush_notifications/screen.png)

### Setup

## Requirements
* PHP 7.0+ with `gmp`, `mbstring`, `curl`, `openssl`!
* [composer.phar](https://getcomposer.org/download/)
* mySQL
* crontab

Install dependencies:
```
php composer.phar install
```

To generate the uncompressed public and secret key via Linux bash:
```
$ openssl ecparam -genkey -name prime256v1 -out private_key.pem
$ openssl ec -in private_key.pem -pubout -outform DER|tail -c 65|base64|tr -d '=' |tr '/+' '_-' >> public_key.txt
$ openssl ec -in private_key.pem -outform DER|tail -c +8|head -c 32|base64|tr -d '=' |tr '/+' '_-' >> private_key.txt
```

Use this keys in `cron.php` and `index.php`

Import dump.sql into yor database. Set connection options in the `db.php` file. Also change $auth_cookie.

Add next line to crontab:
```
* * * * * php cron.php > sub_send.log 2>&1
```

Change index.php as you need.

Use `stat.php` for statistics and `send.php` for newsletters. 

Password is in your `$auth_cookie`.