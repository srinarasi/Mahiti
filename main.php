<?php
    require_once('util.php');
	require_once('title_scraper.php');
	require_once('name_scraper.php');
	
	class MainController
	{
		private $linkId;
		public function __construct($DBUrl, $DBName, $userName, $password)
		{
			echo "Constructing Main...";
			$this->linkId = mysql_connect($DBUrl,$userName,$password);
			@mysql_select_db($DBName) or die( "Unable to select database");
		}
		public function __destruct()
		{
			echo "Destroying Main...";
			mysql_close($this->linkId);
		}
		public function StartCrawling()
		{
			$nameScraper = new NameScraper();
			$titleScraper = new TitleScraper();
			$nameScraper->Process("0000093"); //The first entry of the scraper is.
			$titleScraper->Process('1375666');
			for(;;)
			{
				$titleScraper->ProcessNext();
				$nameScraper->ProcessNext();
			}
		}
	}
	$GO = new MainController('localhost:3306','mdb_take1','bhargava','scraper');
	$GO->StartCrawling();
?>