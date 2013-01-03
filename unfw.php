<?php

////設定
define('LOG_FILE', '/home/hogehoge/cron/unfw.log');
define('CONSUMER_KEY', 'aaaa');
define('CONSUMER_SECRET', 'bbbb');
define('ACCESS_TOKEN', 'cccc');
define('ACCESS_TOKEN_SECRET', 'dddd');
define('TO_MAIL','to@example.com');
define('FROM_MAIL','from@example.com');
define('REPLY_MAIL','replay@example.com');
////

require_once 'twitteroauth.php';

//前回のフォロワー情報初期化
$prev_fws = array();

//ログの存在確認なければ作成
if (!is_file(LOG_FILE)) {
	file_put_contents(LOG_FILE, '');
	chmod(LOG_FILE, 0777);
	echo 'logfile initialized.'.PHP_EOL;
} else {
	$json = file_get_contents(LOG_FILE);
	$prev_fws = unserialize($json);
}

//twitterからデータ取得
$to = new TwitterOAuth(CONSUMER_KEY, CONSUMER_SECRET, ACCESS_TOKEN, ACCESS_TOKEN_SECRET);
$req = $to->OAuthRequest('https://api.twitter.com/1.1/followers/ids.json','GET', array());
$json = json_decode($req);
if (isset($json->errors)) {
	exit($json->errors[0]->message. PHP_EOL);
}

//フォロワー配列の代入
$fws = $json->ids;
//ロギング
file_put_contents(LOG_FILE,serialize($fws));
//前回との差分取得
$fwdiff = array_diff($prev_fws,$fws);
//フォロー解除判定
if (count($fwdiff) === 0) {
	exit;
} 

//メールテキスト初期化
$txt = 'フォロー解除されました。'. date('Y-m-d H:i:s'). PHP_EOL;
//フォロー解除したユーザの配列チェック
foreach ($fwdiff as $unfw) {
	//ユーザデータ取得
	$req = $to->OAuthRequest('http://api.twitter.com/1.1/users/show.json',"GET",array('user_id'=>$unfw));
	$json = json_decode($req);
	
	//メールテキスト生成
	$txt .= $json->screen_name;
	$txt .= ' / '.$json->name;
	if ( $json->following == true ) {
		$txt .= ' (あなたがフォローしている)';
	} else {
		$txt .= ' (あなたがフォローしていない)';
	}
	$txt .= PHP_EOL;
}

//send mail
$to      = TO_MAIL;
$subject = 'remove log';
$message = $txt;
$headers = 'From: '. FROM_MAIL. "\r\n" .
    'Reply-To: '. REPLY_MAIL. "\r\n" .
    'X-Mailer: PHP/' . phpversion();

mail($to, $subject, $message, $headers);
