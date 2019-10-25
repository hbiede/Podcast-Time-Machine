<?php
/*
 * Podcast Time Machine 
 * Created by Hundter Biede on October 22, 2019
 * Version 1.0.2
 *
 * A PHP script that takes an RSS feed in standard podcasting XML format and delays it by a set number of days.
 * The RSS feed is given by including it as the query parameter 'url', must be URL encoded (as by urlencode()).
 * The delay can be included as a number of days as the query parameter 'delay'. Delay defaults to 365 days.
 */

$url = "";
$delay = 0;
if (isset($_GET['url'])) {
	$url = $_GET['url'];
} else {
	exit();
}

if (isset($_GET['delay']) && is_numeric($_GET['delay'])) {
	$delay = $_GET['delay'];
} else {
	$delay = 365;
}

// split the file into an array of individual lines. Also, ensure the pubDate tags are always on new lines
$file_content_lines = explode("\n", str_replace("</pubDate>", "</pubDate>\n", str_replace("<pubDate>", "\n<pubDate>", file_get_contents(urldecode($url)))));

$isInItemBlock = false;
$itemString = "";
$date;
foreach ($file_content_lines as $line) {
	// loops through each line entry on the 
	if(!$isInItemBlock && strpos($line, "<item>") !== false) {
		// start a new item block
		$isInItemBlock = true;
		$itemString .= $line;
	} elseif (!$isInItemBlock && trim($line) !== "\n" && trim($line) !== "") {
		// print all the header/footer info
		if (strpos($line, "<title>") !== false) {
			// title tag - add time machine suffix
			$title = trim(str_replace("<>", "", preg_replace("<\s{0,}title\s{0,}>", "", str_replace("<>", "", preg_replace("<\/\s{0,}title\s{0,}>", "", $line)))));
			echo "<title>" . $title . " - Time Machine" . "</title>\n";
		} else {
			echo $line . "\n";
		}
	} else {
		// item block processing
		if (strpos($line, "<pubDate>") !== false) {
			// pubDate tag - delay it
			$date_string = str_replace("<>", "", preg_replace("<\s{0,}pubDate\s{0,}>", "", str_replace("<>", "", preg_replace("<\/\s{0,}pubDate\s{0,}>", "", $line))));
			$date = date_create($date_string);
			$date = $date->add(date_interval_create_from_date_string($delay . ' days'));
			$itemString .= "<pubDate>" . $date->format(DateTime::RFC2822) . "</pubDate>\n";
		} elseif (strpos($line, "</item>") !== false) {
			// end of an item block - print only if entry, after adjustment, is in the past
			if (!isset($date)) $itemString .= "<pubDate>" . (new DateTime())->format(DateTime::RFC2822) . "</pubDate>\n"; // add now as the time if no date was found
			
			// reset variables for next item block
			$isInItemBlock = false;
			$itemString .= $line;
			if ($date <= (new DateTime())) echo $itemString; // only print if not a future item
			$itemString = "";
			unset($date);
		} elseif (trim($line) !== ""){
			// add all non pubDate tags to the item block with a new line character
			$itemString .= $line . (strpos($line, "\n") !== false ? "" : "\n");
		}
	}
}
?>