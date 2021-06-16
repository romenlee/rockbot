<?php
error_reporting(E_ALL);
header('Content-type: text/html; charset=UTF-8');
include('vendor/autoload.php');
ini_set('error_log', 'php_bot_errors.log');
/**
 * Created by PhpStorm.
 * Date: 23.04.20
 * Time: 19:11
 * @package rockbot
 * @author  Roman Lihatskiy <rlihatskiy@determine.com>
 */


function prn($content, $htmlentities = FALSE) {
    if ($htmlentities)
        $content = htmlentities($content, ENT_QUOTES, 'UTF-8');
    echo '<pre style="background: #fff7d7; border: 2px solid #f57900; padding:10px;margin:5px;overflow:auto;"><code>';
    print_r($content);
    echo '</code></pre>';
}

function prd($content, $htmlentities = FALSE) {
    prn($content, $htmlentities);
    die();
}

echo date('H:i:s');
$date = date('H:i:s');
//phpinfo();
use InstaLite\InstaLite;
$instagram = new InstaLite("new_rock_albums", "netpeak05912");//Derparol05912
prn($instagram);
//prn($instagram->uploadPhoto('img/1588757590.jpg', 'text #hashtag'));
die;
$pdo_opt = array(
    PDO::ATTR_PERSISTENT => true,
);
$dbh = new PDO('mysql:host=pixis.mysql.tools;dbname=pixis_rockbot;charset=utf8', 'pixis_rockbot', '1&sV08S@tt', $pdo_opt);
//$res = $dbh->exec("set character_set_client='utf8'");
//$res = $dbh->exec("set character_set_results='utf8'");
//$res1 = $dbh->exec("INSERT INTO post (text, is_finished) VALUES('A{$date}', 0)");
//prn($res1);
$res = $dbh->query('SELECT * from post;', PDO::FETCH_ASSOC)->fetchAll();

//$res = $dbh->query("SHOW VARIABLES LIKE 'character_set%' ", PDO::FETCH_ASSOC)->fetchAll();
//prn($res);
foreach($res as $row) {
    //prn($row);
}
//$res = $dbh->query("SELECT * from audio WHERE post_id=10 ORDER BY orderby ASC;", PDO::FETCH_ASSOC)->fetchAll();
//prn($res);
//$res = $dbh->query("SELECT stamp from last_query;", PDO::FETCH_ASSOC)->fetch();
//prn($res);
//$time = time();
//prn($time);
//$res = $dbh->exec("UPDATE last_query set stamp={$time};");
//prn($res);
//$dbh->exec("UPDATE last_query set query_count=0;");
/*$res = $dbh->query("SELECT stamp, is_free, query_count from last_query;", PDO::FETCH_ASSOC)->fetch();
$time = time();
prn($time);
prn($res['stamp']);
prn($time - $res['stamp']);
if (($time - $res['stamp']) > 20) {
    $res = $dbh->exec("UPDATE last_query set stamp={$time}, query_count=0;");
} else {
    $count = $res['query_count'] + 1;
    sleep($count);
    $dbh->exec("UPDATE last_query set query_count={$count};");
}*/
/*// images
            'png' => 'image/png',
            'jpe' => 'image/jpeg',
            'jpeg' => 'image/jpeg',
            'jpg' => 'image/jpeg',
            'gif' => 'image/gif',
            'bmp' => 'image/bmp',
            'ico' => 'image/vnd.microsoft.icon',
            'tiff' => 'image/tiff',
            'tif' => 'image/tiff',
            'svg' => 'image/svg+xml',
 */
function getImageByLink($link)
{
    $img_memes = array(
        'image/png' => 'png',
        'image/jpeg' => 'jpg',
        'image/gif' => 'gif',
        'image/bmp' => 'bmp',
        'image/vnd.microsoft.icon' => 'ico',
        'image/tiff' => 'tiff',
        'image/svg+xml' => 'svg',
    );
    $ret = '';
    $buffer = @file_get_contents($link);
    if (empty($buffer)) {
        return $ret;
    }
    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $meme = $finfo->buffer($buffer);
    if (empty($meme) || !isset($img_memes[$meme])) {
        return $ret;
    }

    $name_our_new_file = time() . '.' . $img_memes[$meme];
    if (copy($link, "img/".$name_our_new_file)) {
        $ret = "https://{$_SERVER['HTTP_HOST']}/img/$name_our_new_file";
        //$this->telegram->sendMessage(['chat_id' => $this->chat_id, 'text' => "Image was added"]);
    } else {
        //$this->telegram->sendMessage(['chat_id' => $this->chat_id, 'text' => "IMAGE COPY ERROR"]);
    }
    return $ret;
}
//prn( $finfo->buffer($buffer));

/*$file_type = mime_content_type('img/1588526786');
prn($file_type);
$file_type = mime_content_type('img/1588525744.jpg');
prn($file_type);
$file_type = mime_content_type('img/1588527073');
prn($file_type);
$file_type = mime_content_type('img/1588529362');
prn($file_type);*/
//$name_our_new_file = time().".jpeg";
//$name_our_new_file = time();
//$res = copy($text, "img/".$name_our_new_file);
//prn($res);

/*$buffer = file_get_contents('https://avatars.yandex.net/get-music-content/2789402/a35c8de1.a.10224232-1/m1000x1000');
$finfo = new finfo(FILEINFO_MIME_TYPE);
prn( $finfo->buffer($buffer));
$buffer = file_get_contents('https://hsto.org/storage2/6da/5c9/f6d/6da5c9f6d05c82a919b8cdcfa00e0b9b.png');
$finfo = new finfo(FILEINFO_MIME_TYPE);
prn( $finfo->buffer($buffer));*/
$buffer = file_get_contents('https://i.scdn.co/image/ab67616d0000b2733d3eb53a03d0e7c1fe796223');
$finfo = new finfo(FILEINFO_MIME_TYPE);


prn(getImageByLink('https://i.scdn.co/image/ab67616d0000b2733d3eb53a03d0e7c1fe796223'));

//$buffer = file_get_contents('https://rockbot.pixis.com.ua/img/1588236274.jpg');
//$finfo = new finfo(FILEINFO_MIME_TYPE);
//prn( $finfo->buffer($buffer));
//$buffer = file_get_contents('https://habr.com/ru/post/169381/');
//$finfo = new finfo(FILEINFO_MIME_TYPE);
//prn( $finfo->buffer($buffer));


$video_url = 'https://www.youtube.com/watch?v=NpnjYOF0Wb8';
parse_str(parse_url($video_url, PHP_URL_QUERY), $get_parameters);
$video_id = $get_parameters['v'];
prn($video_id);
/*$content = file_get_contents("http://youtube.com/get_video_info?video_id=$video_id");
parse_str($content, $ytarr);
$player_response = json_decode($ytarr['player_response'], true);
prn($player_response['videoDetails']['title']);*/
$api_key = 'AIzaSyDJjTBG3HPioF_WeLURsCUnuWHahxWxAu8';
$json_result = file_get_contents("https://www.googleapis.com/youtube/v3/videos?id=$video_id&key=$api_key&part=snippet&fields=items(snippet(title))");
$result = json_decode($json_result, true);
prn($result['items'][0]['snippet']['title']);

/*$mask = '/^(\+*)(\d{1,2})\D?(\d{0,2})\s?(\d{0,2})\s?(\d{0,2})\s?(\d{0,2})$/i';
$str = '+1234 56 78 0';
prn(preg_match($mask, $str, $matches));
prn($matches);*/
//fixme если нет альбома и хештеов то не работает и если ссылки через пробел от хештегов
$post_mask = '/^([^-]+?)\s*-\s*([^-#]+)-?\s*(\w*)\s*(#.*)?\s*(https?\:\/\/.+)?$/i';
//$token = "1194011134:AAHSvXAX1yQtvz1kGC4zKe0j-QVI5Kepphs";
//$telegram = new MyApi($token);
//$telegram->sendMessage(['chat_id' => 114082814, 'text' => 'test.php']);
//$rockBot = new RockBot();

$vk_token='16fc2f5b5a5113af3f084ea3fd79af6f05f72445e2d58a695eedee95d3fc0226532669eb84eff62343855';
$vk_token='16bb69f6007b2316b0bc246e446fae053e03e9b49a0b911022be7adb410a9114f06dbfd1bdda69aef41f9';

$vk = "https://api.vk.com/method/wall.post?owner_id=-48186614&friends_only=0&from_group=1&message=test&attachments=&v=5.103&access_token={$vk_token}";
$vk = "https://api.vk.com/method/wall.post";
//to vk
$link = 'https://youtu.be/VYO7YZ6e5Ak'; // ccылка на ютуб
//$name = urlencode($item['title']); //название видео (не обязательно)
//$description = 'https://post-hardcore.ru/video/'.$vid.'-'.$alt.'.html'; //описание видео
$wallpost = 1; //опубливоать на стене (0 - нет, 1 - да)

$a = 'https://api.vk.com/method/video.save?group_id=48186614&link='.$link.'&wallpost=' . $wallpost . "&access_token=$vk_token&v=5.103"; //собираем ccылку для запроса (XYZ - токен, rights to call this method: video)
//$a = 'https://api.vk.com/method/video.save?group_id=23138673&link='.$link.'&name='.$name.'&description=' . urlencode($description) . '&wallpost=' . $wallpost . '&access_token=XYZ'; //собираем ccылку для запроса (XYZ - токен, rights to call this method: video)

/*$addvideo = file_get_contents(rtrim($a)); //отправляем запрос
$obj = json_decode($addvideo); //обрабатываем джисон
prn($obj);
$upload_url = $obj->{'response'}->{'upload_url'}; //тут получаем ссылку для подтверждения добавления
$video_id = $obj->{'response'}->{'video_id'}; //тут получаем ссылку для подтверждения добавления
$video = file_get_contents(rtrim($upload_url)); //открываем ссылку
prn($video);*/
//'owner_id' => '-48186614',
/*$vk_params = array(
    'owner_id' => '-13109196',
    'friends_only' => 0,
    'from_group' => 1,
    'message' => 'test',
    //'attachments' => "video6955601_$video_id",
    'v' => '5.103',
    'access_token' => $vk_token,
);
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $vk);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
//curl_setopt($curl, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, $vk_params);
$result = curl_exec($ch);
curl_close($ch);
prn($result);*/

$vk = new VK\Client\VKApiClient('5.101');
/*$address = $vk->photos()->getWallUploadServer($vk_token);
$photo = $vk->getRequest()->upload($address['upload_url'], 'photo', 'img/1590445843.jpg');
$response_save_photo = $vk->photos()->saveWallPhoto($vk_token, array(
    'server' => $photo['server'],
    'photo' => $photo['photo'],
    'hash' => $photo['hash'],
));*/

/*$video = $vk->video()->save($vk_token, array(
    'group_id' => '13109196',
    'wallpost' => 1,
    'link' => 'https://www.youtube.com/watch?v=i9BupglHdtM',
));
$video_post = file_get_contents(rtrim($video['upload_url'])); //открываем ссылку
prn($video);
prn($video_post);*/
/*$vk_params = array(
    'owner_id' => '-13109196',
    'friends_only' => 0,
    'from_group' => 1,
    'message' => 'test_api_' . date('Y-m-d H:i:s'),
    //'publish_date' => strtotime('2020-06-22 8:11:00'),
    //'attachments' => "photo{$response_save_photo[0]['owner_id']}_{$response_save_photo[0]['id']}",
    //'attachments' => "video{$video['owner_id']}_{$video['video_id']}",
);
$post = $vk->wall()->post($vk_token, $vk_params);
//prn($address);
//prn($photo);
//prn($response_save_photo);
prn($post);*/
prn(strtotime(date('2020-06-25 8:11:00')));
prn(date('Y-m-d H:i:s' ,strtotime('+22 minutes', strtotime(date('2020-06-25 8:11:00')))));

/*$a = "https://api.vk.com/method/audio.getUploadServer?&access_token=$vk_token&v=5.101";
$add_audio = json_decode(file_get_contents($a), true);
prn($add_audio);
$audio = $vk->getRequest()->upload($add_audio['response']['upload_url'], 'file', 'music/d.mp3');*/
/*$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $add_audio['response']['upload_url']);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
curl_setopt($ch, CURLOPT_POST, 1);
$p = ['file' => (class_exists('CURLFile', false)) ? new \CURLFile('music/d.mp3') : '@' . 'music/d.mp3'];
curl_setopt($ch, CURLOPT_POSTFIELDS, $p);
$audio = curl_exec($ch);
curl_close($ch);
$audio = json_decode($audio);*/
/*prn($audio);
$a = "https://api.vk.com/method/audio.save?&access_token=$vk_token&v=5.101&server={$audio['server']}&audio={$audio['audio']}&hash={$audio['hash']}";
$vk_params = array(
    'server' => $audio['server'],
    'audio' => $audio['audio'],
    'hash' => $audio['hash'],
    'v' => '5.101',
    'access_token' => $vk_token,
);
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, 'https://api.vk.com/method/audio.save');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
//curl_setopt($curl, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, $vk_params);
$result1 = curl_exec($ch);
curl_close($ch);
$audio1 = json_decode($result1, true);
prn($audio1);
$vk_params = array(
    'owner_id' => '-13109196',
    'friends_only' => 0,
    'from_group' => 1,
    'message' => 'test_api_' . date('Y-m-d H:i:s'),
    //'publish_date' => strtotime('2020-06-25 8:12:00'),
    //'attachments' => "photo{$response_save_photo[0]['owner_id']}_{$response_save_photo[0]['id']}",
    'attachments' => "audio{$audio1['response']['owner_id']}_{$audio1['response']['id']}",
);
$post = $vk->wall()->post($vk_token, $vk_params);
prn($post);*/
/*Array
(
    [response] => Array
        (
            [artist] => Sabaton
            [id] => 456239248
            [owner_id] => 6955601
            [title] => Dominium Maris Baltici
            [duration] => 29
            [track_code] => faa786a7Q4ZQOWsQVUDjpOw0vcY7cj1c
            [url] =>
            [date] => 1592604809
            [content_restricted] => 6
        )

)*/

function getVkAudios()
{
$vk = new VK\Client\VKApiClient('5.101');
    $ret = array();
    $a = "https://api.vk.com/method/audio.getUploadServer?&access_token=" . '16bb69f6007b2316b0bc246e446fae053e03e9b49a0b911022be7adb410a9114f06dbfd1bdda69aef41f9' . "&v=" . '5.101';
    $uploadAudioServer = json_decode(file_get_contents($a), true);

    if (empty($uploadAudioServer) || empty($uploadAudioServer['response']['upload_url'])) {
        return $ret;
    }

    $audioFiles = glob('music/*', GLOB_NOSORT);

    if (empty($audioFiles)) {
        return $ret;
    }
prn($uploadAudioServer);
prn($audioFiles);
    foreach ($audioFiles as $file) {
        $audioUpload = $vk->getRequest()->upload($uploadAudioServer['response']['upload_url'], 'file', $file);
        if (empty($audioUpload['server']) || empty($audioUpload['audio']) || empty($audioUpload['hash'])) {
            continue;
        }
prn($audioUpload);
        $vk_params = array(
            'server' => $audioUpload['server'],
            'audio' => $audioUpload['audio'],
            'hash' => $audioUpload['hash'],
            'v' => '5.101',
            'access_token' => '16bb69f6007b2316b0bc246e446fae053e03e9b49a0b911022be7adb410a9114f06dbfd1bdda69aef41f9',
        );
        $audioName = explode('--', $file);
        if (!empty($audioName[1])) {
            $vk_params['artist'] = $audioName[1];
        }
        if (!empty($audioName[2])) {
            $vk_params['title'] = $audioName[2];
        }

prn($audioName);
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, 'https://api.vk.com/method/audio.save');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $vk_params);
        $responseAudio = curl_exec($ch);
        curl_close($ch);
        $audioResult = json_decode($responseAudio, true);
prn($audioResult);
        if (!empty($audioResult) && !empty($audioResult['response']['owner_id']) && !empty($audioResult['response']['id'])) {
            $ret[] = "audio{$audioResult['response']['owner_id']}_{$audioResult['response']['id']}";
        }
    }
    return $ret;
}

//prn(getVkAudios());


$url2 = 'http://167.71.12.148/find/Stryper/Blood%20From%20Above?q=google,apple,youtube,yandex,spotify,lastfm,soundcloud&callback=1';
$url2 = 'http://167.71.12.148/find/DARK%20SARAH/Illuminate?q=google,apple,youtube,yandex,spotify,lastfm,soundcloud&callback=1';
$curl = curl_init();
curl_setopt($curl, CURLOPT_URL, $url2);
curl_setopt($curl, CURLOPT_TIMEOUT, 1);
curl_setopt($curl, CURLOPT_CONNECTTIMEOUT, 5);
$result = curl_exec($curl);
curl_close($curl);
prn($result);





function getParsedData($artist = '', $album = '', $sources = array())
{
    $result = array();
    //$link = 'https://newrockbot.herokuapp.com/';
    $link = 'http://167.71.12.148/';
    if (!empty($artist) && !empty($album)) {
        $artist = rawurlencode($artist);
        $album = rawurlencode($album);
        $link .= "find/{$artist}/{$album}";
    } else {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $link);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 27);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5);
        $result = curl_exec($ch);
        curl_close($ch);
        return $result;
    }

    if (!empty($sources)) {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 27);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);
        $url = $link . '?q=' . implode(',', $sources);
        curl_setopt($ch, CURLOPT_URL, $url);
        $content = curl_exec($ch);
        $decoded = json_decode($content, true);
        if (empty($content)) {
            $result = array('error' => 'Empty result');
        } elseif (!isset($decoded)) {
            $result = array('error' => 'Result is not object');
        } else {
            $result = $decoded;
        }
        curl_close($ch);
        /*$attempt = 0;
        do {
            $mh = curl_multi_init();
            $ch = array();
            foreach ($sources as $source) {
                $url = $link . "?q={$source}";
                $ch[$source] = curl_init();
                curl_setopt($ch[$source], CURLOPT_URL, $url);
                curl_setopt($ch[$source], CURLOPT_RETURNTRANSFER, 1);
                curl_setopt($ch[$source], CURLOPT_TIMEOUT, 27);//задает время за которое мы должны загрузить указанный url 32400 = 9 часов (32200 - без 200 секунд)
                curl_setopt($ch[$source], CURLOPT_CONNECTTIMEOUT, 10); //задает время на соединение с сервером
                curl_multi_add_handle($mh, $ch[$source]);
            }

            do {
                $status = curl_multi_exec($mh, $running);
            } while ($running > 0 && $status == CURLM_OK);

            foreach ($sources as $k => $source) {
                $content = curl_multi_getcontent($ch[$source]);
                $decoded = json_decode($content, true);
                if (empty($content)) {
                    $res = array('error' => 'Empty result');
                } elseif (!isset($decoded)) {
                    $res = array('error' => 'Result is not object');
                } elseif (!isset($decoded[$source])) {
                    $res = array('error' => 'No sourse key in result');
                } else {
                    $res = $decoded[$source];
                }
                if (!isset($res['error'])) {
                    unset($sources[$k]);
                }
                $result[$source] = $res;
                curl_multi_remove_handle($mh, $ch[$source]);
            }
            $attempt++;
        } while ($attempt < 1 && !empty($sources));
        curl_multi_close($mh);*/ //возможно надо это засунуть в цикл перед while
    }
    return $result;
}

$artist = 'The Plot In You';//
$album = 'Repay';
$sources = array('google', 'apple', 'yandex', 'youtube');
$sources = array('spotify', 'lastfm', 'soundcloud', 'google', 'apple', 'yandex', 'youtube');
//prn(getParsedData($artist, $album, $sources));
//prn(file_get_contents('https://newrockbot.herokuapp.com/find/Moonspell/Memorial?q=spotify,lastfm'));
