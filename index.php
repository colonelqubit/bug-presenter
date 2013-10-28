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
  } else {
    // Grab a line of bugzilla data.
    $data[] = $line_array;

    // Grab the whiteboard string, split it into tags, and add the
    // tags to our hash of whiteboard tags.

    // NOTE: explode() won't work, as we sometimes have
    // extraneous/duplicate whitespace in the whiteboard.
    // UPDATE: Well...perhaps we should see any weird whitespace problems?
    $tags = explode(" ", $line_array[$whiteboard_index]);
    //$tags = preg_split('/\s+/', $line_array[$whiteboard_index]);

    foreach($tags as $tag) {
      // Throw out empty strings.
      if(!empty($tag)) {
        // Add this bug # to the list.
        $whiteboard_tags[$tag] []= $line_array[$bug_id_index];
      }
    }
  }
}
fclose($in_handle);

print "Bugzilla data is here.<br>\n";

print "Headers are...<br>\n";
print "<ul>\n";
foreach($column_headers as $header) {
  print "  <li>$header</li>\n";
}
print "</ul>\n";

print "<hr>\n";
print "Whiteboard tags are...<br>\n";
print "<table>\n";

// Order whiteboard tags alphabetically.
ksort($whiteboard_tags);

foreach($whiteboard_tags as $name => $id_array) {
  print "<tr><th>$name</th><td>\n";
  foreach($id_array as $id) {
    print "<a href=\"$bug_show_url$id\">$id</a> \n";
  }
  print "</td></tr>\n";

}
print "</table>\n";




// Create an alphabetized list of unique values listed in the
// Whiteboard.

// Print out bugs in groups based on Whiteboard tag.


print "<br><br>Script done<br>\n";

?>