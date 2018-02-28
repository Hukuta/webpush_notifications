<?php
require 'db.php';

if (isset($_POST['password']) && $_POST['password'] === $auth_cookie) {
    setcookie($auth_cookie, '1', time() + 9000000);
    mysqli_close($db_link);
    header('Location: ' . $_SERVER['REQUEST_URI']);
    die('<script>location.href+=""</script>');
}

if (isset($_COOKIE[$auth_cookie])
    && isset($_GET['get_options'])
    && in_array($_GET['get_options'], array('host', 'lang'))
) {
    $response = json_encode(get_grouped_count($_GET['get_options']));
    mysqli_close($db_link);
    header('Content-type: application/json;charset=utf-8');
    die($response);
}

if (isset($_COOKIE[$auth_cookie]) && isset($_POST['action'])) {
    if ($_POST['action'] === 'draft') {
        if (isset($_POST['host']) && is_array($_POST['host']) && count($_POST['host']))
            $host = join(',', $_POST['host']);
        else
            $host = '';
        if (isset($_POST['lang']) && is_array($_POST['lang']) && count($_POST['lang']))
            $lang = join(',', $_POST['lang']);
        else
            $lang = '';
        if (!isset($_POST['mobile']) && isset($_POST['not_mobile']))
            $mobile = '0';
        elseif (isset($_POST['mobile']) && !isset($_POST['not_mobile']))
            $mobile = '1';
        else
            $mobile = '2';
        mysqli_query($db_link, 'INSERT INTO `pushtask` '
            . '(title,body,icon,url,status,host,lang,to_new,mobile) VALUES ('
            . '"' . mysqli_real_escape_string($db_link, $_POST['title']) . '",'
            . '"' . mysqli_real_escape_string($db_link, $_POST['body']) . '",'
            . '"' . mysqli_real_escape_string($db_link, $_POST['icon']) . '",'
            . '"' . mysqli_real_escape_string($db_link, $_POST['url']) . '",'
            . '"' . (isset($_POST['asap']) && $_POST['asap'] == '1' ? '1' : '0') . '",'
            . '"' . mysqli_real_escape_string($db_link, $host) . '",'
            . '"' . mysqli_real_escape_string($db_link, $lang) . '",'
            . '"' . mysqli_real_escape_string($db_link, $_POST['to_new']) . '",'
            . '"' . $mobile . '"'
            . ');');// or die(mysqli_error($db_link));
        mysqli_close($db_link);
        header('Location: ' . $_SERVER['REQUEST_URI']);
        die('<script>location.href+=""</script>');
    } elseif ($_POST['action'] === 'pause') {
        mysqli_query($db_link, 'UPDATE `pushtask` SET `status`=3 WHERE id='
            . '"' . mysqli_real_escape_string($db_link, $_POST['id']) . '"');
        mysqli_close($db_link);
        exit;
    } elseif ($_POST['action'] === 'start') {
        mysqli_query($db_link, 'UPDATE `pushtask` SET `status`=1 WHERE id='
            . '"' . mysqli_real_escape_string($db_link, $_POST['id']) . '"');
        mysqli_close($db_link);
        exit;
    }
}

$task_res = mysqli_query($db_link, 'SELECT * FROM `pushtask` ORDER BY id DESC;');
$tasks = array();
if ($task_res) {
    while ($row = mysqli_fetch_array($task_res, MYSQLI_ASSOC))
        $tasks[] = $row;
    mysqli_free_result($task_res);
}

function render_tasks_body($items){
foreach ($items as $row):
$body = htmlspecialchars(str_replace("\n", ' ', str_replace("\r", '', $row['body']))); ?>
<tr class="<?php switch ($row['status']) {
    case 0: echo 'table-secondary'; break; //draft
    case 1: echo 'table-active'; break; //queue
    case 2: echo 'table-warning'; break; //running
    case 3: echo 'table-info'; break; //paused
    case 4: echo 'table-success'; break; //finished
    default:
} ?>" id="task_td_<?php echo $row['id'] ?>">
    <td scope="row" title="<?php echo $body; ?>"><?php echo $row['id'] ?></td>
    <td title="<?php echo $body; ?>"><?php switch ($row['status']) {
            case 0: echo 'draft'; break;
            case 1: echo 'queue'; break;
            case 2: echo 'running'; break;
            case 3: echo 'paused'; break;
            case 4: echo 'finished'; break;
            default:
        } ?></td>
    <td title="<?php echo $body; ?>"><?php echo htmlspecialchars($row['title']) ?></td>
    <td title="Successfully Sent"><?php echo htmlspecialchars($row['count_ok']) ?></td>
    <td title="Not subscribed"><?php echo htmlspecialchars($row['count_fail']) ?></td>
    <td><?php if ($row['url']): ?><a href="<?php echo htmlspecialchars($row['url']) ?>" rel="noreferrer">
                Link</a><?php endif; ?></td>
    <td><?php if ($row['icon']): ?><a href="<?php echo htmlspecialchars($row['icon']) ?>" rel="noreferrer"><img
                style="max-width: 100px; max-height: 100px" src="<?php echo htmlspecialchars($row['icon']) ?>"
                alt="Image broken"></a><?php endif; ?></td>
    <td><?php echo htmlspecialchars($row['created_at']) ?></td>
    <td><?php switch ($row['mobile']) {
            case 0: echo 'Desktop'; break;
            case 1: echo 'Mobile'; break;
            case 2: echo 'Desktop, Mobile'; break;
            default:
        } ?></td>
    <td><?php switch ($row['status']) {
            case 0:
            case 3:
                echo '<button class="start-task btn btn-primary" data-id="', $row['id'], '">Start</button>';
                break;
            case 1:
            case 2:
                echo '<button class="pause-task btn btn-info" data-id="', $row['id'], '">Pause</button>';
                break;
            case 4:
                break;//finished
            default:
        } ?></td>
</tr>
<?php endforeach;
}

if (isset($_COOKIE[$auth_cookie]) && isset($_POST['action']) && $_POST['action'] === 'get_tasks') {
    header('Content-type: text/html;charset=utf-8');
    render_tasks_body($tasks);
    exit;
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

?></head><body>
<div class="container">

<?php if (count($tasks)): ?>

    <div class="row">
        <div class="col-lg-12 text-center">
            <h1>Created Notifications</h1>
        </div>
        <table class="table table-hover">
            <thead>
            <tr>
                <th scope="col">id</th>
                <th scope="col">Status</th>
                <th scope="col">Title</th>
                <th scope="col">Success</th>
                <th scope="col">Failed</th>
                <th scope="col">URL</th>
                <th scope="col">Icon</th>
                <th scope="col">Created</th>
                <th scope="col">Platforms</th>
                <th scope="col">Actions</th>
            </tr>
            </thead>
            <tbody id="tasksTbody">
            <?php render_tasks_body($tasks);?>
            </tbody>
        </table>
    </div>
<hr />

    <section id="sendFormButtonSection">
        <div class="row">
            <div class="col-lg-12 text-center">
                <button id="sendFormButton" class="btn btn-primary btn-lg">Send message to subscribed users</button>
            </div>
        </div>
    </section>
<?php endif; ?>

<section id="sendFormSection"<?php if (count($tasks)): ?> style="display: none"<?php endif; ?>>
    <div class="row">
        <div class="col-lg-12 text-center">
            <h1>Send message to subscribed users</h1>
        </div>
    </div>

    <div class="row">
        <div class="col-lg-6 col-md-8 offset-lg-3 offset-md-2">
            <form action="" method="post" id="draft_form">
                <input type="hidden" name="action" value="draft">
                <input type="hidden" name="asap" value="0" id="asap_hidden">
                <div class="form-group">
                    <label for="title">Title</label>
                    <input type="text" name="title" id="title" class="form-control" required>
                </div>
                <div class="form-group">
                    <label for="body">Message body</label>
                    <textarea name="body" class="form-control" id="body" rows="3" required></textarea>
                </div>
                <div class="form-group">
                    <label for="url">Action URL</label>
                    <input type="text" name="url" id="url" class="form-control">
                </div>
                <div class="form-group">
                    <label for="icon">Icon URL [Optional]</label>
                    <input type="text" name="icon" id="icon" class="form-control">
                </div>

                <fieldset class="form-group">
                    <legend>Platforms</legend>
                    <div class="form-check">
                        <label class="form-check-label">
                            <input name="mobile" class="form-check-input" type="checkbox" value="" checked="">
                            Mobile phones and tablets
                        </label>
                    </div>
                    <div class="form-check disabled">
                        <label class="form-check-label">
                            <input name="not_mobile" class="form-check-input" type="checkbox" value="" checked="">
                            Desktop computers and laptops
                        </label>
                    </div>
                </fieldset>

                <div class="form-group">
                    <label for="host">Domain names</label>
                    <div id="hostLoading">
                        <img alt="Loading..." data-src="https://cdn-images-1.medium.com/max/1600/1*W8cj-FRc58UozzcMWqVPZw.gif" src="https://cdn-images-1.medium.com/max/1600/1*W8cj-FRc58UozzcMWqVPZw.gif">
                    </div>
                </div>

                <div class="form-group">
                    <label for="lang">Languages</label>
                    <div id="languagesLoading">
                        <img alt="Loading..." data-src="https://cdn-images-1.medium.com/max/1600/1*W8cj-FRc58UozzcMWqVPZw.gif" src="https://cdn-images-1.medium.com/max/1600/1*W8cj-FRc58UozzcMWqVPZw.gif">
                    </div>
                </div>

                <div class="form-group">
                    <label for="for_new">Subscribers status</label>
                    <select name="to_new" id="for_new" class="form-control">
                        <option value="2" selected>All who are subscribed</option>
                        <option value="1">Never received messages</option>
                        <option value="0">Only those who received messages</option>
                    </select>
                </div>

                <div class="form-group">
                    <button type="submit" class="btn btn-secondary">Create Task</button>
                    <button type="button" class="btn btn-primary" id="asap">Run ASAP</button>
                </div>

            </form>

        </div>

    </div>
</section>
<script src="//code.jquery.com/jquery-1.8.0.min.js"></script>
<script>
    $("#asap").click(function (e) {
        e.preventDefault();
        $("#asap_hidden").val('1');
        $("#draft_form").find('button[type=submit]').trigger('click');
    });

    function preload_selects() {
        $.get(document.location.href + '&get_options=host', function (collection) {

            setTimeout(function () {
                $.get(document.location.href + '&get_options=lang', function (collection) {
                    var preloader = $("#languagesLoading");
                    var parent = preloader.parent();
                    if (collection.length == 0)
                        return $(parent).remove();
                    preloader.remove();
                    var select = document.createElement('select');
                    select.className = 'form-control';
                    select.name = 'lang[]';
                    select.size = "15";
                    select.id = 'lang';
                    $(select).attr('multiple', true);
                    $.each(collection, function (value, popularity) {
                        var option = document.createElement('option');
                        option.innerText = (value ? value : '(EMPTY)') + " [total " + popularity + "]";
                        option.value = (value ? value : '??');
                        option.selected = "selected";
                        $(select).append(option)
                    });
                    $(parent).append(select)
                }, 'json');
            }, 500);

            var preloader = $("#hostLoading");
            var parent = preloader.parent();
            if (collection.length == 0)
                return $(parent).remove();
            preloader.remove();
            var select = document.createElement('select');
            select.className = 'form-control';
            select.name = 'host[]';
            select.size = "15";
            select.id = 'host';
            $(select).attr('multiple', true);
            $.each(collection, function (value, popularity) {
                var option = document.createElement('option');
                option.innerText = (value ? value : '(EMPTY)') + " [total " + popularity + "]";
                option.value = value;
                option.selected = "selected";
                $(select).append(option)
            });
            $(parent).append(select)
        }, 'json');
    }
    if ($("#sendFormSection").is(":visible")) {
        preload_selects();
    } else {
        $("#sendFormButton").click(function () {
            preload_selects();
            var sendFormSection = $("#sendFormSection");
            sendFormSection.show();
            $("#sendFormButtonSection").hide();
            $('html, body').animate({
                scrollTop: sendFormSection.offset().top
            }, 2000);
        });
        setInterval(function () {
            $.post(document.location.href,
                {action: 'get_tasks'},
                function (part) {
                    var gost = $("#gostRender"), old = $("#tasksTbody");
                    gost.html(part);
                    $(gost.children().get().reverse()).each(function () {
                        var that = $(this), that_id = that.attr('id'), found = false;
                        old.children().each(function () {
                            var item = $(this);
                            if (item.attr('id') == that_id) {
                                found = true;
                                if (item.html() != that.html()) {
                                    console.log('Replacing #' + that_id);
                                    console.log(item.html());
                                    console.log(that.html());
                                    console.log('----');
                                    item.after(that);
                                    item.remove();
                                }
                            }
                        });
                        if (!found) {
                            old.prepend(that)
                        }
                    });
                },
                'html'
            );
        }, 1e4);
    }


    $(".start-task").click(function () {
        $.post(document.location.href,
            {action: 'start', id: $(this).attr('data-id')},
            function(){location.reload()},
            'text'
        );
    });
    $(".pause-task").click(function () {
        $.post(document.location.href,
            {action: 'pause', id: $(this).attr('data-id')},
            function(){location.reload()},
            'text'
        );
    });
</script>
<div id="gostRender" style="display: none"></div>
</body></html>