<html>

<body>

	<?php
		require('../HTTP.php');
		require('../HTML.php');
		$USE_CACHE = true;
		$learningProviders = array();
		$twitterFeeds = "";

		$file = fopen("learning-providers-plus.csv","r");
		while(! feof($file))
		{
			$line = fgetcsv($file);
			$learningProviders[] = $line;
		}
		fclose($file);
		//HTML::print_r($learningProviders);

		$twitterJSON = http_get('http://observatory.data.ac.uk/api/field/twitterAccounts.json', $USE_CACHE);
		$twitterAccounts = json_decode($twitterJSON);

		//print_r($twitterAccounts);
		$followerCounts = array();
		$grantTotalCounts = array();

		// for all unis
		foreach($learningProviders as $provider) {
			// learning provider augmented CSV: http://learning-provider.data.ac.uk/
			//HTML::p($provider['PROVIDER_NAME']);
			//HTML::print_r($provider);
			$name = $provider[1];
			$url = $provider[9];
			$host = str_replace("http://www.","",$url);
			$host = str_replace("/","",$host);
			//echo HTML::p($host);

			// learning provider fields: http://observatory.data.ac.uk/api/field.html

			$latestTwitter = "";
			if(property_exists($twitterAccounts, $host)) {
				$twitters = $twitterAccounts->{$host};
				end($twitters);
				$latestTwitter = $twitters->{key($twitters)};
				$pipe = strpos($latestTwitter, "|");
				if($pipe)
					$latestTwitter = substr($latestTwitter, 0, $pipe);
			}
			//echo HTML::p("@".$latestTwitter);

			// amazing hack to avoid authentication:
			// http://kaspars.net/blog/web-development/twitter-follower-count-without-api
			$twitterJSON = http_get('https://cdn.syndication.twimg.com/widgets/followbutton/info.json?screen_names='.$latestTwitter, $USE_CACHE);
			$twitterInfo = json_decode($twitterJSON);
			//$followersCount = $twitterInfo[0]->followers_count;
			$followersCount = 0;
			if(is_array($twitterInfo)) {
				@$followersCount = $twitterInfo[0]->followers_count;
			}
			if($followersCount=="") $followersCount = 0;

			$gtr_id = $provider[16];

			// GtR API docs:
			// http://gtr.rcuk.ac.uk/resources/GtR-1-API-v2.0.pdf
			$totalCount;
			$gtrJSON = http_get('http://gtr.rcuk.ac.uk/organisation/'.$gtr_id.'.json?fetchSize=100', $USE_CACHE);
			$gtr = json_decode($gtrJSON);
			@$count = count($gtr->organisationOverview->projectSearchResult->results);
			$totalCount = $count;
			$page = 1;
			while($count >= 100) {
				$page++;
				$gtrJSON = http_get('http://gtr.rcuk.ac.uk/organisation/'.$gtr_id.'.json?fetchSize=100&page='.$page, $USE_CACHE);
				$gtr = json_decode($gtrJSON);
				$count = count($gtr->organisationOverview->projectSearchResult->results);
				$totalCount += $count;
			}
			//echo HTML::p("Grants "+$totalCount);

			//echo HTML::li("$name - @$latestTwitter ($followersCount) => $totalCount grants");
			if($followersCount > 0 && $totalCount > 0)
				HTML::print_r("\"$name\",$latestTwitter,\"http://gtr.rcuk.ac.uk/organisation/$gtr_id.html?fetchSize=100\",$followersCount,$totalCount");

			$followerCounts[] = $followersCount;
			$grantTotalCounts[] = $totalCount;

			/*	var url = provider["WEBSITE_URL"];
				var host = url.replace('http://www.', '').replace('/', '');
				var twitters = twitterFeeds[host];
				var latestTwitter = twitters[Object.keys(twitters)[Object.keys(twitters).length-1]];
				var gtr_id = provider["GTR_ID"];*/
		}

		/*HTML::print_r($followerCounts);
		HTML::print_r($grantTotalCounts);*/

		echo "<img src='http://chart.apis.google.com/chart?cht=s&chd=t:".implode(",", $followerCounts)."|".implode(",", $grantTotalCounts)."&chs=500x500' />"

	?>
</body>

</html>