<?php
$db = 'YOUR_DB_NAME';
$user = 'YOUR_DB_USER';
$pass = 'YOUR_DB_PASSWORD';

$auth_cookie = 'YOUR_SECRET_COOKIE_NAME';

$db_link = mysqli_connect("localhost", $user, $pass, $db);
if (mysqli_connect_error()) {
    die(mysqli_connect_errno() . ' ' . mysqli_connect_error());
}
mysqli_query($db_link, "SET NAMES UTF8");

/**
 * Gets count of each value in given column
 * @param $col
 * @return array
 */
function get_grouped_count($col)
{
    global $db_link;

    $result = mysqli_query($db_link,
        'SELECT count(*) as c, `' . str_replace('`', '', $col) . '` as f FROM `webpush` group by f');

    $data = array();
    if ($result) {
        while ($row = mysqli_fetch_array($result, MYSQLI_ASSOC))
            $data[$row['f']] = $row['c'];

        mysqli_free_result($result);
    }

    return $data;
}