<?php
// SPDX-License-Identifier: GPL-3.0-or-later
$string['pluginname'] = 'PDF2submission Beta';
$string['pdf2submissionbeta:addinstance'] = '紙の答案取込ブロックを追加する';
$string['pdf2submissionbeta:manage'] = '学生に代わって紙の答案を課題へ登録する';
$string['manage'] = '用紙の印刷・スキャンPDFの取込';
$string['intro'] = '個人のファイル提出形式の課題を選び、学生別のQR用紙を印刷してください。回収後のPDFを読み込み、確認してから提出物として登録します。1回100 MBまで。ページ数の固定上限はありません。各学生の先頭ページにQRコードが必要です。QRを読み取れないページは直前の学生の続きとして取り込みます。';
$string['scan'] = 'スキャンしたPDF';
$string['analyse'] = '返却結果を確認する';
$string['sheets'] = '学生別QR用紙をダウンロード';
$string['sheetnote'] = 'この下に記入してください。QRコードを汚さないでください。各学生の先頭ページにQRコードが必要です。';
$string['studentcount'] = 'QR用紙の対象者が1〜500人必要です。';
$string['pages'] = '読み込んだPDF内のページ';
$string['preview'] = 'ダウンロードして確認';
$string['confirmnote'] = '氏名とPDFの内容を必ず確認してください。表示された学生に代わって提出を確定します。既存の提出物を置き換える場合は上書きを選択してください。';
$string['confirm'] = '確認済み・提出物として登録';
$string['saved'] = '{$a}人分を提出物として登録しました。';
$string['unreadable'] = 'ページ {$a}：QRが不明・複数、または先行学生がありません。この取込全体の登録を止めています。QRを修正して再度アップロードしてください。';
$string['badqr'] = 'このサイト・課題のQRコードではありません。';
$string['unsupported'] = 'ファイル提出を有効にした個人課題を使用してください。グループ提出には未対応です。';
$string['notconfigured'] = '署名キーが未設定です。プラグインのセットアップを再実行してください。';
$string['invalidbatch'] = '取込データがないか、有効期限切れ、別の教員・課題のデータです。';
$string['processfailed'] = 'PDFの処理に失敗しました。暗号化のない100 MB以内、通常の用紙サイズのPDFを使用してください。繰り返す場合はGhostscript・ZBar・pypdfの導入を確認してください。';
$string['busy'] = '別の取込処理が実行中です。少し待って再実行してください。';
$string['cannotconfirm'] = '読取エラーがあるか、すでに登録済みです。';
$string['existing'] = '{$a}：提出物がすでに存在します。置き換える場合は「既存の提出PDFを上書きする」を選択してください。今回の取込分は登録していません。';
$string['filesize'] = '生成PDFが課題のファイル容量制限を超えているか、ファイルがありません。';
$string['pdftype'] = 'この課題ではPDF形式の提出が許可されていません。';
$string['savefailed'] = 'Moodleに保存できませんでした。今回の取込分は登録していません。';
$string['cleanup'] = '答案の一時ファイルを削除';
$string['privacy:files'] = '正式な提出物はMoodleの課題とファイル機能で保存されます。';
$string['privacy:temporary'] = '確認用のスキャンPDFと学生の対応情報をローカルに一時保存し、24時間後に自動削除します。';
$string['assignment'] = '課題';
$string['chooseassignment'] = '課題を選択してください';
$string['noassignments'] = '選択できる課題がありません。コースに課題を作成し、採点権限を確認してください。';
$string['pickernote'] = '課題を選ぶとQR用紙を直接ダウンロードします。教員用QRはCSVで指定した位置にのみ印刷します。';
$string['uploadheading'] = 'スキャンPDFのアップロード';

$string['processfailedreason'] = 'PDFの処理に失敗しました：{$a}';
$string['process_encrypted'] = 'PDFが暗号化されています。暗号化を解除したPDFを使用してください。';
$string['process_empty'] = 'PDFにページがありません。';
$string['process_pagesize'] = '{$a}ページの用紙サイズが上限（1700ポイント）を超えています。';
$string['process_ghostscriptmissing'] = 'Ghostscript (/usr/bin/gs) が見つかりません。';
$string['process_zbarmissing'] = 'ZBar (/usr/bin/zbarimg) が見つかりません。';
$string['process_pypdfmissing'] = 'Pythonのpypdfが見つかりません。python3-pypdfの導入を確認してください。';
$string['process_pagetimeout'] = '{$a}ページの画像化またはQR読取が時間切れになりました。';
$string['process_renderfailed'] = '{$a}ページをGhostscriptで画像化できませんでした。';
$string['process_qrfailed'] = '{$a}ページでQR読取プログラムが失敗しました。';
$string['process_invalidpdf'] = 'PDFの読込・分割・結合に失敗しました。PDFの破損やサーバーの空き容量を確認してください。';
$string['process_timeout'] = '処理が900秒（15分）を超えました。PDFを分割して取り込んでください。';
$string['process_environment'] = '処理プログラムを実行できないか、異常終了しました。Python・timeout・メモリ上限などを管理者に確認してください。';

$string['inheritedpages'] = '{$a}ページはQRを読み取れなかったため、直前の学生の続きとして割り当てました。別の学生の先頭ページの読取失敗も同じ扱いになるため、登録前にPDFの内容を確認してください。';

$string['overwrite'] = '既存の提出PDFを上書きする';

$string['overwritenote'] = '今回のPDFに含まれる学生の既存ファイルを置き換えます。含まれない学生の提出物は変更しません。採点・フィードバックは再確認してください。';

$string['noteditable'] = '{$a}：提出期間外、提出ロック、または代理提出の権限がありません。';

$string['teachercopy'] = '教員用';
$string['teacherignoredpages'] = '{$a}ページは教員用として取込対象から除外しました（教員用QRの後のQRなしページを含みます）。';

$string['uploadtoolarge'] = 'PDFが100 MBを超えています。学生の区切りで分割して取り込んでください。';

$string['ordercsv'] = 'QR順序CSV';

$string['saveorder'] = '順序を保存';

$string['resetorder'] = '学籍番号順に戻す';

$string['orderstatus'] = '保存済みの順序：{$a}名（変更）';

$string['orderhelp'] = 'UTF-8 CSVの1列に学籍番号を希望順に並べてください。先頭行はidnumber（省略可）、ユーザー名ならusernameにします。教員を含むすべてのロールの有効なコース参加者と照合します。コース共通で保存し、未指定の学生は末尾に学籍番号順で追加します。重複・不明な番号は保存せずお知らせします。';

$string['orderinvalid'] = 'UTF-8の1列CSVで、少なくとも1人の学籍番号を指定してください。';

$string['ordermismatch'] = 'コース参加者を一意に特定できません：{$a}。保存済みの順序は変更していません。';

$string['orderduplicate'] = '番号が重複しています：{$a}。保存済みの順序は変更していません。';

$string['ordersaved'] = '{$a}名のQR順序を保存しました。次回からこの順序を使います。';

$string['reviewassignments'] = '返却対象の課題';

$string['backtocourse'] = 'コースへ戻る';

$string['crosscourse'] = '別コースの課題が含まれています。コースごとに分割してアップロードしてください。';

$string['privacy:order'] = 'QR用紙の順序として、コース、保存者、学生の内部IDと保存日時を記録します。';
