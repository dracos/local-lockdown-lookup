<?php

if (array_key_exists('govuk', $_GET)) {
    $style = 'govuk-frontend-3.9.1.min.css';
}

$title = 'Local Lockdown Lookup';
require 'site.inc';
require 'utils.php';

load_areas();
load_special();

$results = [];
$cls = [];

$pc = array_key_exists('pc', $_REQUEST) ? $_REQUEST['pc'] : '';
if ($pc) {
    if (preg_match('#^([0-9.-]+)\s*,\s*([0-9.-]+)$#', $pc, $m)) {
        $data = mapit_call("point/4326/$m[2],$m[1]");
        $council = null; $ward = null;
        $county = null; $ced = null;
        foreach ($data as $id => $area) {
            if ($area['type'] == 'CTY') { $county = $area['id']; }
            if ($area['type'] == 'CED') { $ced = $area['id']; }
            if (in_array($area['type'], ['MTD','COI','LGD','LBO','DIS','UTA'])) {
                $council = $area['id'];
            }
            if (in_array($area['type'], ['MTW','COP','LGE','LBW','DIW','UTE','UTW'])) {
                $ward = $area['id'];
            }
        }
        $match = false;
        if ($council && $ward) {
            $match = check_area($data, $council, $ward);
        }
        if ($county && $ced && !$match) {
            check_area($data, $county, $ced, false);
        }
    } else {
        $pc = canonicalise_postcode($pc);
        $pc2 = substr($pc, 0, 2);
        $pc3 = substr($pc, 0, 3);
        if (array_key_exists($pc, $special_postcodes)) {
            special_result($special_postcodes[$pc]);
        } elseif (array_key_exists($pc2, $special_areas)) {
            special_result($special_areas[$pc2]);
        } elseif ($pc3 == 'RE1') {
            $cls[] = 'ok';
            $results[] = 'The crew of the mining ship Red Dwarf should worry more about holo-viruses and Epideme.';
        } elseif (!validate_postcode($pc)) {
            if (validate_partial_postcode($pc)) {
                $results[] = 'A partial postcode is not enough to provide an accurate result, I&rsquo;m afraid.';
            } else {
                $results[] = 'We did not recognise that postcode, sorry.';
            }
            $cls[] = 'error';
        } else {
            $data = mapit_call('postcode/' . urlencode($pc));
            $council = $data['shortcuts']['council'];
            $ward = $data['shortcuts']['ward'];
            if (!is_int($council)) {
                $match = check_area($data['areas'], $council['district'], $ward['district']);
                if (!$match) {
                    $match = check_area($data['areas'], $council['county'], $ward['county'], false);
                }
            } else {
                check_area($data['areas'], $council, $ward);
            }
        }
    }
}

output();
footer();

function matching_area($data, $id) {
    global $areas, $cls, $parliament, $council_urls;

    $area = $areas[$id];
    $result = '<big>' . $data[$id]['name'];

    $tiers = [
        1 => 'medium',
        2 => 'high',
        3 => 'very high',
    ];
    if ($area['tier']) {
        $tier_name = $tiers[$area['tier']];
    }
    if ($area['future'] && $area['future'] == 'future') {
        if ($area['tier']) {
            $result .= " will be in the <strong>$tier_name</strong> tier (tier $area[tier])";
        } else {
            $result .= " will have local restrictions";
        }
        $result .= " at some point soon";
        $cls[] = 'info';
    } elseif ($area['future'] && time() < $area['future']) {
        $date = date('l, jS F', $area['future']);
        $hour = date('H:i', $area['future']);
        if ($hour != '00:00') {
            $date = "$hour on $date";
        }
        if ($area['tier']) {
            $result .= " will be in the <strong>$tier_name</strong> tier (tier $area[tier])";
        } else {
            $result .= " will have local restrictions";
        }
        $result .= " from <strong>$date</strong>";
        $cls[] = 'info';
    } else {
        if ($area['tier']) {
            $result .= " is in the <strong>$tier_name</strong> tier (tier $area[tier])";
        } else {
            $result .= " has local restrictions";
        }
        $cls[] = 'warn';
    }
    $result .= '.</big>';

    $parl_id = $id;
    if (strpos($area['link'], 'llanelli') > -1) { $parl_id = 'Llanelli'; }
    if (strpos($area['link'], 'bangor') > -1) { $parl_id = 'Bangor'; }
    if (strpos($area['link'], 'high-peak') > -1) { $parl_id = 'Part of High Peak'; }
    if ($props = $parliament[$parl_id]) {
        $result .= parl_display($props);
    }

    $result .= "<p><small>Source and more info: " . link_wbr($area['link']) . ".";
    if ($parliament[$parl_id]) {
        $result .= ' Thanks to House of Commons Library for the summary data.';
    }
    if (array_key_exists('extra', $area)) {
        $result .= ' ' . $area['extra'];
    }
    $result .= '</small></p>';
    if ($url = $council_urls[$id]) {
        $result .= "<p>Council website: " . link_wbr($url) . "</p>";
    } elseif (strpos($area['link'], 'high-peak') > -1) {
        $result .= "<p><small>And the council&rsquo;s website: <a href='https://www.highpeak.gov.uk/'>https://www.highpeak.gov.uk/</a></small>";
    }

    return $result;
}

function special_result($r) {
    global $results, $cls;
    $result = $r[2];
    if ($r[1]) {
        $link = $r[1];
        $result .= "<br><small>See the current guidance: " . link_wbr($link) . ".</small>";
    }
    $cls[] = $r[0];
    $results[] = $result;
}

function check_area($data, $council, $ward=null, $showinfo=true) {
    global $results, $cls, $areas, $pc, $pc_country, $council_urls, $parliament;

    $match = 1;
    $pc_country = $data ? $data[$council]['country'] : null;
    if (!$data) {
        $result = 'That postcode did not return a result, sorry.';
        $cls[] = 'error';
    } elseif (array_key_exists($ward, $areas)) {
        $result = matching_area($data, $ward);
    } elseif (array_key_exists($council, $areas)) {
        $result = matching_area($data, $council);
    } elseif ($showinfo) {
        $match = 0;
        $result = $data[$council]['name'];
        if ($pc_country == 'E') {
            $result .= " is in the <strong>medium tier</strong>";
        } else {
            $result .= ' does not currently have additional local restrictions';
        }
        $result .= '.';

        $country_to_parl = [
            'E' => 'Rest of England',
            'W' => 'Rest of Wales',
            'S' => 'Rest of Scotland',
            'N' => 'Rest of Northern Ireland',
        ];
        if ($props = $parliament[$country_to_parl[$pc_country]]) {
            $result .= parl_display($props);
        }

        $link = national_guidance($pc_country);
        $result .= "<p><small>See the current national guidance: " . link_wbr($link) . ".";
        if ($parliament[$country_to_parl[$pc_country]]) {
            $result .= ' Thanks to House of Commons Library for the summary data.';
        }
        $result .= '</small></p>';
        if ($url = $council_urls[$council]) {
            $result .= "<p><small>And the council&rsquo;s website: " . link_wbr($url) . ".</small>";
        }
        $cls[] = 'info';
    }
    $results[] = $result;
    return $match;
}

function national_guidance($country) {
    $guidance = [
        'E' => 'https://www.gov.uk/guidance/local-covid-alert-level-medium',
        'W' => 'https://gov.wales/coronavirus',
        'S' => 'https://www.gov.scot/publications/coronavirus-covid-19-what-you-can-and-cannot-do/',
        'N' => 'https://www.nidirect.gov.uk/articles/coronavirus-covid-19-regulations-guidance-what-restrictions-mean-you',
    ];
    return $guidance[$country];
}

function parl_display($props) {
    $result = '<div>';
    $local = [];
    $national = [];
    if ($props['local_householdmixing']) $local[] = 'Household mixing';
    if ($props['local_ruleofsix']) $local[] = 'Rule of six';
    if ($props['local_stayinglocal']) $local[] = 'Entering/leaving local area';
    if ($props['local_stayinghome']) $local[] = 'Leaving your home';
    if ($props['local_notstayingaway']) $local[] = 'Not staying away';
    if ($props['local_openinghours']) $local[] = 'Opening hours';
    if ($props['local_businessclosures']) $local[] = 'Business closures';
    if ($props['local_alcoholsalesrestrictions']) $local[] = 'Alcohol sales';
    if ($props['national_householdmixing']) $national[] = 'Household mixing';
    if ($props['national_ruleofsix']) $national[] = 'Rule of six';
    if ($props['national_stayinglocal']) $national[] = 'Staying local';
    if ($props['national_stayinghome']) $national[] = 'Entering/leaving local area';
    if ($props['national_notstayingaway']) $national[] = 'Not staying away';
    if ($props['national_gatherings']) $national[] = 'Gatherings';
    if ($props['national_openinghours']) $national[] = 'Opening hours';
    if ($props['national_businessclosures']) $national[] = 'Business closures';
    if ($props['national_alcoholsalesrestrictions']) $national[] = 'Alcohol sales';
    if ($local) {
        $result .= '<div';
        if ($local && $national) $result .= ' style="float:left; width:50%"';
        $result .= '><p><a href="' . $props['url_local'] . '">Restrictions</a> apply for: <ul><li>' . join('<li>', $local) . '</ul></div>';
    }
    if ($national) {
        $result .= '<div';
        if ($local && $national) $result .= ' style="float:left;width:50%"';
        $result .= '><p><a href="' . $props['url_national'] . '">National restrictions</a> apply for: <ul><li>' . join('<li>', $national) . '</ul></div>';
    }
    $result .= '</div>';
    return $result;
}
