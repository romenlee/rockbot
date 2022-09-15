<?php
include('vendor/autoload.php');
error_reporting(E_ALL);
ini_set('error_log', 'php_bot_errors.log');
function prn($content, $htmlentities = FALSE) {
    if ($htmlentities) $content = htmlentities($content, ENT_QUOTES, 'UTF-8');
    echo '<pre style="background: #fff7d7; border: 2px solid #f57900; padding:10px;margin:5px;overflow:auto;"><code>';print_r($content);echo '</code></pre>';
}
function prd($content, $htmlentities = FALSE) {prn($content, $htmlentities);die();}

$cron = (!empty($_GET['cron']));
$type = '';
if (!empty($_GET['cron'])) {
    $type = 'cron';
} elseif (!empty($_REQUEST['save_links'])) {
    $type = 'save_links';
    /*prn(1);
    $fp = fopen('log.txt', 'at');
    fwrite($fp, json_encode($_REQUEST, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) . "\n\n");
    $results = json_decode($_REQUEST['results'], true);
    $results['results']['spotify']['link'];
    fwrite($fp, $results['results']['spotify']['link'] . "\n\n");
    fclose($fp);
    return;*/
} elseif (!empty($_REQUEST['insta'])) {
    $type = 'insta';
} elseif (!empty($_REQUEST['queue'])) {
    $type = 'queue';
}
new RockBot($type);


/*if (!empty($cron)) {
    new RockBot($cron);
    return;
}

use Telegram\Bot\Exceptions\TelegramSDKException;

$token = "1194011134:AAHSvXAX1yQtvz1kGC4zKe0j-QVI5Kepphs";

$music_resources = array(
    't.me' => array('name' => "🎧 СЛУШАТЬ ⏯", 'db_field' => 't_me',), // \ud83d\udd09 \ud83d\udd0a
    'play.google' => array('name' => 'Google music', 'db_field' => 'play_google'),
    'music.apple' => array('name' => 'Apple music', 'db_field' => 'music_apple'),
    'vk.com' => array('name' => 'VK', 'link' => 'https://vk.com/novue_rock_albomu_2013'),
    'music.youtube' => array('name' => 'YouTube music', 'db_field' => 'music_youtube'),
    'music.yandex' => array('name' => 'Yandex music', 'db_field' => 'music_yandex'),
);

$date = date('Y-m-d H:i:s');
$y = date('Y');
$dev_channel = 'my_dev';
$post_channel = 'rock_albums';
$audio_channel = 'new_rotsk';
$likes = [
    [
        [
            'text' => '🤘',
            'callback_data'=>'{"action":"rock","count":0,"text":"🤘"}'
        ],
        [
            'text' => '👎',
            'callback_data'=>'{"action":"dislike","count":0,"text":"👎"}'
            //'url' => 'https://tlgrm.ru/docs/bots/api#inlinekeyboardmarkup',
        ],
    ],
];
$reply_likes = json_encode(['inline_keyboard' => $likes]);



$time_mask = '/^(\+*)(\d{1,2})\D?(\d{0,2})\s?(\d{0,2})\s?(\d{0,2})\s?(\d{0,2})$/i';
$allowed_chats = array(
    '114082814' => '114082814',//me
    '-1001455135875' => 'my develop chat',//my develop chat
    '-1001173139890' => 'new rock chat',//new rock chat
    '-1001488152998' => 'my dev channel',//my dev channel
    '-1001348573922' => 'Канал Новые рок альбомы и клипы',//Канал Новые рок альбомы и клипы
);

try {
    $telegram = new MyApi($token);
    $result = $telegram->getWebhookUpdates();

    if (!empty($result['callback_query']['id'])) {
        new RockBot($cron);
        return;
    }

    $pdo_opt = array(PDO::ATTR_PERSISTENT => true);
    $dbh = new PDO('mysql:host=pixis.mysql.tools;dbname=pixis_rockbot;charset=utf8', 'pixis_rockbot', '1&sV08S@tt', $pdo_opt);
    $fp = fopen('log.txt', 'at');
    fwrite($fp, "$date\n");
    if (!empty($result)) {
        foreach ($result as $k => $v) {
            if ($k == 'message') {
                fwrite($fp, "$k => \n");
                foreach ($v as $k_m => $v_m) {
                    fwrite($fp, "   $k_m => " . json_encode($v_m) . "\n");
                }
            } else {
                fwrite($fp, "$k => " . json_encode($v) . "\n");
            }

        }
        fwrite($fp, json_encode($result) . "\n");
    } else {
        $telegram->sendMessage(['chat_id' => 114082814, 'text' => 'No input data']);
        fwrite($fp, "No input data\n\n");
        fclose($fp);
        $dbh = null;
        return;
    }
    //реакции на        пост в канале              редактирование поста в канале            редактирование сообщения
    if (!empty($result['channel_post']) || !empty($result['edited_channel_post']) || !empty($result['edited_message'])) {
        fwrite($fp, "\n");
        fclose($fp);
        $dbh = null;
        return;
    }

    $text = !empty($result['message']['text']) ? $result['message']['text'] : '';
    $chat_id = !empty($result['message']['chat']) ? $result['message']['chat']['id'] : '';


    if (!empty($chat_id) && $chat_id != '114082814') {
        if (!isset($allowed_chats[$chat_id])) {
            $telegram->sendMessage(['chat_id' => 114082814, 'text' => "Доступ с левого чата!\n\n" . json_encode($result),]);
        }// else $telegram->sendMessage(['chat_id' => 114082814, 'text' => "Доступ с: {$allowed_chats[$chat_id]}",]);

        fwrite($fp, "\n");
        fclose($fp);
        $dbh = null;
        return;
    }
    if (empty($text) || $text != '/start') {
        $res = $dbh->query('SELECT * from post WHERE is_finished=0 LIMIT 1;', PDO::FETCH_ASSOC)->fetchAll();
        if (empty($res)) {
            $reply = 'In order to post press /start';
            $telegram->sendMessage(['chat_id' => $chat_id, 'text' => $reply]);
            fwrite($fp, "\n");
            fclose($fp);
            $dbh = null;
            return;
        }
    }

    if ($text) {
        if ($text == "/start") {
            $res = $dbh->query('SELECT * from post WHERE is_finished=0;', PDO::FETCH_ASSOC)->fetchAll();
            if (empty($res)) {
                $reply = 'Posting started';
                $res = $dbh->exec("INSERT INTO post (is_finished) VALUES(0);");
            } else {
                $reply = 'The post is not finished!!!';
            }

            $telegram->sendMessage(['chat_id' => $chat_id, 'text' => $reply]);
        } elseif ($text == "/c" || $text == "/с") {
            $res = $dbh->query('SELECT * from post WHERE is_finished=0 LIMIT 1;', PDO::FETCH_ASSOC)->fetch();
            $dbh->exec("DELETE FROM audio WHERE post_id={$res['id_post']};");
            $dbh->exec("DELETE FROM post WHERE is_finished=0;");
            $telegram->sendMessage(['chat_id' => $chat_id, 'text' => 'Canceled. Press /start']);
        } elseif (strpos($text, '/p') === 0 || ($is_delay = preg_match($time_mask, $text, $matches))) {//post
            $res = $dbh->query('SELECT * from post WHERE is_finished=0 LIMIT 1;', PDO::FETCH_ASSOC)->fetchAll();
            if (!empty($res[0]['artist'])) {
                $is_date_error = false;
                $delay_date = '';
                if ($is_delay) {
                    $add_day = ($matches[1] == '+');
                    if ($add_day) {
                        $text = str_replace('+', '', $text);
                    }

                    $hour = '';
                    $minute = '00';
                    $day = date('d');
                    $month = date('m');
                    $year = date('y');

                    if (is_numeric($text) && strlen($text) < 4) {
                        if (strlen($text) == 3) {
                            $hour = floor($text / 100);
                            $minute = $text % 100;
                        } else {
                            $hour = $text;
                        }
                    } else {
                        $hour = $matches[2];
                        $minute = $matches[3];
                        if (!empty($matches[4])) {
                            $day = $matches[4];
                        }
                        if (!empty($matches[5])) {
                            $month = $matches[5];
                        }
                        if (!empty($matches[6])) {
                            $year = $matches[6];
                        }
                    }
                    $year = "20" . $year;

                    $parsed_date = "$day.$month.$year $hour:$minute";
                    if (!is_numeric($hour) || intval($hour) > 23 || intval($minute) > 59 || intval($day) > 31 || intval($month) > 12 || empty($month) || intval($year) > 2021 || strtotime($parsed_date) == FALSE) {
                        $is_date_error = true;
                        $telegram->sendMessage(['chat_id' => $chat_id, 'text' => "Incorrect time: $parsed_date"]);
                    } else {
                        $parsed_time = strtotime($parsed_date);
                        if ($parsed_time <= time()) {
                            $parsed_time = strtotime('+1 day', $parsed_time);
                        }
                        if ($add_day) {
                            $parsed_time = strtotime('+1 day', $parsed_time);
                        }

                        if ($parsed_time < time()) {
                            $is_date_error = true;
                            $telegram->sendMessage(['chat_id' => $chat_id, 'text' => "Incorrect time: It's the past!"]);
                        } else {
                            $delay_date = date('Y-m-d H:i:00', $parsed_time);
                        }

                    }
                }

                if (!$is_date_error) {
                    $is_post = false;
                    $audio_channel_id = $chat_id;
                    $post_channel_id = $chat_id;
                    if ($text == '/post' || !empty($delay_date)) {
                        $is_post = true;
                        $audio_channel_id = "@$audio_channel";
                        $post_channel_id = "@$post_channel";
                        //$audio_channel = $dev_channel;
                        //$audio_channel_id = "@$dev_channel";
                        //$post_channel_id = "@$dev_channel";
                    }

                    $post = reset($res);

                    $audio_msg_id = 0;
                    $res = $dbh->query("SELECT * from audio WHERE post_id='{$post['id_post']}' ORDER BY id_message ASC;", PDO::FETCH_ASSOC)->fetchAll();

                    if (!empty($res)) {
                        if (!empty($post['audio_text'])) {
                            $r = $telegram->sendMessage([
                                'chat_id' => $audio_channel_id,
                                'parse_mode' => 'Markdown',
                                'text' => $post['audio_text'],
                                'disable_web_page_preview' => true,
                            ]);
                            $audio_msg_id = $r['message_id'];
                        }

                        $i = 0;
                        foreach ($res as $audio) {
                            $r = $telegram->sendAudio([
                                'chat_id' => "$audio_channel_id",
                                'audio' => $audio['file'],
                                'caption' => $audio['caption'],
                            ]);

                            if (empty($audio_msg_id)) {
                                $audio_msg_id = $r['message_id'];
                            }
                            $i++;
                            if ($i > 3) {
                                sleep(1);
                                $i = 0;
                            }
                        }
                        if (count($res) > 1) {
                            $r = $telegram->sendSticker([
                                'chat_id' => "$audio_channel_id",
                                'sticker' => 'CAACAgIAAxkBAAIE2F6mIbABvnIAAQqXBP1iFqSU-ZJ_0wACDAADNjqiGWbGGbgDTI49GQQ',
                            ]);
                        }
                    }

                    $linkResult = '';
                    $i = 0;
                    foreach ($music_resources as $key_mr => $mr) {
                        $lnk = '';
                        if (!empty($mr['link'])) {
                            $lnk = $mr['link'];
                        } elseif (!empty($mr['db_field']) && !empty($post[$mr['db_field']])) {
                            $lnk = $post[$mr['db_field']];
                        }

                        if ($key_mr == 't.me') {
                            if (empty($lnk) && !empty($audio_msg_id)) {
                                $lnk = "https://t.me/{$audio_channel}/{$audio_msg_id}";
                                if ($is_post) {
                                    $res = $dbh->exec("UPDATE post set t_me='{$lnk}' where id_post = {$post['id_post']};");
                                }
                            }
                            if (!empty($lnk)) {
                                $linkResult .= "[{$mr['name']}]({$lnk})\n\n";
                            }
                        } else {
                            if (empty($lnk)) continue;
                            $i++;
                            $linkResult .= "[{$mr['name']}]({$lnk})       ";
                            if ($i == 3) {
                                $linkResult = rtrim($linkResult) . "\n       ";
                                $i = 0;
                            }
                        }
                    }
                    $linkResult = rtrim($linkResult);

                    $media = '';
                    if (!empty($post['media_link'])) {
                        $s = mb_chr(8203);
                        $media = "[$s$s]({$post['media_link']})";
                    }

                    $post_template = "*{$post['artist']} - {$post['album']}* ({$post['type_album']} {$y})";
                    $post_text = $post_template;
                    $post_template .= "\n" . addcslashes($post['hashtag'], '_');
                    $post_text .= "{$media}\n" . addcslashes($post['hashtag'], '_');
                    $post_vk = "{$post_template}\n\nСлушать/скачать в телеграм https://tlinks.run/rock\_albums";
                    if (!empty($linkResult)) {
                        $post_template .= "\n\n" . addcslashes($linkResult, '_[');
                        $post_text .= "\n\n$linkResult";
                    }
                    $r = $telegram->sendMessage(['chat_id' => $chat_id, 'text' => $post_template, 'disable_web_page_preview' => true, 'parse_mode' => 'Markdown']);
                    $r = $telegram->sendMessage(['chat_id' => $chat_id, 'text' => $post_text, 'reply_markup' => $reply_likes, 'disable_web_page_preview' => false, 'parse_mode' => 'Markdown']);
                    $r = $telegram->sendMessage(['chat_id' => $chat_id, 'text' => $post_vk, 'disable_web_page_preview' => true, 'parse_mode' => 'Markdown']);
                    if ($is_post) {
                        if (empty($delay_date)) {
                            $r = $telegram->sendMessage(['chat_id' => $post_channel_id, 'parse_mode' => 'Markdown', 'text' => $post_text, 'reply_markup' => $reply_likes, 'disable_web_page_preview' => false]);
                            $posted_date = $date;
                            $is_posted = 1;
                        } else {
                            $human_date = date('G:i j F Y', strtotime($delay_date));
                            $posted_date = $delay_date;
                            $is_posted = 0;
                            $r = $telegram->sendMessage(['chat_id' => $chat_id, 'text' => "*$human_date* will be posted", 'parse_mode' => 'Markdown']);
                        }

                        $res = $dbh->exec("UPDATE post set posted_date='{$posted_date}', is_posted={$is_posted}, is_finished=1 where is_finished = 0;");
                        $telegram->sendMessage(['chat_id' => $chat_id, 'text' => 'Press /start']);
                    }
                }
            } else {
                $r = $telegram->sendMessage([
                    'chat_id' => $chat_id,
                    'parse_mode' => 'Markdown',
                    'text' => 'There is no any message for posting',
                    'disable_web_page_preview' => true,
                ]);
            }
//        } elseif ($text == "Картинка") {
//            $url = "https://68.media.tumblr.com/6d830b4f2c455f9cb6cd4ebe5011d2b8/tumblr_oj49kevkUz1v4bb1no1_500.jpg";
//            $telegram->sendPhoto(['chat_id' => $chat_id, 'text' => 'Описание каритнки', 'photo' => $url, 'caption' => "Описание."]);
//        } elseif ($text == "Гифка") {
//            $url = "https://68.media.tumblr.com/bd08f2aa85a6eb8b7a9f4b07c0807d71/tumblr_ofrc94sG1e1sjmm5ao1_400.gif";
//            $telegram->sendDocument(['chat_id' => $chat_id, 'document' => $url, 'caption' => "Описание."]);
        } elseif (mb_strpos($text, 'http') === 0) {
            $telegram->sendMessage(['chat_id' => $chat_id, 'parse_mode' => 'Markdown', 'text' => 'LINK']);
        } else {
            $reply = $text;
            $artist = '';
            $album = '';
            $type = 'single';
            $tag = '';
            $links = '';
            $albumStartPos = mb_strpos($text, '-');
            $artistLength = null;
            $symbols = array(' ', ',', '-', '&', '#', '_', "'", '"', '__');

            if ($albumStartPos) {
                $albumStartPos++;
                $albumLength = null;
                $typeLength = null;
                $tagLength = null;
                $artistLength = $albumStartPos - 1;

                $typeStartPos = mb_strpos($text, '-', $albumStartPos);
                $tagStartPos = mb_strpos($text, '#', $albumStartPos);
                $linkStartPos = mb_strpos($text, 'http', $albumStartPos);
                if ($typeStartPos && $tagStartPos && $typeStartPos >= $tagStartPos) {
                    $typeStartPos = false;
                }
                if ($tagStartPos && $linkStartPos && $tagStartPos >= $linkStartPos) {
                    $typeStartPos = false;
                }
                if ($typeStartPos) {
                    $albumLength = $typeStartPos - $albumStartPos;
                } elseif ($tagStartPos) {
                    $albumLength = $tagStartPos - $albumStartPos;
                } elseif ($linkStartPos) {
                    $albumLength = $linkStartPos - $albumStartPos;
                }

                if ($typeStartPos) {
                    $typeStartPos++;
                    if ($tagStartPos) {
                        $typeLength = $tagStartPos - $typeStartPos;
                    } elseif ($linkStartPos) {
                        $typeLength = $linkStartPos - $typeStartPos;
                    }
                }

                if ($tagStartPos) {
                    if ($linkStartPos) {
                        $tagLength = $linkStartPos - $tagStartPos;
                    }
                }

                if ($typeStartPos) {
                    $typeKey = mb_strtolower(trim(mb_substr($text, $typeStartPos, $typeLength)));
                    switch ($typeKey) {
                        case 'a':
                        case 'а'://rus
                            $type = 'album';
                            break;
                        case 'e':
                        case 'е'://rus
                            $type = 'EP';
                            break;
                        case 'c':
                        case 'с'://rus
                            $type = 'compilation';
                            break;
                        case 'l':
                            $type = 'live album';
                            break;
                    }
                }
                if ($tagStartPos) {
                    $tag = mb_strtolower(trim(mb_substr($text, $tagStartPos, $tagLength)));
                    $tags = explode('#', $tag);
                    $tag = '';
                    foreach ($tags as $t) {
                        $t = trim($t);
                        if (empty($t)) continue;
                        $tag .= '#' . str_replace($symbols, '_', $t) . ' ';
                    }
                    $tag = rtrim($tag);
                }

                if ($linkStartPos) {
                    $links = trim(mb_substr($text, $linkStartPos));
                    $links_array = array_filter(explode('https://', $links));
                    foreach ($links_array as $l) {
                        $domainEndPos = strpos($text, '/');
                        if (!$domainEndPos) {
                            continue;
                        }
                        $domain = substr($l, 0, $domainEndPos);
                        foreach ($music_resources as $key_mr => $mr) {
                            if (strpos($domain, $key_mr) !== false) {
                                $music_resources[$key_mr]['link'] = 'https://' . rtrim($l);
                                break;
                            }
                        }
                    }

                    $linkResult = '';
                    $i = 0;
                    foreach ($music_resources as $key_mr => $mr) {
                        if (empty($mr['link'])) continue;

                        if ($key_mr == 't.me' ) {
                            $linkResult .= "[{$mr['name']}]({$mr['link']})\n\n";
                        } else {
                            $i++;
                            $linkResult .= "[{$mr['name']}]({$mr['link']})       ";
                            if ($i == 3) {
                                $linkResult = rtrim($linkResult) . "\n       ";
                                $i = 0;
                            }
                        }
                    }
                    $linkResult = rtrim($linkResult);
                }

                $artist = trim(mb_substr($text, 0, $artistLength));
                $album = trim(mb_substr($text, $albumStartPos, $albumLength));

                $tag .= ' #' . str_replace($symbols, '_', $artist);
                $tag = str_replace(array('!', '?', '.', ','), '', $tag);
                $tag_db = $tag;
                $tag = addcslashes($tag, '_');

                $reply = "*{$artist} - {$album}* ({$type} {$y})\n{$tag}";

                $audio_txt = '';
                if ($type != 'single') {
                    $audio_txt = "{$reply} @rock\_albums";
                }

                if (!empty($linkResult)) {
                    //addcslashes чтоб отправлять шаблон ссылок, а не готовые ссылки
                    $reply .= "\n\n" . addcslashes($linkResult, '_[');
                }
                $reply_db = addcslashes(str_replace(array('🎧','⏯'), array(':left_smiles:', ':right_smiles:'), $reply), "'");
                $upd = array(
                    'text' => $reply_db,
                    'audio_text' => $audio_txt,
                    'type_album' => $type,
                    'artist' => addcslashes($artist, "'"),
                    'album' => addcslashes($album, "'"),
                    'hashtag' => $tag_db,
                );
                foreach ($music_resources as $mr) {
                    if (empty($mr['db_field']) || empty($mr['link'])) continue;
                    $upd[$mr['db_field']] = $mr['link'];
                }

                $upd_str = '';
                foreach ($upd as $ku => $u) {
                    if (!is_numeric($u)) {
                        $u = "'{$u}'";
                    }
                    $upd_str .= "$ku=$u, ";
                }
                $upd_str = rtrim($upd_str, ', ');
                $res = $dbh->exec("UPDATE post set {$upd_str} where is_finished = 0;");
                $r = $telegram->sendMessage(['chat_id' => $chat_id, 'parse_mode' => 'Markdown', 'text' => $reply, 'disable_web_page_preview' => false]);
            } else {
                $s = mb_chr(8203);
                $s_lnk = "[$s$s](https://rockbot.pixis.com.ua/img/1588236274.jpg)";
                $r = $telegram->sendMessage(['chat_id' => "@$dev_channel", 'parse_mode' => 'Markdown', 'text' => $reply, 'disable_web_page_preview' => false]);
            }
        }
    } elseif (isset($result['message']['audio']['file_id'])) {
        $dbh->exec("LOCK TABLES post READ, audio WRITE;");
        $res = $dbh->query('SELECT * from post WHERE is_finished=0;', PDO::FETCH_ASSOC)->fetchAll();
        if (!empty($res)) {
            $post = reset($res);
            $res = $dbh->query("SELECT orderby from audio WHERE post_id={$post['id_post']} ORDER BY orderby DESC LIMIT 1;", PDO::FETCH_ASSOC)->fetchAll();
            $orderby = 1;
            if (!empty($res)) {
                $last_audio = reset($res);
                $orderby = $last_audio['orderby'] + 1;
            }
            $caption = '';
            if (empty($post['audio_text'])) {
                $caption = "{$post['type_album']} {$post['hashtag']} @rock_albums";
            }
            $file = addcslashes($result['message']['audio']['file_id'], "'");
            $song_title = addcslashes($result['message']['audio']['title'] ?? '', "'");
            $song_artist = $result['message']['audio']['performer'] ?? '';
            $msg_id = !empty($result['message']['message_id']) ? $result['message']['message_id'] : '';
            $res = $dbh->exec("INSERT INTO audio (post_id, file, caption, orderby, artist, title, id_message)
                              VALUES({$post['id_post']}, '{$file}', '{$caption}', {$orderby}, '{$song_artist}', '{$song_title}', $msg_id)");
            $dbh->exec("UNLOCK TABLES;");
        }
    } elseif (!empty($result['message']['photo'])) {
        $photo = $result['message']['photo'];
        $file = $telegram->getFile(['file_id' =>$photo[count($photo) - 1]['file_id']]);
        //fwrite($fp, json_encode($photo[count($photo) - 1]['file_id']) . "\n");
        //fwrite($fp, json_encode($photo) . "\n");
        //fwrite($fp, json_encode($file) . "\n");
        $file_from_tgrm = "https://api.telegram.org/file/bot".$token."/".$file['file_path'];
        // достаем расширение файла
        $ext_arr = explode(".", $file['file_path']);
        $ext = end($ext_arr);
        // назначаем свое имя здесь время_в_секундах.расширение_файла
        $name_our_new_file = time().".".$ext;
        if (copy($file_from_tgrm, "img/".$name_our_new_file)) {
            $img_link = "https://{$_SERVER['HTTP_HOST']}/img/$name_our_new_file";
            $res = $dbh->exec("UPDATE post set media_link='{$img_link}' where is_finished = 0;");
            if ($res) {
                $telegram->sendMessage(['chat_id' => $chat_id, 'text' => "Image was added"]);
            }
        } else {
            $telegram->sendMessage(['chat_id' => $chat_id, 'text' => "IMAGE COPY ERROR"]);
        }
    } elseif (isset($result['message']['sticker']['file_id'])) {
        $r = $telegram->sendSticker([
            'chat_id' => $chat_id,
            'sticker' => $result['message']['sticker']['file_id'],
        ]);
    } else {
        $telegram->sendMessage(['chat_id' => 114082814, 'text' => "Отправьте текстовое сообщение."]);
    }
} catch (TelegramSDKException $e) {
    $error_msg = $e->getMessage() . "\nFile: " . $e->getFile() . "\nLine: " . $e->getLine() . "\nTrace:\n" . $e->getTraceAsString() . "\n";
    if (!empty($result)) {
        $error_msg .= json_encode($result);
    } else {
        $error_msg .= 'Result is empty';
    }
    $telegram->sendMessage(['chat_id' => 114082814, 'text' => $error_msg]);
} catch (PDOException $e) {
    $telegram->sendMessage(['chat_id' => 114082814, 'text' => "PDO: {$e->getMessage()}"]);
}

fwrite($fp, $r . "\n");
fwrite($fp, "\n");
fclose($fp);
$dbh = null;*/

/*$bot = new \TelegramBot\Api\Client($token);

// команда для start
$bot->command('start', function ($message) use ($bot) {
    $answer = 'Добро пожаловать!';
    $bot->sendMessage($message->getChat()->getId(), $answer);
});

// команда для помощи
$bot->command('help', function ($message) use ($bot) {
    $answer = 'Команды:
/help - вывод справки';
    $bot->sendMessage($message->getChat()->getId(), $answer);
});

$bot->run();*/
/*$time = time();
if (isset($result['message']['audio']['file_id'])) {
    if (($time - $last_query['stamp']) > 20) {
        $res = $dbh->exec("UPDATE last_query set stamp={$time}, update_id={$result['update_id']};");
    } else {
        $n_attempt = 0;
        while ($result['update_id'] != ($last_query['update_id']+1) && $n_attempt < 30) {
            $n_attempt++;
            sleep(1);
            $last_query = $dbh->query("SELECT stamp, is_free, update_id from last_query;", PDO::FETCH_ASSOC)->fetch();
        }
        $res = $dbh->exec("UPDATE last_query set update_id={$result['update_id']};");
        //sleep($result['update_id'] - $last_query['update_id'] + 3);
    }
} else {
    $time = $time - 30;
    $res = $dbh->exec("UPDATE last_query set stamp={$time};");
}*/
