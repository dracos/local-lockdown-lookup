<?php

if (php_sapi_name() !== 'cli') { exit; }

require 'utils.php';

$base = 'https://visual.parliament.uk/research/visualisations/coronavirus-restrictions-map/';
print "Fetching $base\n";
$html = file_get_contents($base);
preg_match('#<script src="([^h][^"]*)">#', $html, $m);
print "Fetching $m[1]\n";
$js = file_get_contents("$base$m[1]");
preg_match('#data:"([^"]*\.json)"#', $js, $m);
print "Fetching $m[1]\n";
$data = file_get_contents("$base$m[1]");
$data = preg_replace('#,"geometry":{[^}]*}#', '', $data); # Yes, I KNOW
$data = json_decode($data, 1);

$areas = mapit_call('areas/COI,CTY,DIS,LBO,LGD,MTD,UTA');

foreach ($data['features'] as $feature) {
    $props = $feature['properties'];
    #if (!$props['url_local']) { continue; }
    $name = $props['map_grouping'];
    $type = '';
    if ($name == 'Bedfordshire') $name = ['Central Bedfordshire', 'Bedford','Luton'];
    if ($name == 'County Durham') $name = 'Durham';
    if ($name == 'Greater Manchester')
        $name = ['Bolton','Bury','Manchester','Oldham','Rochdale','Salford','Stockport','Tameside','Trafford','Wigan'];
    if ($name == 'Kent and Medway')
        $name = ['Kent','Medway'];
    if ($name == 'Kingston upon Hull') $name = 'Hull';
    if ($name == 'Liverpool City Region')
        $name = ['Liverpool','Halton','Wirral','Knowsley','St Helens','Sefton'];
    if ($name == 'London') $type = 'LBO';
    if ($name == 'Na h-Eileanan Siar') $name = 'Comhairle nan Eilean Siar';
    if ($name == 'Berkshire')
        $name = ['Reading','Wokingham','Bracknell Forest','Windsor and Maidenhead','West Berkshire','Slough'];
    if ($name == 'South Yorkshire')
        $name = ['Sheffield','Doncaster','Rotherham','Barnsley'];
    if ($name == 'Tees Valley')
        $name = ['Darlington','Redcar and Cleveland','Stockton-on-Tees','Middlesbrough','Hartlepool'];
    if ($name == 'Tyne and Wear')
        $name = ['North Tyneside','Newcastle','South Tyneside','Gateshead','Sunderland'];
    if ($name == 'West Midlands Combined Authority')
        $name = ['Wolverhampton','Dudley','Sandwell','Coventry','Solihull','Birmingham','Walsall'];
    if ($name == 'West Yorkshire')
        $name = ['Leeds','Wakefield','Bradford','Calderdale','Kirklees'];
    if ($name == 'York') $name = 'City of York';

    if ($name == 'Bristol, North Somerset and Bath & NES')
        $name = ['Bristol','North Somerset','Bath and North East Somerset'];
    if ($name == 'Colchester & Tendring')
        $name = ['Colchester', 'Tendring'];
    if ($name == 'Gosport, Havant and Portsmouth')
        $name = ['Gosport', 'Havant', 'Portsmouth'];
    if ($name == 'Hastings and Rother')
        $name = ['Hastings', 'Rother'];
    if ($name == 'Uttlesford (Essex)')
        $name = 'Uttlesford';
    if ($name == 'Essex (Tier 3 areas)')
        $name = ['Braintree', 'Harlow', 'Epping Forest', 'Chelmsford', 'Maldon', 'Brentwood', 'Basildon', 'Rochford', 'Castle Point', 'Southend-on-Sea', 'Thurrock'];
    if ($name == 'Rest of East Sussex')
        $name = ['Lewes', 'Wealden', 'Eastbourne', 'Brighton and Hove'];
    if ($name == 'Rest of Hampshire')
        $name = ['Test Valley', 'Basingstoke and Deane', 'Hart', 'Rushmoor', 'Winchester', 'East Hampshire', 'New Forest', 'Southampton', 'Eastleigh', 'Fareham'];
    if ($name == 'Surrey (Tier 3 areas)')
        $name = ['Spelthorne', 'Runnymede', 'Surrey Heath', 'Woking', 'Elmbridge', 'Guildford', 'Mole Valley', 'Epsom and Ewell', 'Reigate and Banstea', 'Tandridge'];
    if ($name == 'Waverley (Surrey)')
        $name = 'Waverley';


    if (is_array($name)) $name = '(' . join('|', $name) . ')';
    $matches = [];
    foreach ($areas as $id => $area) {
        if ($type === $area['type']) {
            $matches[] = $area['id'];
        }
        if (preg_match("#^$name#", $area['name'])) {
            $matches[] = $area['id'];
        }
    }

    if ($matches) {
        foreach ($matches as $m) {
            $parliament[$m] = $props;
        }
    } else {
        $parliament[$name] = $props;
    }
}

ksort($parliament);

$fp = fopen($dir . '/cache/parliamentN.php', 'w');
fwrite($fp, "<?php\n");
fwrite($fp, '$parliament = ');
fwrite($fp, var_export($parliament, true));
fwrite($fp, ";\n");
fclose($fp);

passthru('diff cache/parliament.php cache/parliamentN.php');
