<?php
class Validation {

    /**
     * phpversion が 7.4 以上であることを確認する
     *
     * @return void
     */
    public static function checkPhpVersion(): void {
        if (version_compare(phpversion(), '7.4', '<')) {
            // 現在のバージョンを表示する
            echo getColorLog('現在のPHPバージョン: ' . phpversion() . PHP_EOL, 'warning');
            echo getColorLog('PHPバージョンが7.4以上に対応しています' . PHP_EOL, 'warning');
            exit;
        }
    }

    /**
     * setting.php が存在するかを確認する
     *
     * @return void
     */
    public static function checkSettingFile(): void {
        if (!file_exists(APP_ROOT . DS . 'lib' . DS . 'setting.php')) {
            echo getColorLog('php run.php init を実行してください' . PHP_EOL, 'error');
            exit;
        }
    }

    /**
     * 各templateファイルが存在するかを確認する
     *
     * @return void
     */
    public static function checkTemplateFiles(): void {
        $templateFiles = [
            'format-footer.template.md',
            'format-header.template.md',
            'format.template.md',
            'new.template.md',
        ];
        foreach ($templateFiles as $templateFile) {
            $templatePath = TEMPLATE_DIR . DS . $templateFile;
            if (!file_exists($templatePath)) {
                echo getColorLog('php run.php init を実行してください' . PHP_EOL, 'error');
                exit;
            }
        }
    }

    /**
     * 引数に指定された文字列が、有効な日付かどうかを判定する
     *
     * @param string $date
     * @return boolean
     */
    public static function isValidDate(string $date): bool {
        // Y-m-d 形式、Ymd 形式、または Y/m/d 形式の日付を DateTime オブジェクトに変換
        $dateTime = DateTime::createFromFormat('Y-m-d', $date)
                    ?: DateTime::createFromFormat('Ymd', $date)
                    ?: DateTime::createFromFormat('Y/m/d', $date);

        // 日付が有効かどうかを確認
        if ($dateTime && ($dateTime->format('Y-m-d') === $date ||
            $dateTime->format('Ymd') === $date ||
            $dateTime->format('Y/m/d') === $date)
        ) {
            return true;
        }
        return false;
    }

}
