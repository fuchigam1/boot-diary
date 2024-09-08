<?php
require_once 'Store.php';
/**
 * Toggl APIを使用してタイムエントリーを取得する連携用クラス
 *
 * @property Store $Store
 */
class Toggl {
    private string $apiToken;
    private string $workspaceId;
    private array $projects;
    private string $dateFormat;
    private object $Store;

    public function __construct() {
        if (defined('YOUR_TOGGL_API_TOKEN') && !empty(YOUR_TOGGL_API_TOKEN)) {
            $this->apiToken = YOUR_TOGGL_API_TOKEN;
        } else {
            echo getColorLog("Toggl APIトークンが設定されていません" . PHP_EOL, 'error');
            return;
        }

        if (defined('YOUR_TOGGL_WORKSPACE_ID') && !empty(YOUR_TOGGL_WORKSPACE_ID)) {
            $this->workspaceId = YOUR_TOGGL_WORKSPACE_ID;
        } else {
            echo getColorLog("TogglワークスペースIDが設定されていません" . PHP_EOL, 'error');
            return;
        }

        $this->Store = new Store();
        $this->dateFormat = 'Ymd';
        $this->projects = $this->getProjects();
    }

    public function execute($argv): void {
        if (!$this->apiToken || !$this->workspaceId) {
            return;
        }

        // コマンドライン引数から日付を取得
        $inputDate = $argv[2] ?? null;
        if ($inputDate) {
            if (!Validation::isValidDate($inputDate)) {
                echo getColorLog("無効な日付指定です: $inputDate" . PHP_EOL, 'error');
                exit;
            }
            $date = date($this->dateFormat, strtotime($inputDate));
        } else {
            // 引数がない場合、Storeクラスから最新の日付を取得
            $date = $this->Store->getLatestReportDate();

            // 最新ファイルがない場合、今日の日付を使用
            if (!$date) {
                $date = date($this->dateFormat);
            }
        }

        $content = $this->Store->readReport($date . REPORT_FILE_EXTENSION);

        if (!$content) {
            $content = "## 内容\n";
        }

        // 日付のタイムエントリーを取得
        $timeEntries = $this->getTimeEntries($date);
        if (!$timeEntries) {
            echo getColorLog("Togglのタイムエントリーがありませんでした" . PHP_EOL, 'error');
            return;
        }

        // データの整理と集約
        $entriesByProjectAndDescription = [];
        foreach ($timeEntries as $entry) {
            // タグがあるエントリーを除外
            if (EXCLUDE_HASTAG_FOR_TOGGL && isset($entry['tag_ids']) && count($entry['tag_ids']) > 0) {
                continue;
            }

            $projectName = isset($entry['project_id']) && isset($this->projects[$entry['project_id']]) ? $this->projects[$entry['project_id']] : 'Without project';

            // プロジェクト名が EXCLUDE_PROJECTS_FOR_TOGGL に含まれているか確認
            if (in_array($projectName, EXCLUDE_PROJECTS_FOR_TOGGL)) {
                continue;
            }

            $description = $entry['description'];
            $totalSeconds = 0;

            foreach ($entry['time_entries'] as $item) {
                $totalSeconds += $item['seconds'];
            }

            if (!isset($entriesByProjectAndDescription[$projectName])) {
                $entriesByProjectAndDescription[$projectName] = [];
            }

            if (!isset($entriesByProjectAndDescription[$projectName][$description])) {
                $entriesByProjectAndDescription[$projectName][$description] = 0;
            }

            $entriesByProjectAndDescription[$projectName][$description] += $totalSeconds;
        }

        // プロジェクト名でソート
        ksort($entriesByProjectAndDescription);

        // $entriesByProjectAndDescription を $duration の降順でソート
        $sortedEntries = [];
        foreach ($entriesByProjectAndDescription as $projectName => $descriptions) {
            foreach ($descriptions as $description => $totalSeconds) {
                $sortedEntries[] = [
                    'project' => $projectName,
                    'description' => $description,
                    'totalSeconds' => $totalSeconds,
                ];
            }
        }

        // $totalSeconds で降順ソート
        usort($sortedEntries, function($a, $b) {
            return $b['totalSeconds'] - $a['totalSeconds'];
        });

        // '## 内容' の直上に追加
        $newContent = "## 活動 Toggl Summary Reports\n\n";
        foreach ($sortedEntries as $entry) {
            $duration = $this->formatDuration($entry['totalSeconds']);
            $newContent .= "- " . $entry['project'] . " " . $entry['description'] . " " . $duration . PHP_EOL;
        }

        $content = str_replace("## 内容", $newContent . "\n\n## 内容", $content);

        // Storeクラスを使用してファイルに書き込み
        $this->Store->saveReport($date . REPORT_FILE_EXTENSION, $content);

        echo getColorLog("Togglのタイムエントリーをファイルに追記しました" . PHP_EOL, 'notice');
    }

    /**
     * Togglに記録しているサマリーを取得する
     *
     * @param string $date
     * @return array
     * @link https://engineering.toggl.com/docs/reports/summary_reports#post-search-time-entries
     */
    public function getTimeEntries(string $date): array {
        // メッセージを表示
        echo getColorLog("Togglから記録を取得しています。この処理には少し時間がかかるのでお待ちください" . PHP_EOL, 'notice');

        $startDate = date('Y-m-d', strtotime($date));
        $endDate = date('Y-m-d', strtotime($date . ' +1 day'));

        $url = "https://api.track.toggl.com/reports/api/v3/workspace/$this->workspaceId/search/time_entries";

        $postData = json_encode([
            'start_date' => $startDate,
            'end_date' => $endDate,
            'user_agent' => 'api_test',
        ]);

        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_USERPWD, $this->apiToken . ':api_token');
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json',
            'Content-Length: ' . strlen($postData),
        ]);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $postData);

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
            if (curl_errno($ch)) {
                echo getColorLog('Error:' . curl_error($ch), 'error');
            }
            curl_close($ch);

            // 子プロセスを終了させる
            posix_kill($pid, SIGTERM);

            // cURLの処理が終わったら改行
            echo PHP_EOL;

            if ($httpCode !== 200) {
                echo getColorLog("APIリクエストが失敗しました。HTTPステータスコード: $httpCode" . PHP_EOL, 'error');
            }
        }
        return json_decode($response, true);
    }

    /**
     * プロジェクト一覧を json ファイルから取得する
     *
     * @return array
     */
    private function getProjects(): array {
        $filePath = APP_ROOT . DS . '.tmp' . DS . 'projects_toggl.json';
        if (!file_exists($filePath)) {
            echo getColorLog("Togglプロジェクト一覧ファイルが見つかりません" . PHP_EOL, 'error');
            return [];
        }

        $projects = json_decode(file_get_contents($filePath), true);
        return $projects;
    }

    /**
     * 秒数を分に変換する
     *
     * @param int $seconds
     * @return int
     */
    private function formatDuration(int $seconds): int {
        $minutes = $seconds / 60;
        return (int) floor($minutes);
    }

}
