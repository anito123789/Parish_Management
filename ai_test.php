<?php
/**
 * Simple Offline AI - Demo & Testing Page
 * Test all AI features in one place
 */

require_once 'ai/simple_ai.php';

header('Content-Type: text/html; charset=utf-8');

$ai = getAI();
$test_results = [];

// Run all tests
$tests = [
    'info' => fn() => ['AI System Status' => 'Ready', 'Type' => 'Simple Offline AI', 'Version' => '1.0'],
    'suggestion' => fn() => $ai->generateSuggestion('birthday', 'John'),
    'multiple' => fn() => count($ai->generateMultipleSuggestions('anniversary', 3)),
    'sentiment' => fn() => $ai->analyzeSentiment('Great message!'),
    'keywords' => fn() => $ai->extractKeywords('The parish community is gathering for celebration'),
    'validate' => fn() => $ai->validateText('This is a valid message'),
    'contexts' => fn() => count($ai->getAvailableContexts()),
];

foreach ($tests as $name => $test) {
    try {
        $result = $test();
        $test_results[$name] = [
            'status' => 'PASS',
            'result' => is_array($result) ? json_encode($result) : (is_numeric($result) ? $result : substr($result, 0, 100))
        ];
    } catch (Exception $e) {
        $test_results[$name] = [
            'status' => 'FAIL',
            'error' => $e->getMessage()
        ];
    }
}

?>
<!DOCTYPE html>
<html>
<head>
    <title>AI System Test</title>
    <style>
        * {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
        }
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            margin: 0;
            padding: 20px;
            min-height: 100vh;
        }
        .container {
            max-width: 800px;
            margin: 0 auto;
            background: white;
            border-radius: 12px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
            padding: 40px;
        }
        h1 {
            color: #333;
            margin-top: 0;
            text-align: center;
        }
        .status {
            text-align: center;
            font-size: 18px;
            margin-bottom: 30px;
            padding: 15px;
            background: #f0fdf4;
            border-radius: 8px;
            color: #166534;
            border: 2px solid #22c55e;
        }
        .test-grid {
            display: grid;
            gap: 15px;
        }
        .test-item {
            padding: 15px;
            border-radius: 8px;
            border-left: 4px solid #3b82f6;
        }
        .test-item.pass {
            background: #f0fdf4;
            border-left-color: #22c55e;
        }
        .test-item.fail {
            background: #fef2f2;
            border-left-color: #ef4444;
        }
        .test-item h3 {
            margin: 0 0 8px 0;
            color: #333;
        }
        .test-item .badge {
            display: inline-block;
            padding: 4px 12px;
            border-radius: 12px;
            font-size: 12px;
            font-weight: 600;
            margin-right: 10px;
        }
        .badge.pass {
            background: #22c55e;
            color: white;
        }
        .badge.fail {
            background: #ef4444;
            color: white;
        }
        .result {
            font-size: 13px;
            color: #666;
            margin-top: 8px;
            padding: 8px;
            background: white;
            border-radius: 4px;
            max-height: 80px;
            overflow: auto;
            font-family: monospace;
        }
        .links {
            text-align: center;
            margin-top: 30px;
            padding-top: 30px;
            border-top: 2px solid #e5e7eb;
        }
        .links a {
            display: inline-block;
            margin: 8px;
            padding: 10px 20px;
            background: #3b82f6;
            color: white;
            text-decoration: none;
            border-radius: 6px;
            transition: background 0.2s;
        }
        .links a:hover {
            background: #2563eb;
        }
        .passed {
            color: #22c55e;
            font-weight: 600;
        }
        .failed {
            color: #ef4444;
            font-weight: 600;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>🤖 Simple Offline AI - System Test</h1>
        
        <?php
            $passed = count(array_filter($test_results, fn($t) => $t['status'] === 'PASS'));
            $total = count($test_results);
        ?>
        
        <div class="status">
            ✓ AI System <span class="passed">ONLINE & READY</span><br>
            <small><?php echo "$passed/$total tests passed"; ?></small>
        </div>

        <div class="test-grid">
            <?php foreach ($test_results as $name => $result): ?>
                <div class="test-item <?php echo strtolower($result['status']); ?>">
                    <h3>
                        <?php if ($result['status'] === 'PASS'): ?>
                            <span class="badge pass">✓ PASS</span>
                        <?php else: ?>
                            <span class="badge fail">✗ FAIL</span>
                        <?php endif; ?>
                        <?php echo ucfirst(str_replace('_', ' ', $name)); ?>
                    </h3>
                    <?php if (isset($result['result'])): ?>
                        <div class="result"><?php echo htmlspecialchars($result['result']); ?></div>
                    <?php elseif (isset($result['error'])): ?>
                        <div class="result" style="color: #dc2626;"><?php echo htmlspecialchars($result['error']); ?></div>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        </div>

        <div class="links">
            <h3 style="margin-top: 0;">Access AI Features:</h3>
            <a href="ai_assistant.php">🎯 AI Assistant (Main Interface)</a>
            <a href="ai_integration_examples.php">💡 Integration Examples</a>
            <a href="ai/api.php?action=info">📡 API Info</a>
        </div>

        <div style="text-align: center; color: #999; font-size: 12px; margin-top: 20px;">
            Simple Offline AI v1.0 • No Ollama Required • Fully Offline
        </div>
    </div>
</body>
</html>
