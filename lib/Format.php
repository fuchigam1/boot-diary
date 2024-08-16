<?php
require_once 'common.php';
require_once 'Store.php';
/**
 * 日報ファイルを指定した日付に移動し、README.md を更新する
 *
 * @property Store $Store
 */
class Format
{
    private string $fileExtension;
    private string $readmePath;
    private string $headerTemplatePath;
    private string $mainTemplatePath;
    private string $footerTemplatePath;
    private string $today;
    private object $Store;

    public function __construct()
    {
        $this->fileExtension = '.md';
        $this->readmePath = APP_ROOT . DS . 'README.md';
        $this->headerTemplatePath = TEMPLATE_DIR . DS . 'format-header.template.md';
        $this->mainTemplatePath = TEMPLATE_DIR . DS . 'format.template.md';
        $this->footerTemplatePath = TEMPLATE_DIR . DS . 'format-footer.template.md';
        $this->today = date('Ymd'); // 今日の日付を取得
        $this->Store = new Store();
    }

    public function execute(array $argv): void
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

    /**
     * 既に日報ファイルが存在する場合、上書きするか確認する
     *
     * @param string $filePath
     * @return void
     */
    private function confirmOverwrite(string $filePath): void
    {
        $fileName = basename($filePath);
        echo getColorLog("$fileName はすでに存在します。上書きしますか？ (y/n): ", 'warning');
        $response = in(['y', 'n']);
        if ($response !== 'y') {
            echo getColorLog("ファイルの上書きをキャンセルしました". PHP_EOL, 'info');
            exit;
        }
    }

    /**
     * README.md を更新する
     *
     * @return void
     */
    private function updateReadme(): void
    {
        $headerTemplate = $this->Store->readTemplate(basename($this->headerTemplatePath));
        $mainTemplate = $this->Store->readTemplate(basename($this->mainTemplatePath));
        $footerTemplate = $this->Store->readTemplate(basename($this->footerTemplatePath));

        $readmeContent = $headerTemplate . "\n";

        // 年ディレクトリを取得
        $years = glob($this->Store->reportsDir . DS . '[0-9]*', GLOB_ONLYDIR);

        // 作成日時でソート（新しい順）
        usort($years, function($a, $b) {
            return filemtime($b) - filemtime($a);
        });

        foreach ($years as $yearPath) {
            $year = basename($yearPath);
            $yearContent = str_replace('{{year}}', $year, $mainTemplate);

            // 月ディレクトリを取得
            $months = glob($yearPath . DS . '*', GLOB_ONLYDIR);

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
                    $monthSection = "### $month\n\n" . $fileLinks . "\n";
                    $monthContent .= $monthSection;
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

    /**
     * 絶対パスをREADME.mdからの相対パスに変換する
     *
     * @param string $absolutePath 変換したい絶対パス
     * @param string $basePath 基準となる絶対パス (README.md が存在するディレクトリ)
     * @return string
     */
    private function convertToRelativePath(string $absolutePath, string $basePath): string {
        // $absolutePath と $basePath を標準化
        $absolutePath = realpath($absolutePath);
        $basePath = realpath($basePath);

        // 絶対パスが basePath で始まる場合、その部分を削除して相対パスにする
        if (strpos($absolutePath, $basePath) === 0) {
            $relativePath = substr($absolutePath, strlen($basePath) + 1); // +1 for the trailing slash
        } else {
            // basePath の外部にある場合は絶対パスを返す (異なるボリューム/ディレクトリツリー間のリンクは相対化できない)
            return $absolutePath;
        }

        // ./を先頭に付加して相対パスにする
        return '.' . DS . $relativePath;
    }

}
