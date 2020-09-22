<?php

$dir = dirname(__FILE__);

# CSV is MapIt ID and government link of local lockdown areas
function load_areas() {
    global $areas, $dir;
    $areas = [];
    $csv_mt = filemtime($dir . '/areas.csv');
    $php_mt = @filemtime($dir . '/cache/areas.php');
    if ($php_mt >= $csv_mt) {
        include_once $dir . '/cache/areas.php';
        return;
    }
    $fp = fopen($dir . '/areas.csv', 'r');
    fgetcsv($fp);
    while ($row = fgetcsv($fp)) {
        $id = intval($row[0]);
        $areas[$id] = [
            'link' => $row[1],
            'future' => strtotime($row[2]),
        ];
        if (strpos($row[1], 'www.gov.uk') && !strpos($row[1], 'birmingham') && !strpos($row[1], '/news/')) {
            $areas[$id]['extra'] = 'Do note the bit hidden many paragraphs down advising you should not &ldquo;socialise with people you do not live with, unless they&rsquo;re in your support bubble, in any public venue&rdquo;.';
        }
    }
    fclose($fp);

    $fp = fopen($dir . '/cache/areas.php', 'w');
    fwrite($fp, "<?php\n");
    fwrite($fp, '$areas = ');
    fwrite($fp, var_export($areas, true));
    fwrite($fp, ";\n");
    fclose($fp);
}

function output() {
    global $results, $cls, $pc;
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
<small>It was last updated at <strong>11am on 22nd September 2020</strong>.</small>
</p>

<div align="center" style="background-color: #eee; padding: 0.5em;">
        <form method="get" action="/made/local-lockdown-lookup/">
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
leave only these links to the various UK government sites.
You can also use those links if you do not want to provide a postcode.
<ul>
<li><a href="https://www.gov.uk/government/collections/local-restrictions-areas-with-an-outbreak-of-coronavirus-covid-19">England</a>
<li><a href="https://www.nidirect.gov.uk/articles/coronavirus-covid-19-regulations-and-localised-restrictions">Northern Ireland</a>
<li><a href="https://www.gov.scot/coronavirus-covid-19/">Scotland</a>
<li><a href="https://gov.wales/local-lockdown">Wales</a>
</ul>

<li>To help me keep this up to date, the code is on <a href="https://github.com/dracos/local-lockdown-lookup">GitHub</a>.
Pull Requests for changes to the areas or postcode list are welcome.

<li><a href="https://www.microcovid.org/">https://www.microcovid.org/</a> is a useful tool to
provide you with estimated risk level of various activities.
<br>Avoid the 3 Cs: Crowds, Closed Spaces, and Close Contact.
MODify your socializing: Masked, Outdoors, Distanced.

</ol>

<?php
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

function validate_partial_postcode ($postcode) {
    // Our test postcode
    if (preg_match("/^zz9/i", $postcode))
        return true;

    // See http://www.govtalk.gov.uk/gdsc/html/noframes/PostCode-2-1-Release.htm
    $fst = 'ABCDEFGHIJKLMNOPRSTUWYZ';
    $sec = 'ABCDEFGHJKLMNOPQRSTUVWXY';
    $thd = 'ABCDEFGHJKSTUW';
    $fth = 'ABEHMNPRVWXY';
    $num0 = '123456789'; # Technically allowed in spec, but none exist
    $num = '0123456789';

    if (preg_match("/^[$fst][$num0]$/i", $postcode) ||
        preg_match("/^[$fst][$num0][$num]$/i", $postcode) ||
        preg_match("/^[$fst][$sec][$num]$/i", $postcode) ||
        preg_match("/^[$fst][$sec][$num0][$num]$/i", $postcode) ||
        preg_match("/^[$fst][$num0][$thd]$/i", $postcode) ||
        preg_match("/^[$fst][$sec][$num0][$fth]$/i", $postcode)) {
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

$key = trim(file_get_contents($dir . '/KEY'));

function mapit_call($url) {
    global $key;
    return json_decode(file_get_contents('https://mapit.mysociety.org/' . $url . '?api_key=' . $key), true);
}

function link_wbr($link) {
    $text = str_replace('/', '/<wbr>', $link);
    return "<a href='$link'>$text</a>";
}
