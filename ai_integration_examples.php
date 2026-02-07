<?php
/**
 * AI Integration Examples
 * Shows how to integrate Simple Offline AI into Parish M features
 */

require_once 'db.php';
require_once 'ai/simple_ai.php';
include 'includes/header.php';

$ai = getAI();
$examples = [];

// Example 1: Message Template Helper
if ($_POST['example'] === 'birthday') {
    $name = $_POST['name'] ?? '';
    $suggestion = $ai->generateSuggestion('birthday', $name);
    $examples['birthday'] = [
        'title' => 'Birthday Message Generator',
        'description' => 'Generate personalized birthday messages',
        'result' => $suggestion,
        'usage' => 'Used in parishioner notifications, WhatsApp messages'
    ];
}

// Example 2: Marriage Anniversary
if ($_POST['example'] === 'anniversary') {
    $name = $_POST['name'] ?? '';
    $suggestion = $ai->generateSuggestion('anniversary', $name);
    $examples['anniversary'] = [
        'title' => 'Anniversary Message Generator',
        'description' => 'Generate marriage anniversary wishes',
        'result' => $suggestion,
        'usage' => 'Used in family anniversary notifications'
    ];
}

// Example 3: Condolence Message
if ($_POST['example'] === 'condolence') {
    $name = $_POST['name'] ?? '';
    $suggestion = $ai->generateSuggestion('condolence', $name);
    $examples['condolence'] = [
        'title' => 'Condolence Message Generator',
        'description' => 'Generate sympathy messages for death anniversaries',
        'result' => $suggestion,
        'usage' => 'Used in death anniversary notifications'
    ];
}

// Example 4: Text Validation
if ($_POST['example'] === 'validate') {
    $text = $_POST['text'] ?? '';
    $validation = $ai->validateText($text);
    $examples['validate'] = [
        'title' => 'Message Validation',
        'description' => 'Check message quality and issues',
        'result' => $validation,
        'usage' => 'Used before sending messages to parishioners'
    ];
}

?>

<div style="margin: 20px; max-width: 1200px; margin-left: auto; margin-right: auto;">
    <h1>AI Integration Examples</h1>
    <p style="color: #666; margin-bottom: 30px;">
        Practical examples of integrating Simple Offline AI into Parish M features.
    </p>

    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-bottom: 30px;">
        
        <!-- Example 1: Birthday Messages -->
        <div style="background: #f3f4f6; padding: 20px; border-radius: 8px; border: 1px solid #e5e7eb;">
            <h3 style="margin-top: 0;">🎂 Birthday Message Generator</h3>
            <p style="color: #666; font-size: 14px;">Generate personalized birthday wishes</p>
            
            <form method="POST">
                <input type="hidden" name="example" value="birthday">
                <input type="text" name="name" placeholder="Parishioner name" style="width: 100%; padding: 8px; margin-bottom: 10px; border: 1px solid #d1d5db; border-radius: 4px; box-sizing: border-box;">
                <button type="submit" style="background: #2563eb; color: white; padding: 8px 16px; border: none; border-radius: 4px; cursor: pointer; width: 100%;">Generate Message</button>
            </form>
            
            <?php if (isset($examples['birthday'])): ?>
                <div style="margin-top: 15px; padding: 12px; background: white; border-radius: 4px; border-left: 3px solid #2563eb;">
                    <p style="margin: 0; line-height: 1.6;"><?php echo htmlspecialchars($examples['birthday']['result']); ?></p>
                    <small style="color: #999; display: block; margin-top: 8px;">
                        <button onclick="copyToClipboard(this)" style="background: none; border: none; color: #2563eb; cursor: pointer; text-decoration: underline;">📋 Copy</button>
                    </small>
                </div>
            <?php endif; ?>
        </div>

        <!-- Example 2: Anniversary Messages -->
        <div style="background: #f3f4f6; padding: 20px; border-radius: 8px; border: 1px solid #e5e7eb;">
            <h3 style="margin-top: 0;">💍 Anniversary Message Generator</h3>
            <p style="color: #666; font-size: 14px;">Generate marriage anniversary wishes</p>
            
            <form method="POST">
                <input type="hidden" name="example" value="anniversary">
                <input type="text" name="name" placeholder="Couple name" style="width: 100%; padding: 8px; margin-bottom: 10px; border: 1px solid #d1d5db; border-radius: 4px; box-sizing: border-box;">
                <button type="submit" style="background: #2563eb; color: white; padding: 8px 16px; border: none; border-radius: 4px; cursor: pointer; width: 100%;">Generate Message</button>
            </form>
            
            <?php if (isset($examples['anniversary'])): ?>
                <div style="margin-top: 15px; padding: 12px; background: white; border-radius: 4px; border-left: 3px solid #2563eb;">
                    <p style="margin: 0; line-height: 1.6;"><?php echo htmlspecialchars($examples['anniversary']['result']); ?></p>
                    <small style="color: #999; display: block; margin-top: 8px;">
                        <button onclick="copyToClipboard(this)" style="background: none; border: none; color: #2563eb; cursor: pointer; text-decoration: underline;">📋 Copy</button>
                    </small>
                </div>
            <?php endif; ?>
        </div>

        <!-- Example 3: Condolence Messages -->
        <div style="background: #f3f4f6; padding: 20px; border-radius: 8px; border: 1px solid #e5e7eb;">
            <h3 style="margin-top: 0;">🙏 Condolence Message Generator</h3>
            <p style="color: #666; font-size: 14px;">Generate sympathy messages</p>
            
            <form method="POST">
                <input type="hidden" name="example" value="condolence">
                <input type="text" name="name" placeholder="Departed name" style="width: 100%; padding: 8px; margin-bottom: 10px; border: 1px solid #d1d5db; border-radius: 4px; box-sizing: border-box;">
                <button type="submit" style="background: #2563eb; color: white; padding: 8px 16px; border: none; border-radius: 4px; cursor: pointer; width: 100%;">Generate Message</button>
            </form>
            
            <?php if (isset($examples['condolence'])): ?>
                <div style="margin-top: 15px; padding: 12px; background: white; border-radius: 4px; border-left: 3px solid #2563eb;">
                    <p style="margin: 0; line-height: 1.6;"><?php echo htmlspecialchars($examples['condolence']['result']); ?></p>
                    <small style="color: #999; display: block; margin-top: 8px;">
                        <button onclick="copyToClipboard(this)" style="background: none; border: none; color: #2563eb; cursor: pointer; text-decoration: underline;">📋 Copy</button>
                    </small>
                </div>
            <?php endif; ?>
        </div>

        <!-- Example 4: Text Validation -->
        <div style="background: #f3f4f6; padding: 20px; border-radius: 8px; border: 1px solid #e5e7eb;">
            <h3 style="margin-top: 0;">✓ Message Validator</h3>
            <p style="color: #666; font-size: 14px;">Check message quality</p>
            
            <form method="POST">
                <input type="hidden" name="example" value="validate">
                <textarea name="text" placeholder="Paste message to validate..." style="width: 100%; padding: 8px; margin-bottom: 10px; border: 1px solid #d1d5db; border-radius: 4px; box-sizing: border-box; font-family: monospace; font-size: 12px; min-height: 80px;"></textarea>
                <button type="submit" style="background: #2563eb; color: white; padding: 8px 16px; border: none; border-radius: 4px; cursor: pointer; width: 100%;">Validate</button>
            </form>
            
            <?php if (isset($examples['validate'])): 
                $v = $examples['validate']['result'];
                $color = $v['is_valid'] ? '#16a34a' : '#dc2626';
                $icon = $v['is_valid'] ? '✓' : '⚠';
            ?>
                <div style="margin-top: 15px; padding: 12px; background: white; border-radius: 4px; border-left: 3px solid <?php echo $color; ?>;">
                    <p style="margin: 0; color: <?php echo $color; ?>; font-weight: bold;">
                        <?php echo $icon; ?> <?php echo $v['is_valid'] ? 'Valid Message' : 'Issues Found'; ?>
                    </p>
                    <?php if (!empty($v['issues'])): ?>
                        <ul style="margin: 8px 0 0 0; padding-left: 20px; color: <?php echo $color; ?>;">
                            <?php foreach ($v['issues'] as $issue): ?>
                                <li style="font-size: 12px;"><?php echo $issue; ?></li>
                            <?php endforeach; ?>
                        </ul>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        </div>

    </div>

    <!-- Code Examples Section -->
    <div style="background: #f3f4f6; padding: 20px; border-radius: 8px; margin-bottom: 30px;">
        <h2>How to Integrate into Your Features</h2>
        
        <h3>In family_view.php (Add AI suggestion button)</h3>
        <pre style="background: white; padding: 12px; border-radius: 4px; overflow-x: auto; border: 1px solid #e5e7eb;"><code>&lt;?php
$ai = getAI();
$birthday_suggestion = $ai-&gt;generateSuggestion('birthday', $family['head_name']);
?&gt;

&lt;button onclick="showAISuggestion('&lt;?php echo addslashes($birthday_suggestion); ?&gt;')"&gt;
    🤖 AI Suggestion
&lt;/button&gt;</code></pre>

        <h3>In subscription form (Add AI validation)</h3>
        <pre style="background: white; padding: 12px; border-radius: 4px; overflow-x: auto; border: 1px solid #e5e7eb; margin-top: 15px;"><code>&lt;?php
$ai = getAI();

// After user enters message
$validation = $ai-&gt;validateText($_POST['message']);

if (!$validation['is_valid']) {
    echo "⚠️ Issues with message:&lt;br&gt;";
    foreach ($validation['issues'] as $issue) {
        echo "- " . $issue . "&lt;br&gt;";
    }
}
?&gt;</code></pre>

        <h3>JavaScript - Show AI suggestion modal</h3>
        <pre style="background: white; padding: 12px; border-radius: 4px; overflow-x: auto; border: 1px solid #e5e7eb; margin-top: 15px;"><code>function showAISuggestion(suggestion) {
    const modal = document.createElement('div');
    modal.innerHTML = `
        &lt;div style="position: fixed; top: 50%; left: 50%; transform: translate(-50%, -50%);
                    background: white; padding: 20px; border-radius: 8px; 
                    box-shadow: 0 4px 6px rgba(0,0,0,0.1); z-index: 1000;"&gt;
            &lt;p&gt;${suggestion}&lt;/p&gt;
            &lt;button onclick="this.closest('div').parentElement.remove()"&gt;Close&lt;/button&gt;
        &lt;/div&gt;
    `;
    document.body.appendChild(modal);
}</code></pre>
    </div>

    <!-- Integration Checklist -->
    <div style="background: #ecfdf5; padding: 20px; border-radius: 8px; border: 1px solid #d1fae5;">
        <h2 style="margin-top: 0; color: #065f46;">Integration Checklist</h2>
        
        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px;">
            <div>
                <h4 style="color: #065f46; margin-bottom: 10px;">✓ Implemented</h4>
                <ul style="margin: 0; padding-left: 20px;">
                    <li>AI Assistant page (ai_assistant.php)</li>
                    <li>Simple offline AI module (ai/simple_ai.php)</li>
                    <li>REST API endpoint (ai/api.php)</li>
                    <li>Navigation menu updated</li>
                    <li>Message suggestions system</li>
                    <li>Sentiment analysis</li>
                    <li>Keyword extraction</li>
                    <li>Text validation</li>
                </ul>
            </div>
            
            <div>
                <h4 style="color: #065f46; margin-bottom: 10px;">💡 Ready to Integrate</h4>
                <ul style="margin: 0; padding-left: 20px;">
                    <li>Family form - Birthday messages</li>
                    <li>Subscription form - Message helper</li>
                    <li>Reports - AI summaries</li>
                    <li>Planner - Event suggestions</li>
                    <li>Parishioner form - Data validation</li>
                    <li>Reports generation - Auto-summary</li>
                </ul>
            </div>
        </div>
    </div>

</div>

<style>
    pre {
        font-size: 12px;
        line-height: 1.4;
    }
    
    code {
        font-family: 'Courier New', monospace;
    }
</style>

<script>
function copyToClipboard(btn) {
    const text = btn.closest('div').querySelector('p').textContent;
    navigator.clipboard.writeText(text).then(() => {
        const oldText = btn.textContent;
        btn.textContent = '✓ Copied!';
        setTimeout(() => btn.textContent = oldText, 2000);
    });
}
</script>

<?php include 'includes/footer.php'; ?>
