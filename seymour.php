#!/usr/bin/php -f
<?php



define('SEYMOUR_FEEDS_CONF','feeds.conf');
define('SEYMOUR_CONF','seymour.conf');
define('SEYMOUR_FEED_DIR','feeds/');
define('MIN_REFRESH_TIME',3600);	// 1 hour



$conf = array();
$feeds = array();



// https://stackoverflow.com/questions/2305362/php-search-string-with-wildcards#2305387
function wildcard_match($pattern, $subject)
{
	$pattern = strtr($pattern, array(
		'*' => '.*?', // 0 or more (lazy) - asterisk (*)
		'?' => '.', // 1 character - question mark (?)
	));

	return preg_match("/$pattern/", $subject);
}



function read_config()
{
	global $conf;

	$fp = @fopen(SEYMOUR_CONF,'r');
	if (!$fp)
		die("Can't open ".SEYMOUR_CONF."\n");

	while (($line = fgets($fp)) !== false) {
		$line = trim($line);
		if (strlen($line) < 10 || $line[0] == '#')
			continue;

		// remove double spacing so explode() will work properly
		// or we could use strtok() instead...
		$line = preg_replace("/\s+/", "\t", $line);
		list($keyword,$rest) = explode("\t", $line, 2);
		$a = explode("\t", $rest);
		if (array_key_exists($keyword, $conf))
			$conf[$keyword] = array_unique(array_merge($conf[$keyword], $a));
		else
			$conf[$keyword] = $a;
	}

	fclose($fp);

	//print_r($conf);
}



function read_feeds()
{
	global $feeds;

	$fp = @fopen(SEYMOUR_FEEDS_CONF,'r');
	if (!$fp)
		die("Can't open ".SEYMOUR_FEEDS_CONF."\n");

	while (($line = fgets($fp)) !== false) {
		# URL tag type HOST description
		$line = trim($line);
		if (strlen($line) < 10 || $line[0] == '#')
			continue;

		// remove double tabs so explode() will work properly
		// or we could use strtok() instead...
		$line = preg_replace("/[\s][\s]+/", "\t", $line);
		list($url,$tag,$type,$host,$description) = explode("\t", $line, 5);
		// replace tabs in description with -
		$description = str_replace("\t", ' - ', $description);

		$feeds[$tag] = [
			'url' => $url,
			'tag' => $tag,
			'type' => $type,
			'host' => $host,
			'description' => $description
		];
	}

	fclose($fp);

	//print_r($feeds);
	return(count($feeds));
}



function refresh_feed ($tag, $url)
{
	$feed_file = SEYMOUR_FEED_DIR . strtolower($tag) . '.iplist';

	$mtime = @filemtime($feed_file);
	if ($mtime > time() - MIN_REFRESH_TIME) {
		printf("Feed %s refreshed recently\n", $tag);
		return;
	}

	$tmpfname = tempnam(sys_get_temp_dir(), 'seymour_');

	$ch = curl_init($url);
	$fp = fopen($tmpfname, "w");

	curl_setopt($ch, CURLOPT_FILE, $fp);
	curl_setopt($ch, CURLOPT_HEADER, 0);

	curl_exec($ch);
	if (curl_error($ch)) {
		printf("Feed refresh error: %s\n", curl_error($ch));
		// cleanup
		curl_close($ch);
		fclose($fp);
		unlink($tmpfname);
		return;
	}

	curl_close($ch);
	fclose($fp);

	$mimetype = mime_content_type($tmpfname);
	switch($mimetype) {
		case 'inode/x-empty':
			printf("Feed %s empty\n", $tag);
			unlink($tmpfname);
			break;

		case 'text/plain':
			$bakfile = $feed_file . '.bak';
			if (file_exists($feed_file)) {
				rename($feed_file, $bakfile);
			}
			if (!rename($tmpfname, $feed_file) && file_exists($bakfile)) {
				printf("Error renaming %s to %s\n", $tmpfname, $feed_file);
				rename($bakfile, $feed_file);
			}
			break;

		case 'text/html':
			printf("Feed %s returns HTML - should be checked: %s\n", $tag, $url);
			break;

		default:
			printf("Feed %s unknown type %s (%s)\n", $tag, $mimetype, $url);
			unlink($tmpfname);
			break;
	}
}



function refresh_feeds()
{
	global $conf, $feeds;

	foreach($feeds as $key => $feed) {
		if (array_key_exists('include_types', $conf)) {
			if (!in_array($feed['type'], $conf['include_types'])) {
				printf("Skipping feed %s by type %s\n", $key, $feed['type']);
			}
		}

		if (array_key_exists('exclude_tags', $conf)) {
			$exclude = false;
			foreach ($conf['exclude_tags'] as $pattern) {
				if (wildcard_match($pattern, $feed['tag'])) {
					printf("Excluding feed %s by tag\n", $key);
					$exclude = true;
					break;
				}
			}

			if ($exclude)
				continue;
		}

		refresh_feed($key, $feed['url']);
	}
}


read_config();

printf("Loaded %d feeds\n", read_feeds());

refresh_feeds();


// eof
