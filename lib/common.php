<?php

ini_set('date.timezone', 'Asia/Tokyo');
if (!defined('DS')) {
    define('DS', DIRECTORY_SEPARATOR);
}

$APP_ROOT = dirname(dirname(__FILE__));
if (!defined('APP_ROOT')) {
    define('APP_ROOT', $APP_ROOT);
}

// 設定ファイルが存在する場合のみ読み込む
$settingPath = __DIR__ . '/setting.php';
if (file_exists($settingPath)) {
    require_once $settingPath;
}

/**
 * 文字に色付け
 *
 * @param string $str
 * @param string $type
 * @return string
 * @link 色指定 https://misc.flogisoft.com/bash/tip_colors_and_formatting
 * @link 種別 https://www.php-fig.org/psr/psr-3/#5-psrlogloglevel
 */
function getColorLog(string $str, $type = ''): string
{
    $string = '';

    // error = 'emergency' // Red
    // alert = 'alert' // Meganta
    // error = 'critical' // Red
    // error = 'error' // Red
    // warning = 'warning' // Yerrow
    // notice = 'notice'; // Green
    // success = 'info'; // Blue
    // 'debug'; // White

    switch ($type) {
        case 'alert':
            $string = "\033[0;35m$str\033[0m"; // Meganta
            break;
        case 'error':
            $string = "\033[0;31m$str\033[0m"; // Red
            break;
        case 'warning':
            $string = "\033[0;33m$str\033[0m"; // Yellow
            break;
        case 'notice':
            $string = "\033[0;36m$str\033[0m"; // Cyan
            break;
        case 'info':
            $string = "\033[0;32m$str\033[0m"; // Green
            break;
        default:
            $string = $str;
            break;
    }
    return $string;
}


/**
 * CLI実行かどうか判定する
 * @return boolean
 */
function isCli(): bool
{
    if (PHP_SAPI === 'cli') {
        return true;
    }
    return false;
}

/**
 * htmlspecialcharsエイリアス
 */
function h(string $str): string
{
    return htmlspecialchars($str);
}

/**
 * 曜日を文字列で取得する
 * @param int $dayNumber
 * @return string
 */
function getDayOfWeek(int $dayNumber): string
{
    $daysOfWeek = ['日', '月', '火', '水', '木', '金', '土'];
    return $daysOfWeek[$dayNumber];
}

/**
 * バージョンを取得する
 */
function getVersion(): string
{
    $versionNo = '';

    $path = APP_ROOT . DS . 'lib' . DS . 'VERSION.txt';
    if (file_exists($path)) {
        $file = new SplFileObject($path, 'r');
        $file->setFlags(SplFileObject::SKIP_EMPTY | SplFileObject::DROP_NEW_LINE);
        foreach ($file as $line) {
            if (!$line) {
                continue; // 空行はスルー
            }
            $versionNo = $line;
            break;
        }
    }

    return $versionNo;
}

/**
 * 別プロセスでドットを表示する関数
 *
 * @return void
 */
function displayDots(): void
{
    echo getColorLog("[Now Loading] ", 'notice');
    $counter = 0; // カウンターを追加
    while ($counter < 50) {
        echo getColorLog(".", 'notice');
        usleep(300000); // 300ミリ秒ごとにドットを表示
        $counter++;
    }
}

/**
 * 標準入力を受け取る
 * 配列で指定された物以外が入力されると再度入力を求める
 *
 * @param array|null $allowStringAry
 * @return string
 */
function in(?array $allowStringAry = null): string
{
    if ($allowStringAry) {
        $times = 0;
        while (1) {
            $times++;
            if ($times > 1) {
                echo getColorLog("入力された文字が間違っています(" . implode(" | ", $allowStringAry) . "): ", 'alert');
            }

            $input = trim(fgets(STDIN));
            if (in_array($input, $allowStringAry)) {
                return $input;
            }
        }

    } else {
        return trim(fgets(STDIN));
    }
}

/**
 * Y-m-d 形式、Ymd 形式、または Y/m/d 形式の日付を DateTime オブジェクトに変換
 *
 * @param string $specifiedDate
 * @return DateTime
 */
function convertDate(string $specifiedDate): DateTime
{
    $dateTime = DateTime::createFromFormat('Y-m-d', $specifiedDate)
                ?: DateTime::createFromFormat('Ymd', $specifiedDate)
                ?: DateTime::createFromFormat('Y/m/d', $specifiedDate);
    return $dateTime;
}
