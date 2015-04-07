<?php
require 'cloudflare.api.php';
require 'Dashboard.php';

$dashboard = new \Dashboard\Dashboard();

if (isset($_GET['delete'])) {
    $dashboard->removePost($_GET['delete']);
    header('Location: http://dashboard.upcyclepost.com');
    die();
}

$recentPosts = $dashboard->getRecentPosts();
$sixHourPostTotal = $dashboard->getSixHourPostTotal();
?>
<!doctype html>
<html>
    <head>
        <title>Dashboard</title>
        <link rel="stylesheet" href="style.css" />
        <link rel="stylesheet" href="//maxcdn.bootstrapcdn.com/font-awesome/4.3.0/css/font-awesome.min.css">
        <meta http-equiv="refresh" content="60">
        <link rel='shortcut icon' type='image/x-icon' href='/favicon.ico'/>
        <link rel="icon" href="/favicon.ico" type="image/x-icon"/>
    </head>
    <body>
        <div style="text-align: center">
            <div style="width: 50%; margin: auto; text-align: left;">
                <div class="chart chartHeader">
                    <strong>#</strong> Traffic<br />
                    <?php
                    $cf = new cloudflare_api("eric@upcyclepost.com", "dd305399405d44dfc22876f0f78193c99f494");
                    $response = $cf->stats("upcyclepost.com", $cf::INTERVAL_6_HOURS);
                    echo '<span><strong>' . number_format($response->response->result->objs[0]->trafficBreakdown->pageviews->regular) . '</strong> page views in the last 6 hours</span>';
                    ?>
                </div>
                <br />
                <div class="chart chartHeader"><strong>#</strong> Members by Day<br /><span>Total: <strong><?=number_format($dashboard->getTotalMembers())?></strong> members; <strong><?=number_format($dashboard->getTotalSubscribers())?></strong> subscribers</span></div>
                <div class="chart">
                    <div id="registrationChart" style="height: 300px; width:70%; float: left;"></div>
                    <div style="height: 300px; width: 30%; float: right; position: relative;">
                        <div id="profileChart" style="height: 75%; position: relative; top: 50%; transform: translateY(-50%)"></div>
                    </div>
                </div>
                <div class="chart chartHeader"><strong>+</strong> Most Recent Members</div>
                <div class="chart">
                    <?php
                    $new = $dashboard->getMostRecentSubscribers();
                    foreach ($new AS $subscriber) {
                        echo '<div class="row">'
                            .'<div style="float: left; width: 300px;">'.$subscriber['email'].'</div>'
                            .'<div style="float: left; width: 100px;">'.$subscriber['date'].'</div>'
                            .'<div style="float: left; width: 200px;">'.$subscriber['username'].'</div>'
                            .'<div style="float: left; width: 200px;">('.$subscriber['name'].')</div>'
                            .'<div style="float: left;">'.($subscriber['complete'] == 1 ? '<i class="fa fa-check"></i> Complete Profile' : '').'</div>'
                            .'<div class="clear"></div></div>';
                    }
                    ?>
                </div>
                <div class="chart chartHeader"><strong>+</strong> Most Recent Newsletter Subscribers</div>
                <div class="chart">
                    <?php
                    $new = $dashboard->getMostRecentNewsletterSubscribers();
                    foreach ($new AS $subscriber) {
                        echo '<div class="row">'
                            .'<div style="float: left; width: 300px;">'.$subscriber['email'].'</div>'
                            .'<div style="float: left; width: 500px;">'.$subscriber['date'].'</div>'
                            .'<div style="float: left;">'.($subscriber['isMember'] ? '<i class="fa fa-check"></i> Has an account' : '').'</div>'
                            .'<div class="clear"></div></div>';
                    }
                    ?>
                </div>
                <br />
                <div class="chart chartHeader"><strong>+</strong> Newest Posts<br />
                    <span>
                        Total: <strong><?=number_format($dashboard->getTotalPosts())?></strong> posts
                        (<?=number_format($sixHourPostTotal)?> in the last 6 hours)
                    </span>
                </div>
                <div class="chart">
                    <?php
                    foreach ($recentPosts AS $post) {
                        echo '<div class="row" style="border-bottom: 1px solid #138a72; margin-bottom: 3px;">'
                            .'<div style="float: left; width: 120px"><img src="'.$post['thumb'].'" width=100></div>'
                            .'<div style="float: left; width: 800px;"><strong>'.$post['title'].'</strong><br /><small>posted ' . $post['created'] . ' UTC &nbsp; &nbsp; <a href="?delete='.$post['ik'].'">Remove</a></small><br /><br />'.$post['description'].'<br /><br /><strong>By</strong> ' . $post['user'] . ' ('.$post['email'].')</div>'
                            .'<div class="clear"></div></div>';
                    }
                    ?>
                </div>
                <br />
                <center><img src="http://www.upcyclepost.com/img/logo.png" /></center>
            </div>
        </div>
    </body>
    <link rel="stylesheet" href="http://cdn.oesmith.co.uk/morris-0.4.3.min.css">
    <script src="//ajax.googleapis.com/ajax/libs/jquery/1.9.0/jquery.min.js"></script>
    <script src="//cdnjs.cloudflare.com/ajax/libs/raphael/2.1.0/raphael-min.js"></script>
    <script src="http://cdn.oesmith.co.uk/morris-0.4.3.min.js"></script>
    <script>
        var registrationChartData = <?=json_encode($dashboard->getSubscribersByDay()); ?>;
        var memberChartData = <?=json_encode([
                ['label' => 'Complete', 'value' => $dashboard->getTotalCompletedProfiles()],
                ['label' => 'Incomplete', 'value' => ($dashboard->getTotalMembers() - $dashboard->getTotalCompletedProfiles())]

            ])?>
    </script>
    <script src="dashboard.js"></script>
</html>