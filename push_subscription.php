<?php
require 'db.php';
require __DIR__ . '/vendor/autoload.php';

use Detection\MobileDetect;

$subscription = json_decode(file_get_contents('php://input'), true);

if (!isset($subscription['endpoint'])) {
    echo 'Error: not a subscription';
    return;
}

$method = $_SERVER['REQUEST_METHOD'];
if (isset($_SERVER['HTTP_ACCEPT_LANGUAGE']) && mb_strlen($_SERVER['HTTP_ACCEPT_LANGUAGE']) >= 2)
    $lang = '\'' . mysqli_real_escape_string($db_link, substr($_SERVER['HTTP_ACCEPT_LANGUAGE'], 0, 2)) . '\'';
else
    $lang = 'NULL';

switch ($method) {
    case 'POST':
        $detect = new MobileDetect;

        // create a new subscription entry in your database (endpoint is unique)
        mysqli_query($db_link, 'INSERT INTO webpush (`host`,`ip`,`endpoint`,`key`,`token`,`useragent`,`lang`,`mobile`)
        VALUES ("' . mysqli_real_escape_string($db_link, $_SERVER['HTTP_HOST'])
            . '",INET_ATON("' . $_SERVER['REMOTE_ADDR'] . '"), \''
            . mysqli_real_escape_string($db_link, $subscription['endpoint']) . "', '"
            . mysqli_real_escape_string($db_link, $subscription['key']) . "', '"
            . mysqli_real_escape_string($db_link, $subscription['token']) . "', '"
            . mysqli_real_escape_string($db_link, (string)@$_SERVER['HTTP_USER_AGENT']) . "', "
            . $lang . ',"' . ($detect->isMobile() ? '1' : '0') . '");'
        );
        break;
    case 'PUT':
        // update the key and token of subscription corresponding to the endpoint
        mysqli_query($db_link, "UPDATE webpush
            SET `ip`=INET_ATON('" . $_SERVER['REMOTE_ADDR'] . "'), "
            . "`key`='" . mysqli_real_escape_string($db_link, $subscription['key']) . "', "
            . "`token`='" . mysqli_real_escape_string($db_link, $subscription['token'])
            . "', `updated_at` = now() WHERE `endpoint`='"
            . mysqli_real_escape_string($db_link, $subscription['endpoint']) . "';"
        );
        break;
    case 'DELETE':
        // delete the subscription corresponding to the endpoint
        break;
    default:
        echo "Error: method not handled";
        return;
}
