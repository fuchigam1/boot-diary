# boot-diary へのコミット

コミットをお待ちしています。貢献するにはいくつかの方法があります。

- 課題を見つけたら、GitHubで [Issue](https://github.com/fuchigam1/boot-diary/issues) を作成してください。
- 課題を解決したら、GitHubで プルリクエストを送信してください。


## PHPStan

プログラムは [PHPStan](https://phpstan.org/) により静的解析を行ってます。

```
.
├── phpstan-baseline.neon 現在は対応しない検出内容
└── phpstan.neon 実行時設定内容
```

### 利用方法

```bash
# 導入
$ brew install phpstan
$ phpstan --version
PHPStan - PHP Static Analysis Tool 1.10.36

# 解析実行
$ phpstan analyse

# 捕捉
# メモリー不足エラー時は以下のように指定する or php.ini で指定
$ phpstan analyse --memory-limit=512M
```

### baseline ファイルの利用

検出されたエラーを今時点では修正が難しいという場合は、baseline ファイルを用いてエラーを無視するようにします。

```bash
$ phpstan analyse --generate-baseline
```
