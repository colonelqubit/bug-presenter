<?php

// Bug Presenter - A tool to present/organize Bugzilla bugs
// Copyright (c) 2013 - Robinson Tryon <qubit@runcibility.com>
//
// This program is free software: you can redistribute it and/or
// modify it under the terms of the GNU General Public License as
// published by the Free Software Foundation, either version 3 of the
// License, or (at your option) any later version.
//
// This program is distributed in the hope that it will be useful, but
// WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the GNU
// General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with this program.  If not, see <http:www.gnu.org/licenses/>.


// IDEAS for IMPROVEMENTS:
//
//  - Look for tags that are differ only in case, and suggest
//    using consistent capitalization.
//
//  - Look for infrequently-used tags (might be misspellings/
//    errors/etc..)
//
//


// Libraries
require_once("Bugzilla.php");

// Bug base url.
$bugtracker_url = "https://bugs.libreoffice.org/";


// Information about particular tags that is helpful for triagers.
$tag_information = array(
  "Confirmed" => "Bugs confirmed using a particular LO and OS version.",
  "EasyHack" => "A bug triaged by developers and tagged by difficult, needed skills, etc..",
  "MultipleBugs" => "This bug report needs to be split into at least two separate reports.",
  "Need_Advice" => "This bug needs input from a developer.",
  "NoRepro" => "Bugs that cannot be confirmed on a particular LO and OS version.",
  "PossibleRegression" => "This bug report could be a regression. We need independent confirmation before we can remove <em>PossibleRegression</em> from the Whiteboard and add <em>regression</em> to the Keywords.",
  "ProposedEasyHack" => "A bug nominated to be triaged by developers."
  
);

// HTML blah, blah
$html_start = <<<EOD
<html>
  <head>
    <title>Qubit and the QA Team welcome you to the magical Bug Presenter</title>

    <link rel="stylesheet" type="text/css" href="styles.css">
  </head>

  <body>
EOD;
print $html_start;

// Base URL for grabbing bugs from Bugzilla
$base_csv_url = $bugtracker_url . "buglist.cgi?product=LibreOffice&query_format=advanced&limit=0&ctype=csv&human=1&columnlist=bug_id,product,component,assigned_to,bug_status,short_desc,op_sys,status_whiteboard,keywords,version";

// Given one or more bug statuses (as an array of strings), this
// function returns a Bugzilla URL that will return CSV output of all
// LibreOffice bugs that match the set of statuses.
function csv_url($statuses) {
  global $base_csv_url;
  $statuses_string = "";

  foreach($statuses as $status) {
    $statuses_string .= "&bug_status=$status";
  }

  return $base_csv_url . $statuses_string;
}


// Take the bugzilla data as CSV input and chop it up by Whiteboard
// tag.
//
// ASSUMPTION: There will be various columns in the data including:
//   - Bug ID
//   - Whiteboard
//$input_file = "bugzilla-data_UNCONFIRMED.csv";
$input_file = csv_url(array("UNCONFIRMED"));

// Optionally show data from a different data set.
$new_assigned_reopened_array = array("NEW", "ASSIGNED", "REOPENED");
if(in_array($_GET["status"], $new_assigned_reopened_array)) {
  $input_file = csv_url($new_assigned_reopened_array);
}

$data = array();

// URL for showing a bug.
$bug_show_url = $bugtracker_url . "show_bug.cgi?id=";

// This is a hash, keyed by tag names.
// Format:
//   "(tag_name)" => array( "display" => "(Tag display name)",
//                          "bugs" => array("(bug_id)", "(bug_id)", ...));
$whiteboard_tags = array();

// Whiteboard tags to ignore:
$tags_to_ignore = array("BSA");
foreach($tags_to_ignore as $tag) {
  $whiteboard_tags[$tag]["display"] = "$tag [ignored]";
}

// Initialize any special groupings
$whiteboard_tags["Confirmed"]["display"] = "Confirmed [grouped]";
$whiteboard_tags["NoRepro"]["display"] = "NoRepro [grouped]";

// Grab the data
$data = Bugzilla::csv_to_array($input_file);

// Populate the $whiteboard_tags array
// (We now need to use a 2-step process, since we factored-out
//  the more general import step)
foreach($data as $bug_id => $bug) {
  foreach($bug["Whiteboard"] as $tag) {
    // Add each tag to the list (keyed by bug #).
    $name_used_as_index = $tag;

    // We lump all of the 'Confirmed:' and 'NoRepro:' tags
    // together. We might want to do something more clever
    // in the future (e.g. grouping by verison or OS?)
    if(preg_match('/^(Confirmed|NoRepro):.+:.+/', $tag, $matches)) {
      $name_used_as_index = $matches[1];
    }

    $whiteboard_tags[$name_used_as_index]["bugs"] []= $bug_id;

    // Make sure each tag has a display name
    if(empty($whiteboard_tags[$name_used_as_index]["display"])) {
      $whiteboard_tags[$name_used_as_index]["display"] = $name_used_as_index;
    }
  }
}


print "<h1><a href=\"\">Bug Presenter:</a></h1>\n";
print "<h3>LibreOffice Bugs organized by <a href=\"https://wiki.documentfoundation.org/QA/Bugzilla/Fields/Whiteboard\">whiteboard</a> tags.</h3>\n";

// Provide options on data source, and list current source of data.


$input_data = <<<EOD
<div class="nicebox">
  <table>
    <tr><th>Input Data:</th> <td><code>$input_file</code></td></tr>
    <tr><th>Available Data:</th>
      <td><a href="?status=UNCONFIRMED">UNCONFIRMED</a> - 
          <a href="?status=NEW">NEW, REOPENED, ASSIGNED</a> - 
          NEEDINFO - 
          RESOLVED
      </td>
    </tr>
  </table>
</div>
EOD;
print $input_data;

// Provide a search link for a whiteboard tag.
function search_link_for_tag($tag) {
  global $bugtracker_url;
  return $bugtracker_url . "buglist.cgi?status_whiteboard_type=allwordssubstr&query_format=advanced&status_whiteboard=$tag&bug_status=UNCONFIRMED&bug_status=NEW&bug_status=ASSIGNED&bug_status=REOPENED&product=LibreOffice";
}

$notes = <<<EOD
<ul>
  <li>Lists of bugs are presented below.</li>
  <li><strike>We're only including UNCONFIRMED bugs for now.</strike> - we're experimenting with different lists: UNCONFIRMED, NEW + ASSIGNED + REOPENED, etc..</li>
  <li>Whiteboard tags are sorted in case-insensitive alphabetical order.</li>
  <li>Code for this Bug Presenter <a href="https://github.com/colonelqubit/bug-presenter">is up on GitHub now.</a></li>
</ul>

EOD;
print $notes;

// OPTIONALLY test out a stats table.

if($_GET["stats-table"]) {
  // Need to keep track of all of the versions we encounter.
  $versions = array();
  // Store bug ids in a 2-d hash keyed on component and version.
  $bugs_by_component_and_version = array();

  foreach($data as $bug_id => $bug) {
    $v = $bug["Version"];

    // Chop-down long version numbers to just MAJOR.minor.
    if(preg_match('/^([0-9]+[.][0-9]+)/', $v, $matches)) {
      $v = $matches[1];
    }

    // Throw a warning for any bugs with no version, etc..
    if(empty($v)) {
      print "<br />Empty bug: <a href=\"$bugtracker_url$bug_id\">$bug_id</a><br />\n";
      // Ignore it and move on.
      continue;
    }

    if(isset($versions[$v])) {
      $versions[$v] += 1;
    } else {
      $versions[$v] = 1;
    }
    if(isset($bugs_by_component_and_version[$bug["Component"]][$v])) {
      $bugs_by_component_and_version[$bug["Component"]][$v] += 1;
    } else {
      $bugs_by_component_and_version[$bug["Component"]][$v] = 1;
    }
  }

  // Sort the versions and components alphabetically.
  uksort($versions, 'strcasecmp');
  uksort($bugs_by_component_and_version, 'strcasecmp');

  // Print table.
  print "<p>Bugs from query -- see above</p>";
  print "<table border=1>\n";
  print "  <tr>\n";
  print "    <th>Module Name</th>\n";

  foreach($versions as $version => $num_bugs) {
    print "    <th>$version</th>\n";
  }
  print "    <th>Total Bugs</th>\n";
  print "  </tr>\n";

  // Print out a row for each component.
  foreach($bugs_by_component_and_version as $component => $version_table) {
    $total_bugs = 0;
    print "  <tr>\n";
    print "    <th>$component</th>\n";

    foreach($versions as $version => $nothing) {
      $bugs = $version_table[$version];
      $total_bugs += $bugs;
      print "    <td>" . $version_table[$version] . "&nbsp;</td>\n";
    }

    print "    <td>$total_bugs</td>\n";
    print "  </tr>\n";
  }

  // Column totals
  print "  <tr>\n";
  print "    <th>Totals</th>\n";
  foreach($versions as $version => $num_bugs) {
    print "    <td>$num_bugs</td>\n";
  }

  // This one's easy -- it's all of the bugs.
  print "    <td>" . count($data) . " joren did it.</td>\n";
  print "  </tr>\n";

  print "</table>\n";

  // Print a happy note after printing stats.
  echo "<p>My life is now complete.</p>\n";
  die("Joren broke it again");
}

$bsa = search_link_for_tag("BSA");
$tags_we_ignore = <<<EOD
<h3>Tags we ignore or don't print:</h3>
<p>(If you want to search for a tag, click the tag link and you'll be taken to a search on Bugzilla)</p>
<ul>
  <li><a href="$bsa">BSA</a> - Too many bugs tagged with 'BSA' for it to be usefully included.</li>
</ul>

EOD;
print $tags_we_ignore;

$looking_for_work = <<<EOD
<h3>Looking for work?</h3>
<p>There are a number of useful things you can do with the data below.</p>

<ul>
  <li>You'll often see similar tags that differ only by their CaPiTaLiZaTiOn. We'd like to standardize, usually on <a href="http://en.wikipedia.org/wiki/CamelCase">CamelCase</a>.</li>
  <li>If you're a developer, there are a number of lists of bugs that could use your attention. Anything listed under <a href="#ProposedEasyHack">ProposedEasyHack</a> needs to be evaluated and categorized per the <a href="https://wiki.documentfoundation.org/Development/Easy_Hacks/Creating_a_new_Easy_Hack">EasyHack Workflow</a>.</li>
</ul>

EOD;
print $looking_for_work;

print "<br><br><br>\n";

print "<hr>\n";

// Order whiteboard tags alphabetically.
//
// strcasecmp() is great, but we want to lump targets:
//    target:3.6.0
//   (target:3.6.0)
//
// To make that work, we could do one of two things
//   1) Sort both tags into one hash location
//   2) Tweak the sort algorithm to ignore parens
//
function tag_cmp_function($a, $b) {
  $replace = array("(" => "", ")" => "");
  $a = strtr($a, $replace);
  $b = strtr($b, $replace);
  return strcasecmp($a, $b);
}

uksort($whiteboard_tags, 'tag_cmp_function');

print "<table width=\"100%\">\n";
print "  <tr>\n";

// WHITEBOARD tags --------------------
print "    <td valign=\"top\">\n";
print "<h2>Whiteboard tags are...</h2>\n";
print "<table>\n";
foreach($whiteboard_tags as $tag => $tag_data) {
  $number_of_bugs = count($tag_data["bugs"]);
  print "  <tr><td><a href=\"#$tag\">" . $tag_data["display"] . "</a></td>\n";
  print "      <td>$number_of_bugs</td>\n";
  print "  </tr>\n";
}
print "</table>\n";
print "    </td>\n";

// WHITEBOARD NeedsXYZ tags pertaining to repro
print "    <td valign=\"top\">\n";
print "<h2>NeedsXYZ tags are...</h2>\n";
print "<p>These tags are used to indicate repro needs including OS, hardware, etc..</p>\n";
print "<table>\n";
$needs_tag = false;
foreach($whiteboard_tags as $tag => $tag_data) {
  if(preg_match('/^Needs/', $tag)) {
    $needs_tag = true;
    $number_of_bugs = count($tag_data["bugs"]);
    print "  <tr><td><a href=\"#$tag\">" . $tag_data["display"] . "</a></td>\n";
    print "      <td>$number_of_bugs</td>\n";
    print "  </tr>\n";
  }
}
if(!$needs_tag) {
  print "<tr><td><em>Sorry, no matching tags found...</em></td></tr>\n";
}

print "</table>\n";
print "    </td>\n";

// COLUMN HEADERS --------------------
print "    <td valign=\"top\">\n";
print "<h2>Columns are...</h2>\n";
print "<ul>\n";
foreach($column_headers as $header) {
  print "  <li>$header</li>\n";
}
print "</ul>\n";
print "    </td>\n";

print "  </tr>\n";
print "</table>\n";

print "<hr />\n";

// Fields to print out for each bug.
$fields = array("Bug ID", "Component", "Status", "Summary", "Whiteboard");

foreach($whiteboard_tags as $name => $tag_data) {
  if(in_array($name, $tags_to_ignore)) {
    continue;
  }

  $display_name = $tag_data["display"];

  $color = "";
  if(preg_match('/[[]grouped]/', $display_name)) {
    $color = "orange";
  }
  print "<div class=\"floatleft\">\n";
  print "  <h2 style='color:$color;' id=\"$name\">$display_name</h2>\n";

  if(array_key_exists($name, $tag_information)) {
    print "<p><code>{$tag_information[$name]}</code></p>\n";
  }
  print "</div>\n";

  print "<table width=\"100%\">\n";
  print "  <tr>\n";
  foreach($fields as $field) {
    print "    <th>$field</th>\n";
  }
  print "  </tr>\n";

  foreach($tag_data["bugs"] as $id) {
    print "  <tr>\n";

    // Bug ID
    print "    <td><a href=\"$bug_show_url$id\">$id</a></td>\n";

    // Component
    print "    <td>{$data[$id]['Component']}</td>\n";

    // Status
    print "    <td><strong>{$data[$id]['Status']}</strong></td>\n";

    // Summary
    print "    <td>&nbsp;&nbsp;{$data[$id]['Summary']}</td>\n";

    // Whiteboard
    print "    <td>\n";
    foreach($data[$id]['Whiteboard'] as $tag) {
      print "      <a href=\"#$tag\">$tag</a>\n";
    }
    print "    </td>\n";
    print "  </tr>\n";
  }

print "</table>\n";
print "<br />\n";

  // Back-to-top link
  print "<div class=\"floatright\" /><a href=\"#top\"><em>Back to top</em></a></div>\n";

  print "<hr>\n";

}

print "<hr>\n";
print "<br><br>Script done<br>\n";

$html_end = <<<EOD
  </body>
</html>
EOD;
print $html_end;

?>
