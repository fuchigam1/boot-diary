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
            curl_close($ch);

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
     * @link https://developer.todoist.com/rest/v2/#get-all-projects
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

        $ch = curl_init('https://api.todoist.com/rest/v2/projects');
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
                echo getColorLog(PHP_EOL . "APIリクエストが失敗しました。HTTPステータスコード: $httpCode" . PHP_EOL, 'error');
                return;
            }

            $projects = json_decode($response, true);
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
