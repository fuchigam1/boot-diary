<?php
/**
 * Todoist APIを利用してタスク一覧を取得する連携用クラス
 */
class Todoist {
    private string $apiToken;
    private array $projects;

    public function __construct() {
        if (defined('YOUR_TODOIST_API_TOKEN') && !empty(YOUR_TODOIST_API_TOKEN)) {
            $this->apiToken = YOUR_TODOIST_API_TOKEN;
        } else {
            echo getColorLog("Todoist APIトークンが設定されていません" . PHP_EOL, 'error');
            return;
        }

        $this->projects = $this->getProjects();
    }

    /**
     * TODOISTのタスクを取得する
     *
     * @return array
     */
    public function getTodoistTasks(): array {
        if (!$this->apiToken) {
            return [];
        }

        // メッセージを表示
        echo getColorLog("Todoistからタスクを取得しています。この処理には少し時間がかかるのでお待ちください" . PHP_EOL, 'notice');

        // プロジェクト一覧を取得
        $projectMap = [];
        if ($this->projects) {
            foreach ($this->projects as $project) {
                $projectMap[$project['id']] = $project['name'];
            }
        }

        // ヘッダーを設定
        $headers = [
            'Authorization: Bearer ' . $this->apiToken,
            'Content-Type: application/json',
        ];

        $ch = curl_init('https://api.todoist.com/rest/v2/tasks');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);

        // ドット表示プロセスをフォーク（LinuxやmacOSで使えるが、Windowsでは互換性のある方法を使う必要がある）
        $pid = pcntl_fork();
        if ($pid == -1) {
            die('プロセスの作成に失敗しました');
        } elseif ($pid == 0) {
            // 子プロセス: ドットを表示
            displayDots();
            exit;
        } else {
            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);

            // 子プロセスを終了させる
            posix_kill($pid, SIGTERM);

            // cURLの処理が終わったら改行
            echo PHP_EOL;

            if ($httpCode !== 200) {
                echo getColorLog("APIリクエストが失敗しました。HTTPステータスコード: $httpCode" . PHP_EOL, 'error');
                return [];
            }

            // 取得したタスクをデコード
            $tasks = json_decode($response, true);
        }

        // 今日のタスクのみを取得
        $todayTasks = []; // タスク一覧の初期化

        // 除外するプロジェクト名の配列
        $excludeProjects = defined('EXCLUDE_PROJECTS_FOR_TODOIST') ? EXCLUDE_PROJECTS_FOR_TODOIST : [];

        foreach ($tasks as $task) {
            if (isset($task['due'])) {
                // デフォルトのタイムゾーンを日本時間に設定
                $timezone = new DateTimeZone('Asia/Tokyo');

                if (isset($task['due']['datetime'])) {
                    // datetime がある場合
                    $dueDateTime = new DateTime($task['due']['datetime'], $timezone);
                } elseif (isset($task['due']['date'])) {
                    // date のみがある場合
                    $dueDateString = $task['due']['date'];
                    $dueDateTime = new DateTime($dueDateString . 'T00:00:00', $timezone);
                } else {
                    // due がない場合はスキップ
                    continue;
                }

                // 今日の開始と終了を設定
                $today = new DateTime('today', $timezone);
                $endOfDay = clone $today;
                $endOfDay->setTime(23, 59, 59);

                if ($dueDateTime >= $today && $dueDateTime <= $endOfDay) {
                    // プロジェクト名を取得
                    $projectName = '未分類'; // デフォルト値
                    if (isset($task['project_id']) && isset($projectMap[$task['project_id']])) {
                        $projectName = $projectMap[$task['project_id']];
                    }

                    // 除外するプロジェクト名のチェック
                    if (in_array($projectName, $excludeProjects, true)) {
                        continue;
                    }

                    // タスクにプロジェクト名を追加
                    $task['project_name'] = $projectName;
                    $todayTasks[] = $task;
                }
            }
        }

        return $todayTasks;
    }

    /**
     * プロジェクト一覧を取得する
     *
     * @return array
     */
    private function getProjects(): array {
        $filePath = APP_ROOT . DS . '.tmp' . DS . 'projects_todoist.json';
        if (!file_exists($filePath)) {
            echo getColorLog("Todoistプロジェクト一覧ファイルが見つかりません" . PHP_EOL, 'error');
            return [];
        }

        $projects = json_decode(file_get_contents($filePath), true);
        return $projects;
    }

}
