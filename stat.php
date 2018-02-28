<?php
require 'db.php';

if (isset($_POST['password']) && $_POST['password'] === $auth_cookie) {
    setcookie($auth_cookie, '1', time() + 9000000);
    mysqli_close($db_link);
    header('Location: ' . $_SERVER['REQUEST_URI']);
    die('<script>location.href+=""</script>');
}
?><!DOCTYPE html>
<html>
<head>
    <meta http-equiv="content-type" content="text/html; charset=UTF-8">
    <meta name="robots" content="noindex, nofollow">
    <meta name="googlebot" content="noindex, nofollow">
    <link href="https://maxcdn.bootstrapcdn.com/bootswatch/4.0.0-beta.3/cerulean/bootstrap.min.css" rel="stylesheet" integrity="sha384-SJRp3lIuvy82g2roYNoSMWGEf7ufogthbl9noRHbJIxsMPcFl6NEi5fO3PuYptFv" crossorigin="anonymous">
    <meta name="viewport" content="width=device-width, initial-scale=1"><?php

    if (!isset($_COOKIE[$auth_cookie])) {
        mysqli_close($db_link);
        die('<form action="" method="post"><input type="password" name="password"><input type="submit"></form>');
    }

$valid_stat = get_grouped_count('valid');

?><script type="text/javascript" src="https://www.gstatic.com/charts/loader.js"></script></head><body>
<div class="container">
    <div class="row"><h2>Total</h2></div>
    <div class="row">
        <div class="col-lg-4 col-md-6 offset-lg-1">
        <ul class="list-group">
            <li class="list-group-item d-flex justify-content-between align-items-center">
                New subscribed
                <span class="badge badge-light badge-pill"><?php echo $valid_stat[''] ?></span>
            </li>
            <li class="list-group-item d-flex justify-content-between align-items-center">
                No longer subscribed
                <span class="badge badge-danger badge-pill"><?php echo $valid_stat['0'] ?></span>
            </li>
            <li class="list-group-item d-flex justify-content-between align-items-center">
                Have already received messages
                <span class="badge badge-primary badge-pill"><?php echo $valid_stat['1'] ?></span>
            </li>
        </ul>
        </div>
        <div class="col-lg-5 col-md-6 offset-lg-1">
            <p>All - devices subscribed on that day.</p>

            <p>Valid - the last message was received successfully.</p>
        </div>
    </div>
    <div class="row">
        <h2>Week</h2>
        <div class="col-lg-12">
            <div id="chart_div1"></div>
        </div>
    </div>
    <div class="row">
        <h2>Today</h2>
        <div class="col-lg-12">
            <div id="chart_div2"></div>
        </div>
    </div>
</div>
<script>
    google.charts.load('current', {packages: ['corechart', 'line']});
    google.charts.setOnLoadCallback(function () {
        var data = new google.visualization.DataTable();
        data.addColumn('string', 'X');
        data.addColumn('number', 'All');
        data.addColumn('number', 'Valid');
        data.addRows([
<?php
$result_new = mysqli_query($db_link, 'SELECT COUNT(*) as a, date(w.created_at) as d FROM (SELECT id, created_at FROM `webpush`  WHERE UNIX_TIMESTAMP(now())-UNIX_TIMESTAMP(created_at)<604800) AS w group by d;');
while ($row = mysqli_fetch_array($result_new, MYSQLI_ASSOC)){

    $result_valid = mysqli_query($db_link, 'SELECT COUNT(*) as a FROM `webpush`  WHERE date(created_at) = "'.$row['d'].'" AND valid="1";');
    $valid = mysqli_fetch_array($result_valid, MYSQLI_NUM);
    $valid = $valid ? $valid[0] : 0;
    mysqli_free_result($result_valid);
    echo '["', $row['d'], '", ', $row['a'], ',', $valid, '], ';
}

mysqli_free_result($result_new);

?>]);
   (new google.visualization.LineChart(document.getElementById('chart_div1'))).draw(data, {
        hAxis: {title: 'Date'},
        vAxis: {title: 'Subscribed'}
    });
});
    google.charts.load('current', {packages: ['corechart', 'bar']});
    google.charts.setOnLoadCallback(function () {
        var data = new google.visualization.DataTable();
        data.addColumn('string', 'X');
        data.addColumn('number', 'Subscribed');

        data.addRows([
            <?php
            $result = mysqli_query($db_link, 'SELECT COUNT(*) as a, hour(w.created_at) as d FROM (SELECT id, created_at FROM `webpush`  WHERE date(now())=date(created_at)) AS w group by d');
            $hours = array();
            foreach (range(0, 23) as $h)
                $hours[(string)$h] = 0;
            while ($row = mysqli_fetch_array($result, MYSQLI_ASSOC))
                $hours[(string)$row['d']] = $row['a'];
            mysqli_free_result($result);

            foreach ($hours as $h => $a)
                echo '["', $h, '", ', $a, '], ';

            ?>]);
        (new google.visualization.ColumnChart(document.getElementById('chart_div2'))).draw(data, {
            hAxis: {title: 'Hour'},
            vAxis: {title: 'Subscribed'}
        });
    });

</script></body><?php
mysqli_close($db_link);