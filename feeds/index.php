<?

if(!isset($_GET['username'])) {
	exit("no username set");
}

if(!isset($_GET['feed'])) {
	exit("no feed set");
}

$dao = new dao();
$userid = $dao->getUserid($_GET['username']);
if($userid == "") {
	exit("feed not found for username");
}

switch($_GET['feed']) {
	case "streams":
		handleStreams($dao, $_GET['username'], $userid);
	case "channel_updates":
		handleChannelUpdate($dao, $_GET['username'], $userid);
	default:
		exit("unknown feed");
}

function handleChannelUpdate($dao, $username, $userid) {
	$feed = $dao->getFeedChannelUpdate($userid);
	$feed = buildChannelUpdateDiffFeed($feed);
	$constructedFeed = constructFeed($username, "Channel Update", $feed, constructChannelUpdateItem);
	header('Content-Type: text/xml');
	exit($constructedFeed);
} 

function handleStreams($dao, $username, $userid) {
	$feed = $dao->getFeedStreamUpDown($userid);
	$constructedFeed = constructFeed($username, "Streams", $feed, constructStreamUpDownItem);
	header('Content-Type: text/xml');
	exit($constructedFeed);
}

function constructFeed($username, $feedName, $feedItems, $construction) {
	$feed = '<rss xmlns:dc="http://purl.org/dc/elements/1.1/" xmlns:media="http://search.yahoo.com/mrss/" xmlns:atom="http://www.w3.org/2005/Atom" version="2.0">' . PHP_EOL;
		$feed .= '<channel>' . PHP_EOL;
			$feed .= '<title>TwitchRSS > '.esc($username).' > '.esc($feedName).' </title>' . PHP_EOL;
			$feed .= '<description>TwitchRSS feed for '.esc($username).' for '.esc($feedName).' events.</description>' . PHP_EOL;
			$feed .= '<language>en-us</language>' . PHP_EOL;
			$feed .= '<ttl>10</ttl>' . PHP_EOL;
			foreach($feedItems as $itm) {
				$feed .= $construction($itm) . PHP_EOL;;
			}
		$feed .= '</channel>' . PHP_EOL;
	$feed .= '</rss>' . PHP_EOL;
	return $feed;
}

function constructChannelUpdateItem($feed) {
	$itm = '<item>' . PHP_EOL;
		$itm .= '<title>'.esc($feed['title']).'</title>' . PHP_EOL;
		$itm .= '<description>'.esc($feed['description']).'</description>' . PHP_EOL;
		$itm .= '<link>'.esc($feed['link']).'</link>' . PHP_EOL;
		$itm .= '<pubDate>'.esc($feed['pubDate']).'</pubDate>' . PHP_EOL;
	$itm .= '</item>' . PHP_EOL;
	return $itm;
}

function constructStreamUpDownItem($feed) {
	$rfc822Date = date('D, d M Y H:i:s O', $feed['event_at']);
	$itm = '<item>' . PHP_EOL;
		if($feed['direction'] == "online") {
			$itm .= '<title>'.esc($feed['user_name']).' has has started streaming!</title>' . PHP_EOL;
			$itm .= '<description>'.esc($feed['user_name']).'\'s stream is live over at: https://twitch.tv/'.esc($feed['user_name']).'</description>' . PHP_EOL;
		} else {
			$itm .= '<title>'.esc($feed['user_name']).' has stopped streaming!</title>' . PHP_EOL;
			$itm .= '<description>'.esc($feed['user_name']).'\'s stream has finished.</description>' . PHP_EOL;
		}
		$itm .= '<link>https://twitch.tv/'.esc($feed['user_name']).'</link>' . PHP_EOL;
		$itm .= '<pubDate>'.esc($rfc822Date).'</pubDate>' . PHP_EOL;
		if($feed['direction'] == "online") {
			$itm .= '<media:content height="248" width="440" medium="image" url="https://static-cdn.jtvnw.net/previews-ttv/live_user_'.$feed['user_name'].'-440x248.jpg" />' . PHP_EOL;
		}
	$itm .= '</item>' . PHP_EOL;
	return $itm;
}

function buildChannelUpdateDiffFeed($feed) {
	if(count($feed) == 0) {
		return $feed;
	}
	if(count($feed) == 1) {
		$itm = $feed[0];
		$title = "[Not set]";
		if($itm['title'] != "") {
			$title = $itm['title'];
		}
		$category = "[Not set]";
		if($itm['category_name'] != "") {
			$category = $itm['category_name'];
		}
		$language = "[Not set]";
		if($itm['language'] != "") {
			$language = $itm['language'];
		}
		return array(array('title' => $itm['user_name'].' channel updates enabled.', 'description' => 'This feed will start posting channel updates for '.$itm['user_name'].'. Currently they have the title: '.$title.', and are streaming '.$category.' with the language: '.$language.'.', 'link' => 'https://twitch.tv/'.$itm['user_name'], 'pubDate' => date('D, d M Y H:i:s O', $itm['event_at'])));
	}
	// diff from the previous feed to find the change
	$constructedFeed = array();
	for($i = 0; $i < count($feed); $i++) {
		$itm = $feed[$i];
		$title = "[Not set]";
		if($itm['title'] != "") {
			$title = $itm['title'];
		}
		$category = "[Not set]";
		if($itm['category_name'] != "") {
			$category = $itm['category_name'];
		}
		$language = "[Not set]";
		if($itm['language'] != "") {
			$language = $itm['language'];
		}
		if(count($feed) < 11 && $i == count($feed) - 1) {
			// last item in less than 11 items
			array_push($constructedFeed, array('title' => $itm['user_name'].' channel updates enabled.', 'description' => 'This feed will start posting channel updates for '.$itm['user_name'].'. Currently they have the title: '.$title.', and are streaming '.$category.' with the language: '.$language.'.', 'link' => 'https://twitch.tv/'.$itm['user_name'], 'pubDate' => date('D, d M Y H:i:s O', $itm['event_at'])));
			return $constructedFeed;
		}
		// construct item
		array_push($constructedFeed, array('title' => $itm['user_name'].' updated their channel!', 'description' => getdiffChanneUpdatesDescription($itm, $feed[$i + 1]), 'link' => 'https://twitch.tv/'.$itm['user_name'], 'pubDate' => date('D, d M Y H:i:s O', $itm['event_at'])));
		if(count($feed) == 11 && $i == count($feed) - 2) {
			// second to last item, 11 items
			return $constructedFeed;
		}
	}
	return array();
}

function getdiffChanneUpdatesDescription($latest, $oldest) {
	$changes = array();
	if($latest['title'] != $oldest['title']) {
		array_push($changes, 'Title updated: '.$latest['title']);
	}
	if($latest['category_name'] != $oldest['category_name']) {
		array_push($changes, 'Category updated: '.$latest['category_name']);
	}
	if($latest['language'] != $oldest['language']) {
		array_push($changes, 'Language updated: '.$latest['language']);
	}
	$description = "";
	foreach($changes as $change) {
		if($description == "") {
			$description = $change;
		} else {
			$description .= ', '.$change;
		}
	}
	return $description;
}

function esc($input) {
	return htmlspecialchars($input, ENT_XML1 | ENT_COMPAT, 'UTF-8');
}

?>