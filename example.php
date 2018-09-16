<?php

# MyAccessAnalyticsの使い方でーす!

# あっ、google analyticsの解析結果が欲しい! と思ったときは…

# まずMyAccessAnalyticsのインスタンスを作る。
require_once 'MyAccessAnalytics.php';
$aa = new MyAccessAnalytics;

# AnalyticsAPIの初期設定を作る。
$configDic = [
    # google-api-php-client直下にもautoloadあるけれど、それは違う。
    'autoloadPath' => dirname(__FILE__) . '/ga/google-api-php-client/src/Google/autoload.php',
    # 秘密鍵(p12ファイル)の場所。
    'keyPath'      => dirname(__FILE__) . '/ga/***.p12',
    # Google Developers Consoleで取得できるメールアドレス。
    'email'        => '',
    # アナリティクスページの「管理」→「ビュー設定」から表示できるビューID。
    'view'         => '',
];

# その初期設定を使ってインスタンスを初期化する。
if (!$aa->initialize($configDic)) {
    echo '設定項目にミスがあります。たぶん。';
}

# あとはクエリを書いて結果を取得するだけ…なのだけれど、クエリを書くのが面倒(あるいはよくわかんない)ならキーワードを送るだけでもよい。
$request = [ 'keyword' => 'sessions_total' ];
// $result = $aa->getData($request);

# キーワードだけで結果を取得できるのは他にもあるよ。(好きなところでprint_r($result)入れてみてね。)
$request = [ 'keyword' => 'sessions_today' ];
// $result = $aa->getData($request);

$request = [ 'keyword' => 'sessions_yesterday' ];
// $result = $aa->getData($request);

$request = [ 'keyword' => 'sessions_dbyesterday' ];
// $result = $aa->getData($request);

# セッション数の推移はこんなキーワードで取得できる。
$request = [ 'keyword' => 'sessions_process_day' ];
// $result = $aa->getData($request);

# 'sessions_process_'シリーズは期間を指定できる。指定しない場合はデフォルトの期間で取得される。
$request = [
    'keyword'   => 'sessions_process_month',
    'startdate' => '2016-07-01',
    'enddate'   => '2016-08-31',
];
// $result = $aa->getData($request);

# さらに、カラム名を指定することもできる。一度カラム名ナシで結果がどんな順番で並んでるのか見てから書くのがいいと思う。
$request = [
    'keyword'     => 'sessions_process_year',
    'startdate'   => '2015-01-01',
    'enddate'     => '2016-12-31',
    'columnNames' => ['年', 'セッション数'],
];
// $result = $aa->getData($request);

# flotでグラフにする場合はchartDataパラメータを追加すべし。グラフにするのに都合のいい形のデータが返ってくる。
$request = [
    'keyword'   => 'sessions_process_day',
    'startdate' => '2016-09-08',
    'enddate'   => '2016-09-15',
    'chartData' => true,
];
// $result = $aa->getData($request);

# デバイスの比率を知りたいならこのキーワード。
$request = [
    'keyword'     => 'sessions_device',
    'columnNames' => ['セッション数', 'パーセンテージ'],
];
// $result = $aa->getData($request);

# ページ別のアクセス数を知りたいならこれ。注意、これはセッション数じゃなくてPV数。除外するURLとか取得件数をLIKEで指定してもよい。
$request = [
    'keyword'     => 'pageaccess',
    'startdate'   => '2016-08-01',
    'enddate'     => '2016-08-31',
    'remove'      => ['share'],
    'max-results' => '20',
    'columnNames' => ['URL', 'ページのタイトル', 'PV数'],
];
// $result = $aa->getData($request);

# 検索キーワードのランキングが知りたいならこれ。
$request = [
    'keyword'     => 'keyword',
    'startdate'   => '2016-08-01',
    'enddate'     => '2016-08-31',
    'remove'      => ['not set', 'not provided', 'share', 'cookie'],
    'max-results' => '10',
    'columnNames' => ['URL', 'ヒットしたページのタイトル', '検索キーワード', '検索数'],
];
// $result = $aa->getData($request);

# わーキーワード便利じゃーん、でも自分にもクエリを書かせろ! ってときは自分で書いちゃっても良いです。
# クエリの説明はしませんクエリ書く手間を省くのがこのモジュールの目的だから。
$request = [
    'metrics'    => 'ga:sessions',
    'startdate'  => '2daysAgo',
    'enddate'    => 'today',
    'dimensions' => 'ga:date',
    'sort'       => '-ga:date',
];
// $result = $aa->getData($request);

print_r($result);
