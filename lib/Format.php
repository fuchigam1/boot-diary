<?php
require_once 'common.php';

class Format
{
    private $fileExtension;
    private $readmePath;
    private $headerTemplatePath;
    private $mainTemplatePath;
    private $footerTemplatePath;
    private $today;

    public function __construct()
    {
        $this->fileExtension = '.md';
        $this->readmePath = __DIR__ . '/../README.md';
        $this->headerTemplatePath = __DIR__ . '/../template/format-header.template.md';
        $this->mainTemplatePath = __DIR__ . '/../template/format.template.md';
        $this->footerTemplatePath = __DIR__ . '/../template/format-footer.template.md';
        $this->today = date('Ymd'); // 今日の日付を取得
    }

    public function execute($argv)
    {
        // コマンドライン引数から日付を取得
        $specifiedDate = $argv[2] ?? null;

        if ($specifiedDate) {
            if (Validation::isValidDate($specifiedDate)) {
                // Y-m-d 形式または Ymd 形式の日付を DateTime オブジェクトに変換
                $dateTime = DateTime::createFromFormat('Y-m-d', $specifiedDate) ?: DateTime::createFromFormat('Ymd', $specifiedDate);
                $dateToProcess = $dateTime->format('Ymd');
            } else {
                echo getColorLog("無効な日付指定です: $specifiedDate" . PHP_EOL, 'error');
                exit;
            }
        } else {
            $dateToProcess = $this->today;
        }

        // ファイルパスの構築
        $baseDir = __DIR__ . '/../';
        $filePath = $baseDir . $dateToProcess . $this->fileExtension;

        if (!file_exists($filePath)) {
            echo getColorLog("$dateToProcess.md は存在しません". PHP_EOL, 'error');
            exit;
        }

        // フォルダの作成とファイルの移動
        $yearDir = $baseDir . substr($dateToProcess, 0, 4);
        $monthDir = $yearDir . '/' . substr($dateToProcess, 4, 2);

        if (!is_dir($yearDir)) {
            mkdir($yearDir, 0777, true);
        }
        if (!is_dir($monthDir)) {
            mkdir($monthDir, 0777, true);
        }

        $destinationPath = $monthDir . '/' . $dateToProcess . $this->fileExtension;

        if (file_exists($destinationPath)) {
            $this->confirmOverwrite($destinationPath);
        }

        rename($filePath, $destinationPath);
        $this->updateReadme();

        echo getColorLog("ファイルを移動し、README.md を更新しました". PHP_EOL, 'info');
        echo getColorLog($destinationPath. PHP_EOL, 'info');
    }

    private function confirmOverwrite($filePath)
    {
        $fileName = basename($filePath);
        echo getColorLog("$fileName はすでに存在します。上書きしますか？ (y/n): ", 'warning');
        $handle = fopen("php://stdin", "r");
        $input = trim(fgets($handle));

        if (strtolower($input) !== 'y') {
            echo getColorLog("ファイルの上書きをキャンセルしました". PHP_EOL, 'info');
            exit;
        }
    }

    private function updateReadme()
    {
        $headerTemplate = file_get_contents($this->headerTemplatePath);
        $mainTemplate = file_get_contents($this->mainTemplatePath);
        $footerTemplate = file_get_contents($this->footerTemplatePath);

        $readmeContent = $headerTemplate . "\n";

        // 年ディレクトリを取得
        $years = glob(__DIR__ . '/../[0-9]*', GLOB_ONLYDIR);

        // 作成日時でソート（新しい順）
        usort($years, function($a, $b) {
            return filemtime($b) - filemtime($a);
        });

        foreach ($years as $yearPath) {
            $year = basename($yearPath);
            $yearContent = str_replace('{{year}}', $year, $mainTemplate);

            // 月ディレクトリを取得
            $months = glob($yearPath . '/*', GLOB_ONLYDIR);

            // 作成日時でソート（新しい順）
            usort($months, function($a, $b) {
                return filemtime($b) - filemtime($a);
            });

            $monthContent = '';
            foreach ($months as $monthPath) {
                $month = basename($monthPath);
                $files = glob($monthPath . '/*.md');
                if (!empty($files)) {
                    $fileLinks = '';
                    foreach ($files as $filePath) {
                        $fileName = basename($filePath);

                        // ファイル名から日付を取得し、曜日を計算
                        $fileDate = substr($fileName, 0, 8);
                        $dayOfWeek = getDayOfWeek(date('w', strtotime($fileDate)));

                        $fileLinks .= "- [$fileName $dayOfWeek](./$year/$month/$fileName)\n";
                    }
                    if (!empty($fileLinks)) {
                        $monthSection = "### $month\n\n" . $fileLinks . "\n";
                        $monthContent .= $monthSection;
                    }
                }
            }

            if (!empty($monthContent)) {
                $readmeContent .= "## $year\n\n" . $monthContent . "\n";
            }
        }

        $readmeContent .= $footerTemplate;

        // プレースホルダーを削除
        $readmeContent = str_replace(
            ['{{year}}', '{{month}}', '{{#files}}', '{{/files}}', '{{file}}', '{{day_of_week}}'],
            ['', '', '', '', '', ''],
            $readmeContent
        );

        // 不要な空行を削除
        $readmeContent = preg_replace('/\n{3,}/', "\n\n", $readmeContent);

        file_put_contents($this->readmePath, $readmeContent);
    }
}
