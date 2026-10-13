# PDF2submission

QRコード付き用紙の生成と、スキャンした答案のMoodle課題への代理提出を行うブロックプラグインです。
Moodle 4.5 / PHP 8.3向けに開発しています。MacMoodleSetupから開発用コードを独立させました。
Mac専用ではなく、対応するMoodleサーバーへインストールできます。

| 版 | Moodleコンポーネント | 現在のリリース | 案内 |
| --- | --- | --- | --- |
| 現行版 | block_pdf2submission | 0.1.10 | [利用方法](plugins/blocks/pdf2submission/README.md) |
| ベータ版 | block_pdf2submissionbeta | 0.2.02 | [利用方法](plugins/blocks/pdf2submissionbeta/README.md) |

両版は別ブロックとして共存できます。ベータ版は順序指定CSVの保存、ブロックからのQR生成、
複数課題のスキャン振り分けに対応します。教員用紙はCSVに指定した位置でのみ印刷します。

## 配布ZIPを作る

PHP CLI、Python 3、Makeのある環境で実行します。

```sh
make check
make package
```

`dist/pdf2submission-0.1.10.zip` と `dist/pdf2submissionbeta-0.2.02.zip` を生成します。
Moodleのプラグインインストール画面から目的のZIPを選んで追加・更新してください。
既存サイトを更新する前に通常のMoodleバックアップを取得してください。
これらのコマンドはMoodleを起動したり、既存サイトへインストールしたりしません。

サーバーではGhostscript、ZBar、Python pypdf、GNU timeoutが必要です。
導入手順と上限は各版の利用案内を参照してください。Moodle 5系は未検証です。

## 開発と検証

`plugins/blocks/` が開発元、`scripts/tests/` がテストです。
`make check` は構文検査と単体回帰テストです。Moodle上での動作確認とは異なります。
`scripts/tests/pdf2_integration.php` は人工的なコース・ユーザーを作成する統合テストなので、
破棄可能な検証用Moodleでのみ、`MACMOODLE_PDF2_TEST=1` を指定して実行してください。

MacMoodleSetupはMoodle＋STACKの起動環境として別に維持します。
その同梱コピーは既存利用を維持するため残しますが、今後のプラグイン開発はこのリポジトリで行います。
ZIPを作っただけではMacMoodleSetupや研究室・大学のMoodleへ自動反映されません。

開発状況・検証済み範囲・次の作業は [HANDOFF.md](HANDOFF.md) を参照してください。

## 出典・ライセンス

喜多敏博氏の [pdf2submission](https://github.com/kita-toshihiro/pdf2submission) のワークフローを参考に、
現行Moodle APIと署名付きQRを使って再実装したものです。上流の公式リリースではありません。
GPL-3.0-or-later。[COPYING.txt](COPYING.txt) と [NOTICE](NOTICE) を同梱します。
