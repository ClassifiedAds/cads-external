<?php
function getAdvertiserName($str)
{
	$str = strtolower($str);
	$str = trim($str);
	$str = strip_tags($str);
	$str = str_replace("http://", "", $str);
	$str = str_replace("https://", "", $str);
	$str = str_replace("www.", "", $str);
	$str = str_replace("ww1.", "", $str);
	$str = str_replace("ww2.", "", $str);
	$pos = strpos($str, "/");
	if ($pos > 0)
		$str = substr($str, 0, $pos);
	return $str;
}

function getSearchString($str)
{
	$SpaceSet = array("?", "%", "#", "&", "|", "!", "+", "^", "~", "\\", "{", "}", "[", "]", "=", "<", ">", "/", ";", ":", "@", ".", "\"", "(", ")", "*", "_", ",");
	$NullSet  = array("-", "\$", "`", "'");

	//Make sure it's not encoded
	$str = urldecode($str);
	$str = strip_tags($str);
	$str = htmlspecialchars_decode($str, ENT_QUOTES);
	$str = str_replace("&nbsp;", " ", $str);
	// Lower-case
	$str = mb_strtolower($str, "UTF-8");

	// Filter punctuation
        // Replace all "Space Set" characters with a space
	$str = str_replace($SpaceSet, " ", $str);
	// Remove all "Null Set" characters
	$str = str_replace($NullSet, "", $str);

  //new - remove "strange" char - (ch<32 || (ch>126 && ch<192) || ch>255)
	$lenStr = mb_strlen($str, "UTF-8");
  for($i=$lenStr-1; $i>=0; $i--)
  {
	  $ch = uniord(mb_substr($str,$i,1, "UTF-8"));
    if ($ch < 32 || ($ch > 126 && $ch < 192) || $ch==247 || $ch > 255)
    {
      $str = mb_substr($str, 0, $i, "UTF-8") . mb_substr($str, $i+1, mb_strlen($str, "UTF-8")-$i-1, "UTF-8");
    }
  }
       
	// Sort alphabetically
	$s_words = explode(" ", $str);

	//$coll = new Collator( '' );
	//$coll->setStrength(Collator::SECONDARY );
	//$coll->sort($s_words );
	sort($s_words, SORT_STRING);

	$s_words_uniq = array_unique($s_words);


	// Reassemble the final string
	$str = implode(" ", $s_words_uniq);

	return trim($str);
}

function getImageFileName($AdvertiserName, $SearchString, $ImageStyle)
{
	$ImageSize = "100"; //use this value
	$Version = "1";	   //use this value
	
	$AdvertiserName = getAdvertiserName($AdvertiserName);
	$SearchString = getSearchString($SearchString);
	$preHash = $AdvertiserName . "|" . $SearchString . "|" . $ImageSize . "|" . $Version .  "|" . $ImageStyle;
	$hashName = md5($preHash);
	$hashName = strtoupper($hashName);

	$hashName = substr($hashName,0,1) . "/" . substr($hashName,1,2) . "/" . substr($hashName,3,2) . "/" . substr($hashName,5,27) . ".jpg";
	return $hashName;
}


function doEncryption($pTxt)
{
	$mPassw = "6394715";

	$lenTxt = mb_strlen($pTxt, "UTF-8");
	$lenPsw = strlen($mPassw);
       	$lBuff = "";

       for($i=0; $i<$lenTxt; $i++)
       {
		$mod = $i % $lenPsw;
		$c = uniord(mb_substr($pTxt,$i,1, "UTF-8")) + floor(substr($mPassw,$mod,1));
		$lBuff .= unichr($c);
       }

	if (mb_detect_encoding($pTxt)=="UTF-8")//>255
	{
		$lEnc = urlencode($lBuff);
	}
	else
	{
		$lEnc = urlencode(utf8_encode($lBuff));
	}


	//some %C3%82, %C3%83 are extra
	$lEnc = str_replace("%C3%82%C2%", "%C2%", $lEnc);
	$lEnc = str_replace("%C3%83%C2%", "%C2%", $lEnc);

	//'->%27 and (->%28 and )->%29 and "->%22 and +->%2B
       return $lEnc;
}
function doDecryption($pTxt)
{
	$mPassw = "6394715";

	//some %C3%82, %C3%83 are missing
	$lC2 = substr_count($pTxt, "%C2%");
	$pTxt = str_replace("%C2%", "%C3%82%C2%", $pTxt);//or "%C3%83%C2%"

	$lDec = urldecode($pTxt);
	if(strlen($lDec)-2*$lC2 == mb_strlen($lDec, "UTF-8") )
	{
		$lDec = utf8_decode($lDec);//do NOT call this if it has chars >255
	}

	$lenTxt = mb_strlen($lDec, "UTF-8");
	$lenPsw = strlen($mPassw);
       	$lBuff = "";
	for($i=0;$i<$lenTxt;$i++)
	{
		$mod = $i % $lenPsw;
		$c = uniord(mb_substr($lDec,$i,1, "UTF-8")) - floor(substr($mPassw,$mod,1));
		$lBuff .= unichr($c);
	}
        return $lBuff;
}

function uniord($u) 
{
    $k = mb_convert_encoding($u, 'UCS-2LE', 'UTF-8');
    $k1 = ord(substr($k, 0, 1));
    $k2 = ord(substr($k, 1, 1));
    return $k2 * 256 + $k1;
}
function unichr($c) 
{
    if ($c <= 0x7F) {
        return chr($c);
    } else if ($c <= 0x7FF) {
        return chr(0xC0 | $c >> 6) . chr(0x80 | $c & 0x3F);
    } else if ($c <= 0xFFFF) {
        return chr(0xE0 | $c >> 12) . chr(0x80 | $c >> 6 & 0x3F)
                                    . chr(0x80 | $c & 0x3F);
    } else if ($c <= 0x10FFFF) {
        return chr(0xF0 | $c >> 18) . chr(0x80 | $c >> 12 & 0x3F)
                                    . chr(0x80 | $c >> 6 & 0x3F)
                                    . chr(0x80 | $c & 0x3F);
    } else {
        return chr(15);
    }
}

function GetIAProductImageURL($pid, $pSearchString, $pTitle, $pDescription, $pAdvertiser)
{
  $pDisplayURL = $pAdvertiser;
	if($pSearchString===null || $pAdvertiser===null || $pSearchString=="" || $pAdvertiser=="")
	{
		$pSearchString="error";
		$pAdvertiser="error";
	}
	if (strlen($pAdvertiser)>120)	{	$pAdvertiser = substr($pAdvertiser,0,119);}
	if (strlen($pSearchString)>250)	{	$pSearchString = substr($pSearchString,0,249);}
	if (strlen($pTitle)>250)	{	$pTitle = substr($pTitle,0,249);}

	$lQS = "ss=" . strip_tags($pSearchString) ."&adv=" . strip_tags($pAdvertiser) . "&ttl=" . strip_tags($pTitle) . "&des=" .  strip_tags($pDescription);
	$lQSEnc = doEncryption($lQS);
	$ImgFileName = getImageFileName($pAdvertiser, $pSearchString, 0);
	$FullURL = "https://ca.imageadvantage.net/" . $ImgFileName . "?pid=" . $pid . "&qs=" . $lQSEnc . "&d=" . urlencode($pDisplayURL);
	return $FullURL;
}
function GetIABrandImageURL($pid, $pAdvertiser)
{
  $pDisplayURL = $pAdvertiser;
	if($pAdvertiser===null || $pAdvertiser=="")
	{
		$pAdvertiser="error";
	}
	if (strlen($pAdvertiser)>120)	{	$pAdvertiser = substr($pAdvertiser,0,119);}

	$lQS = "adv=" . strip_tags($pAdvertiser);
	$lQSEnc = doEncryption($lQS);
	$ImgFileName = getImageFileName($pAdvertiser, "", 1);
	$FullURL = "https://ca.imageadvantage.net/" . $ImgFileName . "?pid=" . $pid . "&qs=" . $lQSEnc . "&d=" . urlencode($pDisplayURL);
	return $FullURL;
}
function GetIATrackingPixel()//($pid, $subID, $pageID, $pSearchString, $pAdvertiser1, $pAdvertiser2, ....)
{
  $lArgs = func_num_args();
  //if ($lArgs<5)
  //  return "";
  $pid =   "";
  $subID = "";
  $pageID= "";
  $pSearchString = "";
  $lAdvertiserList = "";
  
  if ($lArgs>0)
    $pid =   func_get_arg(0);
  if ($lArgs>1)
    $subID = func_get_arg(1);
  if ($lArgs>2)
    $pageID= func_get_arg(2);
  if ($lArgs>3)
    $pSearchString = getSearchString(func_get_arg(3));
  
  for ($i=4; $i<$lArgs; $i++)
  {
    $lAdvertiserList .= getAdvertiserName(func_get_arg($i));
    if ($i<$lArgs-1)
      $lAdvertiserList .= "|";
  }
  $FullURL = "http://iare.worthathousandwords.com/iar.gif" . "?pid=" . $pid . "&subID=" . $subID . "&pageID=" . $pageID . "&ss=" . $pSearchString . "&advList=" . $lAdvertiserList;
  return $FullURL;
}
?>
