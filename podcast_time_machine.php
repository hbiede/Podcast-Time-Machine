<?php
/*
 * Podcast Time Machine 
 * Created by Hundter Biede on October 22, 2019
 * Version 2.1
 *
 * A PHP script that takes an RSS feed in standard podcasting XML format and delays it by a set number of days.
 * The RSS feed is given by including it as the query parameter 'url', must be URL encoded (as by urlencode()).
 * The delay can be included as a number of days as the query parameter 'delay'.
 */

function &find_root_tag($file_content) {
    $return_val = &$file_content;
    while (!contains_item($return_val)) {
        if ($return_val->children()->count()) {
            $return_val = &$return_val->children()[0];
        } else {
            $temp = null;
            return $temp;
        }
    }
    return $return_val;
}

function contains_item($file_contents) {
    foreach ($file_contents as $child) {
        if ($child->getName() === 'item') return true;
    }
    return false;
}

$url = '';
$delay = 0;
if (isset($_GET['url'])) {
    $url = $_GET['url'];
} else if (isset($_GET['debug'])) {
    $url = urlencode('https://www.relay.fm/cortex/feed');
}


if (isset($_GET['delay']) && is_numeric($_GET['delay'])) {
    $delay = $_GET['delay'];
} else if (isset($_GET['debug'])) {
    $delay = 365;
}

if ($delay !== 0) {
    $content = file_get_contents(urldecode($url));
    if (strpos($content, "<itunes:block>") !== false) {
        $content = preg_replace("/<itunes:block>.*?<\/itunes:block>/", "<itunes:block>Yes</itunes:block>", $content);
    } else {
        $content = str_replace("<itunes:category>", "<itunes:block>Yes</itunes:block>\n<itunes:category>", $content);
    }
    $file_content = new SimpleXMLElement($content);
    $root =& find_root_tag($file_content);
    if ($root !== null) {
        foreach ($root as $post) {
            if ($post->getName() === 'item') {
                $children = $post->children();
                $descriptions = [];
                for ($i = 0; $i < $post->children()->count(); $i++) {
                    $child = $post->children()[$i];
                    if ($child->getName() === 'pubDate') {
                        $date = date_create(strval($child));
                        if ($date) {
                            $new_date = $date->add(date_interval_create_from_date_string($delay . ' days'));
                            if ($new_date <= new DateTime()) {
                                $child[0] = $new_date->format("l F jS, Y");
                            } else {
                                $post[0] = null;
                            }
                        }
                    }
                }
            } else if ($post->getName() === 'title') {
                $post[0] .= ' - Time Machine';
            }
        }
    }
    echo preg_replace('#\s*\\<item\\>\\<\\/item\\>\\n#', '', $file_content->asXML());
    return;
}
?>

<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <title>Podcast Time Machine</title>
    <link href="https://stackpath.bootstrapcdn.com/bootstrap/4.4.1/css/bootstrap.min.css" rel="stylesheet"
          integrity="sha384-Vkoo8x4CGsO3+Hhxv8T/Q5PaXtkKtu6ug5TOeNV6gBiFeWPGFN9MuhOf23Q9Ifjh" crossorigin="anonymous">
</head>
<body>
<nav class="navbar navbar-expand-md navbar-dark fixed-top bg-dark">
    <a class="navbar-brand" href="https://github.com/hbiede/Podcast-Time-Machine" target="_blank">Podcast Time
        Machine</a>
    <ul class="navbar-nav mr-auto">
        <li>
            <button class="btn btn-dark" onclick="window.location.href='https://hbiede.com'">By Hundter Biede</button>
        </li>
    </ul>
</nav>
<main role="main">
    <!-- Main jumbotron for a primary marketing message or call to action -->
    <div class="jumbotron">
        <div class="container">
            <h4 style="margin-top:50px">URL Generator</h4>
            <div id="error"></div>
            <p>Listen to podcasts that have longer backlogs on a time delay</p>
            <div class="col-md-6">
                <div class="form-group row">
                    <label for="url" class="col-sm-3 col-form-label">RSS Feed</label>
                    <div class="col-sm-9">
                        <input type="text" class="form-control" id="url" placeholder="URL">
                    </div>
                </div>
                <div class="form-group row">
                    <label for="delay" class="col-sm-3 col-form-label">Delay</label>
                    <div class="col-sm-9">
                        <input type="number" class="form-control" name="delay" id="delay" placeholder="# of Days">
                    </div>
                </div>
                <button class="btn btn-primary" onclick="urlGen()">Create Link</button>
            </div>
            <h5 id="link" style="margin-top:20px;"></h5>
        </div>
    </div>
</main>
<script>
    function urlGen() {
	const errorDiv = document.getElementById('error');
	errorDiv.innerHTML = '';

        let loc = window.location.href.split("?")[0];
        let rssUrl = document.getElementById('url').value;
        let delay = document.getElementById('delay').value;
        if (!rssUrl.includes('http')) rssUrl = 'http://' + rssUrl;
        if (rssUrl.match(/^https?:\/\/[\w\-_]+\.[\w]+/) && delay.trim().length !== 0 && !isNaN(delay) && delay > 0) {
            const tmUrl = loc + '?url=' + encodeURIComponent(rssUrl) + '&delay=' + delay;
            document.getElementById('link').innerHTML = '<a target="_blank" href="'
                + tmUrl + '">' + tmUrl + '</a>';
        } else if (delay.trim().length !== 0 && !isNaN(delay) && delay > 0) {
            errorDiv.innerHTML = '<div class="alert alert-danger" role="alert">Invalid URL</div>';
        } else {
            errorDiv.innerHTML = '<div class="alert alert-danger" role="alert">Invalid Delay</div>';
        }
    }
</script>
</body>
</html>

