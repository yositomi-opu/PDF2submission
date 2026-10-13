# PDF2submission

QRコード付き用紙の生成と、スキャンした答案のMoodle課題への代理提出を行うブロックプラグインです。
Moodle 4.5 / PHP 8.3向けに開発しています。MacMoodleSetupから開発用コードを独立させました。
Mac専用ではなく、対応するMoodleサーバーへインストールできます。

正式版 **1.0.0** はBeta 0.2.03を基にした後継版です。表示名は **PDF2submission** です。
既存Betaからそのまま更新できるよう、内部コンポーネント名 `block_pdf2submissionbeta` と
インストール先 `blocks/pdf2submissionbeta` を維持しています。

順序指定CSVの保存、ブロックからのQR生成、複数課題のスキャン振り分けに対応します。
教員用紙はCSVに指定した位置でのみ印刷します。[利用方法](plugins/blocks/pdf2submissionbeta/README.md)

## 旧版と更新

- Beta 0.2.03利用者: 正式版ZIPで既存ブロックを更新します。先にアンインストールしないでください。
  保存順序・署名キー・既存QRを引き継ぎます。
- 0.1系利用者: 正式版は別コンポーネントとして追加します。0.1系の直接の置換ではありません。
  同じサイトの0.1系QRを読むには旧版の署名キーが必要なため、既存ブロックをアンインストールしないでください。
  課題の提出物はMoodleの課題に保存されます。
- 0.1系は0.1.11で開発終了。mainからソースを外し、`legacy-0.1.11` タグで保存しています。
  最終Betaは `beta-0.2.03` タグです。[旧版配布ZIPと再現手順](releases/legacy/README.md)

## Upgrade (English)

Version 1.0.0 promotes Beta 0.2.03 to the stable **PDF2submission** release.
The component and installation directory remain `block_pdf2submissionbeta` and `blocks/pdf2submissionbeta`.
Update an existing Beta installation in place; do not uninstall it first. Saved ordering, signing keys and QR codes are retained.
Version 0.1.11 is end of development. Install 1.0 alongside it, and retain the old plugin configuration to read its signed QR codes.

## 配布ZIPを作る

PHP CLI、Python 3、Makeのある環境で実行します。

```sh
make check
make package
```

`dist/pdf2submissionbeta-1.0.0.zip` を生成します。ファイル名のbetaは更新互換性のため残しています。
Moodleのプラグインインストール画面から目的のZIPを選んで追加・更新してください。
既存サイトを更新する前に通常のMoodleバックアップを取得してください。
これらのコマンドはMoodleを起動したり、既存サイトへインストールしたりしません。

サーバーではGhostscript、ZBar、Python pypdf、GNU timeoutが必要です。
導入手順と上限は利用案内を参照してください。Moodle 5系は未検証です。

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
