<?php
/**
 * API連携用のファイルを生成するクラス
 */
class GenerateFile
{
    private string $workspaceId;
    private string $email;
    private string $password;

    public function __construct()
    {
        if (!defined('YOUR_TOGGL_WORKSPACE_ID')) {
            echo getColorLog("TogglワークスペースIDが設定されていません" . PHP_EOL, 'error');
            return;
        }

        if (trim(YOUR_TOGGL_WORKSPACE_ID) === '') {
            echo getColorLog("TogglワークスペースID設定に値が設定されていません" . PHP_EOL, 'error');
            return;
        }

        if (!defined('YOUR_TOGGL_EMAIL')) {
            echo getColorLog("Togglメールアドレスが設定されていません" . PHP_EOL, 'error');
            return;
        }

        if (trim(YOUR_TOGGL_EMAIL) === '') {
            echo getColorLog("Togglメールアドレス設定に値が設定されていません" . PHP_EOL, 'error');
            return;
        }

        if (!defined('YOUR_TOGGL_PASSWORD')) {
            echo getColorLog("Togglパスワードが設定されていません" . PHP_EOL, 'error');
            return;
        }

        if (trim(YOUR_TOGGL_PASSWORD) === '') {
            echo getColorLog("Togglパスワード設定に値が設定されていません" . PHP_EOL, 'error');
            return;
        }

        $this->workspaceId = YOUR_TOGGL_WORKSPACE_ID;
        $this->email = YOUR_TOGGL_EMAIL;
        $this->password = YOUR_TOGGL_PASSWORD;
    }

    public function execute(): void
    {
        $this->getProjectsFromTodoist();
        $this->getProjectsFromToggl();
    }

    /**
     * Togglからプロジェクト一覧を取得し、JSONファイルに保存する
     *
     * @link https://engineering.toggl.com/docs/api/projects#get-workspaceprojects
     */
    public function getProjectsFromToggl(): void
    {
        $url = "https://api.track.toggl.com/api/v9/workspaces/$this->workspaceId/projects";
        $projects = [];
        $page = 1;
        $perPage = 151;

        do {
            $ch = curl_init($url . "?page=$page&per_page=$perPage");
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_USERPWD, $this->email . ':' . $this->password);

            $response = curl_exec($ch);
            if (curl_errno($ch)) {
                echo 'Error:' . curl_error($ch);
                break;
            }
            // curl_close は PHP 8.0 以降で無効・8.5 で非推奨。ループ内でハンドルが溜まらないよう unset で解放する（PHP 7/8 両対応）
            unset($ch);

            $result = json_decode($response, true);
            foreach ($result as $project) {
                $projects[$project['id']] = $project['name'];
            }

            $page++;
        } while (count($result) === $perPage);

        $filePath = APP_ROOT . DS . '.tmp' . DS . 'projects_toggl.json';
        if (!is_dir(dirname($filePath))) {
            mkdir(dirname($filePath), 0777, true);
        }

        if ($projects) {
            file_put_contents($filePath, json_encode($projects, JSON_PRETTY_PRINT));
            echo getColorLog("Togglのプロジェクト一覧を取得し .tmp/projects_toggl.json に保存しました" . PHP_EOL, 'info');
        } else {
            echo getColorLog("Togglのプロジェクト一覧がありませんでした" . PHP_EOL, 'info');
        }
    }

    /**
     * TODOISTのプロジェクト一覧を取得する
     *
     * @link https://developer.todoist.com/api/v1/#tag/Projects/operation/get_projects_api_v1_projects_get
     */
    public function getProjectsFromTodoist(): void
    {
        if (!defined('YOUR_TODOIST_API_TOKEN')) {
            echo getColorLog("Todoist APIトークンが設定されていません" . PHP_EOL, 'error');
            return;
        }

        if (trim(YOUR_TODOIST_API_TOKEN) === '') {
            echo getColorLog("Todoist APIトークン設定に値が設定されていません" . PHP_EOL, 'error');
            return;
        }

        $projects = [];

        // ヘッダーを設定
        $headers = [
            'Authorization: Bearer ' . YOUR_TODOIST_API_TOKEN,
            'Content-Type: application/json',
        ];

        // 旧 REST API v2 (/rest/v2/projects) は廃止され 410 を返すため、統合 API v1 を使う。
        // v1 はカーソル方式のページネーションを返すので、タスク取得（Todoist::getTodoistTasks）と
        // 同じ方式で全ページを集約する
        $baseUrl = 'https://api.todoist.com/api/v1/projects';
        $limit = 200;
        $cursor = null;

        $ch = curl_init();
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
            do {
                $query = ['limit' => $limit];
                if ($cursor) {
                    $query['cursor'] = $cursor;
                }

                curl_setopt($ch, CURLOPT_URL, $baseUrl . '?' . http_build_query($query));

                $response = curl_exec($ch);
                $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

                if ($httpCode !== 200) {
                    // curl_close は PHP 8.0 以降で無効・8.5 で非推奨のため unset で解放する（PHP 7/8 両対応）
                    unset($ch);
                    posix_kill($pid, SIGTERM);
                    echo PHP_EOL;
                    echo getColorLog(PHP_EOL . "APIリクエストが失敗しました。HTTPステータスコード: $httpCode" . PHP_EOL, 'error');
                    return;
                }

                $decoded = json_decode($response, true);

                // v1 は {"results": [...], "next_cursor": ...} を返す。旧形式の単純配列にも耐えるようにする
                if (isset($decoded['results']) && is_array($decoded['results'])) {
                    foreach ($decoded['results'] as $project) {
                        $projects[] = $project;
                    }
                    $cursor = $decoded['next_cursor'] ?? null;
                } elseif (is_array($decoded)) {
                    foreach ($decoded as $project) {
                        $projects[] = $project;
                    }
                    $cursor = null;
                } else {
                    $cursor = null;
                }
            } while ($cursor);

            // curl_close は PHP 8.0 以降で無効・8.5 で非推奨のため unset で解放する（PHP 7/8 両対応）
            unset($ch);

            // 子プロセスを終了させる
            posix_kill($pid, SIGTERM);

            // cURLの処理が終わったら改行
            echo PHP_EOL;
        }

        $filePath = APP_ROOT . DS . '.tmp' . DS . 'projects_todoist.json';
        if (!is_dir(dirname($filePath))) {
            mkdir(dirname($filePath), 0777, true);
        }

        if ($projects) {
            file_put_contents($filePath, json_encode($projects, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
            echo getColorLog("Todoistのプロジェクト一覧を取得し .tmp/projects_todoist.json に保存しました" . PHP_EOL, 'info');
        } else {
            echo getColorLog("Todoistのプロジェクト一覧がありませんでした" . PHP_EOL, 'info');
        }
    }

}
