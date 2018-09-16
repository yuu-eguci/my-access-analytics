<?php

require_once 'MyGARapper.php';

/**
 * AnalyticsAPIを気楽に使う(キーワード投げただけで結果が返る、グラフにしやすい形のデータに加工する)ためのクラス。
 * 親切心のかたまり。
 */
class MyAccessAnalytics
{
    protected $gaRapper;

    # キーワードと最小クエリ。
    public static $keywordList = [
        # 全セッション数
        'sessions_total' => [
            'metrics'   => 'ga:sessions',
            'startdate' => '2016-01-01',
            'enddate'   => 'today',
        ],
        # 今日のセッション数
        'sessions_today' => [
            'metrics'   => 'ga:sessions',
            'startdate' => 'today',
            'enddate'   => 'today',
        ],
        # 昨日のセッション数
        'sessions_yesterday' => [
            'metrics'   => 'ga:sessions',
            'startdate' => 'yesterday',
            'enddate'   => 'yesterday',
        ],
        # 一昨日のセッション数
        'sessions_dbyesterday' => [
            'metrics'   => 'ga:sessions',
            'startdate' => '2daysAgo',
            'enddate'   => '2daysAgo',
        ],
        # 期間指定のセッション数推移、日区切り(startdateとenddate必要)
        'sessions_process_day' => [
            'metrics'    => 'ga:sessions',
            'dimensions' => 'ga:date',
            'sort'       => '-ga:date',
            # デフォルト一週間
            'startdate'  => '7daysAgo',
            'enddate'    => 'today',
        ],
        # 期間指定のセッション数推移、月区切り(同上)
        'sessions_process_month' => [
            'metrics'    => 'ga:sessions',
            'dimensions' => 'ga:month',
            'sort'       => '-ga:month',
            # デフォルト三ヶ月
            'startdate'  => '90daysAgo',
            'enddate'    => 'today',
        ],
        # 期間指定のセッション数推移、年区切り(同上)
        'sessions_process_year' => [
            'metrics'    => 'ga:sessions',
            'dimensions' => 'ga:year',
            'sort'       => '-ga:year',
            # デフォルト三年
            'startdate'  => '1095daysAgo',
            'enddate'    => 'today',
        ],
        # 期間指定のデバイス別セッション数(同上)
        'sessions_device' => [
            'metrics'    => 'ga:sessions',
            'dimensions' => 'ga:deviceCategory',
            'filters'    => 'ga:deviceCategory==desktop,ga:deviceCategory==mobile,ga:deviceCategory==tablet',
            # デフォルト一ヶ月
            'startdate'  => '30daysAgo',
            'enddate'    => 'today',
        ],
        # 期間指定のページ別アクセス(startdateとenddate必要。件数、除外url指定可能)
        'pageaccess' => [
            'metrics'     => 'ga:pageviews',
            'dimensions'  => 'ga:pagePath,ga:pageTitle',
            'sort'        => '-ga:pageviews',
            # デフォルト設定
            'filters'     => 'ga:pagePath!@share',
            'max-results' => '10',
            'startdate'   => '30daysAgo',
            'enddate'     => 'today',
        ],
        # 期間指定の検索キーワード(startdateとenddate必要。件数、除外キーワード指定可能)
        'keyword' => [
            'metrics'     => 'ga:organicSearches',
            'dimensions'  => 'ga:pagePath,ga:pageTitle,ga:keyword',
            'sort'        => '-ga:organicSearches',
            # デフォルト設定
            'filters'     => 'ga:keyword!@not set;ga:keyword!@not provided;ga:keyword!@share;ga:keyword!@cookie',
            'max-results' => '10',
            'startdate'   => '30daysAgo',
            'enddate'     => 'today',
        ],
    ];

    /**
     * 結果を加工する。
     * @param  array
     * @param  array
     * @param  boolean
     * @param  boolean
     * @return array
     */
    protected function manipulateResult($result, $columnNames=[], $is_chartData=false, $is_device=false)
    {
        # ※配列は「全体」「行」「列」の順に入れ子になっている。

        # グラフ用の成型。
        if ($is_chartData) {
            return $this->manipulateResult4Chart($result);
        }

        # デバイス用の成型。
        if ($is_device) {
            $result = $this->manipulateResult4Device($result);
        }

        # 列に名前をつける
        if ($columnNames) {
            foreach ($result as &$row) {
                for ($i=0; $i < count($row); $i++) { 
                    $row[$columnNames[$i]] = $row[$i];
                    unset($row[$i]);
                }
            } unset($row);
        }

        # 行も列も一個だけ。
        if (count($result) == 1 && count($result[0]) == 1) {
            return $result[0][0];
        }

        return $result;
    }

    /**
     * flotのグラフにしやすいように加工する。
     * @param  array
     * @return array
     */
    protected function manipulateResult4Chart($result)
    {
        # 当てはまるのは列がふたつのresult。当てはまらなければfalse。
        $valid = true;
        foreach ($result as $row) {
            if (count($row) != 2) {
                $valid = false;
            }
        }
        if (!$valid) return false;

        # 0と1どっちが日付データなのかヴァリデートしたい気もするが、面倒なので0だと断定。

        # 日付データをタイムスタンプにする。
        switch (strlen($result[0][0])) {
            case 4:
                foreach ($result as &$r) {
                    $r[0] = strtotime($r[0] . '0101');
                } unset($r);
                break;
            case 6:
                foreach ($result as &$r) {
                    $r[0] = strtotime($r[0] . '01');
                } unset($r);
                break;
            case 8:
                foreach ($result as &$r) {
                    $r[0] = strtotime($r[0]);
                } unset($r);
                break;
            default:
                break;
        }

        # ticksとセッション数max,minを取得する。
        $ticks = [];
        $max = null;
        $min = null;
        foreach ($result as $r) {
            $ticks[] = $r[0];
            if (is_null($max) || $r[1] > $max) $max = $r[1];
            if (is_null($min) || $r[1] < $min) $min = $r[1];
        }

        return [
            'data'  => $result,
            'ticks' => $ticks,
            'max'   => $max,
            'min'   => $min,
        ];
    }

    /**
     * デバイスのデータとして使いやすいように加工する。
     * @param  array
     * @return array
     */
    protected function manipulateResult4Device($result)
    {
        # 当てはまるのは列がふたつのresult。当てはまらなければfalse。
        $valid = true;
        foreach ($result as $row) {
            if (count($row) != 2) {
                $valid = false;
            }
        }
        if (!$valid) return false;

        $sum = $result[0][1] + $result[1][1] + $result[2][1];
        $return = [
            # パーセンテージ,数値 の配列にする。
            'desktop' => [$result[0][1], round($result[0][1] / $sum * 100 ,1)],
            'mobile'  => [$result[1][1], round($result[1][1] / $sum * 100 ,1)],
            'tablet'  => [$result[2][1], round($result[2][1] / $sum * 100 ,1)],
        ];

        return $return;
    }

    /**
     * インスタンス変数の初期値。
     */
    public function __construct()
    {
        $this->gaRapper = null;
    }

    /**
     * インスタンスの初期化。
     * @param  array
     * @return boolean
     */
    public function initialize($configDic)
    {
        $this->gaRapper = new MyGARapper;
        if (!$this->gaRapper->initialize($configDic)) {
            return false;
        }
        return true;
    }

    /**
     * キーワード、あるいはクエリを受け取りアナリティクス結果を取得する。そして成型して返す。
     * @param  array
     * @return array
     */
    public function getData($request)
    {
        # クエリ原型
        $pre_query = [
            'startdate'   => null,
            'enddate'     => null,
            'metrics'     => null,
            'dimensions'  => null,
            'sort'        => null,
            'filters'     => null,
            'max-results' => null,
        ];

        # キーワード判定してクエリを取得する。
        if (array_key_exists('keyword', $request)) {
            if (in_array($request['keyword'], array_keys(MyAccessAnalytics::$keywordList))) {
                foreach (MyAccessAnalytics::$keywordList[$request['keyword']] as $queryKey => $queryValue) {
                    $pre_query[$queryKey] = $queryValue;
                }
            } else {
                # キーワードが誤っているときはさっさとfalseを返してやる。
                return false;
            }
            # pageaccessとkeywordでremoveがあるときはfilterを作りなおす。
            if (array_key_exists('remove', $request) && gettype($request['remove']) == 'array') {
                if ($request['keyword'] == 'pageaccess') {
                    $pre_query['filters'] = '';
                    foreach ($request['remove'] as $word) {
                        $pre_query['filters'] .= "ga:pagePath!@$word;";
                    }
                    $pre_query['filters'] = substr($pre_query['filters'], 0, -1);
                }
                if ($request['keyword'] == 'keyword') {
                    $pre_query['filters'] = '';
                    foreach ($request['remove'] as $word) {
                        $pre_query['filters'] .= "ga:keyword!@$word;";
                    }
                    $pre_query['filters'] = substr($pre_query['filters'], 0, -1);
                }
            }
        }

        # 他の項目を上書きする。
        foreach ($request as $requestKey => $requestValue) {
            if (array_key_exists($requestKey, $pre_query)) {
                $pre_query[$requestKey] = $requestValue;
            }
        }

        # 実際に使うクエリ(null項目はのちに削除される)
        $query = [
            'startdate' => $pre_query['startdate'],
            'enddate'   => $pre_query['enddate'],
            'metrics'   => $pre_query['metrics'],
            'options' => [
                'dimensions'  => $pre_query['dimensions'],
                'sort'        => $pre_query['sort'],
                'filters'     => $pre_query['filters'],
                'max-results' => $pre_query['max-results'],
            ],
        ];

        # 結果取得。
        $result = $this->gaRapper->getData($query);

        # 良い形に成型する。
        $columnNames = array_key_exists('columnNames', $request) ? $request['columnNames'] : [];
        $is_chartData = array_key_exists('chartData', $request) ? true : false;
        $is_device = false;
        if (array_key_exists('keyword', $request) && $request['keyword'] == 'sessions_device') {
            $is_device = true;
        }
        $return = $this->manipulateResult($result, $columnNames, $is_chartData, $is_device);

        return $return;
    }
}
