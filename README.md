#Volcanus_CsvParser

[![Latest Stable Version](https://poser.pugx.org/volcanus/csv-parser/v/stable.png)](https://packagist.org/packages/volcanus/csv-parser)
[![Build Status](https://travis-ci.org/k-holy/volcanus-csv-parser.png?branch=master)](https://travis-ci.org/k-holy/volcanus-csv-parser)
[![Coverage Status](https://coveralls.io/repos/k-holy/volcanus-csv-parser/badge.png?branch=master)](https://coveralls.io/r/k-holy/volcanus-csv-parser?branch=master)

CSV文字列の解析を行うためのPHPクラスライブラリです。

Standard PHP Library (SPL) のファイル入出力用クラス SplFileObject と組み合わせることで、簡単にファイルからの読み込みを行えます。

[Volcanus_Csv](https://github.com/k-holy/Volcanus_Csv) からの派生物で、CSV入力に関する最低限の機能を移植しています。


##環境

* PHP 5.3以降
* mbstring拡張


##使い方

```php
<?php

$parser = new \Volcanus\CsvParser\CsvParser(array(
    'delimiter'      => ',',
    'enclosure'      => '"',
    'escape'         => '"',
    'inputEncoding'  => 'SJIS',
    'outputEncoding' => 'UTF-8',
    'sanitizing'     => true,
));

$csvFile = new \SplFileObject('php://temp', '+r');
$csvFile->fwrite(mb_convert_encoding("1,田中\r\n", 'SJIS', 'UTF-8'));
$csvFile->fwrite(mb_convert_encoding("2,山田\r\n", 'SJIS', 'UTF-8'));
$csvFile->fwrite(mb_convert_encoding("3,鈴木\r\n", 'SJIS', 'UTF-8'));
$csvFile->rewind();

$users = array();

foreach ($csvFile as $line) {

    // 1件分のレコード取得が終了するまで各行をパース
    if (!$parser->parse($line)) {
        continue;
    }

    $csv = $parser->getBuffer();

    $row = $parser->convert($csv);

    // 空行にはNULLが返されるので無視
    if (is_null($row)) {
        continue;
    }

    // CSVのフィールドをオブジェクトに取得
    $user = new \stdClass();
    $user->id   = $row[0];
    $user->name = $row[1];

    $users[] = $user;
}

echo $users[0]->id; // 1
echo $users[0]->name; // 田中
echo $users[1]->id; // 2
echo $users[1]->name; // 山田
echo $users[2]->id; // 3
echo $users[2]->name; // 鈴木

```

###注意点

SplFileObjectを前提としていますが、CSVの加工は独自の処理を行なっています。
そのため、[SplFileObject::setCsvControl()](http://jp2.php.net/manual/ja/splfileobject.setcsvcontrol.php) で設定した値は利用されません。
また、[fgetcsv()](http://jp2.php.net/manual/ja/function.fgetcsv.php) ,
[SplFileObject::fgetcsv()](http://jp2.php.net/manual/ja/splfileobject.fgetcsv.php) ,
[str_getcsv()](http://jp2.php.net/manual/ja/function.str-getcsv.php) といったPHP標準の関数とは異なる結果を返す可能性があります。

復帰・改行・水平タブ・スペース以外の制御コードを自動で削除するサニタイジング機能を備えていますが、初期設定では無効になっています。

設定値の取得にはプロパティアクセス、配列アクセスを利用できますが、設定値のセットには利用できません。
コンストラクタのパラメータで指定するか、config()メソッドを呼ぶ必要があります。
