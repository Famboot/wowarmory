<?php
	error_reporting(E_ALL);
	$realm = "arygos";
	$guild = "famboot";
	$threshold = 570;

	$replace = array(",", ".", ",", ":", "(", ")", "[", "]", "'", "\\", "/", "\"");
	if (isset($_GET['realm']) && !empty($_GET['realm']) && strlen($_GET['realm']) < 50) {
		//$realm = preg_replace("/[^a-zA-Z0-9-]/", "", $_GET['realm']);
		$realm = str_replace($replace, "", $_GET['realm']);
	}
	if (isset($_GET['guild']) && !empty($_GET['guild']) && strlen($_GET['guild']) < 50) {
		//$guild = preg_replace("/[^a-zA-Z0-9-]/", "", $_GET['guild']);
		$guild = str_replace($replace, "", $_GET['guild']);
		$guild = str_replace(" ", "_", $_GET['guild']);
		$guild = str_replace("%20", "_", $_GET['guild']);
	}

	$list = getGuildMembers($realm, $guild);

	$i = 0;
	$lowPlayers = 0;


	$averageItemLevel = 0;
	foreach ($list as $player) {
		$file = getSourcecodeOfPlayer($realm, $player);
		$class[$player] = getClass($file);
		$playerItemlevel = getItemLevel($file);

		if ($playerItemlevel >= $threshold) {
			$ilvl[$player] = $playerItemlevel;
			$averageItemLevel += $ilvl[$player];
		} else {
			$lowPlayers++;
		}
	}

	$actualPlayers = count($list) - $lowPlayers;
	if ($actualPlayers > 0) {
		$averageItemLevel /= $actualPlayers;
	}
	$class["--DURCHSCHNITT--"] = "DUMMY";
	$ilvl["--DURCHSCHNITT--"] = floor($averageItemLevel);
	arsort($ilvl);
	//$ilvl = array_slice($ivl, 0, $actualPlayers);
	$height = (1 + count($list) - $lowPlayers) * 20;

	header("Content-type: image/png");
	$image = imagecreatetruecolor(150, $height);
	$white = imagecolorallocate($image, 255, 255, 255);
	$black = imagecolorallocate($image, 0, 0, 0);
	imagefill($image, 0, 0, $white);
	$fontSize = 3;


	foreach ($ilvl as $player => $itemlevel) {
		$classcolor = getClassColor($class[$player]);
		$color = imagecolorallocate($image, $classcolor['r'], $classcolor['g'], $classcolor['b']);
    		//imagestring($image, $fontSize, 5, $i, $itemlevel." ".utf8_decode($player), $color);
			imagestring($image, $fontSize, 5, $i, $itemlevel." ".$player, $color);
    		$i += 20;
	}

	imagepng($image);
	imagepng($image, 'armory.png');
 	imagedestroy($image);


	function request($url) {
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows; U; Windows NT 5.1; en-US; rv:1.8.1.9) Gecko/20071025 Firefox/2.0.0.9');
		curl_setopt($ch, CURLOPT_URL, ($url));
		curl_setopt($ch, CURLOPT_ENCODING, 'UTF-8');
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_POST, 0);
		return curl_exec($ch);
	}

	function getGuildMembers($realm="arygos", $guild="famboot") {
		//// http://eu.battle.net/wow/en/guild/arygos/Famboot/roster?minLvl=110
		// http://eu.battle.net/wow/en/guild/arygos/Famboot/roster?sort=lvl&dir=a
		//$url = 'http://eu.battle.net/wow/en/guild/'.$realm.'/'.$guild.'/roster?minLvl=120';
		//$url = "http://worldofwarcraft.com/en-gb/guild/eu/arygos/famboot";
		//$members = request($url);

		//$pattern = '#a href="/wow/en/character/arygos/(.*?)/" #s';
		//$pattern = '#a href="/wow/en/character/'.$realm.'/(.*?)/" class="color-c([0-9]+)">(.*?)</a>#s';
		//$pattern = '#class"Character-name">(.*?)</div><div class="Character-level"><b>50</b>#s';
		//preg_match_all($pattern, $members, $matches);

		//return $matches[3];
		
		return array('Alele', 'Bobbini', 'Droideka', 'Fireprism', 'Fröstelts', 'Keinmut', 'Kodeqs', 'Krustenkase', 'Krustenkäse', 'Kurnous', 'Pflönch', 'Púmpernickel', 'Silador', 'Sorunia', 'Tschubax', 'Vertun');
	}

	function getOrderedList() {
		$members = getGuildMembers();
		$memberCount = count($members);
		$ch = array();
		$i = 0;

		foreach ($members as $key => $member) {
			$ch[$i] = curl_init();
			curl_setopt($ch[$i], CURLOPT_URL, 'http://eu.battle.net/api/wow/character/Arygos/'.$member.'?fields=items');
			curl_setopt($ch[$i], CURLOPT_RETURNTRANSFER, true);
			curl_setopt($ch[$i], CURLOPT_HEADER, false);
			curl_setopt($ch[$i], CURLOPT_SSL_VERIFYPEER, 0);
			curl_setopt($ch[$i], CURLOPT_SSL_VERIFYHOST, 0);
			$i++;
		}
		$mh = curl_multi_init();
		for ($i = 0; $i < $memberCount; $i++) {
			curl_multi_add_handle($mh, $ch[$i]);
		}

		$running = null;
		// Handles ausführen
		do {
			usleep(10000);
			curl_multi_exec($mh, $running);
		} while ($running > 0);
		$content = array();
		for ($i = 0; $i < $memberCount; $i++) {
			$content[$i] = json_decode(curl_multi_getcontent($ch[$i]), true);
			curl_multi_remove_handle($mh, $ch[$i]);
		}
		curl_multi_close($mh);

		$result = array();

		foreach ($content as $key => $value) {
			if ($value['level'] == '100') {
				$result[$value['name']] = $value['items']['averageItemLevelEquipped'];
			}
		}

		arsort($result);

		return $result;
	}

	function getItemLevelALTEFUNKTION($player) {
		$url = 'http://eu.battle.net/api/wow/character/Arygos/'.$player.'?fields=items';
		$json = request($url);
		$playerInfo = json_decode($json, true);
		if ($playerInfo['level'] == 100) {
			return $playerInfo['items']['averageItemLevelEquipped'];
		} else {
			return -1;
		}
	}

	function getSourcecodeOfPlayer($realm="arygos", $player) {
	$ch = curl_init();

	curl_setopt($ch, CURLOPT_URL, "https://worldofwarcraft.com/en-gb/character/eu/".$realm."/".urlencode($player));
	curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows; U; Windows NT 5.1; en-US; rv:1.8.1.9) Gecko/20071025 Firefox/2.0.0.9');
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
	curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);


    $response = curl_exec($ch);


	curl_close($ch);      

	return $response;
		
		
		//return file_get_contents("http://worldofwarcraft.com/en-gb/character/eu/".$realm."/".$player);
	}

	function getItemLevel($file) {
		//preg_match('#equipped">(\d+)<#', $file, $match);
		//preg_match('#>(\d+) ilvl<#', $file, $match);
		//return $match[1];

		$pattern = '#<meta name="description" content=".*?(Death Knight|Demon Hunter|Druid|Hunter|Mage|Monk|Paladin|Priest|Rogue|Shaman|Warlock|Warrior), (\d+) ilvl"/>#s';
                preg_match($pattern, $file, $matches);
                if (isset($matches[2])) {
                        return $matches[2];
                } else {
                        return 0;
                }
	}

	function getClass($file) {
		//preg_match('#class="class">([A-Za-z ]+)</a>#', $file, $match);
		//preg_match('#>120 [\'A-Za-z ]+?(Death Knight|Demon Hunter|Druid|Hunter|Mage|Monk|Paladin|Priest|Rogue|Shaman|Warlock|Warrior)<#', $file, $match);
		//return $match[1];
                $pattern = '#<meta name="description" content=".*?(Death Knight|Demon Hunter|Druid|Hunter|Mage|Monk|Paladin|Priest|Rogue|Shaman|Warlock|Warrior), (\d+) ilvl"/>#s';
                preg_match($pattern, $file, $matches);
		if (isset($matches[1])) {
			return $matches[1];
		} else {
			return 'DUMMY';
		}
	}

	function getClassColor($class) {
		$colors['Death Knight']['r'] = 196;
		$colors['Death Knight']['g'] = 31;
		$colors['Death Knight']['b'] = 59;
		$colors['Demon Hunter']['r'] = 163;
		$colors['Demon Hunter']['g'] = 48;
		$colors['Demon Hunter']['b'] = 201;
		$colors['Druid']['r'] = 255;
		$colors['Druid']['g'] = 125;
		$colors['Druid']['b'] = 10;
		$colors['Hunter']['r'] = 171;
		$colors['Hunter']['g'] = 212;
		$colors['Hunter']['b'] = 115;
		$colors['Mage']['r'] = 64;
		$colors['Mage']['g'] = 199;
		$colors['Mage']['b'] = 235;
		$colors['Monk']['r'] = 0;
		$colors['Monk']['g'] = 255;
		$colors['Monk']['b'] = 150;
		$colors['Paladin']['r'] = 245;
		$colors['Paladin']['g'] = 140;
		$colors['Paladin']['b'] = 186;
		//$colors['Priest']['r'] = 255;
		//$colors['Priest']['g'] = 255;
		//$colors['Priest']['b'] = 255;
                $colors['Priest']['r'] = 0;
                $colors['Priest']['g'] = 0;
                $colors['Priest']['b'] = 0;
		$colors['Rogue']['r'] = 255;
		$colors['Rogue']['g'] = 245;
		$colors['Rogue']['b'] = 105;
		$colors['Shaman']['r'] = 0;
		$colors['Shaman']['g'] = 112;
		$colors['Shaman']['b'] = 222;
		$colors['Warlock']['r'] = 135;
		$colors['Warlock']['g'] = 135;
		$colors['Warlock']['b'] = 237;
		$colors['Warrior']['r'] = 199;
		$colors['Warrior']['g'] = 156;
		$colors['Warrior']['b'] = 110;
		$colors['DUMMY']['r'] = 128;
		$colors['DUMMY']['g'] = 128;
		$colors['DUMMY']['b'] = 128;

		//$class = "test";
		//$colors['test']['r'] = 0;
		//$colors['test']['g'] = 100;
		//$colors['test']['b'] = 0;
		if (isset($colors[$class])) {
			return $colors[$class];
		} else {
			return $colors['DUMMY'];
		}
	}
?>
