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
        $text = str_replace('/', '/<wbr>', $link);
        $results[] = "The area is in a local lockdown.<br><small>Source and more info: <a href='$link'>$text</a>.</small>";
        $cls[] = 'warn';
    } else {
        $data = mapit_call('postcode/' . urlencode($pc));
        $council = $data['shortcuts']['council'];
        $ward = $data['shortcuts']['ward'];
        # If two-tier, we want the district, not the county.
        if (!is_int($council)) { $council = $council['district']; }
        if (!is_int($ward)) { $ward = $ward['district']; }
        matching_area($data['areas'], $council, $ward);
    }
}

output();
footer();

function matching_area($data, $council, $ward=null) {
    global $results, $cls, $areas, $pc;

    if (array_key_exists($ward, $areas)) {
        $area = $areas[$ward];
        $result = $data[$ward]['name'] . " ward is in a local lockdown.<br><small>Source and more info: <a href='$area[link]'>$area[text]</a>.</small>";
        if (array_key_exists('extra', $area)) {
            $result .= ' <small>' . $area['extra'] . '</small>';
        }
        $cls[] = 'warn';
    } elseif (array_key_exists($council, $areas)) {
        $area = $areas[$council];
        if (array_key_exists('future', $area) && date('Y-m-d') < date('Y-m-d', $area['future'])) {
            $result = $data[$council]['name'] . " will be in a local lockdown from <strong>" . date('jS F', $area['future']) . '</strong>';
            $cls[] = 'info';
        } else {
            $result = $data[$council]['name'] . " is in a local lockdown";
            $cls[] = 'warn';
        }
        $result .= ".<br><small>Source and more info: <a href='$area[link]'>$area[text]</a>.</small>";
        if (array_key_exists('extra', $area)) {
            $result .= ' <small>' . $area['extra'] . '</small>';
        }
    } elseif (!$data) {
        $result = 'That postcode did not return a result, sorry.';
        $cls[] = 'error';
    } else {
        if (preg_match('#^BT#', $pc)) {
            $result = "That area is not currently in a local lockdown.";
        } elseif ($council) {
            $result = $data[$council]['name'] . ' is not currently in a local lockdown.';
        } else {
            $result = "That postcode is not currently in a local lockdown.";
        }
        $country = $data[$council]['country'];
        if ($country == 'E') {
            $link = 'https://www.gov.uk/government/publications/coronavirus-outbreak-faqs-what-you-can-and-cant-do/coronavirus-outbreak-faqs-what-you-can-and-cant-do';
        } elseif ($country == 'W') {
            $link = 'https://gov.wales/coronavirus';
        } elseif ($country == 'S') {
            $link = 'https://www.gov.scot/publications/coronavirus-covid-19-what-you-can-and-cannot-do/';
        } elseif ($country == 'N') {
            $link = 'https://www.nidirect.gov.uk/campaigns/coronavirus-covid-19';
        }
        $text = str_replace('/', '/<wbr>', $link);
        $result .= "<br><small>National lockdown guidance: <a href='$link'>$text</a>.</small>";
        $cls[] = 'ok';
    }
    $results[] = $result;
}
