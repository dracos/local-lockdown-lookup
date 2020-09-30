<?php

if (php_sapi_name() !== 'cli') { exit; }

require 'utils.php';

$data = json_decode(file_get_contents($argv[1]), 1);

$areas = mapit_call('areas/COI,CTY,DIS,LBO,LGD,MTD,UTA');

foreach ($data['features'] as $feature) {
    $props = $feature['properties'];
    if (!$props['url_local']) { continue; }
    $name = $props['Category'];
    $name = str_replace('St. ', 'St ', $name);
    $name = str_replace('County ', '', $name);
    $name = str_replace('upon Tyne', '', $name);
    $match = false;
    foreach ($areas as $id => $area) {
        if (preg_match("#^$name#", $area['name'])) {
            $match = $area;
        }
    }
    if ($match) {
        $parliament[$match['id']] = $props;
    } else {
        $parliament[$name] = $props;
    }
}

$fp = fopen($dir . '/cache/parliament.php', 'w');
fwrite($fp, "<?php\n");
fwrite($fp, '$parliament = ');
fwrite($fp, var_export($parliament, true));
fwrite($fp, ";\n");
fclose($fp);
