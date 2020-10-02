<?php

if (php_sapi_name() !== 'cli') { exit; }

require 'utils.php';

$areas = mapit_call('areas/COI,CTY,DIS,LBO,LGD,MTD,UTA');
$urls = [];
foreach ($areas as $id => $area) {
    $pc = mapit_call("area/$id/example_postcode");
    if (!$pc) {
        $pc = mapit_call("area/$id/example_postcode");
    }
    if (!$pc) { print "$id failed\n"; continue; }

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL,"https://www.gov.uk/find-local-council");
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, "postcode=$pc");
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);

    $output = curl_exec($ch);
    curl_close ($ch);
    if ($area['type'] == 'CTY') {
        preg_match('#Website: <a[^>]*county[^>]*href="([^"]*)">#', $output, $m);
    } elseif ($area['type'] == 'DIS') {
        preg_match('#Website: <a[^>]*district[^>]*href="([^"]*)">#', $output, $m);
    } else {
        preg_match('#Website: <a[^>]*href="([^"]*)">#', $output, $m);
    }
    $urls[$id] = $m[1];
}

$fp = fopen('councils.php', 'w');
fwrite($fp, "<?php\n");
fwrite($fp, '$council_urls = ');
fwrite($fp, var_export($urls, true));
fwrite($fp, ";\n");
