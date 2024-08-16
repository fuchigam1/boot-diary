<?php
require_once 'common.php';

class Store {
    private string $reportsDir;

    public function __construct() {
        $this->reportsDir = REPORT_DIR;
        // reportsディレクトリが存在しない場合は作成する
        if (!file_exists($this->reportsDir)) {
            mkdir($this->reportsDir);
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

}
