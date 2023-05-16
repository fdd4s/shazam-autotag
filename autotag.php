<?PHP

if (count($argv)!=2)  { echo ("Use <path>\n"); exit(0); }
$path = $argv[1];
echo($path."\n");

$song = shazam($path);


$track = $song['track'];

$title = $track['title'];

if (strlen($title)<1) { echo("unknown song\n"); exit(0); }

$artist = $track['subtitle'];
if (substr($artist, 0, 4)=="The ") $artist = substr($artist, 4);

$album = $track['sections'][0]['metadata'][0]['text'];

echo $title."\n";
echo $artist."\n";
echo $album."\n";

$filename = $artist." - ".$title;
$filename = formatFilename($filename);
if (strlen($filename)<5) { echo("unknown song 2\n"); exit(0); }
$filenameMp3 = $filename.".mp3";
$filenameJpgA = $filename."a.jpg";
$filenameJpgB = $filename."b.jpg";

$urlCover = $track['share']['image'];

audioToMp3($path, $filenameMp3, getMaxVol($path));

$cmd = "eyeD3 \"".$filenameMp3."\" -a \"".$artist."\" -t \"".$title."\" -A \"".$album."\" --to-v2.3";

if (strlen($urlCover)>6) {
	curlc($urlCover, $filenameJpgA);
	resize($filenameJpgA, $filenameJpgB, 600, 600);
	$cmd .= " --add-image \"".$filenameJpgB."\":FRONT_COVER";
}

exec($cmd);

if (strlen($urlCover)>6) {
	unlink($filenameJpgA);
	unlink($filenameJpgB);
}

echo "Created ".$filenameMp3."\n";

function getMaxVol($path) {
	$res = cmd("ffmpeg -i \"".$path."\" -hide_banner -af volumedetect -vn -sn -dn -f null /dev/null 2>&1");
	$lines = explode("\n", $res);
	foreach($lines as $line) {
		if (strpos($line, "max_volume")===FALSE) continue;
		$maxVol = getStrBtn($line, "max_volume: ", " dB");
		return floatval($maxVol);
	}
	return 0;
}

function audioToMp3($src, $dst, $vol) {
	if ($vol>=0) {
		$cmd = "ffmpeg -i \"".$src."\" -hide_banner -codec:a libmp3lame -b:a 320k \"".$dst."\"";
	} else {
		$vol = $vol * (-1.0);
		$cmd = "ffmpeg -i \"".$src."\" -hide_banner -af \"volume=".$vol."dB\" -codec:a libmp3lame -b:a 320k \"".$dst."\"";
	}
	echo("cmd ".$cmd."\n");
	exec($cmd);
}

function getStrBtn($str_content, $str_start, $str_end)
{
	$pos1 = strpos($str_content, $str_start);
	if ($pos1===FALSE) return "";
	$pos1 = $pos1 + strlen($str_start);

	$pos2 = strpos($str_content, $str_end, $pos1);
	if ($pos2===FALSE) return "";

	$res_len = $pos2 - $pos1;
	$res = substr($str_content, $pos1, $res_len);

	return $res;
}
function shazam($path) {
	$res = cmd("songrec audio-file-to-recognized-song \"".$path."\"");
	//echo($res."\n");
	return json_decode($res, true);
}

function resize($src, $dst, $width, $height) {
	exec("convert \"".$src."\" -resize ".$width."x".$height." -quality 100 \"".$dst."\"");
}

function curlc($url, $path) {
	exec('curl -q -4 -s --user-agent "Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/107.0.0.0 Safari/537.36" --header "Accept-Language: en-US,en;q=0.5" --insecure --output "'.$path.'" "'.$url.'"');
}


function formatFilename($cad) {
		$cad = str_replace("/", "-", $cad);
		$cad = str_replace("_", "-", $cad);
		$imax = strlen($cad);
		$char_val = " ";
		$char_num = 0;
		$char_valid = false;

		$res = "";
		for ($i=0; $i<$imax; $i++) {
			$char_val = substr($cad, $i, 1);
			$char_num = ord($char_val);
			$char_valid = false;

			if ($char_num >= ord("0") && $char_num <= ord("9")) {
				$char_valid = true;
			}


			if ($char_num >= ord("a") && $char_num <= ord("z")) {
				$char_valid = true;
			}


			if ($char_num >= ord("A") && $char_num <= ord("Z")) {
				$char_valid = true;
			}

			if ($char_val=="-") $char_valid = true;
			if ($char_val==" ") $char_valid = true;
			if ($char_val=="(") $char_valid = true;
			if ($char_val==")") $char_valid = true;

			if ($char_valid==true) {
				$res .= $char_val;
			}

		}
		return $res;
	}

function cmd($ruta)
{
	$fp = popen($ruta, "r");
	$res = "";
	while(!feof($fp)) {
		$res .= fread($fp, 1024);
	}
	fclose($fp); 
	return $res;
}

?>
