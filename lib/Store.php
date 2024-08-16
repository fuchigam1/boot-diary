<?php
require_once 'common.php';

class Store {
    public string $reportsDir;
    public string $templateDir;

    public function __construct() {
        $this->reportsDir = REPORT_DIR;
        // reportsディレクトリが存在しない場合は作成する
        if (!file_exists($this->reportsDir)) {
            mkdir($this->reportsDir);
        }

        $this->templateDir = TEMPLATE_DIR;
        // templatesディレクトリが存在しない場合は作成する
        if (!file_exists($this->templateDir)) {
            mkdir($this->templateDir);
        }
    }

    /**
     * 指定したファイル名で、指定した内容を保存する
     *
     * @param string $fileName
     * @param string $content
     * @return void
     */
    public function saveReport(string $fileName, string $content): void {
        $filePath = $this->reportsDir . DS . $fileName;
        file_put_contents($filePath, $content);
    }

    /**
     * 指定したファイル名のファイルが存在するか確認する
     *
     * @param string $fileName
     * @return bool
     */
    public function fileExists(string $fileName): bool {
        $filePath = $this->reportsDir . DS . $fileName;
        return file_exists($filePath);
    }

    /**
     * 指定したファイル名のファイルの内容を読み込む
     *
     * @param string $fileName
     * @return string
     */
    public function readReport(string $fileName): string {
        $filePath = $this->reportsDir . DS . $fileName;
        if ($this->fileExists($fileName)) {
            return file_get_contents($filePath);
        }
        return '';
    }

    /**
     * 指定したテンプレートファイルの内容を読み込む
     *
     * @param string $fileName
     * @return string
     */
    public function readTemplate(string $fileName): string {
        $filePath = $this->templateDir . DS . $fileName;
        if (file_exists($filePath)) {
            return file_get_contents($filePath);
        }
        return '';
    }

}