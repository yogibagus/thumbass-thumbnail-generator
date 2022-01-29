<?php

// get url
if (!isset($_GET['url']) or empty($_GET['url'])) {
    // default
    throwResponse(
        400,
        'url not found (?url=your-url-here)',
        file_get_contents("default.txt")
    );
} else {
    // custom url
    $get_url = $_GET['url'];
    if (isHttp($get_url) == true) {
        $custom_url = parse_url($get_url, PHP_URL_HOST);
    } else {
        $custom_url = $get_url;
    }
    if (checkdnsrr($custom_url, "A")) {
        getThumbnail(removeHttp($get_url));
    } else {
        throwResponse(
            400,
            'Server was unable to reliably load the page you requested. (url has no dns detected)',
            file_get_contents("default.txt")
        );
    }
}

function getThumbnail($custom_url)
{
    $url     = 'https://www.googleapis.com/pagespeedonline/v5/runPagespeed?url=https://' . $custom_url . '&screenshot=true';

    $ch      = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_HEADER, 1);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HEADER, false);
    $data = curl_exec($ch);
    // get error message if curl fail
    if (curl_errno($ch)) {
        $error_msg = curl_error($ch);
    }
    curl_close($ch);

    if (isset($error_msg)) { // if error
        throwResponse(
            400,
            $error_msg . '| Server busy.',
            file_get_contents("default.txt")
        );
    } else {
        $decoded_data = json_decode($data, true);
        if (isset($decoded_data['error']) or !empty($decoded_data['error'])) { // if server response error
            throwResponse(
                400,
                "Server was unable to reliably load the page you requested. Make sure you are testing the correct URL and that the server is properly responding to all requests.",
                file_get_contents("default.txt")
            );
        } else {
            // if success
            $audits         = $decoded_data['lighthouseResult']['audits'];
            $decoded_screenshot = $audits['final-screenshot']['details']['data'];
            throwResponse(200, "The page successfully captured", $decoded_screenshot);
        }
    }
}

function throwResponse($status, $message, $base64)
{
    $ss = array(
        'status' => $status,
        'message' => $message,
        'thumbnail' => $base64,
        'author' => "https://github.com/yogibagus"
    );

    echo json_encode($ss);
}

function isHttp($url)
{
    if (strpos($url, "http") === 0) {
        return true;
    } else {
        return false;
    }
}

function removeHttp($url)
{
    $removeChar = ["https://", "http://"];
    return str_replace($removeChar, "", $url);
}
