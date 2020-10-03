<?php

if (array_key_exists('govuk', $_GET)) {
    $style = 'govuk-frontend-3.9.1.min.css';
}

$title = 'Local Lockdown Lookup &ndash; Comparison';
require 'site.inc';
require '../utils.php';

?>

<p>There are a plethora of these services now, it appears.
I have made a quick comparison table between them,
hopefully have not got anything wrong. Let me know if so,
of course.
</p>

<style>
td.lll-n { background-color: #d34; color: #fff; }
td.lll-y { background-color: #3a4; color: #fff; }
td.lll-p { background-color: #fb1; color: #000; }
.lll-head { text-align: left; }
th, td { padding: 1em; }
th { border-top: none; }
th { border-left: none; }
tr th:first-child { background-color: #fff; position: sticky; left: 0; }
</style>

<div style="overflow-x:auto">
<table>

<thead>
<tr>
    <th>Site</th>
    <th><a href="https://www.lockdownapi.com/">LockdownAPI</a></th>
    <th><a href="https://www.politicshome.com/news/article/live-map-local-lockdown-restrictions-coronavirus-uk">PoliticsHome</a></th>
    <th><a href="https://visual.parliament.uk/research/visualisations/coronavirus-restrictions-map/">Parliament</a></th>
    <th><a href="https://www.thetimes.co.uk/edition/news/what-new-lockdown-rules-local-area-latest-7xhrlvz5m">Times</a></th>
    <th><a href="https://www.bbc.co.uk/news/uk-54373904">BBC</a></th>
    <th><a href="../">dracos.co.uk</a></th>
</tr>
</thead>

<tr><th class="lll-head">Accuracy</th></tr>

<tr>
<th>Handles sub-council area (e.g. Llanelli) <!--  SA32 8HN --></th>
<td class="lll-n">No</td>
<td class="lll-p">Whole area marked on map, text says Llanelli only</td>
<td class="lll-y">Yes</td>
<td class="lll-n">No, restricts all Carmarthenshire</td>
<td class="lll-n">No result, links to council website</td>
<td class="lll-y">Yes</td>
</tr>

<tr>
<th>Handles non-centralised rules (e.g. Tower Hamlets)</th>
<td class="lll-n">No</td>
<td class="lll-n">No</td>
<td class="lll-n">No</td>
<td class="lll-n">No</td>
<td class="lll-n">No</td>
<td class="lll-y">Yes</td>
</tr>

<tr>
<th>Links to direct source for checking (not just top-level)</th>
<td class="lll-n">No</td>
<td class="lll-n">No</td>
<td class="lll-y">Yes</td>
<td class="lll-n">No</td>
<td class="lll-n">No</td>
<td class="lll-y">Yes</td>
</tr>

<tr>
<th>Links to council websites</th>
<td class="lll-n">No</td>
<td class="lll-n">No</td>
<td class="lll-n">No</td>
<td class="lll-n">No</td>
<td class="lll-p">Only in lockdown areas it knows about</td>
<td class="lll-y">Yes</td>
</tr>

<tr>
<th>Handles all UK</th>
<td class="lll-y">Yes</td>
<td class="lll-n">No; has out-of-date text for NI</td>
<td class="lll-y">Yes</td>
<td class="lll-y">Yes</td>
<td class="lll-y">Yes</td>
<td class="lll-y">Yes</td>
</tr>

<tr>
<th>Is accurate as of 10am, 3rd October</th>
<td class="lll-p">Lacking important info, such as travel restrictions</td>
<td class="lll-n">No (e.g. is missing Wolverhampton)</td>
<td class="lll-p">Only points out missing local areas in Scotland if you click Show more</td>
<td class="lll-n">No (e.g. says can meet others in pubs in Preston)</td>
<td class="lll-n">No (e.g. says parts of Bradford are different)</td>
<td class="lll-y">Yes</td>
</tr>

<tr><th class="lll-head">Features</th></tr>

<tr>
<th>Has postcode lookup</th>
<td class="lll-y">Yes</td>
<td class="lll-n">No</td>
<td class="lll-n">No</td>
<td class="lll-y">Yes</td>
<td class="lll-y">Yes</td>
<td class="lll-y">Yes</td>
</tr>

<tr>
<th>Has geolocation</th>
<td class="lll-n">No</td>
<td class="lll-n">No</td>
<td class="lll-n">No</td>
<td class="lll-n">No</td>
<td class="lll-n">No</td>
<td class="lll-y">Yes</td>
</tr>

<tr>
<th>Has a map</th>
<td class="lll-n">No</td>
<td class="lll-y">Yes</td>
<td class="lll-y">Yes</td>
<td class="lll-y">Yes</td>
<td class="lll-n">No</td>
<td class="lll-n">No</td>
</tr>

<tr>
<th>Include NHS App risk level in England/Wales</th>
<td class="lll-n">No</td>
<td class="lll-n">No</td>
<td class="lll-n">No</td>
<td class="lll-n">No</td>
<td class="lll-n">No</td>
<td class="lll-y">Yes</td>
</tr>

<tr>
<th>Includes other information</th>
<td class="lll-n">No</td>
<td class="lll-n">No</td>
<td class="lll-n">No</td>
<td class="lll-y">Local case figures</td>
<td class="lll-n">No</td>
<td class="lll-n">No</td>
</tr>

<tr>
<th>Open process for accepting changes</th>
<td class="lll-n">No</td>
<td class="lll-n">No</td>
<td class="lll-n">No</td>
<td class="lll-n">No</td>
<td class="lll-n">No</td>
<td class="lll-y">Yes</td>
</tr>

<tr><th class="lll-head">Performance</th></tr>

<tr>
<th>Works without JavaScript</th>
<td class="lll-n">No</td>
<td class="lll-n">No</td>
<td class="lll-n">No</td>
<td class="lll-n">No</td>
<td class="lll-n">No</td>
<td class="lll-y">Yes</td>
</tr>

<tr>
<th>Size of page transfer</th>
<td class="lll-">0.53MB</td>
<td class="lll-">1.3MB</td>
<td class="lll-">3.5MB</td>
<td class="lll-">2.4MB</td>
<td class="lll-">0.88MB</td>
<td class="lll-">0.13MB</td>
</tr>

</table>
</div>

<hr>

<?php


output();
footer();
