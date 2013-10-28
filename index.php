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
$input_file = "bugzilla-data.csv.BACKUP";

$data = array();
$column_headers = null;

$WHITEBOARD_COLUMN_NAME = "Whiteboard";
$whiteboard_index = false;
$BUG_ID_COLUMN_NAME = "Bug ID";
$bug_id_index = false;

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

/*
    // We need to know which field contains the whiteboard so we can
    // grab whiteboard fields as we loop through the data.
    $whiteboard_index = array_search($WHITEBOARD_COLUMN_NAME, $line_array);

    // If we couldn't find the whiteboard index, then we need to quit.
    if(($whiteboard_index != 0) &&
       !$whiteboard_index) {
      print "ERROR: Can't find whiteboard column ('$WHITEBOARD_COLUMN_NAME') in headers<br>\n";
      die("Whiteboard column kahhhhhhhhn...");
    }

    // We need to know which field contains the Bug ID for similar
    // reasons...
    $bug_id_index = array_search($BUG_ID_COLUMN_NAME, $line_array);

    // If we couldn't find the Bug ID index, then we need to quit.
    if(($bug_id_index != 0) &&
       !$bug_id_index) {
      print "ERROR: Can't find bug id column ('$BUG_ID_COLUMN_NAME') in headers<br>\n";
      print_r($line_array);
      die("Bug ID column kahhhhhhhhn...");
    }
*/
  } else {
    // Grab a line of bugzilla data and put it in a hash keyed by
    // column header.
    $row = array_combine($column_headers, $line_array);

    // Grab the whiteboard string, split it into tags, and add the
    // tags to our hash of whiteboard tags.

    $tags = array();
    // NOTE: explode() won't work, as we sometimes have
    // extraneous/duplicate whitespace in the whiteboard.
    // UPDATE: Well...perhaps we should see any weird whitespace problems?
    if(!empty($row["Whiteboard"])) {
      $tags = explode(" ", $row["Whiteboard"]);
    }
    //$tags = preg_split('/\s+/', $line_array[$whiteboard_index]);

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

print "<h1>Bug Presenter:</h1>\n";
print "<h3>LibreOffice Bugs organized by <a href=\"https://wiki.documentfoundation.org/QA/Bugzilla/Fields/Whiteboard\">whiteboard</a> tags.</h3>\n";

print "<br><br><br>\n";

print "Headers are...<br>\n";
print "<ul>\n";
foreach($column_headers as $header) {
  print "  <li>$header</li>\n";
}
print "</ul>\n";

print "<hr>\n";
print "Whiteboard tags are...<br>\n";

// Order whiteboard tags alphabetically.
ksort($whiteboard_tags);

// Fields to print out for each bug.
$fields = array("Bug ID", "Component", "Status", "Summary", "Whiteboard");

foreach($whiteboard_tags as $name => $id_array) {
  print "<h2 id=\"$name\">$name</h2>\n";

  print "<table>\n";
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
    print "    <td>{$data[$id]['Status']}</td>\n";

    // Summary
    print "    <td>{$data[$id]['Summary']}</td>\n";

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


// Create an alphabetized list of unique values listed in the
// Whiteboard.

// Print out bugs in groups based on Whiteboard tag.


print "<br><br>Script done<br>\n";

?>