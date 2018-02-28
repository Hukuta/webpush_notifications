<?php
if (!isset($argv))
    die('Command line only!');

require 'db.php';

require __DIR__ . '/vendor/autoload.php';
use Minishlink\WebPush\WebPush;


$auth = array(
    'VAPID' => array(
        'subject' => 'https://github.com/Hukuta/webpush_notifications/',
        'publicKey' => 'YOUR_PUBLIC_KEY',
        'privateKey' => 'YOUR_PRIVATE_KEY',
    ),
);

$fp = fopen("lock.txt", "w+");

if (flock($fp, LOCK_EX | LOCK_NB)) { // do an exclusive lock

    $result = mysqli_query($db_link, 'SELECT * FROM `pushtask` WHERE `status` IN ("1","2") ORDER BY `status` DESC, id LIMIT 1;');

    if ($result) {
        $task = mysqli_fetch_array($result, MYSQLI_ASSOC);
        if ($task) {
            if ($task['status'] == '1') // queue
                mysqli_query($db_link, 'UPDATE `pushtask` SET `status`="2" WHERE `id`="' . $task['id'] . '"');

            $payload = array('title' => $task['title']);
            if ($task['body'])
                $payload['body'] = $task['body'];
            if ($task['icon'])
                $payload['icon'] = $task['icon'];
            if ($task['url'])
                $payload['data'] = array('click_url' => $task['url']);
            $payload = json_encode($payload);

            $webPush = new WebPush($auth);

            $q = 'SELECT wp.*, tu.push_id FROM `webpush` wp '
                . 'LEFT JOIN tasks_users tu ON wp.id=tu.push_id AND tu.task_id="' . $task['id'] . '"'
                . ' WHERE tu.push_id IS NULL ';
            if ($task['lang']) {
                $q .= 'AND wp.lang IN (';
                $langList = array();
                foreach (explode(',', $task['lang']) as $lang)
                    if ($lang) {
                        if ($lang == '??') $lang = '';
                        $langList[] = "'$lang'";
                    }
                $q .= join(',', $langList) . ') ';
            }

            if ($task['host']) {
                $q .= 'AND wp.host IN (';
                $hostList = array();
                foreach (explode(',', $task['host']) as $h)
                    $hostList[] = "'$h'";
                $q .= join(',', $hostList) . ') ';
            }

            if ($task['mobile'] === '1' || $task['mobile'] === '0') {
                $q .= 'AND wp.mobile="' . $task['mobile'] . '" ';
            }

            switch ($task['to_new']) {
                case '0':
                    $q .= "AND wp.valid='1' ";
                    break;
                case '1':
                    $q .= "AND wp.valid IS NULL ";
                    break;
                default: //2
                    $q .= "AND (wp.valid='1' OR wp.valid IS NULL) ";
                    break;
            }

            $limit = 60 * 10;
            $q .= "LIMIT $limit;";

            $result_users = mysqli_query($db_link, $q);
            $num_rows = mysqli_num_rows($result_users);
            if ($num_rows) {
                while ($sub = mysqli_fetch_array($result_users, MYSQLI_ASSOC)) {
                    mysqli_query($db_link, 'INSERT INTO `tasks_users` (`push_id`, `task_id`) VALUES (\'' . $sub['id'] . '\', \'' . $task['id'] . '\');');
                    $response = $webPush->sendNotification(
                        $sub['endpoint'],
                        $payload,
                        $sub['key'],
                        $sub['token'],
                        true
                    );
                    if ($response === TRUE) {
                        mysqli_query($db_link, 'UPDATE `webpush` SET `valid`="1" WHERE `id`="' . $sub['id'] . '"');
                        mysqli_query($db_link, 'UPDATE `pushtask` SET `count_ok`=`count_ok`+1 WHERE `id`="' . $task['id'] . '"');
                    } else {
                        if (isset($response['message'])) {
                            if (strpos($response['message'], 'NotRegistered') !== false)
                                echo "410 NotRegistered\r\n";
                            else
                                print_r($response['message']);
                        }
                        else
                            print_r($response);
                        mysqli_query($db_link, 'UPDATE `webpush` SET `valid`="0" WHERE `id`="' . $sub['id'] . '"');
                        mysqli_query($db_link, 'UPDATE `pushtask` SET `count_fail`=`count_fail`+1 WHERE `id`="' . $task['id'] . '"');
                    }
                }
            }
            if ($num_rows < $limit) {
                mysqli_query($db_link, 'UPDATE `pushtask` SET `status`="4" WHERE `id`="' . $task['id'] . '"');
            }
        }
    }

    flock($fp, LOCK_UN); // release the lock
} else {
    echo "Couldn't get the lock!\r\n";
}

fclose($fp);


mysqli_close($db_link);

