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
function getColorLog(string $str, $type = ''): string {
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
function isCli(): bool {
    if (PHP_SAPI === 'cli') {
        return true;
    }
    return false;
}

/**
 * htmlspecialcharsエイリアス
 */
function h(string $str): string {
    return htmlspecialchars($str);
}

/**
 * 曜日を文字列で取得する
 * @param int $dayNumber
 * @return string
 */
function getDayOfWeek(int $dayNumber): string {
    $daysOfWeek = ['日', '月', '火', '水', '木', '金', '土'];
    return $daysOfWeek[$dayNumber];
}

/**
 * バージョンを取得する
 */
function getVersion(): string {
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
