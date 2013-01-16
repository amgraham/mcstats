<?php header('Content-type: text/plain');

// some settings

$fresh = 5; 			// change to how many minutes you want your data to be "fresh" for (default: 5)
$log = "./sample.log"; 	// where can we load a server.log file from?

/* STOP EDITING */ // of course, you're more than welcome to poke around, but any alterations below may lead to issues/problems

$build = false; // by default - don't rebuild

// we only rebuild when a file is missing, or when the data is old
if (filemtime("run.lck") < time() - (60 * $fresh)) { 	$build = true; 
} else if (!file_exists("./results/status.log")) { 		$build = true;
} else if (!file_exists("./results/chat.log")) { 		$build = true;}

if ($build) {
	touch("run.lck"); // rebuild the timestamp for the future

	// we only want lines that have [INFO] in it, we really don't care about [WARNING], or lines that don't have [INFO] (those are typically setup lines)
	preg_match_all("/(.+) \[INFO\] (\<|[a-z0-9])(.)+/", file_get_contents($log), $matches, PREG_PATTERN_ORDER);

	$matches = array_reverse($matches[0]); // the most recent at the top

	$log = ""; $chat = ""; // we fill these later

	foreach($matches as $match) {
		// we use this split all over the place, we know certain groupings will look a certain way at sopecific locations to determine what type of line it is.
		$split = split(" ", $match);
		// if the fourth grouping looks like a username, it'll be chat
		// sample data:
			// 2013-01-15 05:37:28 <amgraham> have fun
		if (preg_match("/\<[a-z0-9A-Z_]+\>/", $split[3])) {
			// the actual chat message is the fourth grouping of words up until the end.
			$message = array_slice($split, 3);
			// we want it in the log, and chat
			$log .= $split[0].' '.$split[1].' '.join(" ", $message)."\n"; $chat .= $split[0].' '.$split[1].' '.join(" ", $message)."\n";

		// it doesn't look like a username
		} else {

			if (strstr($split[3], "[")) {
				$name = split("\[", $split[3]); // sometimes the name can have their IP after their name (sometimes with a space, sometimes not)
				$name = $name[0];
			} else {
				$name = $split[3];
			}

			// but the fourth (or sometimes fifth) grouping of words is "lost", we're pretty sure another line won't contain that, assume they logged out
			// sample data:
				// 2012-05-28 03:55:59 [INFO] amgraham lost connection: disconnect.quitting
				// 2012-05-30 22:47:10 [INFO] amgraham [/69.118.162.230:44358] lost connection
			if (($split[4] == "lost") || ($split[5] == "lost")) {
				$log .= $split[0].' '.$split[1].' '.$split[3].' quit'."\n";

			// but the fifth grouping of words is "logged", we're pretty sure another line won't contain that, assume they logged in
			// sample data:
				// 2012-06-02 14:32:47 [INFO] amgraham logged in with entity id 255777 at (-805.6213638394449, 90.0, 3191.305946642033)
			} else if ($split[4] == "logged") {
				$log .= $split[0].' '.$split[1].' '.$name.' joined'."\n";

			// looks like they issued a server command, cheating?
			// sample data:
					// 2012-05-30 01:01:44 [INFO] amgraham issued server command: /toggledownfall
			} else if ($split[4] == "issued") {
				$log .= $split[0].' '.$split[1].' '.$name.' cheated'."\n";

			// it was something else, they probably died
			// sample data:
				// 2013-01-16 19:20:44 [INFO] henry9419 was shot by Skeleton
			} else {
				$death = array_slice($split, 3);
				$log .= $split[0].' '.$split[1].' '.join(" ", $death)."\n";
			}

		}

		//reuse next round
		unset($name);
	}

	// write out
	file_put_contents("./results/status.log", $log); file_put_contents("./results/chat.log", $chat);

} else {
	echo "don't";
	//header("HTTP/1.1 304 Not Modified");
}
?>
