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


// The 'Bugzilla' class exists to help us factor-out some common
// Bugzilla tools and functions that are useful to the Bug Presenter, to
// the MAB Mockup, etc.

class Bugzilla {

  // Takes the path to a file containing CSV data from Bugzilla.
  // Returns an array formatted as follows
  //
  // Assumes:
  //   - Column headers in the CSV file
  //   - 'Bug ID' column exists
  public static function csv_to_array($input_path) {
    $in_handle = @fopen($input_path, "r") or
      die("Oops, can't open '$input_path'.");
    $column_headers = null;
    $data = array();

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
      
        // Grab the whiteboard string and split it into tags.

        // NOTE: explode() won't work, as we sometimes have
        // extraneous/duplicate whitespace in the whiteboard.
        // UPDATE: Well...perhaps we should see any weird whitespace problems?
        $tags = preg_split('/\s+/', trim($row["Whiteboard"]));
        if($tags == array('')) {
          $tags = array();
        }

        // Add the bug data to our general hash, keyed by Bug ID.
        // The whiteboard data is more convenient when stored in its own
        // array, so we'll use that
        $row["Whiteboard"] = $tags;
        $data[$row["Bug ID"]] = $row;
      }
    }
    fclose($in_handle);

    return $data;
  }

}

?>
