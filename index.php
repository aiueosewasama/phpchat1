<?php
// .envから設定を読み込む簡易関数
function getEnvValue($key) {
    if (!file_exists('.env')) return null;
    $lines = file('.env', FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if (strpos($line, '=') !== false) {
            list($name, $value) = explode('=', $line, 2);
            if (trim($name) === $key) return trim($value);
        }
    }
    return null;
}

$apiKey = getEnvValue('OPENAI_API_KEY');
$result = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_POST['message'])) {
    $userMessage = $_POST['message'];
    
    // APIへのリクエスト設定
    $url = 'https://api.openai.com/v1/chat/completions';
    $data = [
        'model' => 'gpt-5-mini', // ご指定のモデル名
        'messages' => [
            ['role' => 'system', 'content' => 'ユーザーの入力から最も重要な名詞を1つ選び、「原語:英訳」の形式で1行のみ返してください。余計な説明は一切不要です。'],
            ['role' => 'user', 'content' => $userMessage]
        ],
        'temperature' => 0.3,
    ];

    $options = [
        'http' => [
            'method'  => 'POST',
            'header'  => [
                'Content-Type: application/json',
                'Authorization: Bearer ' . $apiKey
            ],
            'content' => json_encode($data),
            'ignore_errors' => true
        ]
    ];

    $context = stream_context_create($options);
    $response = file_get_contents($url, false, $context);
    $responseDict = json_decode($response, true);

    if (isset($responseDict['choices'][0]['message']['content'])) {
        $result = $responseDict['choices'][0]['message']['content'];
    } else {
        $result = "エラー: " . ($responseDict['error']['message'] ?? '通信に失敗しました。');
    }
}
?>

<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <title>Chat Translator</title>
</head>
<body>
    <h1>単語抽出チャット</h1>
    <form method="post">
        <input type="text" name="message" placeholder="文章を入力してください" style="width: 300px;" required>
        <button type="submit">送信</button>
    </form>

    <?php if ($result): ?>
        <h2>結果:</h2>
        <pre><?php echo htmlspecialchars($result); ?></pre>
    <?php endif; ?>
</body>
</html>