<?php

namespace plugins\ngWord;

class NgWordCheck {

    /**
     * 禁止単語が含まれているかチェックする.
     * 禁止単語(words.txt)に定義できる単語：
     *   1) 全角ひらがな
     *   2) 全角カタカナ（半角は全角に置換される）
     *   3) 漢字
     *   4) 半角数字（全角は半角に置換される）
     *   5) 小文字半角英字（大文字・全角は、小文字半角に置換される）
     *   6) 半角記号（！＃＄％＆（）＊＋，－．／：；＜＝＞？＠［］＾＿｀｛｜｝）(半角に置換される)
     *   7) 上記以外の記号
     *   8) 正規表現
     * @param string $comment チェック対象文字列.
     * @return boolean NGワードが入っていたら、trueを返す.
     */
    public static function checkNgWord($comment) {
        //すげー日本向け. 海外版で英文対応する時に、スペースがややこしいかも.
        //1. KV : 半角カナを全角カナへ（濁音はつなげる）
        //2. a  : 全角英数字を半角へ
        //3. s  : 全角スペースを半角へ
        // 　（c:全角カナを全角かなへ、も入れたいが、ニュアンスが変わりそうなのでとりあえず様子見）
        $option = 'KVas';
        $comment  = mb_convert_kana($comment, $option);
        //基本小文字に寄せる.
        $comment  = strtolower($comment);
        //予想される区切り文字を予め消しておく. 改行も入れたいが、とりあえず様子見.
        $delimiters = array(" ", "\t");
        foreach ($delimiters as $del) {
            $comment  = str_replace($del, '', $comment);
        }

        $words_file   = 'words.txt';
        $ngWordExists = false;

        if (strlen($comment) > 0) {
            //現状ファイルから取る.
            $resource = fopen(__DIR__ . '/' . $words_file, 'r');
            while ($line = fgets($resource)) {
                $wordsx  = trim($line);
                if (mb_substr($wordsx, 0, 1) === '/') {
                    //デリミタがあったら正規表現でチェック.
                    if (preg_match($wordsx, $comment)) {
                        $ngWordExists = true;
                        break;
                    }
                } else {
                    //デリミタがなかったら文字列検索.
                    if (mb_strpos($comment, $wordsx) !== false) {
                        $ngWordExists = true;
                        break;
                    }
                }
            }
        }
        //ワードが問題なかったら、電番とメアドのチェックも入れる
        if( $ngWordExists ){
            return true;
        }
        return NgWordCheck::telmailurlCheck($comment);
    }

    /**
     * 禁止ワード電話番号URLメールアドレス
     *
     * @param $comment: チェックしたい文字列
     * @return true/false 禁止ワードあり：true 問題なし：false
     * 2010/02/19
     */
    public static function telmailurlCheck($comment) {

        //禁止ワードチェック
        if ((preg_match("/(https?:)|(www\.)/i", $comment))
            || preg_match('/^([-!#-\'*+\/-9=?^-~]+(\.[-!#-\'*+\/-9=?^-~]+)*|"([]-~!#-[]|\\[ -~])*")@[a-z0-9]([-a-z0-9]{0,61}[a-z0-9])?(\.[a-z0-9]([-a-z0-9]{0,61}[a-z0-9])?)*\.([a-z]{2,4}|museum)$/i', $comment) // アドレス厳しい
            || (preg_match("/(^(?<!090|080|070)(^\d{2,5}?\-\d{1,4}?\-\d{4}$|^[\d\-]{12}$))|(^(090|080|070)(\-\d{4}\-\d{4}|[\\d-]{13})$)|(^0120(\-\d{2,3}\-\d{3,4}|[\d\-]{12})$)|(^0080\-\d{3}\-\d{4})/", $comment)) // 電話番号 ハイフン付
            || preg_match("/^\d{3}\-\d{4}$/", $comment)    //郵便番号
            ) {
            return true;
        } else {
            return false;
        }
    }

}
