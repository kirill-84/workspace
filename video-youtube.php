<?php
// Added last four video from youtube channel
$API_Key    = ''; 
$Channel_ID = ''; 
$Max_Results = 4;

$apiError = 'Video not found';
try {
	$apiData = @file_get_contents('https://www.googleapis.com/youtube/v3/search?order=date&part=snippet&channelId='.$Channel_ID.'&maxResults='.$Max_Results.'&key='.$API_Key.'');
	if($apiData) {
    		$videoList = json_decode($apiData); 
	} else {
		throw new Exception('Invalid API key or channel ID.'); 
	}
} catch(Exception $e) {
	$apiError = $e->getMessage();
}

if(!empty($videoList->items)) {
	foreach($videoList->items as $item) {
		if(isset($item->id->videoId)) {?>
			<div><img src="<?= $item->snippet->thumbnails->medium->url; ?>"/></div>
			<div><?= $item->snippet->title; ?></div>
		<? }
	}
} else {
	echo '<p>'.$apiError.'</p>';
}
?>
