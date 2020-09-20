<?php

$title = 'Local Lockdown Lookup';
require 'site.inc';

require 'utils.php';

# CSV is MapIt ID and government link of local lockdown areas
load_areas();

# A file containing all the NI postcodes in lockdown, also see check below
$postcodes = explode("\n", file_get_contents('bt-postcodes.txt'));;

$results = [];
$cls = [];

$pc = array_key_exists('pc', $_GET) ? $_GET['pc'] : '';
if ($pc) {
    $pc = canonicalise_postcode($pc);
    if (!validate_postcode($pc)) {
        $results[] = 'We did not recognise that postcode, sorry.';
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
