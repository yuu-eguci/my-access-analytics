<?php

/**
 * AnalyticsAPIを、MyAccessAnalyticsで処理しやすいよう加工するクラス。
 * 具体的にはAnalytics結果からrowsだけ抜き出すのが仕事。
 */
class MyGARapper
{
    protected $analytics;
    public $view;

    /**
     * インスタンス変数の初期値。
     */
    public function __construct()
    {
        $this->analytics = null;
        $this->view = null;
    }

    /**
     * インスタンスの初期化。これを行えばあとはクエリをgetDataに投げるだけでよい。
     * @param  array
     * @return boolean
     */
    public function initialize($configDic)
    {
        $this->analytics = $this->createAnalytics($configDic);
        $this->view = $configDic['view'];
        if (is_null($this->analytics)) {
            return false;
        }
        return true;
    }

    /**
     * 結果の取得。
     * @param  array
     * @return array
     */
    public function getData($query)
    {
        # 空文字列の項目を消す。
        $query = $this->manipulateQuery($query);
        return $this->analytics->data_ga->get(
            'ga:'.$this->view,
            $query['startdate'],
            $query['enddate'],
            $query['metrics'],
            $query['options']
        )->rows;
    }

    /**
     * AnalyticsAPIのインスタンスを作る。
     * @param  array
     * @return object
     */
    protected function createAnalytics($configDic)
    {
        $autoloadPath = $configDic['autoloadPath'];
        $keyPath      = $configDic['keyPath'];
        $email        = $configDic['email'];
        $applicationName = 'APPLICATIONNAME';

        require_once $autoloadPath;
        $client = new Google_Client();
        $client->setApplicationName($applicationName);
        $analytics = new Google_Service_Analytics($client);
        $key = file_get_contents($keyPath);
        $cred = new Google_Auth_AssertionCredentials(
                $email,
                array(Google_Service_Analytics::ANALYTICS_READONLY),
                $key
        );
        $client->setAssertionCredentials($cred);
        if($client->getAuth()->isAccessTokenExpired()) {
            $client->getAuth()->refreshTokenWithAssertion($cred);
        }
        return $analytics;
    }

    /**
     * クエリを加工する。
     * @param  array
     * @return array
     */
    public function manipulateQuery($query)
    {
        # クエリからnullの項目を消す。
        foreach ($query as $key => &$value) {
            if (is_null($value)) unset($query[$key]);
            if ($key == 'options') {
                foreach ($value as $k => &$v) {
                    if (is_null($v)) unset($value[$k]);
                } unset($v);
            }
        } unset($value);
        return $query;
    }
}
