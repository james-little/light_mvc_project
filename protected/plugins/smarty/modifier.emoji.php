<?php
/**
 *  smarty modifier:emoji()
 *
 *  絵文字変換関数
 *
 *
 *  @param  string  $carrier キャリア判別文字列(docomo, au, softbank
 *  @param  string  $emoji 絵文字種
 *  @return string  フォーマット済み文字列
 */
function smarty_modifier_emoji($emoji, $carrier) {
    switch ($carrier) {
    // docomoここから
    case 'docomo':
        switch ($emoji) {
            // 晴れ
        case 'sunny':
            return '<span style="color:#ff0000">&#xE63E;</span>';
            // 曇り
        case 'cloudy':
            return '<span style="color:#0000ff">&#xE63F;</span>';
            // 雨
        case 'rainy':
            return '<span style="color:#0000ff">&#xE640;</span>';
            // 雪
        case 'snowy':
            return '<span style="color:#0000ff">&#xE641;</span>';
            // 雷
        case 'thunder':
            return '<span style="color:#ff8000">&#xE642;</span>';
            // 台風
        case 'typhoon':
            return '<span style="color:#ff0000">&#xE643;</span>';
            // 霧
        case 'fog':
            return '<span style="color:#0000ff">&#xE644;</span>';
            // 小雨
        case 'drizzle':
            return '<span style="color:#0000ff">&#xE645;</span>';
            // 牡羊座
        case 'aries':
            return '<span style="color:#ff0000">&#xE48F;</span>';
            // 牡牛座
        case 'taurus':
            return '<span style="color:#ff8000">&#xE647;</span>';
            // 双子座
        case 'gemini':
            return '<span style="color:#00ff00">&#xE648;</span>';
            // 蟹座
        case 'cancer':
            return '<span style="color:#0000ff">&#xE649;</span>';
            // 獅子座
        case 'leo':
            return '<span style="color:#ff0000">&#xE64A;</span>';
            // 乙女座
        case 'virgo':
            return '<span style="color:#ff8000">&#xE64B;</span>';
            // 天秤座
        case 'libra':
            return '<span style="color:#00ff00">&#xE64C;</span>';
            // 蠍座
        case 'scorpio':
            return '<span style="color:#0000ff">&#xE64D;</span>';
            // 射手座
        case 'sagittarius':
            return '<span style="color:#ff0000">&#xE64E;</span>';
            // 山羊座
        case 'capricorn':
            return '<span style="color:#ff8000">&#xE64F;</span>';
            // 水瓶座
        case 'aquarius':
            return '<span style="color:#00ff00">&#xE640;</span>';
            // 魚座
        case 'pisces':
            return '<span style="color:#0000ff">&#xE651;</span>';
            // スポーツ
        case 'sports':
            return '<span style="color:#ff00ff">&#xE652;</span>';
            // 野球
        case 'baseball':
            return '<span style="color:#000000">&#xE653;</span>';
            // ゴルフ
        case 'golf':
            return '<span style="color:#0000ff">&#xE654;</span>';
            // テニス
        case 'tennis':
            return '<span style="color:#00ff00">&#xE655;</span>';
            // サッカー
        case 'soccer':
            return '<span style="color:#000000">&#xE656;</span>';
            // スキー
        case 'skiing':
            return '<span style="color:#0000ff">&#xE657;</span>';
            // バスケット
        case 'basket':
            return '<span style="color:#ff8000">&#xE658;</span>';
            // モータースポーツ
        case 'motor':
            return '<span style="color:#000000">&#xE659;</span>';
            // ポケットベル
        case 'pkbell':
            return '<span style="color:#ff00ff">&#xE65A;</span>';
            // 電車
        case 'train':
            return '<span style="color:#00ff00">&#xE65B;</span>';
            // 地下鉄
        case 'subway':
            return '<span style="color:#ff8000">&#xE65C;</span>';
            // 新幹線
        case 'shinkansen':
            return '<span style="color:#0000ff">&#xE65D;</span>';
            // 車(セダン)
        case 'car1':
            return '<span style="color:#000000">&#xE65E;</span>';
            // 車(RV)
        case 'car2':
            return '<span style="color:#00ff00">&#xE65F;</span>';
            // バス
        case 'bus':
            return '<span style="color:#ff0000">&#xE660;</span>';
            // 船
        case 'ship':
            return '<span style="color:#0000ff">&#xE661;</span>';
            // 飛行機
        case 'airplane':
            return '<span style="color:#0000ff">&#xE662;</span>';
            // 家
        case 'house':
            return '<span style="color:#ff0000">&#xE663;</span>';
            // ビル
        case 'building':
            return '<span style="color:#0000ff">&#xE664;</span>';
            // 郵便局
        case 'postoffice':
            return '<span style="color:#ff0000">&#xE666;</span>';
            // 病院
        case 'hospital':
            return '<span style="color:#000000">&#xE666;</span>';
            // 銀行
        case 'bank':
            return '<span style="color:#ff00ff">&#xE667;</span>';
            // ATM
        case 'ATM':
            return '<span style="color:#ff0000">&#xE668;</span>';
            // ホテル
        case 'hotel':
            return '<span style="color:#00ff00">&#xE669;</span>';
            // コンビニ
        case 'convenience':
            return '<span style="color:#0000ff">&#xE66A;</span>';
            // ガソリンスタンド
        case 'GS':
            return '<span style="color:#ff00ff">&#xE66B;</span>';
            // 駐車場
        case 'parking':
            return '<span style="color:#0000ff">&#xE66C;</span>';
            // 信号
        case 'signal':
            return '<span style="color:#000000">&#xE66D;</span>';
            // トイレ
        case 'WC':
            return '<span style="color:#000000">&#xE66E;</span>';
            // レストラン
        case 'restaulant':
            return '<span style="color:#CCCCCC">&#xE66F;</span>';
            // 喫茶店
        case 'coffee':
            return '<span style="color:#00ff00">&#xE670;</span>';
            // バー
        case 'bar':
            return '<span style="color:#ff00ff">&#xE671;</span>';
            // ビール
        case 'beer':
            return '<span style="color:#ff8000">&#xE672;</span>';
            // ファーストフード
        case 'fastfood':
            return '<span style="color:#ff8000">&#xE673;</span>';
            // ブティック
        case 'boutique':
            return '<span style="color:#ff0000">&#xE674;</span>';
            // 美容院
        case 'barber':
            return '<span style="color:#0000ff">&#xE675;</span>';
            // カラオケ
        case 'karaoke':
            return '<span style="color:#000000">&#xE676;</span>';
            // 映画
        case 'movie':
            return '<span style="color:#000000">&#xE677;</span>';
            // 右斜め上
        case 'right_diagonal_upper':
            return '<span style="color:#000000">&#xE678;</span>';
            // 遊園地
        case 'amusement':
            return '<span style="color:#ff8000">&#xE679;</span>';
            // 音楽
        case 'music':
            return '<span style="color:#0000ff">&#xE67A;</span>';
            // アート
        case 'art':
            return '<span style="color:#ff00ff">&#xE67B;</span>';
            // 演劇
        case 'play':
            return '<span style="color:#000000">&#xE67C;</span>';
            // イベント
        case 'event':
            return '<span style="color:#ff0000">&#xE67D;</span>';
            // ガチャ券
        case 'ticket':
            return '<span style="color:#ff8000">&#xE67E;</span>';
            // 喫煙
        case 'smoking':
            return '<span style="color:#000000">&#xE67F;</span>';
            // 禁煙
        case 'no_smoking':
            return '<span style="color:#ff0000">&#xE680;</span>';
            // カメラ
        case 'camera':
            return '<span style="color:#000000">&#xE681;</span>';
            // カバン
        case 'bag':
            return '<span style="color:#ff0000">&#xE682;</span>';
            // 本
        case 'book':
            return '<span style="color:#ff8000">&#xE683;</span>';
            // リボン
        case 'ribbon':
            return '<span style="color:#ff0000">&#xE684;</span>';
            // プレゼント
        case 'present':
            return '<span style="color:#ff0000">&#xE685;</span>';
            // バースデー
        case 'birthday':
            return '<span style="color:#ff0000">&#xE686;</span>';
            // 電話
        case 'phone':
            return '<span style="color:#000000">&#xE687;</span>';
            // 携帯電話
        case 'mphone':
            return '<span style="color:#000000">&#xE688;</span>';
            // メモ
        case 'memo':
            return '<span style="color:#ff8000">&#xE689;</span>';
            // TV
        case 'TV':
            return '<span style="color:#0000ff">&#xE68A;</span>';
            // ゲーム
        case 'game':
            return '<span style="color:#000000">&#xE68B;</span>';
            // CD
        case 'CD':
            return '<span style="color:#0000ff">&#xE68C;</span>';
            // ハート
        case 'heart':
            return '<span style="color:#ff0000">&#xE68D;</span>';
            // スペード
        case 'spade':
            return '<span style="color:#000000">&#xE68E;</span>';
            // ダイヤ
        case 'diamond':
            return '<span style="color:#ff0000">&#xE68F;</span>';
            // クラブ
        case 'club':
            return '<span style="color:#000000">&#xE690;</span>';
            // 目
        case 'eye':
            return '<span style="color:#000000">&#xE691;</span>';
            // 耳
        case 'ear':
            $code ='<span style="color:#ff8000">&#xE692;</span>';
            // 手(グー)
        case 'gu-':
            return '<span style="color:#ff8000">&#xE693;</span>';
            // 手(チョキ)
        case 'choki':
            return '<span style="color:#ff8000">&#xE694;</span>';
            // 手(パー)
        case 'pa-':
            return '<span style="color:#ff8000">&#xE695;</span>';
            // 右斜め下
        case 'right_diagonal_under':
            return '<span style="color:#000000">&#xE696;</span>';
            // 左斜め上
        case 'left_diagonal_upper':
            return '<span style="color:#000000">&#xE697;</span>';
            // 足
        case 'foot':
            return '<span style="color:#ff8000">&#xE698;</span>';
            // 靴
        case 'shoes':
            return '<span style="color:#000000">&#xE699;</span>';
            // メガネ
        case 'glasses':
            return '<span style="color:#000000">&#xE69A;</span>';
            // 車椅子
        case 'wheelchair':
            return '<span style="color:#0000ff">&#xE69B;</span>';
            // 新月
        case 'newmoon':
            return '<span style="color:#000000">&#xE69C;</span>';
            // 欠け月
        case 'moon':
            return '<span style="color:#000000">&#xE69D;</span>';
            // 半月
        case 'halfmoon':
            return '<span style="color:#000000">&#xE69E;</span>';
            // 三日月
        case 'crescent':
            return '<span style="color:#000000">&#xE69F;</span>';
            // 満月
        case 'fullmoon':
            return '<span style="color:#000000">&#xE6A0;</span>';
            // 犬
        case 'dog':
            return '<span style="color:#ff8000">&#xE6A1;</span>';
            // 猫
        case 'cat':
            return '<span style="color:#ff8000">&#xE6A2;</span>';
            // リゾート
        case 'resort':
            return '<span style="color:#0000ff">&#xE6A3;</span>';
            // クリスマス
        case 'christmas':
            return '<span style="color:#00ff00">&#xE6A4;</span>';
            // 左斜め下
        case 'left_diagonal_under':
            return '<span style="color:#000000">&#xE6A5;</span>';
            // カチンコ
        case 'clapperboard':
            return '<span style="color:#000000">&#xE6AC;</span>';
            // ふくろ
        case 'sac':
            return '<span style="color:#000000">&#xE6AD;</span>';
            // ペン
        case 'pen':
            return '<span style="color:#000000">&#xE6AE;</span>';
            // 人影
        case 'shadow':
            return '<span style="color:#000000">&#xE6B1;</span>';
            // 椅子
        case 'chair':
            return '<span style="color:#000000">&#xE6B2;</span>';
            // 夜
        case 'night':
            return '<span style="color:#000000">&#xE6B3;</span>';
            // 時計
        case 'clock':
            return '<span style="color:#FFFFFF">&#xE6BA;</span>';
            // 電話CALL
        case 'call':
            return '<span style="color:#FFFFFF">&#xE6CE;</span>';
            // MAIL送信
        case 'sendmail':
            return '<span style="color:#000000">&#xE6CF;</span>';
            // FAX
        case 'FAX':
            return '<span style="color:#000000">&#xE6D0;</span>';
            // imode
        case 'imode':
            return '<span style="color:#ff8000">&#xE6D1;</span>';
            // imode(枠あり)
        case 'imode2':
            return '<span style="color:#ff8000">&#xE6D2;</span>';
            // メール
        case 'mail':
            return '<span style="color:#FFFFFF">&#xE6D3;</span>';
            // 有料
        case 'charge':
            return '<span style="color:#ff0000">&#xE6D6;</span>';
            // 無料
        case 'free':
            return '<span style="color:#ff0000">&#xE6D7;</span>';
            // ID
        case 'ID':
            return '<span style="color:#ff0000">&#xE6D8;</span>';
            // PASS
        case 'PASS':
            return '<span style="color:#ff0000">&#xE6D9;</span>';
            // 次項有
        case 'next':
            return '<span style="color:#ff0000">&#xE6DA;</span>';
            // クリア
        case 'clear':
            return '<span style="color:#ff0000">&#xE6DB;</span>';
            // サーチ
        case 'search':
            return '<span style="color:#0000ff">&#xE6DC;</span>';
            // NEW
        case 'new':
            return '<span style="color:#ff0000">&#xE6DD;</span>';
            // 位置情報
        case 'flag':
            return '<span style="color:#ff0000">&#xE6DE;</span>';
            // フリーダイアル
        case 'toll-free':
            return '<span style="color:#000000">&#xE6DF;</span>';
            // シャープダイアル
        case '#':
            return '<span style="color:#000000">&#xE6E0;</span>';
            // モバQ
        case 'mobaQ':
            return '<span style="color:#000000">&#xE6E1;</span>';
            // 1
        case '1':
            return '<span style="color:#FFFFFF">&#xE6E2;</span>';
            // 2
        case '2':
            return '<span style="color:#FFFFFF">&#xE6E3;</span>';
            // 3
        case '3':
            return '<span style="color:#FFFFFF">&#xE6E4;</span>';
            // 4
        case '4':
            return '<span style="color:#FFFFFF">&#xE6E5;</span>';
            // 5
        case '5':
            return '<span style="color:#FFFFFF">&#xE6E6;</span>';
            // 6
        case '6':
            return '<span style="color:#FFFFFF">&#xE6E7;</span>';
            // 7
        case '7':
            return '<span style="color:#FFFFFF">&#xE6E8;</span>';
            // 8
        case '8':
            return '<span style="color:#FFFFFF">&#xE6E9;</span>';
            // 9
        case '9':
            return '<span style="color:#FFFFFF">&#xE6EA;</span>';
            // 0
        case '0':
            return '<span style="color:#FFFFFF">&#xE6EB;</span>';
            // 黒ハート
        case 'blackheart':
            return '<span style="color:#ff0000">&#xE6EC;</span>';
            // 揺れるハート
        case 'swingheart':
            return '<span style="color:#ff0000">&#xE6ED;</span>';
            // 失恋
        case 'brokenheart':
            return '<span style="color:#ff0000">&#xE6EE;</span>';
            // ハートたち
        case 'hearts':
            return '<span style="color:#ff0000">&#xE6EF;</span>';
            // わーい
        case 'face1':
            return '<span style="color:#ff00ff">&#xE6F0;</span>';
            // ちっ
        case 'face2':
            return '<span style="color:#ff0000">&#xE6F1;</span>';
            // がく～
        case 'face3':
            return '<span style="color:#0000ff">&#xE6F2;</span>';
            // もうやだ
        case 'face4':
            return '<span style="color:#00ff00">&#xE6F3;</span>';
            // フラフラ
        case 'face5':
            return '<span style="color:#0000ff">&#xE6F4;</span>';
            // グッド(上向き矢印)
        case 'good':
            return '<span style="color:#ff0000">&#xE6F5;</span>';
            // ルンルン
        case 'note':
            return '<span style="color:#ff0000">&#xE6F6;</span>';
            // いい気分(温泉)
        case 'hot-spring':
            return '<span style="color:#ff0000">&#xE6F7;</span>';
            // かわいい
        case 'cute':
            return '<span style="color:#ff00ff">&#xE6F8;</span>';
            // キスマーク
        case 'kiss':
            return '<span style="color:#ff0000">&#xE6F9;</span>';
            // ピカピカ(新しい)
        case 'pikapika':
            return '<span style="color:#ff8000">&#xE6FA;</span>';
            // ひらめき
        case 'flashing':
            return '<span style="color:#ffcc00">&#xE6FB;</span>';
            // ムカッ
        case 'anger':
            return '<span style="color:#000000">&#xE6FC;</span>';
            // パンチ
        case 'punch':
            return '<span style="color:#ff0000">&#xE6FD;</span>';
            // 爆弾
        case 'bomb':
            return '<span style="color:#000000">&#xE6FE;</span>';
            // ムード
        case 'mood':
            return '<span style="color:#ff0000">&#xE6FF;</span>';
            // バッド(下向き矢印)
        case 'bad':
            return '<span style="color:#0000ff">&#xE700;</span>';
            // 眠い
        case 'zzz':
            return '<span style="color:#0000ff">&#xE701;</span>';
            // !
        case '!':
            return '<span style="color:#ff0000">&#xE702;</span>';
            // !?
        case '!?':
            return '<span style="color:#ff00ff">&#xE703;</span>';
            // !!
        case '!!':
            return '<span style="color:#ff0000">&#xE704;</span>';
            // どんっ(衝撃)
        case 'impact':
            return '<span style="color:#ff0000">&#xE705;</span>';
            // あせあせ
        case 'hurry':
            return '<span style="color:#000000">&#xE706;</span>';
            // たらー
        case 'sweat':
            return '<span style="color:#000000">&#xE707;</span>';
            // ダッシュ
        case 'dash':
            return '<span style="color:#000000">&#xE708;</span>';
            // 長音記号1
        case 'sign1':
            return '<span style="color:#000000">&#xE709;</span>';
            // 長音記号2
        case 'sign2':
            return '<span style="color:#000000">&#xE70A;</span>';
            // 決定
        case 'OK':
            return '<span style="color:#ff8000">&#xE70B;</span>';
            // iアプリ
        case 'iapp':
            return '<span style="color:#ff8000">&#xE70C;</span>';
            // iアプリ(枠あり)
        case 'iapp2':
            return '<span style="color:#ff8000">&#xE70D;</span>';
            // Tシャツ
        case 'Tshirt':
            return '<span style="color:#0000ff">&#xE70E;</span>';
            // がまぐち財布
        case 'purse':
            return '<span style="color:#000000">&#xE70F;</span>';
            // 化粧
        case 'makeup':
            return '<span style="color:#ff0000">&#xE710;</span>';
            // ジーンズ
        case 'jeans':
            return '<span style="color:#000080">&#xE711;</span>';
            // スノボ
        case 'snowboard':
            return '<span style="color:#0000ff">&#xE712;</span>';
            // チャペル
        case 'chapel':
            return '<span style="color:#ff8000">&#xE713;</span>';
            // ドア
        case 'door':
            return '<span style="color:#800000">&#xE714;</span>';
            // ドル袋
        case '$':
            return '<span style="color:#800000">&#xE715;</span>';
            // パソコン
        case 'PC':
            return '<span style="color:#000000">&#xE716;</span>';
            // ラブレター
        case 'loveletter':
            return '<span style="color:#ff0000">&#xE717;</span>';
            // レンチ
        case 'wrench':
            return '<span style="color:#000000">&#xE718;</span>';
            // 鉛筆
        case 'pencil':
            return '<span style="color:#00ff00">&#xE719;</span>';
            // 王冠
        case 'crown':
            return '<span style="color:#ff8000">&#xE71A;</span>';
            // 指輪
        case 'ring':
            return '<span style="color:#ff00ff">&#xE71B;</span>';
            // 砂時計
        case 'hourglass':
            return '<span style="color:#000000">&#xE71C;</span>';
            // 自転車
        case 'bicycle':
            return '<span style="color:#000000">&#xE71D;</span>';
            // 湯のみ
        case 'cup':
            return '<span style="color:#00ff00">&#xE71E;</span>';
            // 腕時計
        case 'watch':
            return '<span style="color:#000000">&#xE71F;</span>';
            // 考えている顔
        case 'face6':
            return '<span style="color:#00ff00">&#xE720;</span>';
            // ほっとした顔
        case 'face7':
            return '<span style="color:#ff00ff">&#xE721;</span>';
            // 冷や汗1
        case 'face8':
            return '<span style="color:#0000ff">&#xE722;</span>';
            // 冷や汗2
        case 'face9':
            return '<span style="color:#0000ff">&#xE723;</span>';
            // ぷっくっくな顔
        case 'face10':
            return '<span style="color:#ff0000">&#xE724;</span>';
            // ボケーっとした顔
        case 'face11':
            return '<span style="color:#7f0080">&#xE725;</span>';
            // 目がハート
        case 'face12':
            return '<span style="color:#ff00ff">&#xE726;</span>';
            // 親指立てる(了解)
        case 'consent':
            return '<span style="color:#ff8000">&#xE727;</span>';
            // あっかんべー
        case 'face13':
            return '<span style="color:#ff0000">&#xE728;</span>';
            // ウィンク
        case 'face14':
            return '<span style="color:#ff00ff">&#xE729;</span>';
            // うれしい顔
        case 'face15':
            return '<span style="color:#ff00ff">&#xE72A;</span>';
            // がまん顔
        case 'face16':
            return '<span style="color:#000080">&#xE72B;</span>';
            // 猫2
        case 'cat2':
            return '<span style="color:#ff8000">&#xE72C;</span>';
            // 泣き顔
        case 'face17':
            return '<span style="color:#0000ff">&#xE72D;</span>';
            // 涙
        case 'face18':
            return '<span style="color:#0000ff">&#xE72E;</span>';
            // NG
        case 'NG':
            return '<span style="color:#ff0000">&#xE72F;</span>';
            // クリップ
        case 'clip':
            return '<span style="color:#0000ff">&#xE730;</span>';
            // コピーライト
        case '(C)':
            return '<span style="color:#000000">&#xE731;</span>';
            // トレードマーク
        case 'TM':
            return '<span style="color:#000000">&#xE732;</span>';
            // 走る人
        case 'runner':
            return '<span style="color:#000000">&#xE733;</span>';
            // マル秘
        case 'secret':
            return '<span style="color:#ff0000">&#xE734;</span>';
            // リサイクル
        case 'recycle':
            return '<span style="color:#00ff00">&#xE735;</span>';
            // レジスタードトレードマーク
        case '(R)':
            return '<span style="color:#000000">&#xE736;</span>';
            // 危険・警告
        case 'warning':
            return '<span style="color:#ff8000">&#xE737;</span>';
            // 禁止
        case 'prohibition':
            return '<span style="color:#ff0000">&#xE738;</span>';
            // 空室空席
        case 'vacant':
            return '<span style="color:#0000ff">&#xE739;</span>';
            // 合格
        case 'pass':
            return '<span style="color:#ff0000">&#xE73A;</span>';
            // 満員満室
        case 'full':
            return '<span style="color:#ff0000">&#xE73B;</span>';
            // 矢印左右
        case 'LRarrow':
            return '<span style="color:#000000">&#xE73C;</span>';
            // 矢印上下
        case 'UDarrow':
            return '<span style="color:#000000">&#xE73D;</span>';
            // 学校
        case 'school':
            return '<span style="color:#00ff00">&#xE73E;</span>';
            // 波
        case 'wave':
            return '<span style="color:#0000ff">&#xE73F;</span>';
            // 富士山
        case 'fujiyama':
            return '<span style="color:#0000ff">&#xE740;</span>';
            // クローバー
        case 'clover':
            return '<span style="color:#00ff00">&#xE741;</span>';
            // さくらんぼ
        case 'cherry':
            return '<span style="color:#ff0000">&#xE742;</span>';
            // チューリップ
        case 'tulip':
            return '<span style="color:#ff0000">&#xE743;</span>';
            // バナナ
        case 'banana':
            return '<span style="color:#ff8000">&#xE744;</span>';
            // りんご
        case 'apple':
            return '<span style="color:#ff0000">&#xE745;</span>';
            // 芽
        case 'bud':
            return '<span style="color:#00ff00">&#xE746;</span>';
            // もみじ
        case 'momiji':
            return '<span style="color:#ff0000">&#xE747;</span>';
            // 桜
        case 'sakura':
            return '<span style="color:#ff00ff">&#xE748;</span>';
            // おにぎり
        case 'onigiri':
            return '<span style="color:#000000">&#xE749;</span>';
            // ショートケーキ
        case 'shortcake':
            return '<span style="color:#ff0000">&#xE74A;</span>';
            // とっくり
        case 'tokkuri':
            return '<span style="color:#800000">&#xE74B;</span>';
            // どんぶり
        case 'donburi':
            return '<span style="color:#ff8000">&#xE74C;</span>';
            // パン
        case 'bread':
            return '<span style="color:#800000">&#xE74D;</span>';
            // かたつむり
        case 'snail':
            return '<span style="color:#800000">&#xE74E;</span>';
            // ひよこ
        case 'hiyoko':
            return '<span style="color:#ff8000">&#xE74F;</span>';
            // ペンギン
        case 'penguin':
            return '<span style="color:#000080">&#xE750;</span>';
            // 魚
        case 'fish':
            return '<span style="color:#0000ff">&#xE751;</span>';
            // うまい！
        case 'face19':
            return '<span style="color:#ff8000">&#xE752;</span>';
            // うっしっし
        case 'face20':
            return '<span style="color:#ff8000">&#xE753;</span>';
            // ウマ
        case 'horse':
            return '<span style="color:#800000">&#xE754;</span>';
            // ブタ
        case 'pig':
            return '<span style="color:#ff8000">&#xE755;</span>';
            // ワイングラス
        case 'wine':
            return '<span style="color:#7f0080">&#xE756;</span>';
            // ゲッソリ
        case 'face21':
            return '<span style="color:#7f0080">&#xE757;</span>';
            // SOON
        case 'SOON':
            return '<span style="color:#000000">&#xE6B7;</span>';
            // ON
        case 'ON':
            return '<span style="color:#000000">&#xE6B8;</span>';
            // END
        case 'END':
            return '<span style="color:#000000">&#xE6B9;</span>';
        }
        break;
        // docomoここまで

        // auここから
    case 'au':
        switch ($emoji) {
            // 晴れ
        case 'sunny':
            return '&#xE488;';
            // 曇り
        case 'cloudy':
            return '&#xE48D;';
            // 雨
        case 'rainy':
            return '&#xE48C;';
            // 雪
        case 'snowy':
            return '&#xE485;';
            // 雷
        case 'thunder':
            return '&#xE487;';
            // 台風
        case 'typhoon':
            return '&#xE469;';
            // 霧
        case 'fog':
            return '&#xE598;';
            // 小雨
        case 'drizzle':
            return '&#xEAE8;';
            // 牡羊座
        case 'aries':
            return '&#xE48F;';
            // 牡牛座
        case 'taurus':
            return '&#xE490;';
            // 双子座
        case 'gemini':
            return '&#xE491;';
            // 蟹座
        case 'cancer':
            return '&#xE492;';
            // 獅子座
        case 'leo':
            return '&#xE493;';
            // 乙女座
        case 'virgo':
            return '&#xE494;';
            // 天秤座
        case 'libra':
            return '&#xE495;';
            // 蠍座
        case 'scorpio':
            return '&#xE496;';
            // 射手座
        case 'sagittarius':
            return '&#xE497;';
            // 山羊座
        case 'capricorn':
            return '&#xE498;';
            // 水瓶座
        case 'aquarius':
            return '&#xE499;';
            // 魚座
        case 'pisces':
            return '&#xE49A;';
            // スポーツ
        case 'sports':
            return '&#xEB6B;';
            // 野球
        case 'baseball':
            return '&#xE4BA;';
            // ゴルフ
        case 'golf':
            return '&#xE599;';
            // テニス
        case 'tennis':
            return '&#xE4B7;';
            // サッカー
        case 'soccer':
            return '&#xE4B6;';
            // スキー
        case 'skiing':
            return '&#xE60C;';
            // バスケット
        case 'basket':
            return '&#xE59A;';
            // モータースポーツ
        case 'motor':
            return '&#xE4B9;';
            // ポケットベル
        case 'pkbell':
            return '&#xE59B;';
            // 電車
        case 'train':
            return '&#xE4B5;';
            // 地下鉄
        case 'subway':
            return '&#xE5BC;';
            // 新幹線
        case 'shinkansen':
            return '&#xE5BC;';
            // 車(セダン)
        case 'car1':
            return '&#xE4B1;';
            // 車(RV)
        case 'car2':
            return '&#xE4B1;';
            // バス
        case 'bus':
            return '&#xE4AF;';
            // 船
        case 'ship':
            return '&#xE5E2;';
            // 飛行機
        case 'airplane':
            return '&#xE4B3;';
            // 家
        case 'house':
            return '&#xE4B3;';
            // ビル
        case 'building':
            return '&#xE4AD;';
            // 郵便局
        case 'postoffice':
            return '&#xE5DE;';
            // 病院
        case 'hospital':
            return '&#xE5DF;';
            // 銀行
        case 'bank':
            return '&#xE4AA;';
            // ATM
        case 'ATM':
            return '&#xE4A3;';
            // ホテル
        case 'hotel':
            return '&#xE5E1;';
            // コンビニ
        case 'convenience':
            return '&#xE4A4;';
            // ガソリンスタンド
        case 'GS':
            return '&#xE571;';
            // 駐車場
        case 'parking':
            return '&#xE4A6;';
            // 信号
        case 'signal':
            return '&#xE46A;';
            // トイレ
        case 'WC':
            return '&#xE4A5;';
            // レストラン
        case 'restaulant':
            return '&#xE4AC;';
            // 喫茶店
        case 'coffee':
            return '&#xE597;';
            // バー
        case 'bar':
            return '&#xE4C2;';
            // ビール
        case 'beer':
            return '&#xE4C3;';
            // ファーストフード
        case 'fastfood':
            return '&#xE4D6;';
            // ブティック
        case 'boutique':
            return '&#xE51A;';
            // 美容院
        case 'barber':
            return '&#xE516;';
            // カラオケ
        case 'karaoke':
            return '&#xE503;';
            // 映画
        case 'movie':
            return '&#xE517;';
            // 右斜め上
        case 'right_diagonal_upper':
            return '&#xE555;';
            // 遊園地
        case 'amusement':
            return '&#xE4D8;';
            // 音楽
        case 'music':
            return '&#xE508;';
            // アート
        case 'art':
            return '&#xE59C;';
            // 演劇
        case 'play':
            return '&#xEAF5;';
            // イベント
        case 'event':
            return '&#xE59E;';
            // ガチャ券
        case 'ticket':
            return '&#xE49E;';
            // 喫煙
        case 'smoking':
            return '&#xE47D;';
            // 禁煙
        case 'no_smoking':
            return '&#xE47E;';
            // カメラ
        case 'camera':
            return '&#xE515;';
            // カバン
        case 'bag':
            return '&#xE49C;';
            // 本
        case 'book':
            return '&#xE49F;';
            // リボン
        case 'ribbon':
            return '&#xE59F;';
            // プレゼント
        case 'present':
            return '&#xE4CF;';
            // バースデー
        case 'birthday':
            return '&#xE313;';
            // 電話
        case 'phone':
            return '&#xE596;';
            // 携帯電話
        case 'mphone':
            return '&#xE588;';
            // メモ
        case 'memo':
            return '&#xF365;';
            // TV
        case 'TV':
            return '&#xE502;';
            // ゲーム
        case 'game':
            return '&#xE4C6;';
            // CD
        case 'CD':
            return '&#xE50C;';
            // ハート
        case 'heart':
            return '&#xE605;';
            // スペード
        case 'spade':
            return '&#xE5A1;';
            // ダイヤ
        case 'diamond':
            return '&#xE5A2;';
            // クラブ
        case 'club':
            return '&#xE5A3;';
            // 目
        case 'eye':
            return '&#xE5A4;';
            // 耳
        case 'ear':
            return '&#xE5A5;';
            // 手(グー)
        case 'gu-':
            return '&#xEB83;';
            // 手(チョキ)
        case 'choki':
            return '&#xE5A6;';
            // 手(パー)
        case 'pa-':
            return '&#xE5A7;';
            // 右斜め下
        case 'right_diagonal_under':
            return '&#xE58C;';
            // 左斜め上
        case 'left_diagonal_upper':
            return '&#xE58B;';
            // 足
        case 'foot':
            return '&#xEB2A;';
            // 靴
        case 'shoes':
            return '&#xEB2B;';
            // メガネ
        case 'glasses':
            return '&#xE4FE;';
            // 車椅子
        case 'wheelchair':
            return '&#xE47F;';
            // 新月
        case 'newmoon':
            return '&#xE5A8;';
            // 欠け月
        case 'moon':
            return '&#xE5A9;';
            // 半月
        case 'halfmoon':
            return '&#xE5AA;';
            // 三日月
        case 'crescent':
            return '&#xE486;';
            // 満月
        case 'fullmoon':
            return '&#xE6A0;';
            // 犬
        case 'dog':
            return '&#xE4E1;';
            // 猫
        case 'cat':
            return '&#xE4DB;';
            // リゾート
        case 'resort':
            return '&#xE4B4;';
            // クリスマス
        case 'christmas':
            return '&#xE4C9;';
            // 左斜め下
        case 'left_diagonal_under':
            return '&#xE556;';
            // カチンコ
        case 'clapperboard':
            return '&#xE4BE;';
            // ふくろ
        case 'sac':
            return '&#xE5CE;';
            // ペン
        case 'pen':
            return '&#xEB03;';
            // 人影
        case 'shadow':
            return '&#xE4FC;';
            // 椅子
        case 'chair':
            return '&#xE47F;';
            // 夜
        case 'night':
            return '&#xEAF1;';
            // 時計
        case 'clock':
            return '&#xE594;';
            // 電話CALL
        case 'call':
            return '&#xEB08;';
            // MAIL送信
        case 'sendmail':
            return '&#xEB62;';
            // FAX
        case 'FAX':
            return '&#xE520;';
            // imode
        case 'imode':
            return '&#xE577;';
            // imode(枠あり)
        case 'imode2':
            return '&#xE577;';
            // メール
        case 'mail':
            return '&#xE521;';
            // 有料
        case 'charge':
            return '&#xE57D;';
            // 無料
        case 'free':
            return '&#xE578;';
            // ID
        case 'ID':
            return '&#xEA88;';
            // PASS
        case 'PASS':
            return '&#xE519;';
            // 次項有
        case 'next':
            return '&#xE55D;';
            // クリア
        case 'clear':
            return '&#xE5AB;';
            // サーチ
        case 'search':
            return '&#xE518;';
            // NEW
        case 'new':
            return '&#xE5B5;';
            // 位置情報
        case 'flag':
            return '&#xEB2C;';
            // フリーダイアル
        case 'toll-free':
            return '&#x[FD];';
            // シャープダイアル
        case '#':
            return '&#xEB84;';
            // モバQ
        case 'mobaQ':
            return '&#xE52C;';
            // 1
        case '1':
            return '&#xE522;';
            // 2
        case '2':
            return '&#xE523;';
            // 3
        case '3':
            return '&#xE524;';
            // 4
        case '4':
            return '&#xE525;';
            // 5
        case '5':
            return '&#xE526;';
            // 6
        case '6':
            return '&#xE527;';
            // 7
        case '7':
            return '&#xE528;';
            // 8
        case '8':
            return '&#xE529;';
            // 9
        case '9':
            return '&#xE52A;';
            // 0
        case '0':
            return '&#xE5AC;';
            // 黒ハート
        case 'blackheart':
            return '&#xE595;';
            // 揺れるハート
        case 'swingheart':
            return '&#xF479;';
            // 失恋
        case 'brokenheart':
            return '&#xE477;';
            // ハートたち
        case 'hearts':
            return '&#xE478;';
            // わーい
        case 'face1':
            return '&#xE471;';
            // ちっ
        case 'face2':
            return '&#xE472;';
            // がく～
        case 'face3':
            return '&#xE620;';
            // もうやだ
        case 'face4':
            return '&#xE623;';
            // フラフラ
        case 'face5':
            return '&#xE5AE;';
            // グッド(上向き矢印)
        case 'good':
            return '&#xEB2D;';
            // ルンルン
        case 'note':
            return '&#xE5BE;';
            // いい気分(温泉)
        case 'hot-spring':
            return '&#xE4BC;';
            // かわいい
        case 'cute':
            return '&#xEB49;';
            // キスマーク
        case 'kiss':
            return '&#xE4EB;';
            // ピカピカ(新しい)
        case 'pikapika':
            return '&#xF37E;';
            // ひらめき
        case 'flashing':
            return '&#xE476;';
            // ムカッ
        case 'anger':
            return '&#xE4E5;';
            // パンチ
        case 'punch':
            return '&#xE4F3;';
            // 爆弾
        case 'bomb':
            return '&#xE47A;';
            // ムード
        case 'mood':
            return '&#xE505;';
            // バッド(下向き矢印)
        case 'bad':
            return '&#xEB2E;';
            // 眠い
        case 'zzz':
            return '&#xE475;';
            // !
        case '!':
            return '&#xE482;';
            // !?
        case '!?':
            return '&#xEB2F;';
            // !!
        case '!!':
            return '&#xEB30;';
            // どんっ(衝撃)
        case 'impact':
            return '&#xE5B0;';
            // あせあせ
        case 'hurry':
            return '&#xE5B1;';
            // たらー
        case 'sweat':
            return '&#xE4E6;';
            // ダッシュ
        case 'dash':
            return '&#xE4F4;';
            // 長音記号1
        case 'sign1':
            return '&#x～;';
            // 長音記号2
        case 'sign2':
            return '&#xEB31;';
            // 決定
        case 'OK':
            return '&#x[決定];';
            // iアプリ
        case 'iapp':
            return '&#xE577;';
            // iアプリ(枠あり)
        case 'iapp2':
            return '&#xE577;';
            // Tシャツ
        case 'Tshirt':
            return '&#xE5B6;';
            // がまぐち財布
        case 'purse':
            return '&#xE504;';
            // 化粧
        case 'makeup':
            return '&#xE509;';
            // ジーンズ
        case 'jeans':
            return '&#xEB77;';
            // スノボ
        case 'snowboard':
            return '&#xE4B8;';
            // チャペル
        case 'chapel':
            return '&#xE512;';
            // ドア
        case 'door':
            return '&#xE4AB;';
            // ドル袋
        case '$':
            return '&#xE4C7;';
            // パソコン
        case 'PC':
            return '&#xE5B8;';
            // ラブレター
        case 'loveletter':
            return '&#xEB78;';
            // レンチ
        case 'wrench':
            return '&#xE587;';
            // 鉛筆
        case 'pencil':
            return '&#xE4A1;';
            // 王冠
        case 'crown':
            return '&#xE5C9;';
            // 指輪
        case 'ring':
            return '&#xE514;';
            // 砂時計
        case 'hourglass':
            return '&#xE47C;';
            // 自転車
        case 'bicycle':
            return '&#xE4AE;';
            // 湯のみ
        case 'cup':
            return '&#xE60E;';
            // 腕時計
        case 'watch':
            return '&#xE57A;';
            // 考えている顔
        case 'face6':
            return '&#xE620;';
            // ほっとした顔
        case 'face7':
            return '&#xE625;';
            // 冷や汗1
        case 'face8':
            return '&#xE471;';
            // 冷や汗2
        case 'face9':
            return '&#xE5C6;';
            // くっくっくな顔
        case 'face10':
            return '&#xEB5D;';
            // ボケーっとした顔
        case 'face11':
            return '&#xE629;';
            // 目がハート
        case 'face12':
            return '&#xE5C4;';
            // 親指立てる(了解)
        case 'consent':
            return '&#xE4F9;';
            // あっかんべー
        case 'face13':
            return '&#xE4E7;';
            // ウィンク
        case 'face14':
            return '&#xE5C3;';
            // うれしい顔
        case 'face15':
            return '&#xE625;';
            // がまん顔
        case 'face16':
            return '&#xE622;';
            // 猫2
        case 'cat2':
            return '&#xE61F;';
            // 泣き顔
        case 'face17':
            return '&#xE473;';
            // 涙
        case 'face18':
            return '&#xEB69;';
            // NG
        case 'NG':
            return '&#x[NG];';
            // クリップ
        case 'clip':
            return '&#xE4A0;';
            // コピーライト
        case '(C)':
            return '&#xE558;';
            // トレードマーク
        case 'TM':
            return '&#xE54E;';
            // 走る人
        case 'runner':
            return '&#xE46B;';
            // マル秘
        case 'secret':
            return '&#xE4F1;';
            // リサイクル
        case 'recycle':
            return '&#xEB79;';
            // レジスタードトレードマーク
        case '(R)':
            return '&#xE559;';
            // 危険・警告
        case 'warning':
            return '&#xE481;';
            // 禁止
        case 'prohibition':
            return '&#x[禁];';
            // 空室空席
        case 'vacant':
            return '&#xE5EA;';
            // 合格
        case 'pass':
            return '&#x[合];';
            // 満員満室
        case 'full':
            return '&#xEA89;';
            // 矢印左右
        case 'LRarrow':
            return '&#xEB7A;';
            // 矢印上下
        case 'UDarrow':
            return '&#xEB7B;';
            // 学校
        case 'school':
            return '&#xE5E0;';
            // 波
        case 'wave':
            return '&#xEB7C;';
            // 富士山
        case 'fujiyama':
            return '&#xE5BD;';
            // クローバー
        case 'clover':
            return '&#xE513;';
            // さくらんぼ
        case 'cherry':
            return '&#xE4D2;';
            // チューリップ
        case 'tulip':
            return '&#xE4E4;';
            // バナナ
        case 'banana':
            return '&#xEB35;';
            // りんご
        case 'apple':
            return '&#xEAB9;';//E619
            // 芽
        case 'bud':
            return '&#xEB7D;';
            // もみじ
        case 'momiji':
            return '&#xE4CE;';
            // 桜
        case 'sakura':
            return '&#xE4CA;';
            // おにぎり
        case 'onigiri':
            return '&#xE4D5;';
            // ショートケーキ
        case 'shortcake':
            return '&#xE4D0;';
            // とっくり
        case 'tokkuri':
            return '&#xE5F7;';
            // どんぶり
        case 'donburi':
            return '&#xE5B4;';
            // パン
        case 'bread':
            return '&#xE60F;';
            // かたつむり
        case 'snail':
            return '&#xEB7E;';
            // ひよこ
        case 'hiyoko':
            return '&#xE4E0;';
            // ペンギン
        case 'penguin':
            return '&#xE4DC;';
            // 魚
        case 'fish':
            return '&#xE49A;';
            // うまい！
        case 'face19':
            return '&#xEACD;';
            // うっしっし
        case 'face20':
            return '&#xEB80;';
            // ウマ
        case 'horse':
            return '&#xE4D8;';
            // ブタ
        case 'pig':
            return '&#xE4DE;';
            // ワイングラス
        case 'wine':
            return '&#xE4C1;';
            // ゲッソリ
        case 'face21':
            return '&#xE5C5;';
            // SOON
        case 'SOON':
            return '&#x[SOON];';
            // ON
        case 'ON':
            return '&#x[ON];';
            // END
        case 'END':
            return '&#x[END];';
        }
        break;
        // auここまで

        // softbankここから
    case 'softbank':
        switch ($emoji) {
            // 晴れ
        case 'sunny':
            return '&#xE04A';
            // 曇り
        case 'cloudy':
            return '&#xE049';
            // 雨
        case 'rainy':
            return '&#xE04B';
            // 雪
        case 'snowy':
            return '&#xE048';
            // 雷
        case 'ぱんｔ':
            return '&#xE13D';
            // 台風
        case 'typhoon':
            return '&#xE443';
            // 霧
        case 'fog':
            return '&#xxE049';
            // 小雨
        case 'drizzle':
            return '&#xE04B';
            // 牡羊座
        case 'aries':
            return '&#xE23F';
            // 牡牛座
        case 'taurus':
            return '&#xE240';
            // 双子座
        case 'gemini':
            return '&#xE241';
            // 蟹座
        case 'cancer':
            return '&#xE242';
            // 獅子座
        case 'leo':
            return '&#xE243';
            // 乙女座
        case 'virgo':
            return '&#xE244';
            // 天秤座
        case 'libra':
            return '&#xE245';
            // 蠍座
        case 'scorpio':
            return '&#xE246';
            // 射手座
        case 'sagittarius':
            return '&#xE247';
            // 山羊座
        case 'capricorn':
            return '&#xE248';
            // 水瓶座
        case 'aquarius':
            return '&#xE249';
            // 魚座
        case 'pisces':
            return '&#xE24A';
            // スポーツ
        case 'sports':
            return '&#xE018';
            // 野球
        case 'baseball':
            return '&#xE016';
            // ゴルフ
        case 'golf':
            return '&#xE014';
            // テニス
        case 'tennis':
            return '&#xE015';
            // サッカー
        case 'soccer':
            return '&#xE018';
            // スキー
        case 'skiing':
            return '&#xE013';
            // バスケット
        case 'basket':
            return '&#xE42A';
            // モータースポーツ
        case 'motor':
            return '&#xE01B';
            // ポケットベル
        case 'pkbell':
            return '&#xE104';
            // 電車
        case 'train':
            return '&#xE01E';
            // 地下鉄
        case 'subway':
            return '&#xE434';
            // 新幹線
        case 'shinkansen':
            return '&#xE435';
            // 車(セダン)
        case 'car1':
            return '&#xE42E';
            // 車(RV)
        case 'car2':
            return '&#xE42E';
            // バス
        case 'bus':
            return '&#xE159';
            // 船
        case 'ship':
            return '&#xE202';
            // 飛行機
        case 'airplane':
            return '&#xE01D';
            // 家
        case 'house':
            return '&#xE036';
            // ビル
        case 'building':
            return '&#xE038';
            // 郵便局
        case 'postoffice':
            return '&#xE102';
            // 病院
        case 'hospital':
            return '&#xE155';
            // 銀行
        case 'bank':
            return '&#xE154';
            // ATM
        case 'ATM':
            return '&#xE154';
            // ホテル
        case 'hotel':
            return '&#xE158';
            // コンビニ
        case 'convenience':
            return '&#xE156';
            // ガソリンスタンド
        case 'GS':
            return '&#xE03A';
            // 駐車場
        case 'parking':
            return '&#xE14F';
            // 信号
        case 'signal':
            return '&#xE14E';
            // トイレ
        case 'WC':
            return '&#xE151';
            // レストラン
        case 'restaulant':
            return '&#xE043';
            // 喫茶店
        case 'coffee':
            return '&#xE338';
            // バー
        case 'bar':
            return '&#xE044';
            // ビール
        case 'beer':
            return '&#xE047';
            // ファーストフード
        case 'fastfood':
            return '&#xE120';
            // ブティック
        case 'boutique':
            return '&#xE31A';
            // 美容院
        case 'barber':
            return '&#xE313';
            // カラオケ
        case 'karaoke':
            return '&#xE03C';
            // 映画
        case 'movie':
            return '&#xE03D';
            // 右斜め上
        case 'right_diagonal_upper':
            return '&#xE236';
            // 遊園地
        case 'amusement':
            return '&#xE124';
            // 音楽
        case 'music':
            return '&#xE03E';
            // アート
        case 'art':
            return '&#xE502';
            // 演劇
        case 'play':
            return '&#xE51F';
            // イベント
        case 'event':
            return '&#xE124';
            // ガチャ券
        case 'ticket':
            return '&#xE125';
            // 喫煙
        case 'smoking':
            return '&#xE30E';
            // 禁煙
        case 'no_smoking':
            return '&#xE208';
            // カメラ
        case 'camera':
            return '&#xE008';
            // カバン
        case 'bag':
            return '&#xE323';
            // 本
        case 'book':
            return '&#xE148';
            // リボン
        case 'ribbon':
            return '&#xE314';
            // プレゼント
        case 'present':
            return '&#xE112';
            // バースデー
        case 'birthday':
            return '&#xE34B';
            // 電話
        case 'phone':
            return '&#xE009';
            // 携帯電話
        case 'mphone':
            return '&#xE00A';
            // メモ
        case 'memo':
            return '&#xE301';
            // TV
        case 'TV':
            return '&#xE12A';
            // ゲーム
        case 'game':
            return '&#xE12B';
            // CD
        case 'CD':
            return '&#xE126';
            // ハート
        case 'heart':
            return '&#xE20C';
            // スペード
        case 'spade':
            return '&#xE20E';
            // ダイヤ
        case 'diamond':
            return '&#xE20D';
            // クラブ
        case 'club':
            return '&#xE20F';
            // 目
        case 'eye':
            return '&#xE419';
            // 耳
        case 'ear':
            return '&#xE41B';
            // 手(グー)
        case 'gu-':
            return '&#xE010';
            // 手(チョキ)
        case 'choki':
            return '&#xE011';
            // 手(パー)
        case 'pa-':
            return '&#xE012';
            // 右斜め下
        case 'right_diagonal_under':
            return '&#xE238';
            // 左斜め上
        case 'left_diagonal_upper':
            return '&#xE236';
            // 足
        case 'foot':
            return '&#xE536';
            // 靴
        case 'shoes':
            return '&#xE007';
            // メガネ
        case 'glasses':
            return '&#xE419';
            // 車椅子
        case 'wheelchair':
            return '&#xE20A';
            // 新月
        case 'newmoon':
            return '&#xE219';
            // 欠け月
        case 'moon':
            return '&#xE04C';
            // 半月
        case 'halfmoon':
            return '&#xE04C';
            // 三日月
        case 'crescent':
            return '&#xE04C';
            // 満月
        case 'fullmoon':
            return '&#xE332';
            // 犬
        case 'dog':
            return '&#xE52A';
            // 猫
        case 'cat':
            return '&#xE04F';
            // リゾート
        case 'resort':
            return '&#xE50A';
            // クリスマス
        case 'christmas':
            return '&#xE033';
            // 左斜め下
        case 'left_diagonal_under':
            return '&#xE239';
            // カチンコ
        case 'clapperboard':
            return '&#xE324';
            // ふくろ
        case 'sac':
            return '&#xE11E';
            // ペン
        case 'pen':
            return '&#xE148';
            // 人影
        case 'shadow':
            return '&#xE053';
            // 椅子
        case 'chair':
            return '&#xE11F';
            // 夜
        case 'night':
            return '&#xE44B';
            // 時計
        case 'clock':
            return '&#xE02F';
            // 電話CALL
        case 'call':
            return '&#xE104';
            // MAIL送信
        case 'sendmail':
            return '&#xE103';
            // FAX
        case 'FAX':
            return '&#xE00B';
            // imode
        case 'imode':
            return '&#xE255';
            // imode(枠あり)
        case 'imode2':
            return '&#xE255';
            // メール
        case 'mail':
            return '&#xE103';
            // 有料
        case 'charge':
            return '&#xE215';
            // 無料
        case 'free':
            return '&#xE216';
            // ID
        case 'ID':
            return '[ID]';
            // PASS
        case 'PASS':
            return '&#xE03F';
            // 次項有
        case 'next':
            return '&#xE235';
            // クリア
        case 'clear':
            return '&#xE22B';
            // サーチ
        case 'search':
            return '&#xE114';
            // NEW
        case 'new':
            return '&#xE212';
            // 位置情報
        case 'flag':
            return '&#xE505';
            // フリーダイアル
        case 'toll-free':
            return '&#xE211';
            // シャープダイアル
        case '#':
            return '&#xE210';
            // モバQ
        case 'mobaQ':
            return '&#xE12B';
            // 1
        case '1':
            return '&#xE21C';
            // 2
        case '2':
            return '&#xE21D';
            // 3
        case '3':
            return '&#xE21E';
            // 4
        case '4':
            return '&#xE21F';
            // 5
        case '5':
            return '&#xE220';
            // 6
        case '6':
            return '&#xE221';
            // 7
        case '7':
            return '&#xE222';
            // 8
        case '8':
            return '&#xE223';
            // 9
        case '9':
            return '&#xE224';
            // 0
        case '0':
            return '&#xE225';
            // 黒ハート
        case 'blackheart':
            return '&#xE327';
            // 揺れるハート
        case 'swingheart':
            return '&#xE328';
            // 失恋
        case 'brokenheart':
            return '&#xE023';
            // ハートたち
        case 'hearts':
            return '&#xE327';
            // わーい
        case 'face1':
            return '&#xE001';
            // ちっ
        case 'face2':
            return '&#xE407';
            // がく～
        case 'face3':
            return '&#xE406';
            // もうやだ
        case 'face4':
            return '&#xE40F';
            // フラフラ
        case 'face5':
            return '&#xE108';
            // グッド(上向き矢印)
        case 'good':
            return '&#xE236';
            // ルンルン
        case 'note':
            return '&#xE40A';
            // いい気分(温泉)
        case 'hot-spring':
            return '&#xE001';
            // かわいい
        case 'cute':
            return '&#xE412';
            // キスマーク
        case 'kiss':
            return '&#xE41C';
            // ピカピカ(新しい)
        case 'pikapika':
            return '&#xE32E';
            // ひらめき
        case 'flashing':
            return '&#xE10F';
            // ムカッ
        case 'anger':
            return '&#xE334';
            // パンチ
        case 'punch':
            return '&#xE00D';
            // 爆弾
        case 'bomb':
            return '&#xE311';
            // ムード
        case 'mood':
            return '&#xE03E';
            // バッド(下向き矢印)
        case 'bad':
            return '&#xE238';
            // 眠い
        case 'zzz':
            return '&#xE408';
            // !
        case '!':
            return '&#xE021';
            // !?
        case '!?':
            return '&#xE020';
            // !!
        case '!!':
            return '&#xE021';
            // どんっ(衝撃)
        case 'impact':
            return '&#xE330';
            // あせあせ
        case 'hurry':
            return '&#xE331';
            // たらー
        case 'sweat':
            return '&#xE331';
            // ダッシュ
        case 'dash':
            return '&#xE330';
            // 長音記号1
        case 'sign1':
            return '～';
            // 長音記号2
        case 'sign2':
            return '～';
            // 決定
        case 'OK':
            return '&#xE24D';
            // iアプリ
        case 'iapp':
            return '&#xE00A';
            // iアプリ(枠あり)
        case 'iapp2':
            return '&#xE00A';
            // Tシャツ
        case 'Tshirt':
            return '&#xE006';
            // がまぐち財布
        case 'purse':
            return '&#xE12F';
            // 化粧
        case 'makeup':
            return '&#xE31C';
            // ジーンズ
        case 'jeans':
            return '&#xE319';
            // スノボ
        case 'snowboard':
            return '&#xE013';
            // チャペル
        case 'chapel':
            return '&#xE43D';
            // ドア
        case 'door':
            return '&#xE036';
            // ドル袋
        case '$':
            return '&#xE12F';
            // パソコン
        case 'PC':
            return '&#xE00C';
            // ラブレター
        case 'loveletter':
            return '&#xE103';
            // レンチ
        case 'wrench':
            return '&#xE302';
            // 鉛筆
        case 'pencil':
            return '&#58113';
            // 王冠
        case 'crown':
            return '&#xE10E';
            // 指輪
        case 'ring':
            return '&#xE034';
            // 砂時計
        case 'hourglass':
            return '&#xE024';
            // 自転車
        case 'bicycle':
            return '&#xE201';
            // 湯のみ
        case 'cup':
            return '&#xE338';
            // 腕時計
        case 'watch':
            return '&#xE024';
            // 考えている顔
        case 'face6':
            return '&#xE403';
            // ほっとした顔
        case 'face7':
            return '&#xE40A';
            // 冷や汗1
        case 'face8':
            return '&#xE40F';
            // 冷や汗2
        case 'face9':
            return '&#xE331';
            // くっくっくな顔
        case 'face10':
            return '&#xE402';
            // ボケーっとした顔
        case 'face11':
            return '&#xE40E';
            // 目がハート
        case 'face12':
            return '&#xE106';
            // 親指立てる(了解)
        case 'consent':
            return '&#58400;';
            // あっかんべー
        case 'face13':
            return '&#xE105';
            // ウィンク
        case 'face14':
            return '&#xE405';
            // うれしい顔
        case 'face15':
            return '&#xE414';
            // がまん顔
        case 'face16':
            return '&#xE416';
            // 猫2
        case 'cat2':
            return '&#xE04F';
            // 泣き顔
        case 'face17':
            return '&#xE411';
            // 涙
        case 'face18':
            return '&#xE413';
            // NG
        case 'NG':
            return '&#xE423';
            // クリップ
        case 'clip':
            return '&#xE148';
            // コピーライト
        case '(C)':
            return '&#xE24E';
            // トレードマーク
        case 'TM':
            return '&#xE537';
            // 走る人
        case 'runner':
            return '&#xE330';
            // マル秘
        case 'secret':
            return '&#xE315';
            // リサイクル
        case 'recycle':
            return '&#xE51E';
            // レジスタードトレードマーク
        case '(R)':
            return '&#xE537';
            // 危険・警告
        case 'warning':
            return '&#xE252';
            // 禁止
        case 'prohibition':
            return '&#xE423';
            // 空室空席
        case 'vacant':
            return '&#xE22B';
            // 合格
        case 'pass':
            return '&#xE5AD';
            // 満員満室
        case 'full':
            return '&#xE22A';
            // 矢印左右
        case 'LRarrow':
            return '⇔';
            // 矢印上下
        case 'UDarrow':
            return '↑↓';
            // 学校
        case 'school':
            return '&#xE157';
            // 波
        case 'wave':
            return '&#xE43E';
            // 富士山
        case 'fujiyama':
            return '&#xE03B';
            // クローバー
        case 'clover':
            return '&#xE110';
            // さくらんぼ
        case 'cherry':
            return '&#xE347';
            // チューリップ
        case 'tulip':
            return '&#xE304';
            // バナナ
        case 'banana':
            return '&#xE33A';
            // りんご
        case 'apple':
            return '&#xE345';
            // 芽
        case 'bud':
            return '&#xE307';
            // もみじ
        case 'momiji':
            return '&#xE118';
            // 桜
        case 'sakura':
            return '&#xE030';
            // おにぎり
        case 'onigiri':
            return '&#xE342';
            // ショートケーキ
        case 'shortcake':
            return '&#xE046';
            // とっくり
        case 'tokkuri':
            return '&#xE338';
            // どんぶり
        case 'donburi':
            return '&#xE33E';
            // パン
        case 'bread':
            return '&#xE339';
            // かたつむり
        case 'snail':
            return '＠';
            // ひよこ
        case 'hiyoko':
            return '&#xE523';
            // ペンギン
        case 'penguin':
            return '&#xE055';
            // 魚
        case 'fish':
            return '&#xE019';
            // うまい！
        case 'face19':
            return '&#xE40A';
            // うっしっし
        case 'face20':
            return '&#xE404';
            // ウマ
        case 'horse':
            return '&#xE134';
            // ブタ
        case 'pig':
            return '&#xE10B';
            // ワイングラス
        case 'wine':
            return '&#xE110';
            // ゲッソリ
        case 'face21':
            return '&#xE107';
            // SOON
        case 'SOON':
            return '[SOON]';
            // ON
        case 'ON':
            return '[ON]';
            // END
        case 'END':
            return '[END]';
        }
        break;
        // softbankここまで
    }
    
    return '';
}
