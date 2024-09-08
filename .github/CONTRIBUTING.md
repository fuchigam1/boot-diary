# boot-diary へのコミット

コミットをお待ちしています。貢献するにはいくつかの方法があります。

- 課題を見つけたら、GitHubで [Issue](https://github.com/fuchigam1/boot-diary/issues) を作成してください。
- 課題を解決したら、GitHubで プルリクエストを送信してください。


## git cz

プロジェクトでは [git-cz](https://github.com/streamich/git-cz) を使用して、コミットメッセージをフォーマットしています。  
`git cz` コマンドを使うことで、セマンティックなコミットメッセージが簡単に書けます。  

1. 導入  [git-cz](https://github.com/streamich/git-cz) を確認
2. `git add` で変更をステージング
3. `git cz` でガイドに従ってコミットメッセージを作成

### 参考

- Conventional Commits https://www.conventionalcommits.org/ja/v1.0.0/


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
