<?php
require_once 'lib/common.php';

class Init
{
    private $templateDir;
    private $newTemplatePath;
    private $formatHeaderTemplatePath;
    private $formatTemplatePath;
    private $formatFooterTemplatePath;

    public function __construct()
    {
        $this->templateDir = __DIR__ . '/../template';
        $this->newTemplatePath = $this->templateDir . '/new.template.md';
        $this->formatHeaderTemplatePath = $this->templateDir . '/format-header.template.md';
        $this->formatTemplatePath = $this->templateDir . '/format.template.md';
        $this->formatFooterTemplatePath = $this->templateDir . '/format-footer.template.md';
    }

    public function execute()
    {
        $newTemplateContent = <<<EOT
# {{date}} {{day_of_week}}

{{todoist_tasks}}

## 内容

EOT;

        $formatHeaderTemplateContent = <<<EOT
# 業務日報 diary record

EOT;

        $formatTemplateContent = <<<EOT
## {{year}}

### {{month}}

{{#files}}
- [{{file}}](./{{year}}/{{month}}/{{file}})
{{/files}}

EOT;

        $formatFooterTemplateContent = <<<EOT

# boot-diary

日報を簡単に作成できるツールです。

## 使い方

- ローカルに boot-deary ディレクトリ毎配置してください。
- ターミナルにて、設置したディレクトリに移動し、以下のコマンドを実行してください。
- ```php run.php```

EOT;

        if (!is_dir($this->templateDir)) {
            mkdir($this->templateDir, 0777, true);
        }

        file_put_contents($this->newTemplatePath, $newTemplateContent);
        file_put_contents($this->formatHeaderTemplatePath, $formatHeaderTemplateContent);
        file_put_contents($this->formatTemplatePath, $formatTemplateContent);
        file_put_contents($this->formatFooterTemplatePath, $formatFooterTemplateContent);

        echo getColorLog(APP_ROOT .DS. "template 配下にテンプレートファイルを作成しました". PHP_EOL, 'info');

        // lib/setting.php.org を lib/setting.php として複製（存在しない場合のみ）
        $settingOrgPath = APP_ROOT .DS. 'lib' .DS. 'setting.php.org';
        $settingPath = APP_ROOT .DS. 'lib' .DS. 'setting.php';
        if (file_exists($settingOrgPath)) {
            if (!file_exists($settingPath)) {
                copy($settingOrgPath, $settingPath);
                echo getColorLog($settingPath ." を作成しました". PHP_EOL, 'info');
                echo getColorLog("Todoist, Toggl との連携利用時は setting.php を編集してください". PHP_EOL, 'warning');
            } else {
                echo getColorLog($settingPath ." は既に存在します". PHP_EOL, 'warning');
            }
        } else {
            echo getColorLog($settingOrgPath ." が見つかりません". PHP_EOL, 'error');
        }
    }
}
