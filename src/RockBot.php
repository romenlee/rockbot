<?php
/**
 * Created by PhpStorm.
 * Date: 06.05.20
 * Time: 22:59
 * @package rockbot
 * @author  Roman Lihatskiy <rlihatskiy@determine.com>
 */

use Telegram\Bot\Exceptions\TelegramSDKException;
use InstaLite\InstaLite;

class RockBot {

    const BOT_CHAT = 114082814;
    const NEW_ROCK_CHAT = -1001173139890;
    //const NEW_ROCK_CHAT = -1001455135875;
    //const AUDIO_CHAT = -1001655871229;
    const AUDIO_CHAT = -1001652457722;
    //const AUDIO_CHAT_NAME = 'alternative_rock_metal';
    const AUDIO_CHAT_NAME = 'rock_alternative_metal';
    const VERSION_VK = '5.101';
    const GROUP_ID_VK = '48186614';//13109196

    private $fp;
    private $date;
    private $y;
    private $dbh;
    /**
     * @var \VK\Client\VKApiClient
     */
    private $vk;
    /**
     * @var Telegram\Bot\Api
     */
    private $telegram;
    private $result;
    private $chat_id;
    private $dev_channel = 'my_dev';
    private $post_channel = 'rock_albums';
    //private $audio_channel = 'new_rotsk';
    //private $audio_channel = '-1001655871229';
    private $audio_channel = '-1001311635101';
    private $audio_channel_reserve = '-1001652161100';
    private $allowed_chats = array(
        '114082814' => '114082814',//me
        '-1001455135875' => 'my develop chat',//my develop chat
        '-1001173139890' => 'new rock chat',//new rock chat
        '-1001488152998' => 'my dev channel',//my dev channel
        //'-1001655871229' => 'audio chat',//audio files channel
        self::AUDIO_CHAT => 'audio chat',//audio files channel
        '-1001348573922' => 'Канал Новые рок альбомы и клипы',//Канал Новые рок альбомы и клипы
    );
    private $currentPost;
    private $settings;
    //private $parser_link = 'https://newrockbot.herokuapp.com/';
    //private $parser_link = 'http://167.71.12.148/';
    private $parser_link = 'http://167.71.51.122/';
    private $music_resources = array(
        't.me' => array('name' => "🎸 СЛУШАТЬ ⏯", 'db_field' => 't_me', 'format' => "   "),
        'chat' => array('name' => "Chat", 'link' => 'https://t.me/rock_chat', 'format' => "   "),
        'insta' => array('name' => "Insta", 'link' => 'https://instagram.com/new_rock_albums', 'format' => "\n\n"),
        'spotify.com' => array('name' => 'Spotify', 'db_field' => 'spotify', 'parser_name' => 'spotify', 'image' => 1, 'format' => "  ♪  "),
        'music.apple' => array('name' => 'Apple music', 'db_field' => 'music_apple', 'parser_name' => 'apple', 'format' => "\n"),
        'music.youtube' => array('name' => 'YouTube music', 'db_field' => 'music_youtube', 'parser_name' => 'youtube', 'format' => "  ♪  "),
        //'music.yandex' => array('name' => 'Yandex music', 'db_field' => 'music_yandex', 'parser_name' => 'yandex', 'image' => 3, 'format' => "\n", 'default' => 'https://music.yandex.ru/search?text={search_text}&type=albums'),
        'deezer.com' => array('name' => 'Deezer', 'db_field' => 'deezer', 'parser_name' =>'deezer', 'image' => 2, 'format' => "\n"),
        'soundcloud.com' => array('name' => 'Soundcloud', 'db_field' => 'soundcloud', 'parser_name' => 'soundcloud', 'format' => "  ♪  ", 'default' => 'https://soundcloud.com/search?q={search_text}'),
        'vk.com' => array('name' => 'vk', 'link' => 'https://vk.com/novue_rock_albomu_2013', 'format' => "  ♪  "),
        'last.fm' => array('name' => 'last.fm', 'db_field' => 'last_fm', 'parser_name' => 'lastfm', 'format' => " ♪ ", 'default' => 'https://www.last.fm/search?q={search_text}'),

    );
    private $types = [
        'a' => 'album',
        'а' => 'album',
        'e' => 'EP',
        'c' => 'compilation',
        'с' => 'compilation',
        'la' => 'live album',
        's' => 'single',
        'l' => 'live',
        'v' => 'video',
        'lv' => 'live video',
        'lh' => 'home live',
        'nlv' => 'new live video',
        'ly' => 'lyric video',
        'li' => 'lyric video',
        'ls' => 'live single',
    ];

    public function __construct($type = '') {
        $this->date = date('Y-m-d H:i:s');
        $this->y = date('Y');
		try {
			$pdo_opt = array(PDO::ATTR_PERSISTENT => true, PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION);
			$this->dbh = new PDO('mysql:host=pixis.mysql.tools;dbname=pixis_rockbot;charset=utf8', 'pixis_rockbot', '1&sV08S@tt', $pdo_opt);
			$this->settings = $this->dbh->query('SELECT * from settings LIMIT 1;', PDO::FETCH_ASSOC)->fetch();
			if ($type === 'cron') {
				$this->executeCron();
			} elseif ($type === 'save_links') {
				$this->saveLinks();
            } elseif ($type === 'insta') {
                if ($this->settings['instagram_api_enabled']) {
                    $this->instagram();
                }
            } elseif ($type === 'queue') {
                $this->queueParse();
			} else {
				$this->execute();
			}
		} catch (TelegramSDKException $e) {
			$error_msg = $e->getMessage() . "\nFile: " . $e->getFile() . " Line: " . $e->getLine() . "\nTrace:\n" . $e->getTraceAsString() . "\n";
			if (!empty($this->result)) {
				$error_msg .= json_encode($this->result, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
			} else {
				$error_msg .= 'Result is empty';
			}
			if (!empty($this->telegram)) {
				$this->telegram->sendMessage(['chat_id' => self::BOT_CHAT, 'text' => $error_msg]);
			}
		} catch (PDOException $e) {
			$text = "PDO error:\n" .$e->getMessage() . "\nFile: " . $e->getFile() . " Line: " . $e->getLine() . "\nTrace:\n" . $e->getTraceAsString() . "\n";
			if (!empty($this->telegram)) {
				$this->telegram->sendMessage(['chat_id' => self::BOT_CHAT, 'text' => $text]);
			}
        } catch (\InstaLite\Exception $e) {
            $text = "Instagram:\n" .$e->getMessage() . "\nFile: " . $e->getFile() . " Line: " . $e->getLine() . "\nTrace:\n" . $e->getTraceAsString() . "\n";
            if (!empty($this->telegram)) {
                $this->telegram->sendMessage(['chat_id' => self::BOT_CHAT, 'text' => $text]);
            }
		}
        $this->dbh = null;
        if (!empty($this->fp)) {
            //fwrite($this->fp, "\n");
            fclose($this->fp);
        }
    }

    private function execute()
    {
		$this->telegram = new MyApi($this->settings['telegram_token']);
		$this->result = $this->telegram->getWebhookUpdates();

		if (!$this->checkUpdates()) {
			return;
		}

		if ($this->checkCallback()) {
			return;
		}
		if (!$this->checkMessage()) {
			return;
		}
		if ($this->processMessage()) {
			return;
		}
		if ($this->processAudio()) {
			return;
		}
		$this->processPhoto();
    }

    private function processMessage() {
        if (empty($this->result['message']['text'])) {
            return false;
        }
        $text = trim($this->result['message']['text']);
        $time_mask = '/^(\+*)(\d{1,2})\D?(\d{0,2})\s?(\d{0,2})\s?(\d{0,2})\s?(\d{0,2})$/i';

        if ($text == '/start') {
            $this->startCommand();
        } elseif ($text == '/delayed') {
            $this->getDelayedPosts();
        } elseif ($text == '/parser_on') {
            $this->dbh->exec('UPDATE settings set parser_enabled = 1;');
            $this->telegram->sendMessage(['chat_id' => $this->chat_id, 'text' => 'Parser enabled']);
        } elseif ($text == '/parser_off') {
            $this->dbh->exec('UPDATE settings set parser_enabled = 0;');
            $this->telegram->sendMessage(['chat_id' => $this->chat_id, 'text' => 'Parser disabled']);
        } elseif (mb_strpos($text, '/edit_') === 0) {
            $id = explode('_', $text);
            if (!empty($id[1])) {
                $this->dbh->exec("DELETE FROM post WHERE finished=0;");
                $this->dbh->exec("UPDATE post set finished = 0, posted_date = NULL, is_edit = 1 where id_post={$id[1]};");
                $this->telegram->sendMessage(['chat_id' => $this->chat_id, 'text' => "Edit post {$id[1]}"]);
            }
        } elseif (mb_strpos($text, '/delete_') === 0) {
            $id = explode('_', $text);
            if (!empty($id[1])) {
                $this->dbh->exec("DELETE FROM post WHERE id_post={$id[1]};");
                $this->telegram->sendMessage(['chat_id' => $this->chat_id, 'text' => "Deleted {$id[1]}"]);
            }
        } elseif (mb_strpos($text, '/all') === 0) {
            $links = explode("\n", str_replace('/all', '', $text));
            $date = date('Y-m-d 00:00:00');
            $msg = "$date\n";
            foreach ($links as $l) {
                $l = trim($l);
                $sep = mb_strpos($l, ' - ');
                if ($sep !== false) {
                    $artist = addcslashes(trim(mb_substr($l, 0, $sep)), "'");
                    $album = addcslashes(mb_substr($l, $sep + 3), "'");
                    $album = trim(str_replace(['=s', '=a', '=la', '=e', '=c', '=с'], '', $album));
                    $this->dbh->exec("INSERT INTO queue (artist, album, date)
                      VALUES('$artist', '$album', '$date')");
                    $msg .= "$artist - $album  ADDED\n";
                    continue;
                }
                $dates = array_filter(explode(' ', $l));
                if (empty($dates[0]) || !is_numeric($dates[0]) || empty($dates[1]) || !is_numeric($dates[1])) {
                    continue;
                }
                $parsed_time = strtotime("$dates[0].$dates[1].$this->y");
                if ($parsed_time) {
                    $date = date('Y-m-d 00:00:00', $parsed_time);
                    $msg .= "$date set\n";
                }
            }
            $this->telegram->sendMessage(['chat_id' => $this->chat_id, 'text' => $msg]);
        } else {//post commands
            $this->currentPost = $this->dbh->query('SELECT * from post WHERE finished=0 LIMIT 1;', PDO::FETCH_ASSOC)->fetch();
            $this->vk = new \VK\Client\VKApiClient(self::VERSION_VK);
            if (empty($this->currentPost)) {
                $this->telegram->sendMessage(['chat_id' => $this->chat_id, 'text' => 'In order to post press /start']);
                return true;
            }
            if ($text == '/c' || $text == '/с') {
                $this->cancelCommand(false);
                $this->startCommand();
            } elseif ($text == '/cancel') {
                $this->cancelCommand();
            } elseif ($text == '/parse_links') {
                //$this->parseLinks();
                $parser_link2 = $this->parser_link . 'find/' . rawurlencode($this->currentPost['artist']) . '/' . rawurlencode($this->currentPost['album']) . '?callback=1&flush=1&q=';
                foreach ($this->music_resources as $mr) {
                    if (!empty($mr['db_field']) && empty($this->currentPost[$mr['db_field']]) && !empty($mr['parser_name'])) {
                        $parser_link2 .= $mr['parser_name'] . ',';
                    }
                }
                $parser_link2 = rtrim($parser_link2, ',');
                $this->telegram->sendMessage(['chat_id' => $this->chat_id, 'text' => $parser_link2, 'disable_web_page_preview' => true]);
                $this->backgroundParser($parser_link2);
            } elseif ($text == '/p' || $text == '/post' || ($is_delay = preg_match($time_mask, $text, $matches))) {//post
                $is_delay = (!empty($is_delay));
                $matches = $matches ?? array();
                $this->postCommand($text, $matches, $is_delay);
            } elseif ($text == '/camel_artist') {
                $camelArtist = mb_convert_case($this->currentPost['artist'], MB_CASE_TITLE);
                $this->dbh->exec("UPDATE post set artist = '{$camelArtist}' where finished = 0;");
                $this->telegram->sendMessage(['chat_id' => $this->chat_id, 'text' => 'Camel Case Artist']);
            } elseif ($text == '/camel_album') {
                $camelAlbum = mb_convert_case($this->currentPost['album'], MB_CASE_TITLE);
                $this->dbh->exec("UPDATE post set album = '{$camelAlbum}' where finished = 0;");
                $this->telegram->sendMessage(['chat_id' => $this->chat_id, 'text' => 'Camel Case Album']);
            } elseif ($text == '/clear_audio') {
                $this->dbh->exec("DELETE FROM audio where post_id = {$this->currentPost['id_post']};");
                $this->telegram->sendMessage(['chat_id' => $this->chat_id, 'text' => 'Current audio is cleared']);
            } elseif (mb_strpos($text, '/sort ') === 0) {
                $order = (int) trim(str_replace('/sort ', '', $text));
                $this->dbh->exec("UPDATE post set sort = $order where finished=0;");
                $this->telegram->sendMessage(['chat_id' => $this->chat_id, 'text' => 'Sorted']);
            } elseif (mb_strpos($text, '/add ') === 0) {
                $add_txt = str_replace('/add ', '', $text);
                if (!empty($add_txt)) {
                    $add_txt = addcslashes($add_txt, "'");
                    $this->dbh->exec("UPDATE post set add_text = '{$add_txt}' where finished=0;");
                    $this->telegram->sendMessage(['chat_id' => $this->chat_id, 'text' => "Additional text was updated"]);
                }
            } elseif (mb_strpos($text, 'http') === 0) {
                $this->processLink($text);
            } elseif (mb_strpos($text, '#') === 0) {
                $this->processTag($text);
            } elseif (mb_strpos($text, '=') === 0) {
                $this->processType($text);
            } elseif (mb_strpos($text, '- ') === 0) {
                $this->processAlbum($text);
            } elseif (mb_strpos($text, '+') === 0) {
                $v_name = addcslashes(ltrim($text, '+'), "'");
                $this->dbh->exec("UPDATE post set video_name = '{$v_name}' where finished=0;");
                $this->telegram->sendMessage(['chat_id' => $this->chat_id, 'text' => "Video name was updated"]);
            } else {
                $this->parseText($text);
            }
        }
        return true;
    }

    private function parseLinks($parsed = array())
    {
        $sources = array();
        foreach ($this->music_resources as $key_mr => $mr) {
            if (!empty($mr['db_field']) && empty($this->currentPost[$mr['db_field']]) && !empty($mr['parser_name'])) {
                $sources[$key_mr] = $mr['parser_name'];
            }
        }
        if (!empty($sources)) {
            if (empty($parsed)) {
                $this->telegram->sendMessage(['chat_id' => $this->chat_id, 'text' => "Parsing (" . implode(', ', $sources) . ") ..."]);
                $parsed = $this->getParsedData($this->currentPost['artist'], $this->currentPost['album'], $sources);
            }
            if (!empty($parsed) && is_array($parsed)) {
                $parsed_images = array();
                $upd = array();
                $msg = '';
                $repeat = [];
                foreach ($sources as $k_s => $source) {
                    if (empty($parsed[$source])) {
                    	$msg .= "ERROR PARSING {$this->music_resources[$k_s]['name']} was not returned from parser\n\n";
                    }
                    if (!empty($parsed[$source]['error'])) {
						$msg .= "ERROR PARSING {$this->music_resources[$k_s]['name']}: {$parsed[$source]['error']}\n\n";
                        $repeat[$k_s] = $source;
                    }
                    if (!empty($parsed[$source]['link'])) {
                        $fld = $this->music_resources[$k_s]['db_field'];
                        $upd[$fld] = $parsed[$source]['link'];
						$msg .= "{$this->music_resources[$k_s]['name']} was parsed\n{$upd[$fld]}\n\n";
                    } elseif (!empty($this->music_resources[$k_s]['default'])
						&& ((!empty($parsed[$source]['error']) && strpos($parsed[$source]['error'], 'TimeoutError') === false ) || empty($parsed[$source]['error']))
					) {
						$fld = $this->music_resources[$k_s]['db_field'];
						$search_album = rawurlencode("{$this->currentPost['artist']} {$this->currentPost['album']}");
						$upd[$fld] = str_replace('{search_text}', $search_album, $this->music_resources[$k_s]['default']);
						$msg .= "DEFAULT: {$this->music_resources[$k_s]['name']} was set\n{$upd[$fld]}\n\n";
                        unset($repeat[$k_s]);
					}
                    if (!empty($this->music_resources[$k_s]['image']) && !empty($parsed[$source]['image']) && strpos($parsed[$source]['image'], 'http') !== false) {
                        $parsed_images[$this->music_resources[$k_s]['image']] = array(
                            'source_name' => $this->music_resources[$k_s]['name'],
                            'image' => $parsed[$source]['image'],
                        );
                    }
                }

                if ($parsed_images && empty($this->currentPost['media_link'])) {
                    ksort($parsed_images);
                    foreach ($parsed_images as $img) {
                        $upd['media_link'] = $this->getImageByLink($img['image']);
                        if (!empty($upd['media_link'])) {
							$msg .= "Image was parsed and copied from {$img['source_name']}\n";
                            break;
                        }
                    }
                }
                if (empty($upd['media_link']) && empty($this->currentPost['media_link'])) {
					$msg .= "THERE IS NO PARSED IMAGE!!!\n";
				}

                if (!empty($upd)) {
                    $upd_str = '';
                    foreach ($upd as $ku => $u) {
                        $u = addcslashes($u, "'");
                        $upd_str .= "{$ku}='{$u}', ";
                    }
                    $upd_str = rtrim($upd_str, ', ');
                    $this->dbh->exec("UPDATE post set {$upd_str} where finished = 0;");
                }
                if (!empty($repeat)) {
                    /*if ($this->settings['is_parser_repeat']) {
                        $parser_link2 = $this->parser_link . 'find/' . rawurlencode($this->currentPost['artist']) . '/' . rawurlencode($this->currentPost['album']) . '?callback=1&flush=1&q=';
                        $parser_link2 .= implode(',', $repeat);
                        $this->backgroundParser($parser_link2);
                        $msg .= "Repeat parser...\n$parser_link2";
                    } else {*/
                        $msg .= '/parse_links';
                    //}
                }
                if ($msg) {
                    $this->telegram->sendMessage(['chat_id' => $this->chat_id, 'text' => $msg, 'disable_web_page_preview' => true]);
                }
            } else {
                $this->telegram->sendMessage(['chat_id' => $this->chat_id, 'text' => "Parser returned error: {$parsed}"]);
            }
        } else {
            if (empty($parsed)) {
                $this->telegram->sendMessage(['chat_id' => $this->chat_id, 'text' => "There are no links to parse"]);
            }
        }
        //$this->dbh->exec('UPDATE settings set is_parser_repeat = 0;');
    }
    private function processLink($text)
    {
        $upd = $text;
        $reply = "updated";
        if (strpos($text, ' del') !== false) {
            $reply = "deleted";
            $upd = '';
        }
        $is_found = false;
        foreach ($this->music_resources as $key_mr => $mr) {
            if (strpos($text, $key_mr) !== false) {
                $this->dbh->exec("UPDATE post set {$mr['db_field']}='{$upd}' where finished = 0;");
                $this->telegram->sendMessage([
                    'chat_id' => $this->chat_id,
                    'text' => "Link {$mr['name']} was {$reply}",
                ]);
                $is_found = true;
                break;
            }
        }
        if (!$is_found) {
            $fld = 'media_link';
            $fld_name = 'Media';
            if (strpos($this->currentPost['media_link'], 'youtube.com/') === false
                && strpos($this->currentPost['media_link'], 'youtu.be/') === false
                && (strpos($text, 'youtube.com/') !== false || strpos($text, 'youtu.be/') !== false)
            ) {
                if (strpos($upd, 'youtu.be') !== false) {
                    $upd = 'https://www.youtube.com/watch?v=' . ltrim(parse_url($upd,   PHP_URL_PATH), '/');
                }
                $fld = 'video_link';
                $fld_name = 'Video';
            } elseif ($local_link = $this->getImageByLink($upd)) {
                $upd = $local_link;
            }
            $this->dbh->exec("UPDATE post set {$fld}='{$upd}' where finished = 0;");
            $this->telegram->sendMessage([
                'chat_id' => $this->chat_id,
                'text' => "{$fld_name} link was {$reply}",
            ]);
        }
    }

    private function processTag($text)
    {
        $this->dbh->exec("UPDATE post set hashtag='{$text}' where finished = 0;");
        $this->telegram->sendMessage(['chat_id' => $this->chat_id, 'text' => "Hashtags was updated",]);
    }

    private function processAlbum($text)
    {
        $album = addcslashes(mb_substr($text, 2), "'");
        $this->dbh->exec("UPDATE post set album='{$album}' where finished = 0;");
        $this->telegram->sendMessage(['chat_id' => $this->chat_id, 'text' => "Album was updated",]);
    }
    private function processType($text)
    {
        $fld = 'type_album';
        $fld_text = '';
        if (mb_strpos($text, '==') === 0) {
            $fld = 'type_video';
            $fld_text = 'video ';
        }
        $text = ltrim($text, '= ');
        if (isset($this->types[$text])) {
            $text = $this->types[$text];
        }
        $this->dbh->exec("UPDATE post set {$fld}='{$text}', strict_type=1 where finished = 0;");
        $this->telegram->sendMessage(['chat_id' => $this->chat_id, 'text' => "Type {$fld_text}was updated",]);
    }

    private function postCommand($text, $matches, $is_delay)
    {
        $post = $this->currentPost;
        if (empty($post['artist'])) {
            $this->telegram->sendMessage([
                'chat_id' => $this->chat_id,
                'text' => 'There is no any message for posting',
            ]);
            return;
        }
        $delay_date = '';
        if ($is_delay) {
            $delay_date = $this->getPostDate($text, $matches);
            if (empty($delay_date)) {
                return;
            }
        }

        $is_post = false;
        $audio_channel_id = $this->chat_id;
        $post_channel_id = $this->chat_id;
        $post['is_post'] = false;
        if (/*$text == '/post' || disable till correct link to audio group*/!empty($delay_date)) {
            $is_post = true;
            $post['is_post'] = true;
            $audio_channel_id = "{$this->audio_channel}";
            $post_channel_id = "@{$this->post_channel}";
            /*$audio_channel_id = "@{$this->dev_channel}";
            $post_channel_id = "@{$this->dev_channel}";*/
        }

        $old_type_album = $post['type_album'];
        if (empty($post['t_me'])) {
            $this->getPostAudio($post, $audio_channel_id);
        }

        if ($old_type_album != $post['type_album']) {
            $this->dbh->exec("UPDATE post set type_album='{$post['type_album']}' where id_post = {$post['id_post']};");
        }

        $post_text = $this->getPostText($post);

//todo $this->vkPost($post_text, $delay_date);
        //$this->telegram->sendMessage(['chat_id' => $this->chat_id, 'text' => $post_text['post_template'], 'disable_web_page_preview' => true, 'parse_mode' => 'HTML']);

        //if ($text != '/post') {
            if (!empty($post_text['post_video'])) {
                $this->telegram->sendMessage([
                    'chat_id' => $this->chat_id,
                    'text' => $post_text['post_video'],
                    'parse_mode' => 'HTML',
                ]);
            }
            $this->telegram->sendMessage([
                'chat_id' => $this->chat_id,
                'text' => $post_text['post_text'],
                'disable_web_page_preview' => (empty($post['media_link'])),
                'parse_mode' => 'HTML',
            ]);
        //}
        //$this->telegram->sendMessage(['chat_id' => $this->chat_id, 'text' => $post_text['post_vk_template'], 'disable_web_page_preview' => true]);

        if ($is_post) {
			if (empty($post['media_link']) && !empty($this->settings['forbid_post_without_image'])) {
				$this->telegram->sendMessage(['chat_id' => $this->chat_id, 'text' => 'THERE IS NO IMAGE OR VIDEO!!!']);
				return;
			}
            if (!empty($this->settings['vk_api_enabled']) && !$post['is_edit']) {
                $this->vkPost($post_text, $delay_date);
            }
            if (empty($delay_date)) {
                if (!empty($post_text['post_video'])) {
                    $this->telegram->sendMessage([
                        'chat_id' => $post_channel_id,
                        'text' => $post_text['post_video'],
                        'parse_mode' => 'HTML',
                    ]);
                }
                $this->telegram->sendMessage([
                    'chat_id' => $post_channel_id,
                    'text' => $post_text['post_text'],
                    'disable_web_page_preview' => (empty($post['media_link'])),
                    'parse_mode' => 'HTML',
                ]);
                $posted_date = $this->date;
                $posted = 1;
            } else {
                $human_date = date('G:i j F Y', strtotime($delay_date));
                $posted_date = $delay_date;
                $posted = 0;
                $this->telegram->sendMessage(['chat_id' => $this->chat_id, 'text' => "<b>$human_date</b> will be posted", 'parse_mode' => 'HTML']);
            }
            $this->dbh->exec("UPDATE post set posted_date='{$posted_date}', posted={$posted}, finished=1, is_edit=0 where finished = 0;");
            $this->dbh->exec("INSERT INTO post (finished) VALUES(0);");
            //$this->dbh->exec('UPDATE settings set is_parser_repeat = 0;');
            $this->telegram->sendMessage(['chat_id' => $this->chat_id, 'text' => 'New posting started. In order to cancel: /cancel']);
        }
    }

    private function vkPost($text, $delay_date) {
        $vk_params = array(
            'owner_id' => '-' . self::GROUP_ID_VK,
            'friends_only' => 0,
            'from_group' => 1,
            'message' => $text['post_vk_api'],
        );
        $vk_token = $this->settings['vk_token'];
        $is_link = false;
        if (strpos($this->currentPost['media_link'], "//{$_SERVER['HTTP_HOST']}/img/")) {
            $photo_server = $this->vk->photos()->getWallUploadServer($vk_token);
            $photo_path = ltrim(parse_url($this->currentPost['media_link'], PHP_URL_PATH), '/');
            $photo_upload = $this->vk->getRequest()->upload($photo_server['upload_url'], 'photo', $photo_path);
            $save_photo = $this->vk->photos()->saveWallPhoto($vk_token, array(
                'server' => $photo_upload['server'],
                'photo' => $photo_upload['photo'],
                'hash' => $photo_upload['hash'],
            ));
            if (!empty($save_photo[0]['owner_id']) && !empty($save_photo[0]['id'])) {
                $vk_params['attachments'] = "photo{$save_photo[0]['owner_id']}_{$save_photo[0]['id']}";
                $is_link = true;
            }
        } elseif (strpos($this->currentPost['media_link'], 'youtube.com/watch')) {
            $save_video = $this->vk->video()->save($vk_token, array(
                'group_id' => self::GROUP_ID_VK,
                'wallpost' => 0,
                'link' => $this->currentPost['media_link'],
            ));
            $video_post = json_decode(file_get_contents(rtrim($save_video['upload_url'])), true);
            if (!empty($save_video['owner_id']) && !empty($save_video['video_id']) && !empty($video_post['response'])) {
                $vk_params['attachments'] = "video{$save_video['owner_id']}_{$save_video['video_id']}";
            }
        } else {
            $vk_params['attachments'] = $this->currentPost['media_link'];
        }

        $this->clearMusic();
        $is_copy = $this->copyAudio();
        if ($is_copy && !empty($vk_params['attachments'])) {
            $this->telegram->sendMessage(['chat_id' => $this->chat_id, 'text' => "Upload to VK..."]);
            $audiosVk = $this->uploadVkAudios();
            sleep(3);
            if (!empty($audiosVk)) {
                $vk_params['attachments'] .=  ',' . implode(',', $audiosVk);
            }
        } elseif ($is_link) {
            $vk_params['attachments'] .= ',https://t.me/rock_albums';
        }

        $publish_date = time();
        if (!empty($delay_date)) {
            $publish_date = strtotime($delay_date);
            $same_date_post = $this->dbh->query("SELECT count(*) as cnt from post WHERE posted_date='{$delay_date}';", PDO::FETCH_ASSOC)->fetch();
            $same_date_video = $this->dbh->query("SELECT count(*) as cnt from post WHERE posted_date='{$delay_date}'  AND video_link != '';", PDO::FETCH_ASSOC)->fetch();
            $same_date = $same_date_post['cnt'] + $same_date_video['cnt'];
            if (!empty($same_date)) {
                $time = 61 * $same_date;
                $publish_date = strtotime("+{$time} minutes", $publish_date);
            }
            $vk_params['publish_date'] = $publish_date;
        }
        $post = $this->vk->wall()->post($vk_token, $vk_params);

        if (!empty($post)) {
            if (!empty($vk_params['publish_date'])) {
                $human_date = date('G:i j F Y', $vk_params['publish_date']);
                $this->telegram->sendMessage(['chat_id' => $this->chat_id, 'text' => "VK post is at <b>$human_date</b>", 'parse_mode' => 'HTML']);
            } else {
                $this->telegram->sendMessage(['chat_id' => $this->chat_id, 'text' => "VK posted"]);
            }
        } else {
            $this->telegram->sendMessage(['chat_id' => $this->chat_id, 'text' => "ERROR VK posting"]);
        }

        if (!empty($text['post_vk_api_video'])) {
            $save_video = $this->vk->video()->save($vk_token, array(
                'group_id' => self::GROUP_ID_VK,
                'wallpost' => 0,
                'link' => $this->currentPost['video_link'],
            ));
            $video_post = json_decode(file_get_contents(rtrim($save_video['upload_url'])), true);
            $vk_params = array(
                'owner_id' => '-' . self::GROUP_ID_VK,
                'friends_only' => 0,
                'from_group' => 1,
                'message' => $text['post_vk_api_video'],
                'publish_date' => strtotime("+61 minutes", $publish_date)
            );
            if (!empty($save_video['owner_id']) && !empty($save_video['video_id']) && !empty($video_post['response'])) {
                $vk_params['attachments'] = "video{$save_video['owner_id']}_{$save_video['video_id']}";
            }
            $post = $this->vk->wall()->post($vk_token, $vk_params);
            if (!empty($post)) {
                $human_date = date('G:i j F Y', $vk_params['publish_date']);
                $this->telegram->sendMessage(['chat_id' => $this->chat_id, 'text' => "VK VIDEO post is at <b>$human_date</b>", 'parse_mode' => 'HTML']);
            } else {
                $this->telegram->sendMessage(['chat_id' => $this->chat_id, 'text' => "ERROR VK VIDEO posting"]);
            }
        }
    }

    private function uploadVkAudios()
    {
        $ret = array();
        $a = "https://api.vk.com/method/audio.getUploadServer?&access_token={$this->settings['vk_token']}&v=" . self::VERSION_VK;
        $uploadAudioServer = json_decode(file_get_contents($a), true);

        if (empty($uploadAudioServer) || empty($uploadAudioServer['response']['upload_url'])) {
            return $ret;
        }

        $audioFiles = glob('music/*');

        if (empty($audioFiles)) {
            return $ret;
        }

        $i = 0;
        foreach ($audioFiles as $file) {
            $ext_arr = explode(".", $file);
            $ext = end($ext_arr);
            if ($ext != 'mp3' && $ext != 'mpeg' && $ext != 'mpg') {
                $this->telegram->sendMessage(['chat_id' => $this->chat_id, 'text' => "{$file} is skipped because of not mp3"]);
                continue;
            }
            $audioUpload = $this->vk->getRequest()->upload($uploadAudioServer['response']['upload_url'], 'file', $file);
            if (empty($audioUpload['server']) || empty($audioUpload['audio']) || empty($audioUpload['hash'])) {
                continue;
            }
            $vk_params = array(
                'server' => $audioUpload['server'],
                'audio' => $audioUpload['audio'],
                'hash' => $audioUpload['hash'],
                'v' => self::VERSION_VK,
                'access_token' => $this->settings['vk_token'],
            );
            $audioName = explode('--', $file);
            if (!empty($audioName[1])) {
                $vk_params['artist'] = $audioName[1];
            }
            if (!empty($audioName[2])) {
                $vk_params['title'] = $audioName[2];
            }

            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, 'https://api.vk.com/method/audio.save');
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $vk_params);
            $responseAudio = curl_exec($ch);
            curl_close($ch);
            $audioResult = json_decode($responseAudio, true);
            if (!empty($audioResult) && !empty($audioResult['response']['owner_id']) && !empty($audioResult['response']['id'])) {
                $ret[] = "audio{$audioResult['response']['owner_id']}_{$audioResult['response']['id']}";
            }
            $i++;
            if ($i >= 9) {
                break;
            }
        }
        return $ret;
    }
    private function getPostDate($text, $matches)
    {
        $ret = '';
        if (empty($text) || empty($matches)) {
            return $ret;
        }
        $add_day = ($matches[1] == '+');
        if ($add_day) {
            $text = str_replace('+', '', $text);
        }

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
        if (!is_numeric($hour) || intval($hour) > 23 || intval($minute) > 59 || intval($day) > 31 || intval($month) > 12 || empty($month) || strtotime($parsed_date) == FALSE) {
            $this->telegram->sendMessage(['chat_id' => $this->chat_id, 'text' => "Incorrect time: $parsed_date"]);
        } else {
            $parsed_time = strtotime($parsed_date);
            if ($parsed_time <= time()) {
                $parsed_time = strtotime('+1 day', $parsed_time);
            }
            if ($add_day) {
                $parsed_time = strtotime('+1 day', $parsed_time);
            }

            if ($parsed_time < time()) {
                $this->telegram->sendMessage(['chat_id' => $this->chat_id, 'text' => "Incorrect time: It's the past!"]);
            } else {
                $ret = date('Y-m-d H:i:00', $parsed_time);
            }
        }
        return $ret;
    }

    private function getParsedData($artist = '', $album = '', $sources = array())
    {
        $result = array();
        $link = $this->parser_link;
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
                $result = 'Empty result';
            } elseif (!isset($decoded)) {
                $result = 'Result is not object';
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

    private function parseText($text)
    {
        $albumStartPos = mb_strpos($text, ' - ');
        if ($albumStartPos) {
            $artistLength = null;
            $symbols_tag_replace = array(' ', '#', '  ', '-', '&', "'", '"', '/', '__');
            $tag = '';
            $type = 'single';
            $media_link = '';
            $strict_type = 0;
            $albumStartPos += 3;
            $albumLength = null;
            $typeLength = null;
            $tagLength = null;
            $artistLength = $albumStartPos - 3;
            $post = $this->currentPost;

            $typeStartPos = mb_strpos($text, ' =', $albumStartPos);
            $tagOffset = $albumStartPos;
            $linkOffset = $albumStartPos;
            if ($typeStartPos) {
                $tagOffset = $typeStartPos;
                $linkOffset = $typeStartPos;
            }
            $tagStartPos = mb_strpos($text, '#', $tagOffset);
            if ($tagStartPos) {
                $linkOffset = $tagStartPos;
            }
            $httpsPos = mb_strpos($text, 'https://', $linkOffset);
            $httpPos = mb_strpos($text, 'http://', $linkOffset);
            $linkStartPos = ($httpsPos !== false && ($httpPos === false || $httpsPos < $httpPos)) ? $httpsPos : $httpPos;

            if ($typeStartPos) {
                $albumLength = $typeStartPos - $albumStartPos;
            } elseif ($tagStartPos) {
                $albumLength = $tagStartPos - $albumStartPos;
            } elseif ($linkStartPos) {
                $albumLength = $linkStartPos - $albumStartPos;
            }

            if ($typeStartPos) {
                $typeStartPos += 2;
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
                if (isset($this->types[$typeKey])) {
                    $type = $this->types[$typeKey];
                    $strict_type = 1;
                }
            }
            if ($tagStartPos) {
                $tag = trim(mb_substr($text, $tagStartPos, $tagLength));
                $tags = explode('#', $tag);
                $tag = '';
                foreach ($tags as $t) {
                    $t = trim($t);
                    if (empty($t)) continue;
                    $tag .= '#' . str_replace($symbols_tag_replace, '_', $t) . ' ';
                }
                $tag = rtrim($tag);
            }

            $artist = trim(mb_substr($text, 0, $artistLength));
            $album = trim(mb_substr($text, $albumStartPos, $albumLength));

            $addArtistPos = mb_strpos($artist, '(');
            $add_artist = '';
            if ($addArtistPos) {
                $add_artist = ' ' . ltrim(mb_substr($artist, $addArtistPos));
                $artist = rtrim(mb_substr($artist, 0, $addArtistPos));
            }

            $is_audio = true;
            if ($linkStartPos) {
                $links = trim(mb_substr($text, $linkStartPos));
                $links_array = array();
                $lnkStartPos = 0;
                $i = 0;
                do {
                    if ($i++ > 9) break;
                    $httpsPos = mb_strpos($links, 'https://', $lnkStartPos + 7);
                    $httpPos = mb_strpos($links, 'http://', $lnkStartPos + 7);
                    $lnkOffset = ($httpsPos !== false && ($httpPos === false || $httpsPos < $httpPos)) ? $httpsPos : $httpPos;
                    $lnkLength = null;
                    if ($lnkOffset !== false) {
                        $lnkLength = $lnkOffset - $lnkStartPos;
                    }
                    $links_array[] = trim(mb_substr($links, $lnkStartPos, $lnkLength));
                    $lnkStartPos = $lnkOffset;
                } while (isset($lnkLength));

                $is_found_music_link = false;
                foreach ($links_array as $l) {
                    $is_found_music_link = false;
                    $domain = parse_url($l,  PHP_URL_HOST);
                    foreach ($this->music_resources as $key_mr => $mr) {
                        if (strpos($domain, $key_mr) !== false) {
                            $this->music_resources[$key_mr]['link'] = $l;
                            $is_found_music_link = true;
                            break;
                        }
                    }
                    if (!$is_found_music_link) {
                        $media_link = $l;
                    }
                }

                if (!empty($media_link) && (strpos($media_link, 'youtube.com/') !== false || strpos($media_link, 'youtu.be/') !== false)) {
                    $is_audio = false;
                    if (!$strict_type) {
                        $type = 'video';
                    }
                    if (strpos($media_link, 'youtu.be') !== false) {
                        $media_link = 'https://www.youtube.com/watch?v=' . ltrim(parse_url($media_link,   PHP_URL_PATH), '/');
                    }
                }
            }

            foreach ($this->music_resources as $key_mr => $mr) {
                if (empty($mr['link']) && !empty($mr['db_field']) && !empty($post[$mr['db_field']])) {
                    $this->music_resources[$key_mr]['link'] = $post[$mr['db_field']];
                }
            }

            $artist_db = addcslashes($artist, "'");
            $info_artist = $this->dbh->query("SELECT * from artist WHERE artist_name='{$artist_db}' LIMIT 1;", PDO::FETCH_ASSOC)->fetch();
            if (empty($tag)) {
                if (!empty($info_artist)) {
                    $tag = $info_artist['hashtag'];
                }
            } else {
                $tag .= ' #' . str_replace($symbols_tag_replace, '_', $artist);
                $tag = str_replace(array('!', '?', '.', ','), '', $tag);
                if (empty($info_artist)) {
                    $this->dbh->exec("INSERT INTO artist (artist_name, hashtag)
                          VALUES('{$artist_db}', '{$tag}')");
                } else {
                    $this->telegram->sendMessage(['chat_id' => $this->chat_id, 'text' => 'У исполнителя есть сохраненные хештеги, рекомендовано использовать их, кроме уникальных случаев']);
                }
            }

            if (empty($media_link) && !empty($post['media_link'])) {
                $media_link = $post['media_link'];
            }

            $upd = array(
                'type_album' => $type,
                'strict_type' => $strict_type,
                'artist' => $artist,
                'add_artist' => $add_artist,
                'album' => $album,
                'hashtag' => $tag,
                'media_link' => $media_link,
            );
            foreach ($this->music_resources as $mr) {
                if (empty($mr['db_field']) || empty($mr['link'])) continue;
                $upd[$mr['db_field']] = $mr['link'];
            }

			$reply = $this->getPostText($upd);
            $upd_str = '';
            foreach ($upd as $ku => $u) {
                if (!is_numeric($u)) {
                    $u = addcslashes($u, "'");
                    $u = "'{$u}'";
                }
                $upd_str .= "$ku=$u, ";
            }
            $upd_str = rtrim($upd_str, ', ');

            $this->dbh->exec("UPDATE post set {$upd_str} where finished = 0;");
            $this->telegram->sendMessage(['chat_id' => $this->chat_id, 'parse_mode' => 'HTML', 'text' => $reply['post_template'], 'disable_web_page_preview' => true]);
            if ($is_audio) {
                $parser_link2 = $this->parser_link . 'find/' . rawurlencode($artist) . '/' . rawurlencode($album) . '?callback=1&flush=1&q=';
                foreach ($this->music_resources as $mr) {
                    if (!empty($mr['db_field']) && empty($post[$mr['db_field']]) && !empty($mr['parser_name'])) {
                        $parser_link2 .= $mr['parser_name'] . ',';
                    }
                }
                $parser_link2 = rtrim($parser_link2, ',');
                $this->telegram->sendMessage(['chat_id' => $this->chat_id, 'text' => "{$parser_link2}\nWait a minute for response\n/parse_links", 'disable_web_page_preview' => true]);
                if (!empty($this->settings['parser_enabled'])) {
                    //$this->dbh->exec('UPDATE settings set is_parser_repeat = 1;');
                    $this->backgroundParser($parser_link2);
                }
            }
        } else {
            //$s = mb_chr(8203);$s_lnk = "[$s$s](https://rockbot.pixis.com.ua/img/1588236274.jpg)";
            $artist = $text;
            $addArtistPos = mb_strpos($artist, '(');
            $add_artist = '';
            $upd_str = '';
            if ($addArtistPos !== FALSE) {
                $add_artist = ' ' . addcslashes(ltrim(mb_substr($artist, $addArtistPos)), "'");
                $artist = rtrim(mb_substr($artist, 0, $addArtistPos));
            }

            $upd_names = array();
            if (!empty($artist)) {
                $artist_db = addcslashes($artist, "'");
                $upd_str = "artist = '{$artist_db}', ";
                $upd_names[] = 'Artist';
            }
            if (!empty($add_artist)) {
                $upd_str .= "add_artist = '{$add_artist}'";
                $upd_names[] = 'Additional artist';
            }
            $upd_str = rtrim($upd_str, ', ');

            $this->dbh->exec("UPDATE post set $upd_str where finished = 0;");
            $r = $this->telegram->sendMessage(['chat_id' => $this->chat_id, 'text' => implode(' and ', $upd_names) . ' were updated', 'disable_web_page_preview' => false]);
            //fwrite($this->fp, json_encode($r, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) . "\n");
        }
    }

    private function backgroundParser($link) {
        $callParser = curl_init();
        curl_setopt($callParser, CURLOPT_URL, $link);
        curl_setopt($callParser, CURLOPT_TIMEOUT, 1);
        curl_setopt($callParser, CURLOPT_CONNECTTIMEOUT, 5);
        curl_setopt($callParser, CURLOPT_RETURNTRANSFER, 1);
        curl_exec($callParser);
        curl_close($callParser);
    }

    private function startCommand()
    {
        $res = $this->dbh->query('SELECT * from post WHERE finished=0;', PDO::FETCH_ASSOC)->fetch();
        if (empty($res)) {
            $reply = 'New posting started';
            $this->dbh->exec("INSERT INTO post (finished) VALUES(0);");
        } else {
            $reply = 'The post is not finished!!!';
        }
        $this->telegram->sendMessage(['chat_id' => $this->chat_id, 'text' => $reply]);
    }

    private function cancelCommand($is_msg = true)
    {
        $this->dbh->exec("DELETE FROM audio WHERE post_id={$this->currentPost['id_post']};");
        $this->dbh->exec("DELETE FROM post WHERE finished=0;");
        //$this->dbh->exec('UPDATE settings set is_parser_repeat = 0;');
        if ($is_msg) {
            $this->telegram->sendMessage(['chat_id' => $this->chat_id, 'text' => 'Canceled. Press /start']);
        }
    }

    private function processAudio()
    {
        if (!isset($this->result['message']['audio']['file_id'])) {
            return false;
        }
        $this->dbh->exec("LOCK TABLES post READ, audio WRITE;");
        $post = $this->dbh->query('SELECT * from post WHERE finished=0;', PDO::FETCH_ASSOC)->fetch();
        if (!empty($post)) {
            $last_audio = $this->dbh->query("SELECT orderby from audio WHERE post_id={$post['id_post']} ORDER BY orderby DESC LIMIT 1;", PDO::FETCH_ASSOC)->fetch();
            $orderby = 1;
            if (!empty($last_audio)) {
                $orderby = $last_audio['orderby'] + 1;
            }
            $file = addcslashes($this->result['message']['audio']['file_id'], "'");
            $song_title = addcslashes($this->result['message']['audio']['title'] ?? '', "'");
            $song_artist = addcslashes($this->result['message']['audio']['performer'] ?? '', "'");
            $msg_id = !empty($this->result['message']['message_id']) ? $this->result['message']['message_id'] : '';
            $this->dbh->exec("INSERT INTO audio (post_id, file, orderby, artist, title, id_message)
                          VALUES({$post['id_post']}, '{$file}', {$orderby}, '{$song_artist}', '{$song_title}', $msg_id)");
            $this->dbh->exec("UNLOCK TABLES;");
            if ($this->result['message']['audio']['mime_type'] != 'audio/mp3' && $this->result['message']['audio']['mime_type'] != 'audio/mpeg') {
                $this->telegram->sendMessage(['chat_id' => $this->chat_id, 'text' => "NOT MP3 ({$this->result['message']['audio']['mime_type']})"]);
            }
        }
        return true;
    }

    private function clearMusic() {
        if (file_exists('music/')) {
            foreach (glob('music/*') as $file) {
                unlink($file);
            }
        }
    }

    private function copyAudio()
    {
        if (empty($this->currentPost)) {
            return false;
        }
        $post = $this->currentPost;
        $audios = $this->dbh->query("SELECT id_audio, artist, title, file, id_message from audio WHERE post_id={$post['id_post']} ORDER BY id_message;", PDO::FETCH_ASSOC)->fetchAll();
        if (empty($audios) || count($audios) > $this->settings['vk_audio_upload_limit']) {
            return false;
        }

        foreach ($audios as $audio) {
            $file = $this->telegram->getFile(['file_id' =>$audio['file']]);
            $file_from_tgrm = "https://api.telegram.org/file/bot{$this->settings['telegram_token']}/{$file['file_path']}";
            $ext_arr = explode(".", $file['file_path']);
            $ext = end($ext_arr);
            $name_our_new_file =  "{$audio['id_message']}--{$audio['artist']}--{$audio['title']}--";
            if (!copy($file_from_tgrm, "music/{$name_our_new_file}.{$ext}")) {
                $this->telegram->sendMessage(['chat_id' => $this->chat_id, 'text' => "AUDIO COPY ERROR"]);
            }
        }
        $this->telegram->sendMessage(['chat_id' => $this->chat_id, 'text' => "Audios copying was finished"]);
        return true;
    }

    private function getImageByLink($link)
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
            $this->telegram->sendMessage(['chat_id' => $this->chat_id, 'text' => 'Не удалось получить контент картинки']);
            return $ret;
        }
        $finfo = new finfo(FILEINFO_MIME_TYPE);
        $meme = $finfo->buffer($buffer);
        if (empty($meme) || !isset($img_memes[$meme])) {
            $this->telegram->sendMessage(['chat_id' => $this->chat_id, 'text' => "Не верный MEME-тип картинки: $meme"]);
            return $ret;
        }

        $name_our_new_file = time() . '.' . $img_memes[$meme];
        if (copy($link, "img/".$name_our_new_file)) {
            $ret = "https://{$_SERVER['HTTP_HOST']}/img/$name_our_new_file";
        } else {
            $this->telegram->sendMessage(['chat_id' => $this->chat_id, 'text' => "Не удалось скопировать картинку на сервер"]);
        }
        return $ret;
    }

    private function processPhoto()
    {
        if (empty($this->result['message']['photo'])) {
            return false;
        }
        $photo = $this->result['message']['photo'];
        $file = $this->telegram->getFile(['file_id' =>$photo[count($photo) - 1]['file_id']]);
        $file_from_tgrm = "https://api.telegram.org/file/bot{$this->settings['telegram_token']}/{$file['file_path']}";
        // достаем расширение файла
        $ext_arr = explode(".", $file['file_path']);
        $ext = end($ext_arr);
        // назначаем свое имя здесь время_в_секундах.расширение_файла
        $name_our_new_file = time().".".$ext;
        if (copy($file_from_tgrm, "img/".$name_our_new_file)) {
            $img_link = "https://{$_SERVER['HTTP_HOST']}/img/$name_our_new_file";
            $res = $this->dbh->exec("UPDATE post set media_link='{$img_link}' where finished = 0;");
            if ($res) {
                $this->telegram->sendMessage(['chat_id' => $this->chat_id, 'text' => "Image was added"]);
            }
        } else {
            $this->telegram->sendMessage(['chat_id' => $this->chat_id, 'text' => "IMAGE COPY ERROR"]);
        }
        return true;
    }

    private function checkUpdates()
    {
		if (empty($this->result['update_id'])) {
			//$this->telegram->sendMessage(['chat_id' => self::BOT_CHAT, 'text' => 'Скорей всего доступ по прямой ссылке']);
			header('HTTP/1.0 403 Forbidden');
			echo 'You are forbidden!';
			return false;
		}
        $ret = true;
		$this->fp = fopen('log.txt', 'at');
		fwrite($this->fp, "{$this->date} ");
		//remove JSON_PRETTY_PRINT
		fwrite($this->fp, json_encode($this->result, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) . "\n");
		$ignoredActions = [
			'channel_post',// пост в канале
			'edited_channel_post',//редактирование поста в канале
			'edited_message',//редактирование сообщения
			'my_chat_member',// действие с участником чата
			'chat_member',// действие с участником чата
			'poll',//опросы
			'poll_answer',//опросы
			'chosen_inline_result',
		];
		foreach ($ignoredActions as $act) {
			if (isset($this->result[$act])) {
				$ret = false;
				break;
			}
		}

        return $ret;
    }

    private function checkCallback()
    {
        if (empty($this->result['callback_query']['id'])) {
            return false;
        }
        $ret = true;
        if (empty($this->result['callback_query']['message']['reply_markup']['inline_keyboard'])) {
            $this->telegram->sendAnyRequest('answerCallbackQuery', [
                'callback_query_id' => $this->result['callback_query']['id'],
                'text' => "ЛАЙКАТЬ можно только в КАНАЛЕ",
            ]);
            return $ret;
        }

        if (!empty($this->result['callback_query']['data'])
            && !empty($this->result['callback_query']['message']['reply_markup']['inline_keyboard'])
            && !empty($this->result['callback_query']['from']['id'])
        ) {
            if (empty($this->result['callback_query']['message']['forward_from_message_id']) || empty($this->result['callback_query']['message']['forward_from_chat']['id'])) {
                $chat_id = $this->result['callback_query']['message']['chat']['id'];
                $msg_id = $this->result['callback_query']['message']['message_id'];
            } else {
                $chat_id = $this->result['callback_query']['message']['forward_from_chat']['id'];
                $msg_id = $this->result['callback_query']['message']['forward_from_message_id'];
            }

            $user_id = $this->result['callback_query']['from']['id'];
            $data = json_decode($this->result['callback_query']['data'], true);
            $lineButtonsKey = 0;
            foreach ($this->result['callback_query']['message']['reply_markup']['inline_keyboard'] as $k => $lineButtons) {
                foreach ($lineButtons as $btn) {
                    if (!empty($btn['callback_data'])) {
                        $current_likes = $lineButtons;
                        $lineButtonsKey = $k;
                        break 2;
                    }
                }
            }

            if (empty($current_likes)) {
                $this->telegram->sendMessage(['chat_id' => self::BOT_CHAT, 'text' => "There is no likes in the callback {$this->result['callback_query']['id']}"]);
                fwrite($this->fp, "There is no likes in the callback {$this->result['callback_query']['id']}\n");
                return $ret;
            }

            $res = $this->dbh->query("SELECT * from likes WHERE user_id='{$user_id}' AND message_id='{$msg_id}' AND chat_id='{$chat_id}';", PDO::FETCH_ASSOC)->fetch();
            $is_allow = true;
            if (!empty($res)) {
                $key_like = $res['key_like'];
                if ((strtotime($this->date) - strtotime($res['last_date'])) < 10 && $res['count_try'] > 2) {
                    $is_allow = false;
                }
            } else {
                $key_like = '';
            }

            if ($is_allow) {
                $is_add = true;
                foreach ($current_likes as $cur_key => $cur_value) {
                    $data_for = json_decode($cur_value['callback_data'], true);
                    if ($data['action'] == $data_for['action']) {
                        if ($data['action'] == $key_like) {
                            $data_for['count']--;
                            $is_add = false;
                        } else {
                            $data_for['count']++;
                        }
                    }  elseif ($data_for['action'] == $key_like) {
                        $data_for['count']--;
                    }
                    $cur_value['text'] = $data_for['text'];
                    if ($data_for['count'] != 0) {
                        $cur_value['text'] .= " " . $data_for['count'];
                    }
                    $cur_value['callback_data'] = json_encode($data_for);
                    $current_likes[$cur_key] = $cur_value;
                }

                if (!empty($res)) {
                    $new_key = ($data['action'] != $key_like) ? $data['action'] : '';
                    $res['count_try']++;
                    $this->dbh->exec("UPDATE likes set 
                            key_like='{$new_key}', 
                            last_date='{$this->date}', 
                            count_try='{$res['count_try']}' 
                     where user_id='{$user_id}' AND message_id='{$msg_id}' AND chat_id='{$chat_id}';");
                } else {
                    $this->dbh->exec("INSERT INTO likes (message_id, user_id, key_like, chat_id)
                                             VALUES('{$msg_id}', '{$user_id}', '{$data['action']}', '{$chat_id}')");
                }

                $reply_markup = json_encode(['inline_keyboard' => [$current_likes]]);
                if ($this->result['callback_query']['message']['reply_markup']['inline_keyboard'][$lineButtonsKey] != $current_likes) {
                    $answer = "Да прибудет с вами ROCK {$data['text']}";
                    if ($is_add) {
                        if ($data['action'] == 'dislike') {
                            $answer = "Це мерзость {$data['text']}";
                        }
                    } else {
                        if ($data['action'] == 'dislike') {
                            $answer = "Це не такая уж мерзость";
                        } else {
                            $answer = "Так сабе ROCK";
                        }
                    }
                    $this->telegram->sendAnyRequest('editMessageReplyMarkup', ['chat_id' => $chat_id, 'message_id' => $msg_id, 'reply_markup' => $reply_markup]);
                    $this->telegram->sendAnyRequest('answerCallbackQuery', ['callback_query_id' => $this->result['callback_query']['id'], 'text' => $answer,]);
                } else {
                    $this->telegram->sendAnyRequest('answerCallbackQuery', ['callback_query_id' => $this->result['callback_query']['id'], 'text' => "Попробуйте позже.", 'show_alert' => true,]);
                }
            } else {
                $this->telegram->sendAnyRequest('answerCallbackQuery', [
                    'callback_query_id' => $this->result['callback_query']['id'],
                    'text' => "Вы слишком сильно облизываете этот пост, он здесь не один.\n\nПопробуйте через несколько секунд.",
                    'show_alert' => true,
                ]);
            }
        } else {
            $this->telegram->sendMessage(['chat_id' => self::BOT_CHAT, 'text' => 'Unknown callback error']);
            fwrite($this->fp, "Unknown callback error\n");
        }
        return $ret;
    }

    private function checkMessage() {
        if (empty($this->result['message']['chat']['id'])) {
            $this->telegram->sendMessage(['chat_id' => self::BOT_CHAT, 'text' => "Неизвестное действие \n\n" . json_encode($this->result, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE)]);
            return false;
        }

        $this->chat_id = $this->result['message']['chat']['id'];
        if ($this->chat_id != self::BOT_CHAT) {
            if (!isset($this->allowed_chats[$this->chat_id])) {
                $this->telegram->sendMessage(['chat_id' => self::BOT_CHAT, 'text' => "Доступ с левого чата!\n" . json_encode($this->result, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE),]);
            }// else $this->telegram->sendMessage(['chat_id' => self::BOT_CHAT, 'text' => "Доступ с: {$this->allowed_chats[$this->chat_id]}",]);

			if ($this->chat_id == self::NEW_ROCK_CHAT) {
				$this->processChatMessage();
			}
            if ($this->chat_id == self::AUDIO_CHAT) {
                $this->processAudioChatMessage();
            }
            return false;
        }

        if (empty($this->result['message']['text']) && empty($this->result['message']['photo']) && empty($this->result['message']['audio']['file_id'])) {
            $this->telegram->sendMessage(['chat_id' => $this->chat_id, 'text' => 'Можно отправлять только текст, аудио и картинку']);
            return false;
        }
        return true;
    }

    private function processChatMessage()
	{
		//777000 - messages automatically forwarded to the discussion group
		if (empty($this->result['message']['text']) || empty($this->result['message']['message_id']) || !empty($this->result['message']['entities'])
			|| empty($this->result['message']['from']['id']) || $this->result['message']['from']['id'] != 777000) {
			return;
		}
		$text = trim($this->result['message']['text']);
		if (mb_strlen($text) > 100 || mb_substr_count($text, ' - ') != 1)
			return;
		$this->telegram->sendAnyRequest('deleteMessage', ['chat_id' => $this->chat_id, 'message_id' => $this->result['message']['message_id']]);
	}

    private function processAudioChatMessage()
    {
        if (!empty($this->result['message']['left_chat_member'])
            || !empty($this->result['message']['new_chat_members'])
            || !empty($this->result['message']['new_chat_member'])
            || !empty($this->result['message']['new_chat_title'])
            || !empty($this->result['message']['new_chat_photo'])
        ) {
            $this->telegram->sendAnyRequest('deleteMessage', ['chat_id' => $this->chat_id, 'message_id' => $this->result['message']['message_id']]);
            return;
        }
        //777000 - messages automatically forwarded to the discussion group
        if (empty($this->result['message']['from']['id']) || $this->result['message']['from']['id'] != 777000
            || empty($this->result['message']['forward_from_message_id'])
        ) {
            return;
        }
        $post = $this->dbh->query("SELECT id_post from post WHERE msg_audio_id={$this->result['message']['forward_from_message_id']} LIMIT 1;", PDO::FETCH_ASSOC)->fetch();
        if (empty($post['id_post'])) {
            return;
        }
        $a_link = self::AUDIO_CHAT_NAME;
        $new_link = "https://t.me/{$a_link}/{$this->result['message']['message_id']}";;
        $this->dbh->exec("UPDATE post set t_me='$new_link' where id_post = {$post['id_post']};");

        $r = $this->telegram->sendMessage(['chat_id' => self::BOT_CHAT, 'text' => 'Updated audio link']);
    }

    private function getPostAudio(&$postData, $audio_channel_id)
    {
        $audio_msg_id = 0;
        $a_link = 'https://t.me/' . str_replace('-100', 'c/', $this->audio_channel) . '/';
        $audios = $this->dbh->query("SELECT * from audio WHERE post_id='{$postData['id_post']}' ORDER BY id_message;", PDO::FETCH_ASSOC)->fetchAll();
        if (!empty($audios)) {
            $caption = '@rock_albums';
            $cAudios = count($audios);
            if ($cAudios > 2) {
                if ($postData['strict_type'] == 0) {
                    if ($cAudios > 7) {
                        $postData['type_album'] = 'album';
                    } elseif ($cAudios > 3) {
                        $postData['type_album'] = 'EP';
                    }
                }
                $r = $this->telegram->sendMessage([
                    'chat_id' => $audio_channel_id,
                    'text' => "<b><u>{$postData['artist']}{$postData['add_artist']} - {$postData['album']}</u></b> ({$postData['type_album']} {$this->y})\n<i>{$postData['hashtag']} @rock_albums</i>",
                    'parse_mode' => 'HTML',
                    'disable_web_page_preview' => true,
                ]);
                if (!empty($postData['is_post'])) {
                    $this->telegram->sendMessage([
                        'chat_id' => $this->audio_channel_reserve,
                        'text' => "<b><u>{$postData['artist']}{$postData['add_artist']} - {$postData['album']}</u></b> ({$postData['type_album']} {$this->y})\n<i>{$postData['hashtag']} @rock_albums</i>",
                        'parse_mode' => 'HTML',
                        'disable_web_page_preview' => true,
                    ]);
                }
                $audio_msg_id = $r['message_id'];
                $postData['t_me'] = "{$a_link}{$audio_msg_id}";
                if ($postData['is_post']) {
                    $this->dbh->exec("UPDATE post set t_me='{$postData['t_me']}', msg_audio_id=$audio_msg_id where id_post = {$postData['id_post']};");
                }
            } else {
                $caption = "{$postData['type_album']} {$postData['hashtag']} @rock_albums";
            }
            if ($cAudios > 1/* && $cAudios <= 10*/) {
                if (($cAudios % 10) == 0) {
                    $chunk = 10;
                    $countParts = $cAudios / 10;
                } else {
                    $countParts = floor($cAudios / 10) + 1;
                    $chunk = ceil($cAudios / $countParts);
                }
                $audioChunk = array_chunk($audios, $chunk);
                foreach ($audioChunk as $cKey => $partAudios) {
                    $media = array();
                    $media_caption = '';
                    foreach ($partAudios as $aKey => $audio) {

                        if (($aKey + 1) == count($partAudios)) {
                            /*if (($countParts - 1) == $cKey) {
                                $media_caption = "{$postData['hashtag']}\n—♪—♪—@rock_albums—♪—♪—";
                            } else {*/
                                $media_caption = ($cAudios == 2) ? $caption : '@rock_albums';
                            //}
                        }
                        $media[] = [
                            'type' => 'audio',
                            'media' => $audio['file'],
                            'caption' => $media_caption,
                        ];
                    }
                    $r = $this->telegram->sendAnyRequest('sendMediaGroup', [
                        'chat_id' => $audio_channel_id,
                        'media' => json_encode($media),
                    ]);
                    if (!empty($postData['is_post'])) {
                        $this->telegram->sendAnyRequest('sendMediaGroup', [
                            'chat_id' => $this->audio_channel_reserve,
                            'media' => json_encode($media),
                        ]);
                    }
                    //sleep(2);
                    if (empty($audio_msg_id)) {
                        if (!empty($r[0]['message_id'])) {
                            $audio_msg_id = $r[0]['message_id'];
                        } elseif (!empty($r['message_id'])) {
                            $audio_msg_id = $r['message_id'];
                        }
                        $postData['t_me'] = "{$a_link}{$audio_msg_id}";
                        if ($postData['is_post']) {
                            $this->dbh->exec("UPDATE post set t_me='{$postData['t_me']}', msg_audio_id=$audio_msg_id where id_post = {$postData['id_post']};");
                        }
                    }
                }
                if ($cAudios > 2) {
                    $this->telegram->sendSticker([
                        'chat_id' => $audio_channel_id,
                        'sticker' => 'CAACAgIAAxkBAAIE2F6mIbABvnIAAQqXBP1iFqSU-ZJ_0wACDAADNjqiGWbGGbgDTI49GQQ',
                    ]);
                    if (!empty($postData['is_post'])) {
                        $this->telegram->sendSticker([
                            'chat_id' => $this->audio_channel_reserve,
                            'sticker' => 'CAACAgIAAxkBAAIE2F6mIbABvnIAAQqXBP1iFqSU-ZJ_0wACDAADNjqiGWbGGbgDTI49GQQ',
                        ]);
                    }
                }
            } else {
                $i = 0;
                foreach ($audios as $audio) {
                    $r = $this->telegram->sendAudio([
                        'chat_id' => $audio_channel_id,
                        'audio' => $audio['file'],
                        'caption' => $caption,
                    ]);
                    if (!empty($postData['is_post'])) {
                        $this->telegram->sendAudio([
                            'chat_id' => $this->audio_channel_reserve,
                            'audio' => $audio['file'],
                            'caption' => $caption,
                        ]);
                    }

                    if (empty($audio_msg_id)) {
                        $audio_msg_id = $r['message_id'];
                        $postData['t_me'] = "{$a_link}{$audio_msg_id}";
                        if ($postData['is_post']) {
                            $this->dbh->exec("UPDATE post set t_me='{$postData['t_me']}', msg_audio_id=$audio_msg_id where id_post = {$postData['id_post']};");
                        }
                    }
                    $i++;
                    if ($i > 5) {
                        sleep(2);
                        $i = 0;
                    }
                }
                if ($cAudios > 10) {
                    $this->telegram->sendSticker([
                        'chat_id' => $audio_channel_id,
                        'sticker' => 'CAACAgIAAxkBAAIE2F6mIbABvnIAAQqXBP1iFqSU-ZJ_0wACDAADNjqiGWbGGbgDTI49GQQ',
                    ]);
                    if (!empty($postData['is_post'])) {
                        $this->telegram->sendSticker([
                            'chat_id' => $this->audio_channel_reserve,
                            'sticker' => 'CAACAgIAAxkBAAIE2F6mIbABvnIAAQqXBP1iFqSU-ZJ_0wACDAADNjqiGWbGGbgDTI49GQQ',
                        ]);
                    }
                }
            }
        }
        return $audio_msg_id;
    }

    //addcslashes('string', '\-\._,*][)(~`>#+=|}{!') for 'parse_mode' => 'MarkdownV2'
    private function getPostText($postData)
    {
        $ret = array(
            'post_text' => '',
            'post_template' => '',
            'post_vk_template' => '',
        );

		foreach ($postData as $k => $v) {
			if ($k != 'add_text') {
				$postData[$k] = str_replace(['>', '<'], ['&gt;', '&lt;'], $v);
			}
        }

        $media = '';
        $mediaMarkdown = '';
        $s = mb_chr(8203);
        if (!empty($postData['media_link'])) {
			$media = "<a href='{$postData['media_link']}'>$s$s</a>";
			$mediaMarkdown = "[$s$s]({$postData['media_link']})";
        }

		$is_found_musuc_service = false;//in order to post or not post VR link
		foreach ($this->music_resources as $mr) {
			if (!empty($mr['parser_name']) && !empty($mr['db_field']) && !empty($postData[$mr['db_field']])) {
				$is_found_musuc_service = true;
				break;
			}
		}

        $linkResult = '';
        $linkResultMarkdown = '';
        $audio_found = false;
        foreach ($this->music_resources as $mr) {
            if (!empty($mr['db_field']) && !empty($postData[$mr['db_field']])) {
                $lnk = $postData[$mr['db_field']];
                $audio_found = true;
            } elseif (!empty($mr['link']) && $is_found_musuc_service) {
                $lnk = $mr['link'];
            } else {
                if ($mr['format'] === "\n") {
                    $linkResult  .= $mr['format'];
                }
                continue;
            }
			$linkResultMarkdown .= "[{$mr['name']}]({$lnk}){$mr['format']}";
            $linkResult .= "<a href=\"$lnk\">{$mr['name']}</a>{$mr['format']}";
        }
		$linkResultMarkdown = rtrim($linkResultMarkdown, " ♪\n");
        $linkResult = rtrim($linkResult, " ♪\n");

        if (!$audio_found && strpos($postData['media_link'], 'youtube.com/')) {
            parse_str(parse_url($postData['media_link'],  PHP_URL_QUERY), $video_param);
            if (!empty($video_param['v'])) {
                $linkResult = "https://youtu.be/{$video_param['v']}";
            } else {
                $linkResult = $postData['media_link'];
            }
        }

		$ret['post_title'] = "{$postData['artist']}{$postData['add_artist']} - {$postData['album']}";
		$ret['post_template'] = "<b><u>{$ret['post_title']}</u></b> ({$postData['type_album']}";
        $ret['post_vk_api'] = "{$postData['artist']}{$postData['add_artist']} - {$postData['album']} ({$postData['type_album']}";

        if ($postData['type_album'] != $this->types['nlv']) {
            $ret['post_template'] .= " {$this->y}";
            $ret['post_vk_api'] .= " {$this->y}";
        }

        $ret['post_template'] .= ')';
        $ret['post_vk_api'] .= ')';
        $ret['post_text'] = $ret['post_template'];
        $ret['post_vk_template'] = $ret['post_vk_api'];
        $ret['post_template'] .= $mediaMarkdown . "\n{$postData['hashtag']}";
        $ret['post_text'] .= "{$media}\n<i>{$postData['hashtag']}</i>";
        $ret['post_vk_template'] .= "\n{$postData['hashtag']}\n{$postData['media_link']}";
        if (!empty($linkResult)) {
            $ret['post_template'] .= "\n\n" . $linkResultMarkdown;
            $ret['post_text'] .= "\n\n{$linkResult}";
        }
        $ret['post_vk_api'] .= "\n{$postData['hashtag']}\n";
        if (!empty($postData['add_text'])) {
            $ret['post_template'] .= "\n\n{$postData['add_text']}";
            $ret['post_text'] .= "\n\n{$postData['add_text']}";
            $ret['post_vk_template'] .= "\n{$postData['add_text']}\n";
            $ret['post_vk_api'] .= "\n{$postData['add_text']}\n";
        }
        $ret['post_vk_template'] .= "\nСлушать в телеграм https://t.me/rock_albums";//https://tlinks.run/rock_albums
        $ret['post_vk_api'] .= "\nСлушать в телеграм https://t.me/rock_albums";

        $ret['post_video'] = '';
        $ret['post_vk_api_video'] = '';
        if (!empty($postData['video_link'])) {
            $v_type = empty($postData['type_video']) ? 'video' : $postData['type_video'];
            if (empty($postData['video_name'])) {
                $ret['post_vk_api_video'] = "{$ret['post_title']} ";
            } else {
                $ret['post_vk_api_video'] = "{$postData['video_name']} ";
            }

            parse_str(parse_url($postData['video_link'],  PHP_URL_QUERY), $video_param);
            if (!empty($video_param['v'])) {
                $linkResult = "https://youtu.be/{$video_param['v']}";
            } else {
                $linkResult = $postData['video_link'];
            }
            $media = "<a href='{$postData['video_link']}'>$s$s</a>";
            $ret['post_video'] = "<i>{$ret['post_vk_api_video']}($v_type)</i>{$media}\n{$linkResult}";
            $ret['post_vk_api_video'] .= "({$v_type}";
            if ($v_type != $this->types['nlv']) {
                $ret['post_vk_api_video'] .= " {$this->y}";
            }
            $ret['post_vk_api_video'] .= ")\n{$postData['hashtag']}\n\nСлушать в телеграм https://t.me/rock_albums";
        }
        return $ret;
    }

    private function getDelayedPosts()
    {
        $res = $this->dbh->query("SELECT * from post WHERE posted=0 AND finished=1 ORDER BY posted_date,sort,id_post;", PDO::FETCH_ASSOC)->fetchAll();
        foreach ($res as $post) {
            if (empty($post['artist'])) {
                continue;
            }
            $post_text = $this->getPostText($post);

            $ready_posts[$post['id_post']] = array(
                'text' => $post_text['post_text'],
                'date' => date('G:i j F Y', strtotime($post['posted_date'])),
                'id' => $post['id_post'],
                'disable_preview' => (empty($post['media_link'])),
            );
            if (!empty($post_text['post_video'])) {
                $ready_posts[$post['id_post']]['video'] = $post_text['post_video'];
            }
        }

        if (empty($ready_posts)) {
            $this->telegram->sendMessage(['chat_id' => $this->chat_id, 'text' => 'There is no delayed posts',]);
            return;
        }

        foreach ($ready_posts as $post_ready_text) {
            $txt = "<b>{$post_ready_text['date']}</b> will be posted\n/edit_{$post_ready_text['id']}       /delete_{$post_ready_text['id']}\n\n";
            if (!empty($post_ready_text['video'])) {
                $this->telegram->sendMessage([
                    'chat_id' => $this->chat_id,
                    'text' => '(Video for the next post) ' . $txt . $post_ready_text['video'],
                    'parse_mode' => 'HTML',
                ]);
            }
            $this->telegram->sendMessage([
                'chat_id' => $this->chat_id,
                'text' => $txt . $post_ready_text['text'],
                'disable_web_page_preview' => $post_ready_text['disable_preview'],
                'parse_mode' => 'HTML',
            ]);
        }
    }

    private function executeCron()
    {
        $res = $this->dbh->query("SELECT * from post WHERE posted_date < '{$this->date}' AND posted=0 AND finished=1 ORDER BY posted_date,sort,id_post;", PDO::FETCH_ASSOC)->fetchAll();
        $ready_posts = array();
        foreach ($res as $post) {
            if (empty($post['artist'])) {
                continue;
            }
            $post_text = $this->getPostText($post);
            $ready_posts[$post['id_post']] = array(
                'text' => $post_text['post_text'],
                'disable_preview' => (empty($post['media_link'])),
                'title' => $post_text['post_title'],
            );
            if (!empty($post_text['post_video'])) {
                $ready_posts[$post['id_post']]['video'] = $post_text['post_video'];
            }
        }

        if (empty($ready_posts)) {
            return;
        }

        $this->fp = fopen('log.txt', 'at');
        fwrite($this->fp, "{$this->date} cron ");
		$this->telegram = new MyApi($this->settings['telegram_token']);
		foreach ($ready_posts as $id_post => $post_ready_text) {
			if (!empty($post_ready_text['video'])) {
				$this->telegram->sendMessage([
					'chat_id' => "@{$this->post_channel}",
					'text' => $post_ready_text['video'],
					'parse_mode' => 'HTML',
				]);
			}
			$r = $this->telegram->sendMessage([
				'chat_id' => "@{$this->post_channel}",
				'text' => $post_ready_text['text'],
				'disable_web_page_preview' => $post_ready_text['disable_preview'],
				'parse_mode' => 'HTML',
			]);
			$this->dbh->exec("UPDATE post set posted=1 where id_post = {$id_post};");
			fwrite($this->fp, $r . "\n");
		}
    }

    private function saveLinks()
    {
        if (empty($_REQUEST['results'])) {
            return;
        }
        $results = json_decode($_REQUEST['results'], true);
        if (empty($results) || empty($results['group']) || empty($results['album']) || empty($results['results'])) {
            return;
        }
        $post = $this->dbh->query("SELECT * from post WHERE finished=0;", PDO::FETCH_ASSOC)->fetch();
        if (empty($post['artist'])) {
            return;
        }
        $artistReq = trim(mb_strtolower($results['group']));
        $post['artist'] = mb_strtolower($post['artist']);
        $albumReq = trim(mb_strtolower($results['album']));
        $post['album'] = mb_strtolower($post['album']);

        if (strpos($post['artist'], $artistReq) !== 0 || strpos($post['album'], $albumReq) !== 0) {
            return;
        }

        $this->currentPost = $post;
        $this->chat_id = self::BOT_CHAT;
		$this->telegram = new MyApi($this->settings['telegram_token']);
		$this->parseLinks($results['results']);
    }

    private function instagram()
    {
        $res = $this->dbh->query("SELECT * from post WHERE is_insta_post=0 AND finished=1 AND posted_date < '{$this->date}' ORDER BY posted_date,sort,id_post;", PDO::FETCH_ASSOC)->fetchAll();
        if (empty($res)) {
            return;
        }
        $symbols_tag_replace = array(' ', '#', '  ', '-', '&', "'", '"', '/', '__');
        foreach ($res as $post) {
            if (empty($post['artist']) || strpos($post['media_link'], $_SERVER['HTTP_HOST']) === FALSE) {
                continue;
            }
            $ready_posts[] = array(
                'text' => "{$post['artist']}{$post['add_artist']} - {$post['album']}\n#" . str_replace($symbols_tag_replace, '_', $post['type_album']) . " $this->y\nLink for listening in bio\nhttps://t.me/rock_albums\n\n{$post['hashtag']}\n#rock #metal #core #alternative",
                'id_post' => $post['id_post'],
                'media_link' => str_replace("https://{$_SERVER['HTTP_HOST']}/", '', $post['media_link']),
            );
        }
        if (empty($ready_posts)) {
            return;
        }
        $this->telegram = new MyApi($this->settings['telegram_token']);
        $inst = new InstaLite($this->settings['insta_login'], $this->settings['insta_pass']);
        if (empty($inst->user['userId'])) {
            $this->telegram->sendMessage(['chat_id' => self::BOT_CHAT, 'text' => 'Instagram login error',]);
            return;
        }
        foreach ($ready_posts as $post) {
            try {
                $inst->uploadPhoto($post['media_link'], $post['text']);
            } catch (\InstaLite\Exception $e) {
                $text = "Upload Instagram:\n" .$e->getMessage() . "\nFile: " . $e->getFile() . " Line: " . $e->getLine() . "\nTrace:\n" . $e->getTraceAsString() . "\n";
                $this->telegram->sendMessage(['chat_id' => self::BOT_CHAT, 'text' => $text]);
            }
            $this->dbh->exec("UPDATE post set is_insta_post = 1 where id_post={$post['id_post']}; ");
            sleep(3);
        }
        $this->dbh->exec("UPDATE post set is_insta_post = 1 where is_insta_post=0 AND finished=1 AND posted_date < '{$this->date}'; ");
    }

    private function queueParse()
    {
        if (empty($this->settings['parser_enabled'])) {
            return;
        }
        $res = $this->dbh->query("SELECT * from queue WHERE count_try < 7 AND date < '$this->date' ORDER BY count_try DESC LIMIT 1;", PDO::FETCH_ASSOC)->fetch();
        if (empty($res)) {
            return;
        }
        $parser_link2 = $this->parser_link . 'find/' . rawurlencode($res['artist']) . '/' . rawurlencode($res['album']) . '?flush=1';
        $neededLinks = [];
        foreach ($this->music_resources as $mr) {
            if (!empty($mr['db_field']) && !empty($mr['parser_name']) && $mr['parser_name'] != 'yandex') {
                $neededLinks[] = $mr['parser_name'];
            }
        }
        if (isset($neededLinks[$res['count_try']])) {
            $parser_link2 .= "&q={$neededLinks[$res['count_try']]}";
        }
        $this->backgroundParser($parser_link2);
        $this->dbh->exec("UPDATE queue set count_try = count_try + 1 WHERE id_queue = {$res['id_queue']};");
    }

}