<?php
require_once 'lib/common.php';
require_once 'lib/Validation.php';
require_once 'lib/Init.php';
require_once 'lib/DailyReportCreator.php';
require_once 'lib/Format.php';
require_once 'lib/GenerateFile.php';
require_once 'lib/Toggl.php';
require_once 'lib/Todoist.php';

# -------------------------------------------------
# 実行
# -------------------------------------------------
$Run = new Run($argv);
$Run->exec();

class Run {
    private array $allowCommand = [
        'init',
        'new',
        'toggl',
        'format',
        'generate',
    ];

    private array $commandAliases = [
        'i' => 'init',
        'n' => 'new',
        't' => 'toggl',
        'f' => 'format',
        'g' => 'generate',
    ];

    private array $argv;

    public function __construct($argv) {
        $this->argv = $argv;
    }

    public function exec(): void {
        $this->init($this->argv);

        $command = $this->argv[1] ?? '';

        // コマンドがエイリアスに該当するかチェックして、正規コマンドに変換する
        if (isset($this->commandAliases[$command])) {
            $command = $this->commandAliases[$command];
        }

        switch ($command) {
            case 'init':
                $init = new Init();
                $init->execute();
                break;
            case 'new':
                $new = new DailyReportCreator();
                $new->execute($this->argv);
                break;
            case 'format':
                $format = new Format();
                $format->execute($this->argv);
                break;
            case 'generate':
                $generateFile = new GenerateFile();
                $generateFile->execute();
                break;
            case 'toggl':
                $toggl = new Toggl();
                $toggl->execute($this->argv);
                break;
            default:
                $manual = $this->getManual();
                if (!isset($this->argv[1])) {
                    if (isCli()) {
                        echo $manual;
                    } else {
                        echo nl2br($manual);
                        echo getColorLog('[Error]CLIで実行してください' . PHP_EOL, 'error');
                    }
                    exit;
                }
                break;
        }
    }

    /**
     * 初期実行処理
     *
     * @param array $argv
     * @return void
     */
    private function init(array $argv): void {
        $this->checkEnviroment($argv);
        $this->checkArguments($argv);
    }

    /**
     * 環境チェック
     *
     * @param array $argv
     * @return void
     */
    private function checkEnviroment(array $argv): void {
        Validation::checkPhpVersion();
        Validation::checkSettingFile();

        // 初期化時は各templeteファイルがまだ存在しない状態のためチェックしない
        if (isset($argv[1]) && $argv[1] === 'init') {
            return;
        }
        Validation::checkTemplateFiles();
    }

    /**
     * 引数チェック
     *
     * @param array $argv
     * @return void
     */
    private function checkArguments(array $argv): void {
        if (isset($argv[1])) {
            $command = $argv[1];
            // コマンドがエイリアスに該当するかチェックして、正規コマンドに変換する
            if (isset($this->commandAliases[$command])) {
                $command = $this->commandAliases[$command];
            }

            if (!in_array($command, $this->allowCommand, true)) {
                echo getColorLog('実行できません' . PHP_EOL, 'error');
                exit;
            }
        }
    }

    /**
     * マニュアル取得
     *
     * @return string
     */
    private function getManual(): string {
        $manual = '';
        $manual .= '==================================================' . PHP_EOL;
        $manual .= 'boot-diary は日報の簡単作成を補助するツールです。以下のステップで完結できます。' . PHP_EOL;
        $manual .= 'VERSION: ' . getVersion() . PHP_EOL;
        $manual .= getColorLog('  1. php run.php new でファイル作成' . PHP_EOL, 'notice');
        $manual .= getColorLog('  2. 自由に書く' . PHP_EOL, 'notice');
        $manual .= getColorLog('  3. php run.php format で整理' . PHP_EOL, 'notice');
        $manual .= getColorLog('  4. 終了' . PHP_EOL, 'notice');
        $manual .= '' . PHP_EOL;
        $manual .= '日報は markdown 形式により git に commit ＆ push で管理できます。' . PHP_EOL;
        $manual .= 'また、Todoist, Toggl と連携することで、日々の日報作成を強力にサポートします。' . PHP_EOL;
        $manual .= '連携利用することで、以下のステップで完結できます。' . PHP_EOL;
        $manual .= getColorLog('  1. php run.php new でファイル作成: Todoistから当日の予定一覧を自動出力' . PHP_EOL, 'notice');
        $manual .= getColorLog('  2. php run.php toggl で活動追記: Togglから当日の活動一覧を自動出力' . PHP_EOL, 'notice');
        $manual .= getColorLog('  3. 自由に書く' . PHP_EOL, 'notice');
        $manual .= getColorLog('  4. php run.php format で整理' . PHP_EOL, 'notice');
        $manual .= getColorLog('  5. 終了（git に commit ＆ push）' . PHP_EOL, 'notice');
        $manual .= '==================================================' . PHP_EOL;
        $manual .= getColorLog('使い方: php run.php [command]' . PHP_EOL, 'info');
        $manual .= '利用可能なcommand' . PHP_EOL;
        $manual .= '  <non> ' . implode(' ', $this->allowCommand) . PHP_EOL;
        $manual .= 'commandの短縮形（利用可能な command の頭文字）' . PHP_EOL;
        $manual .= '   ' . implode(' ', array_keys($this->commandAliases)) . PHP_EOL;
        $manual .= 'command一覧' . PHP_EOL;
        $manual .= getColorLog('  - 初期化: run.php init' . PHP_EOL, 'info');
        $manual .= '    - 設置時初回だけ実行し、以降の実行は不要' . PHP_EOL;
        $manual .= '    - 実行後に ディレクトリに各種テンプレートファイルを作成する' . PHP_EOL;
        $manual .= getColorLog('  - 開始時: run.php new [yyyymmdd]' . PHP_EOL, 'info');
        $manual .= '    - 今日の日報ファイルを作成する' . PHP_EOL;
        $manual .= getColorLog('  - 終了時: run.php toggl [yyyymmdd]' . PHP_EOL, 'info');
        $manual .= '    - 今日の記録を書き出す' . PHP_EOL;
        $manual .= getColorLog('  - 終了時: run.php format [yyyymmdd]' . PHP_EOL, 'info');
        $manual .= '    - 今日の日報ファイルを yyyy/mm 配下に移動し、README.md にリンクを追加する' . PHP_EOL;
        $manual .= getColorLog('  - 適　宜: run.php generate' . PHP_EOL, 'info');
        $manual .= '    - Todoist, Toggl のプロジェクト一覧を取得し json ファイルで保存する' . PHP_EOL;
        $manual .= '利用可能なオプション指定' . PHP_EOL;
        $manual .= '    - yyyymmdd で日付を指定することで、対象日のファイルを操作する' . PHP_EOL;
        $manual .= '    - 指定可能なフォーマットは yyyymmdd, yyyy-mm-dd' . PHP_EOL;
        $manual .= 'templateの利用' . PHP_EOL;
        $manual .= '  - 日報ファイルの新規作成時のフォーマットは new.template.md。'. getColorLog('「## 内容」箇所は消さないこと' . PHP_EOL, 'warning');
        $manual .= '  - README.md のヘッダー部分は format-header.template.md' . PHP_EOL;
        $manual .= '  - README.md の年・月・各日報のリンク部分は format.template.md' . PHP_EOL;
        $manual .= '  - README.md のフッター部分は format-footer.template.md' . PHP_EOL;
        $manual .= '==================================================' . PHP_EOL;
        return $manual;
    }

}
