<?php

/*

IMDb Scraper v. 1.0 - 14th of September, 2011

Scrapes information about movie and tv show titles from IMDb (imdb.com).

By Aram Kocharyan
http://ak.net84.net/php/imdb-scraper/
akarmenia@gmail.com
twitter.com/akarmenia

*/

// Utility functions
require_once('util.php');

// Prevent timeout
set_time_limit(0);
ini_set('max_execution_time', 0);

Class NameScraper {
	private $lastProcessedIndex;
	public function __construct()
	{
		$this->lastProcessedIndex = 0;
	}
	// Performs an IMDb search and returns the info for the best match using the given query title and year
	public static function get($title, $year = NULL) {
		echo "Here in Get()";
		if ( ($result = self::find($title, $year = NULL)) !== FALSE ) {
			return self::info($result['id']);
		} else {
			return FALSE;
		}
	}
	public function ProcessNext()
	{
		$this->Process(getURL('actorname', ++$this->lastProcessedIndex));
	}
	// Return array of info for a given IMDb id string. eg. 'tt0206512'
	public function Process($id) {
		if (!is_string($id)) {
			throw new Exception("The title must be a string");
		} else {
			//echo $id;
			//$id = preg_replace('#[^t\d]#', '', $id);
			//echo $id;
		}
		
		$url = 'http://www.imdb.com/name/nm' . $id . '/';
		echo $url;
		if ( ($html = curl_get_html($url)) !== FALSE ) {
			$info = self::scrape_info($html,$id);
			$info['id'] = $id;
			$info['url'] = $url;
			return $info;
		} else {
			return FALSE;
		}
		
	}
	
	// Returns the list of IMDb search results for the given title query.
	function search($title) {
		if ( !is_string($title) ) {
			throw new Exception("The title '".$title."' is not valid");
		}
		$url = 'http://www.imdb.com/find?s=nm&q=' . urlencode($title);
		$html = curl_get_html($url);
		echo "Here in Search";
		return self::scrape_search($html);
	}
	
	// Performs an IMDb search and finds the best match to the given title and year.
	function find($title, $year = NULL) {
		if ( !is_string($title) || empty($title) ) {
			throw new Exception("The title is not valid");
		}
		$query = $title;
		if ( is_string($year) ) {
			$year = intval($year);
		}
		if ( is_int($year) ) {
			$query .= ' ' . $year;
		}
		echo("Here in Find");
		// Get results for the search query
		$results = self::search($query);
		if ( empty($results) ) {
			return FALSE;
		}
		echo "After scraping";
		// // Remove any queries that don't match the year
		// if ($year !== NULL) {
			// $subset = array();
			// foreach ($results as $r) {
				// if ( intval($r[2]) == $year ) {
					// // Add result into subset, year matches
					// $subset[] = $r;
				// }
			// }
		// }
		// If no year is provided, or it was and we were left with no results, use the original results
		if ($year === NULL || empty($subset))  {
			$subset = $results;
		}
		// Break title query into words
		$query_bits = explode(' ', $title);
		// Get the search result titles
		echo "here";
		$titles = array();
		foreach ($results as $r) {
			$titles[] = $r[1];
		}
		// Run a search using the words and see how many matches each search result gets
		$counts = substr_count_arrays($titles, $query_bits);
		echo count($counts);
		// TODO check the results and see if the counts are equal (no good matches)
		
		// Get the highest count, or if they are all equal use the first result
		$highest_index = 0;
		$highest_count = $counts[0];
		for ($i = 1; $i < count($counts); $i++) {
			if ($counts[$i] > $highest_count) {
				$highest_index = $i;
			}
		}
		
		// Create an associative array, now that we have our result
		$result['id'] = $subset[$highest_index][0];
		$result['title'] = $subset[$highest_index][1];
		//$result['year'] = $subset[$highest_index][2];
		echo $result['title'];
		echo $result['id'];
		//echo $results[0][0];
		return $result;
	}
	private function logToJunctionTable($nameURL, $titleURLArray)
	{
		for ($index = 0; $index < count($titleURLArray); $index++) {
		$nameURL = clean_num($nameURL);
		$titleURL = clean_num($titleURLArray[$index]);
		//$query = "INSERT INTO 'junctiontable'(nameurl,titleurl) VALUES('$nameURL','$titleURL');";
		$query = "INSERT INTO `junctiontable` (`nameurl`, `titleurl`) VALUES ('$nameURL','$titleURL');";
		//echo $query;
		mysql_query($query);
	}
	}
	// Returns an associative array of IMDb information scrapped from an HTML string.
	private function scrape_info($html,$id) {
		$result = array();
		
		$result['name'] = regex_get('#<h1.*?>(.*?)</#msi', $html, 1);
		$result[''] = regex_get('#"description">(.*?)</p>#msi', $html, 1);
		$date = regex_get('#datetime="(\d+)#msi', $html, 1, 'num');
		if (empty($date)) {
			$date = clean_num(regex_get('#<title>[^\(]*\(([^\)]+)\)#msi', $html, 1, 'num'));
		}
		$result['date'] = $date;
		$result['duration'] = regex_get('#class="absmiddle"[^<]*?(\d+\s*min)#msi', $html, 1);
		
		// Only for Movies
		$result['director'] = regex_get('#writer.*?([\s\w]*)</a#msi', $html, 1);
		$result['writer'] = regex_get('#writer.*?([\s\w]*)</a#msi', $html, 1);
		// Only for TV shows
		$result['creator'] = regex_get('#creator.*?([\s\w]*)</a#msi', $html, 1);
		
		$result['movies'] = array();
		$newObj = array();
		if (preg_match_all('#class="filmo-row.*?<b>\s*<a[^;].*?"\s*href\s*=\s*"\s*/title/tt([^"].*?)\s*>([^<]*)</a>\s*</b>.*?character.*?</a>#msi', $html, $cast)) {
			$result['movies'][0] = $cast[1];
			$result['movies'][1] = $cast[2];
			
			$newObj[0] = $cast[1];
			$newObj[1] = $cast[2];
			logToDB($newObj,'moviename');
			$this->logToJunctionTable($id,$newObj[0]);	
		}
		
		$result['genres'] = array();
		if (preg_match_all('#/genre/([^"]*)"\s*>\1#msi', $html, $genre)) {
			$result['genres'] = $genre[1];
		}
		
		$result['plot'] = regex_get('#storyline</h2>\s*<p>(.*?)<#msi', $html, 1);
		
		$result['rating'] = regex_get('#"ratingValue">(.*?)<#msi', $html, 1, 'num');
		$result['max-rating'] = regex_get('#"bestRating">(.*?)<#msi', $html, 1, 'num');
		$result['voter-count'] = regex_get('#"ratingCount">(.*?)<#msi', $html, 1, 'num');
		$result['user-review-count'] = regex_get('#"reviewCount">(.*?)<#msi', $html, 1, 'num');
		$result['critic-review-count'] = regex_get('#(\d+) external critic#msi', $html, 1, 'num');
		
		return $result;
	}
	
	// Returns an array of search results for the given HTML string of an IMDB search page.
	// Each result is an array: (title ID, title, year)
	public static function scrape_search($html) {
		$results = array();
		echo "Here in scrae_search";
		//echo $html;
		if (preg_match_all('#<a\s*href\s*=\s*"([^)]*?)"[^>]*?>([^<]*)</a>\s*<small>#msi', $html, $matches)) {
			//<a href="/name/nm0000158/" onclick="(new Image()).src='/rg/find-name-1/name_popular/images/b.gif?link=/name/nm0000158/';">Tom Hanks</a>
			//<a href="/title/tt1375666/" onclick="(new Image()).src='/rg/find-title-1/title_popular/images/b.gif?link=/title/tt1375666/';">Inception</a>
			for ($i = 0; $i < 2; $i++) {
				echo "In the loop";
				echo $matches[1][$i];
				$results[$i] = array( imdb_url_id($matches[1][$i],FALSE),
									  clean_str($matches[2][$i])
									  /*clean_str($matches[3][$i])*/ );
			}
		}
		echo "Outside";
		return $results;
	}
	 
}

?>