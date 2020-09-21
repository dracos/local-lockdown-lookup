<?php

$title = 'Local Lockdown Lookup';
require 'site.inc';

require 'utils.php';

# CSV is MapIt ID and government link of local lockdown areas
load_areas();

# A file containing all the NI postcodes in lockdown, also see check below
$postcodes = explode("\n", file_get_contents('bt-postcodes.txt'));;
$special_postcodes = [
    'ASCN 1ZZ' => [ 'info', 'https://www.ascension.gov.ac/government/news', 'Ascension Island is at Level 1 AMBER.' ],
    'BIQQ 1ZZ' => [ 'ok', 'https://www.bas.ac.uk/media-post/update-on-2020-21-antarctic-field-season-responding-to-covid-19-pandemic/', 'The British Antarctic Survey is currently COVID-19 free.' ],
    'BBND 1ZZ' => [ 'ok', 'https://www.afgsc.af.mil/News/Article-Display/Article/2323616/maintaining-bomber-lethality-readiness-during-covid-19/', 'Diego Garcia is quarantining everyone.' ],
    'FIQQ 1ZZ' => [ 'ok', 'https://fig.gov.fk/covid-19/', 'The Falkland Islands have no cases, and quarantines all arrivals.' ],
    'PCRN 1ZZ' => [ 'ok', 'https://www.visitpitcairn.pn/covid19/', 'Pitcairn Island has never had any coronavirus; no-one but residents and essential staff are allowed to visit until at least 31st March 2021.' ],
    'SIQQ 1ZZ' => [ 'ok', 'http://www.gov.gs/july-20/', 'South Georgia remains free from COVID-19.' ],
    'STHL 1ZZ' => [ 'ok', 'https://www.sainthelena.gov.sh/coronavirus-covid-19-live-qa/', 'St Helena is COVID-19 free; visitors must quarantine.' ],
    'TDCU 1ZZ' => [ 'ok', 'https://www.tristandc.com/coronavirusnews.php', 'Tristan da Cunha is currently free of COVID-19.', ],
    'TKCA 1ZZ' => [ 'info', 'https://www.gov.tc/moh/coronavirus/', 'The Turks and Caicos Islands have national restrictions.' ],
    'SANTA1' => [ 'ok', '', 'Father Christmas&rsquo;s workshop is free of COVID-19.' ],
    'XM4 5HQ' => [ 'ok', '', 'Father Christmas&rsquo;s workshop is free of COVID-19.' ],
];
$special_areas = [
    'JE' => [ 'info', 'https://www.gov.je/Health/Coronavirus/Pages/index.aspx', 'Jersey has some social restrictions.' ],
    'GY' => [ 'ok', 'https://covid19.gov.gg/', 'Guernsey and Alderney have no social restrictions, but has rules on quarantine on arrival.' ],
    'IM' => [ 'ok', 'https://covid19.gov.im/', 'The Isle of Man has lifted social distancing measures.' ],
];

$results = [];
$cls = [];

$pc = array_key_exists('pc', $_GET) ? $_GET['pc'] : '';
if ($pc) {
    $pc = canonicalise_postcode($pc);
    if (array_key_exists($pc, $special_postcodes)) {
        $result = $special_postcodes[$pc][2];
        if ($special_postcodes[$pc][1]) {
            $link = $special_postcodes[$pc][1];
            $result .= "<br><small>See the current guidance: " . link_wbr($link) . ".</small>";
        }
        $cls[] = $special_postcodes[$pc][0];
        $results[] = $result;
    } elseif (array_key_exists(substr($pc, 0, 2), $special_areas)) {
        $part = substr($pc, 0, 2);
        $cls[] = $special_areas[$part][0];
        $link = $special_areas[$part][1];
        $result = $special_areas[$part][2];
        $result .= "<br><small>See the current guidance: " . link_wbr($link) . ".</small>";
        $results[] = $result;
    } elseif (preg_match('#^RE1#', $pc)) {
        $cls[] = 'ok';
        $results[] = 'The crew of the mining ship Red Dwarf should worry more about holo-viruses and Epideme.';
    } elseif (!validate_postcode($pc)) {
        if (validate_partial_postcode($pc)) {
            $results[] = 'A partial postcode is not enough to provide an accurate result, I&rsquo;m afraid.';
        } else {
            $results[] = 'We did not recognise that postcode, sorry.';
        }
        $cls[] = 'error';
    } elseif (preg_match('#^BT(28|29|43|60)#', $pc) || in_array($pc, $postcodes)) {
        $link = 'https://www.nidirect.gov.uk/articles/coronavirus-covid-19-regulations-and-localised-restrictions';
        $results[] = "The area has local restrictions.<br><small>Source and more info: " . link_wbr($link) . ".</small>";
        $cls[] = 'warn';
    } else {
        $data = mapit_call('postcode/' . urlencode($pc));
        $council = $data['shortcuts']['council'];
        $ward = $data['shortcuts']['ward'];
        # If two-tier, we want the district, not the county.
        if (!is_int($council)) { $council = $council['district']; }
        if (!is_int($ward)) { $ward = $ward['district']; }
        check_area($data['areas'], $council, $ward);
    }
}

output();
footer();

function matching_area($data, $id) {
    global $areas, $cls;

    $area = $areas[$id];
    $result = $data[$id]['name'];
    if (array_key_exists('future', $area) && date('Y-m-d') < date('Y-m-d', $area['future'])) {
        $result .= " will have local restrictions from <strong>" . date('jS F', $area['future']) . '</strong>';
        $cls[] = 'info';
    } else {
        $result .= " has local restrictions";
        $cls[] = 'warn';
    }
    $result .= ".<br><small>Source and more info: " . link_wbr($area['link']) . ".</small>";
    if (array_key_exists('extra', $area)) {
        $result .= ' <small>' . $area['extra'] . '</small>';
    }
    return $result;
}

function check_area($data, $council, $ward=null) {
    global $results, $cls, $areas, $pc;

    if (!$data) {
        $result = 'That postcode did not return a result, sorry.';
        $cls[] = 'error';
    } elseif (array_key_exists($ward, $areas)) {
        $result = matching_area($data, $ward);
    } elseif (array_key_exists($council, $areas)) {
        $result = matching_area($data, $council);
    } else {
        $result = preg_match('#^BT#', $pc) ? "That area" : $data[$council]['name'];
        $result .= ' does not currently have additional local restrictions.';
        $link = national_guidance($data[$council]['country']);
        $result .= "<br><small>See the current national guidance: " . link_wbr($link) . ".</small>";
        $cls[] = 'ok';
    }
    $results[] = $result;
}

function national_guidance($country) {
    $guidance = [
        'E' => 'https://www.gov.uk/government/publications/coronavirus-outbreak-faqs-what-you-can-and-cant-do/coronavirus-outbreak-faqs-what-you-can-and-cant-do',
        'W' => 'https://gov.wales/coronavirus',
        'S' => 'https://www.gov.scot/publications/coronavirus-covid-19-what-you-can-and-cannot-do/',
        'N' => 'https://www.nidirect.gov.uk/campaigns/coronavirus-covid-19',
    ];
    return $guidance[$country];
}
