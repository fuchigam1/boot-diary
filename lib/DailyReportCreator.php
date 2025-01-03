<?php

require_once 'common.php';
require_once 'Todoist.php';
require_once 'Store.php';
require_once 'GenerateFile.php';

class DailyReportCreator
{
    private string $dateFormat;
    private string $templatePath;
    private object $Store;
    private bool $autoRunGenerate = false;

    public function __construct()
    {
        $this->dateFormat = 'Ymd';
        $this->templatePath = TEMPLATE_DIR . DS . 'new.template.md';
        $this->Store = new Store();

        if (defined('AUTO_RUN_GENERATE')) {
            $this->autoRunGenerate = AUTO_RUN_GENERATE;
        }
    }

    public function execute($argv): void
    {
        // コマンドライン引数から日付を取得
        $inputDate = $argv[2] ?? null;

        if ($inputDate) {
            if (!Validation::isValidDate($inputDate)) {
                echo getColorLog("無効な日付指定です: $inputDate" . PHP_EOL, 'error');
                exit;
            }
            $date = date($this->dateFormat, strtotime($inputDate));
        } else {
            // 引数が指定されていない場合は現在の日付を使用
            $date = date($this->dateFormat);
        }

        $fileName = "$date" . REPORT_FILE_EXTENSION;
        $filePath = $this->Store->reportsDir . DS . $fileName;

        if ($this->Store->fileExists($fileName)) {
            echo getColorLog("ファイル '$fileName' は既に存在します。上書きしますか？ (y/n): ", 'warning');
            $response = in(['y', 'n']);
            if ($response !== 'y') {
                echo getColorLog("ファイルの上書きをキャンセルしました" . PHP_EOL, 'info');
                return;
            }
        }

        $templateContent = file_get_contents($this->templatePath);
        $dayOfWeek = getDayOfWeek((int)date('w', strtotime($date)));
        $templateContent = str_replace('{{date}}', date('Y-m-d', strtotime($date)), $templateContent);
        $templateContent = str_replace('{{day_of_week}}', $dayOfWeek, $templateContent);

        if ($this->autoRunGenerate) {
            $GenerateFile = new GenerateFile();
            $GenerateFile->execute();
        }

        // TODOISTのタスクを取得して追加する
        $Todoist = new Todoist();
        $tasks = $Todoist->getTodoistTasks();
        if (!empty($tasks)) {
            $todoistSection = "## 予定\n\n";
            foreach ($tasks as $task) {
                $projectName = '未分類';
                if (isset($task['project_name'])) {
                    $projectName = $task['project_name'];
                }
                $taskName = $task['content'];
                $todoistSection .= "- $projectName $taskName" . PHP_EOL;
            }
            $templateContent = str_replace('{{todoist_tasks}}', $todoistSection, $templateContent);
        } else {
            $templateContent = str_replace(PHP_EOL . '{{todoist_tasks}}' . PHP_EOL, "", $templateContent);
        }

        $this->Store->saveReport($filePath, $templateContent);  // Storeクラスを使用して保存

        echo getColorLog("日報ファイル '$fileName' を作成しました" . PHP_EOL, 'info');
        echo getColorLog($filePath . PHP_EOL, 'info');
    }

}
