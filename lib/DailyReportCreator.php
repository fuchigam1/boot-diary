<?php
require_once 'common.php';
require_once 'Todoist.php';

class DailyReportCreator
{
    private $dateFormat;
    private $templatePath;

    public function __construct()
    {
        $this->dateFormat = 'Ymd';
        $this->templatePath = __DIR__ . '/../template/new.template.md';
    }

    public function execute($argv)
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

        $fileName = "$date.md";
        $filePath = __DIR__ . '/../' . $fileName;

        if (file_exists($filePath)) {
            echo getColorLog("ファイル '$fileName' は既に存在します。上書きしますか？ (y/n): ", 'warning');
            $handle = fopen("php://stdin", "r");
            $response = trim(fgets($handle));

            if (strtolower($response) !== 'y') {
                echo getColorLog("ファイルの上書きをキャンセルしました". PHP_EOL, 'info');
                return;
            }
        }

        $templateContent = file_get_contents($this->templatePath);
        $dayOfWeek = getDayOfWeek(date('w', strtotime($date)));
        $templateContent = str_replace('{{date}}', date('Y-m-d', strtotime($date)), $templateContent);
        $templateContent = str_replace('{{day_of_week}}', $dayOfWeek, $templateContent);

        // TODOISTのタスクを取得して追加する
        $Todoist = new Todoist();
        $tasks = $Todoist->getTodoistTasks();
        if (!empty($tasks)) {
            $todoistSection = "## 予定\n\n";
            foreach ($tasks as $task) {
                $projectName = $task['project_name'] ?? '未分類';
                $taskName = $task['content'];
                $todoistSection .= "- $projectName $taskName". PHP_EOL;
            }
            $templateContent = str_replace('{{todoist_tasks}}', $todoistSection, $templateContent);
        } else {
            $templateContent = str_replace(PHP_EOL .'{{todoist_tasks}}'. PHP_EOL, "", $templateContent);
        }

        file_put_contents($filePath, $templateContent);

        echo getColorLog("日報ファイル '$fileName' を作成しました". PHP_EOL, 'info');
        echo getColorLog(APP_ROOT . DS . $fileName . PHP_EOL, 'info');
    }

}
