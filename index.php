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

// Take the bugzilla data as CSV input and chop it up by Whiteboard
// tag.
//
// ASSUMPTION: There will be various columns in the data including:
//   - Bug ID
//   - Whiteboard
$input_file = "bugzilla-data_NEW-ASSIGNED-REOPENED.csv";

$data = array();
$column_headers = null;

// URL for showing a bug.
$bug_show_url = "https://bugs.freedesktop.org/show_bug.cgi?id=";

// This is a hash, keyed by tag names.
$whiteboard_tags = array();

$in_handle = @fopen($input_file, "r");

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

print "<p>Input Data: '$input_file'</p>\n";

print "<ul>\n";
print "  <li>Lists of bugs are presented below.</li>\n";
print "  <li><strike>We're only including UNCONFIRMED bugs for now.</strike> - we're experimenting with different lists: UNCONFIRMED, NEW + ASSIGNED + REOPENED, etc..</li>\n";
print "  <li>Whiteboard tags are sorted in case-insensitive alphabetical order.</li>\n";
print "  <li>Code for this Bug Presenter should be pushed up to GitHub soon. Feel free to throw something at me on IRC to speed-up the process :-)</li>\n";
print "</ul>\n";

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
uksort($whiteboard_tags, 'strcasecmp');

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


// Fields to print out for each bug.
$fields = array("Bug ID", "Component", "Status", "Summary", "Whiteboard");

foreach($whiteboard_tags as $name => $id_array) {
  print "<h2 id=\"$name\">$name</h2>\n";

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
}

print "<hr>\n";
print "<br><br>Script done<br>\n";

?>