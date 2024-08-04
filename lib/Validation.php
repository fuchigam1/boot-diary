<?php
class Validation {

    // 引数に指定された文字列が、有効な日司かどうかを判定する
    public static function isValidDate($date) {
        // Y-m-d 形式または Ymd 形式の日付を DateTime オブジェクトに変換
        $dateTime = DateTime::createFromFormat('Y-m-d', $date) ?: DateTime::createFromFormat('Ymd', $date);
        // 日付が有効かどうかを確認
        if ($dateTime && ($dateTime->format('Y-m-d') === $date || $dateTime->format('Ymd') === $date)) {
            return true;
        }
        return false;
    }

}
