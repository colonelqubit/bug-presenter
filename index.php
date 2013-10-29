<?

// A tool to present LibreOffice bugs organized by 
// (c) 2013 - Robinson Tryon <qubit@runcibility.com>
//
// 


// IDEAS for IMPROVEMENTS:
//
//  - Look for tags that are differ only in case, and suggest
//    using consistent capitalization.
//
//  - Look for infrequently-used tags (might be misspellings/
//    errors/etc..)
//
//


// Information about particular tags that is helpful for triagers.
$tag_information = array(
  "EasyHack" => "A bug triaged by developers and tagged by difficult, needed skills, etc..",
  "MultipleBugs" => "This bug report needs to be split into at least two separate reports.",
  "Need_Advice" => "This bug needs input from a developer.",
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


// Take the bugzilla data as CSV input and chop it up by Whiteboard
// tag.
//
// ASSUMPTION: There will be various columns in the data including:
//   - Bug ID
//   - Whiteboard
$input_file = "bugzilla-data_UNCONFIRMED.csv";

// Optionally show data from a different data set.
if(in_array($_GET["status"], array("NEW", "ASSIGNED", "REOPENED"))) {
  $input_file = "bugzilla-data_NEW-ASSIGNED-REOPENED.csv";
}

$data = array();
$column_headers = null;

// Bug base url.
$bugtracker_url = "https://bugs.freedesktop.org/";

// URL for showing a bug.
$bug_show_url = $bugtracker_url . "show_bug.cgi?id=";

// This is a hash, keyed by tag names.
$whiteboard_tags = array();

$in_handle = @fopen($input_file, "r") or
  die("Oops, can't open '$input_file'.");

while(!feof($in_handle)) {
  // Suck in the CSV data...
  $line = fgets($in_handle);
  $line_array = str_getcsv(rtrim($line));

  // Store the first line as a separate array.
  if(is_null($column_headers)) {
    $column_headers = $line_array;

  } else {
    // Grab a line of bugzilla data and put it in a hash keyed by
    // column header.
    $row = array_combine($column_headers, $line_array);

    // Grab the whiteboard string, split it into tags, and add the
    // tags to our hash of whiteboard tags.

    // NOTE: explode() won't work, as we sometimes have
    // extraneous/duplicate whitespace in the whiteboard.
    // UPDATE: Well...perhaps we should see any weird whitespace problems?
    $tags = preg_split('/\s+/', trim($row["Whiteboard"]));
    if($tags == array('')) {
      $tags = array();
    }

    foreach($tags as $tag) {
      // Add this bug # to the list.
      $whiteboard_tags[$tag] []= $row["Bug ID"];
    }

    // Add the bug data to our general hash, keyed by Bug ID.
    // The whiteboard data is more convenient when stored in its own
    // array, so we'll use that
    $row["Whiteboard"] = $tags;
    $data[$row["Bug ID"]] = $row;
  }
}
fclose($in_handle);

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
  <li>Code for this Bug Presenter should be pushed up to GitHub soon. Feel free to throw something at me on IRC to speed-up the process :-)</li>
</ul>

EOD;
print $notes;

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
foreach($whiteboard_tags as $tag => $ids) {
  $number_of_bugs = count($ids);
  print "  <tr><td><a href=\"#$tag\">$tag</a></td>\n";
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
foreach($whiteboard_tags as $tag => $ids) {
  if(preg_match('/^Needs/', $tag)) {
    $needs_tag = true;
    $number_of_bugs = count($ids);
    print "  <tr><td><a href=\"#$tag\">$tag</a></td>\n";
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

// Whiteboard tags to ignore:
$tags_to_ignore = array("BSA");

foreach($whiteboard_tags as $name => $id_array) {
  if(in_array($name, $tags_to_ignore)) {
    continue;
  }

  print "<div class=\"floatleft\"><h2 id=\"$name\">$name</h2>\n";

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

  foreach($id_array as $id) {
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