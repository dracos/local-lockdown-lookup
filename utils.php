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
            'future' => $row[2] == 'future' ? $row[2] : strtotime($row[2]),
        ];
        if (strpos($row[1], 'www.gov.uk') && !strpos($row[1], 'birmingham') && !strpos($row[1], '/news/')) {
            $areas[$id]['extra'] = 'Do note the bit hidden many paragraphs down advising you should not &ldquo;socialise with people you do not live with, unless they&rsquo;re in your support bubble, in any public venue&rdquo;.';
        }
        if (strpos($row[1], 'north-east-of-england')) {
            $areas[$id]['extra'] = '<br>Please also see the <a href="https://www.gov.uk/government/news/indoor-inter-household-mixing-restricted-in-parts-of-the-north-east">new restrictions on inter-household mixing</a> from Wednesday 30th September.';
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

function load_special() {
    global $dir, $special_postcodes, $special_areas, $parliament, $council_urls;

    @include_once $dir . '/cache/parliament.php';
    @include_once $dir . '/councils.php';

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
        'GY' => [ 'ok', 'https://covid19.gov.gg/', 'Guernsey, Alderney and Sark have no social restrictions, but have rules on quarantine on arrival.' ],
        'IM' => [ 'ok', 'https://covid19.gov.im/', 'The Isle of Man has lifted social distancing measures.' ],
    ];
}

function output() {
    global $results, $cls, $pc, $pc_country;
?>

<style>
.lll-form-wrapper {
<?php if (array_key_exists('govuk', $_GET)) { ?>
    padding-left: 2em;
<?php } else { ?>
    padding: 0.5em;
    background-color: #eee;
    text-align: center;
<?php } ?>
}
.res { color: #fff; margin: 0; padding: 0.5em; font-size: 1.2em;
    overflow: auto; }
.res big { font-size: 125%; }
.res-warn { background-color: #d34; }
.res-info { background-color: #29b; }
.res-error { color: #000; background-color: #fb1; }
.res-ok { background-color: #3a4; }
.res a { color: #fff; }
.res a:hover { color: #000; }
</style>

<?php
if ($results) {
    $nhs = json_decode(file_get_contents('cache/risky-post-districts'), 1);
    $pd = preg_replace('# .*#', '', $pc);

    print "<h2 style='overflow:auto'>" . htmlspecialchars($pc);
    if (($pc_country=='E' || $pc_country=='W') && ($level = $nhs['postDistricts'][$pd])) {
        if ($level == 'H') $level = 'High';
        if ($level == 'M') $level = 'Medium';
        if ($level == 'L') $level = 'Low';
        print " <small style='float:right'>(NHS App <a href='https://faq.covid19.nhs.uk/article/KA-01101/en-us?parentid=CAT-01034&rootid=CAT-01032'>risk level</a> for " . htmlspecialchars($pd) . ": $level)</small>";
    }

    print "</h2>";
    foreach ($results as $i => $result) {
        print "<div class='res res-$cls[$i]'>$result</div>";
    }
}
?>
<p style="font-size: 125%">This lookup uses <a href="https://mapit.mysociety.org/">MapIt</a>
<small>(an API to provide postcode/point to council lookup, take a look)</small>
to look up the council and ward for the location, and then tells you if
there are currently any nationally-imposed local restrictions.
</p>

<div class="lll-form-wrapper">
        <form id="lll-form" method="get" action="/made/local-lockdown-lookup/">
<?php if (array_key_exists('govuk', $_GET)) { ?>
<input type="hidden" name="govuk" value="1">
        <div class="govuk-form-group">
            <label class="govuk-label govuk-label--l" for="pc">Please enter a postcode below</label>
            <div id="event-name-hint" class="govuk-hint">
                <a href="#" id="geolocate_link">Or use your location</a>
            </div>
            <input class="govuk-input govuk-input--width-10" type="text" size=10 maxlength=10 name="pc" id="pc" value="<?=htmlspecialchars($pc) ?>">
        </div>
        <div class="govuk-form-group">
            <input type="submit" value="Look up" class="govuk-button">
        </div>
        </form>
<?php } else { ?>
            <p style="font-size:150%">
                <label for="pc" style="display:inline">Postcode:</label>
                <input type="text" size=10 maxlength=10 name="pc" id="pc" value="<?=htmlspecialchars($pc) ?>">
                <input type="submit" value="Look up">
        </form>
<p><a href="#" id="geolocate_link">Use your location</a></p>
<?php } ?>
</div>

<p>Data last updated at <strong>4pm on 2nd October 2020</strong>,
with information about the local measures in Tower Hamlets.
</p>

<h3>Notes</h3>
<ol>
<li>A few postcodes cross council boundaries, and this tool will return the result for
the centroid of the postcode. Sadly better data is not available as open data, though
many have campaigned for this over the years; the government do have access to better
data and could make a tool like this that worked even for those postcodes.

<li>You can also enter lat,lon if you don&rsquo;t have a postcode, or use the
&ldquo;Use your location&rdquo; button. Any locations and postcodes are not stored
anywhere apart from the server&rsquo;s log file which is automatically archived each
week and then automatically deleted after ten weeks.

<li>Local authorities may also have put in place local restrictions I don&rsquo;t know
about from the national pages. Do check your council&rsquo;s website, and
please feel free to let me know on
<a href="https://github.com/dracos/local-lockdown-lookup">GitHub</a> and I can get them included.

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

<script>
(function(){
    var link = document.getElementById('geolocate_link');
    if ('geolocation' in navigator && window.addEventListener) {
        link.addEventListener('click', function(e) {
            var link = this;
            e.preventDefault();
            link.className += ' loading';
            navigator.geolocation.getCurrentPosition(
                function(pos) {
                    link.className = link.className.replace(/loading/, ' ');
                    var latitude = pos.coords.latitude.toFixed(6);
                    var longitude = pos.coords.longitude.toFixed(6);
                    document.getElementById('pc').value = latitude + ',' + longitude;
                    document.getElementById('lll-form').submit();
                },
                function(err) {
                    link.className = link.className.replace(/loading/, ' ');
                    link.innerHTML = 'Unable to retrieve your location';
                },
                {
                    enableHighAccuracy: true,
                    timeout: 10000
                }
            );
        });
    } else {
        link.style.display = 'none';
    }
})();
</script>

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
