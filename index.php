<?php

$title = 'Local Lockdown Lookup';
require 'site.inc';

# CSV is MapIt ID and government link of local lockdown areas
$areas = [];
$fp = fopen('areas.csv', 'r');
fgetcsv($fp);
while ($row = fgetcsv($fp)) {
    $id = intval($row[0]);
    $areas[$id] = [
        'link' => $row[1],
        'text' => str_replace('/', '/<wbr>', $row[1]),
    ];
    if (count($row) > 2) {
        $areas[$id]['future'] = strtotime($row[2]);
    }
    if (strpos($row[1], 'www.gov.uk') && !strpos($row[1], 'birmingham') && !strpos($row[1], '/news/')) {
        $areas[$id]['extra'] = 'Do note the bit hidden many paragraphs down advising you should not &ldquo;socialise with people you do not live with, unless they&rsquo;re in your support bubble, in any public venue&rdquo;.';
    }
}
fclose($fp);

# A file containing all the NI postcodes in lockdown, also see check below
$postcodes = explode("\n", file_get_contents('bt-postcodes.txt'));;

$results = [];
$cls = [];

$key = trim(file_get_contents('KEY'));

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
?>

<style>
.res { color: #fff; margin: 0; padding: 0.5em; font-size: 150%; }
.res-warn { background-color: #d34; }
.res-info { background-color: #29b; }
.res-error { color: #000; background-color: #fb1; }
.res-ok { background-color: #3a4; }
.res a { color: #fff; }
.res a:hover { color: #000; }
</style>

<?php
if ($results) {
    print "<h2>" . htmlspecialchars($pc);
    print "</h2>";
    foreach ($results as $i => $result) {
        print "<p class='res res-$cls[$i]'>$result</p>";
    }
}
?>
<p style="font-size: 125%">This postcode lookup uses <a href="https://mapit.mysociety.org/">MapIt</a>
<small>(an API to provide postcode to council lookup, take a look)</small>
to look up the council or ward for your postcode, and then tells you if
that is currently in a localised lockdown.
<small>It was last updated at <strong>11pm on 18th September 2020</strong>.</small>
</p>

<div align="center" style="background-color: #eee; padding: 0.5em;">
        <form method="get">
            <p style="font-size:150%"><label for="pc" style="display:inline">Postcode:</label>
                <input type="text" size=10 maxlength=10 name="pc" id="pc" value="<?=htmlspecialchars($pc) ?>">
                <input type="submit" value="Look up">
        </form>
</div>

<h3>Notes</h3>
<ol>
<li>A few postcodes cross council boundaries, and this tool will return the result for
the centroid of the postcode. Sadly better data is not available as open data, though
many have campaigned for this over the years; the government do have access to better
data and could make a tool like this that worked even for those postcodes.</p>

<li>If I am unable to keep this up to date, I will immediately remove it and
leave only these links to the various UK government sites:
<ul>
<li><a href="https://www.gov.uk/government/collections/local-restrictions-areas-with-an-outbreak-of-coronavirus-covid-19">England</a>
<li><a href="https://www.nidirect.gov.uk/articles/coronavirus-covid-19-regulations-and-localised-restrictions">Northern Ireland</a>
<li><a href="https://www.gov.scot/coronavirus-covid-19/">Scotland</a>
<li><a href="https://gov.wales/local-lockdown">Wales</a>
</ul>

<li>To help me keep this up to date, the code is on <a href="https://github.com/dracos/local-lockdown-lookup">GitHub</a>.
Pull Requests for changes to the areas or postcode list are welcome.

</ol>

<?php

footer();

function matching_area($data, $council, $ward=null) {
    global $results, $cls, $areas;

    if (array_key_exists($ward, $areas)) {
        $area = $areas[$ward];
        $result = $data[$ward]['name'] . " ward is in a local lockdown.<br><small>Source and more info: <a href='$area[link]'>$area[text]</a>.</small>";
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
        if ($council) {
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

function validate_postcode ($postcode) {
    // Our test postcode
    if (preg_match("/^zz9\s*9z[zy]$/i", $postcode))
        return true; 
    
    // See http://www.govtalk.gov.uk/gdsc/html/noframes/PostCode-2-1-Release.htm
    $in  = 'ABDEFGHJLNPQRSTUWXYZ';
    $fst = 'ABCDEFGHIJKLMNOPRSTUWYZ';
    $sec = 'ABCDEFGHJKLMNOPQRSTUVWXY';
    $thd = 'ABCDEFGHJKSTUW';
    $fth = 'ABEHMNPRVWXY';
    $num0 = '123456789'; # Technically allowed in spec, but none exist
    $num = '0123456789';
    $nom = '0123456789';

    if (preg_match("/^[$fst][$num0]\s*[$nom][$in][$in]$/i", $postcode) ||
        preg_match("/^[$fst][$num0][$num]\s*[$nom][$in][$in]$/i", $postcode) ||
        preg_match("/^[$fst][$sec][$num]\s*[$nom][$in][$in]$/i", $postcode) ||
        preg_match("/^[$fst][$sec][$num0][$num]\s*[$nom][$in][$in]$/i", $postcode) ||
        preg_match("/^[$fst][$num0][$thd]\s*[$nom][$in][$in]$/i", $postcode) ||
        preg_match("/^[$fst][$sec][$num0][$fth]\s*[$nom][$in][$in]$/i", $postcode)) {
        return true;
    } else {
        return false;
    }
}

function canonicalise_postcode($pc) {
    $pc = preg_replace('#[^A-Z0-9]#i', '', $pc);
    $pc = strtoupper($pc);
    $pc = preg_replace('#(\d[A-Z]{2})#', ' $1', $pc);
    return $pc;
}

function mapit_call($url) {
    global $key;
    return json_decode(file_get_contents('https://mapit.mysociety.org/' . $url . '?api_key=' . $key), true);
}
