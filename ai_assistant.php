<?php
require_once 'db.php';
require_once 'ai/simple_ai.php';
include 'includes/header.php';

$ai = getAI();
$contexts = $ai->getAvailableContexts();
?>

<div class="content-wrapper" style="padding: 20px; max-width: 1200px; margin: 0 auto;">
    <div class="header-section" style="margin-bottom: 30px;">
        <h1 style="font-size: 2.5rem; color: #1e293b; margin-bottom: 10px;">✨ Smart Parish Assistant</h1>
        <p style="color: #64748b; font-size: 1.1rem;">
            Your intelligent, offline-first helper for parish management and communication.
            <span style="display: block; font-size: 0.9rem; color: #94a3b8; margin-top: 5px;">🔒 100% Private • Fully
                Offline • Locally Trained</span>
        </p>
    </div>

    <!-- AI Features Tabs -->
    <div class="tab-wrapper"
        style="background: #fff; border-radius: 12px; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.1), 0 2px 4px -1px rgba(0,0,0,0.06); overflow: hidden;">
        <div class="tab-header"
            style="background: #f8fafc; border-bottom: 1px solid #e2e8f0; display: flex; gap: 5px; padding: 10px 15px;">
            <button class="tab-button active" onclick="switchTab(event, 'chat')"
                style="padding: 12px 24px; border: none; background: none; cursor: pointer; font-weight: 600; color: #64748b; border-radius: 8px; transition: all 0.2s;">
                💬 AI Chat
            </button>
            <button class="tab-button" onclick="switchTab(event, 'suggestions')"
                style="padding: 12px 24px; border: none; background: none; cursor: pointer; font-weight: 600; color: #64748b; border-radius: 8px; transition: all 0.2s;">
                💡 Suggestions
            </button>
            <button class="tab-button" onclick="switchTab(event, 'analysis')"
                style="padding: 12px 24px; border: none; background: none; cursor: pointer; font-weight: 600; color: #64748b; border-radius: 8px; transition: all 0.2s;">
                🔍 Analysis
            </button>
        </div>

        <div class="tab-body" style="padding: 30px;">
            <!-- Chat Tab (Default) -->
            <div id="chat" class="tab-content" style="display: block;">
                <div style="display: grid; grid-template-columns: 1fr 300px; gap: 30px;">
                    <!-- Main Chat Area -->
                    <div
                        style="border: 1px solid #e2e8f0; border-radius: 12px; display: flex; flex-direction: column; height: 500px; background: #fafafa;">
                        <div id="chatWindow"
                            style="flex: 1; padding: 20px; overflow-y: auto; display: flex; flex-direction: column; gap: 15px;">
                            <div class="bot-msg"
                                style="align-self: flex-start; background: #fff; border: 1px solid #e2e8f0; padding: 12px 18px; border-radius: 16px 16px 16px 4px; max-width: 80%; box-shadow: 0 2px 4px rgba(0,0,0,0.05);">
                                Hello! I'm your Parish Assistant. How can I help you today? You can ask me about
                                vouchers, members, or certificates.
                            </div>
                        </div>
                        <div
                            style="padding: 20px; border-top: 1px solid #e2e8f0; background: #fff; display: flex; gap: 10px;">
                            <input type="text" id="chatInput" placeholder="Ask me something..."
                                style="flex: 1; padding: 12px 20px; border: 2px solid #e2e8f0; border-radius: 10px; font-size: 1rem; outline: none; transition: border-color 0.2s;"
                                onkeypress="if(event.key==='Enter') sendChat()">
                            <button onclick="sendChat()"
                                style="background: #2563eb; color: white; border: none; padding: 0 25px; border-radius: 10px; font-weight: 600; cursor: pointer;">Send</button>
                        </div>
                    </div>

                    <!-- Sidebar Info -->
                    <div>
                        <div
                            style="background: #eff6ff; padding: 20px; border-radius: 12px; border: 1px solid #bfdbfe; margin-bottom: 20px;">
                            <h4 style="margin: 0 0 10px 0; color: #1e40af;">Try asking:</h4>
                            <ul
                                style="margin: 0; padding-left: 20px; color: #1e40af; font-size: 0.9rem; line-height: 1.6;">
                                <li style="cursor:pointer;" onclick="fillChat('How to record a receipt?')">How to record
                                    a receipt?</li>
                                <li style="cursor:pointer;" onclick="fillChat('Where are expense vouchers?')">Where are
                                    expense vouchers?</li>
                                <li style="cursor:pointer;" onclick="fillChat('How to pay subscription?')">How to pay
                                    subscription?</li>
                                <li style="cursor:pointer;" onclick="fillChat('Need help with certificates')">Need help
                                    with certificates</li>
                            </ul>
                        </div>
                        <a href="ai_guide.php"
                            style="display: block; text-align: center; padding: 15px; background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 12px; text-decoration: none; color: #475569; font-weight: 500;">
                            📖 Custom Training Guide
                        </a>
                    </div>
                </div>
            </div>

            <!-- Suggestions Tab -->
            <div id="suggestions" class="tab-content" style="display: none;">
                <div
                    style="max-width: 700px; margin: 0 auto; background: #f8fafc; padding: 30px; border-radius: 16px; border: 1px solid #e2e8f0;">
                    <h2 style="margin-top: 0; font-size: 1.5rem; color: #1e293b;">Message Generator</h2>
                    <p style="color: #64748b; margin-bottom: 25px;">Generate thoughtful messages for parishioner events.
                    </p>

                    <div style="margin-bottom: 20px;">
                        <label style="display: block; margin-bottom: 8px; font-weight: 600; color: #475569;">Select
                            Occasion</label>
                        <select id="context"
                            style="width: 100%; padding: 12px; border: 1px solid #cbd5e1; border-radius: 8px; font-size: 1rem;">
                            <option value="">Choose...</option>
                            <?php foreach ($contexts as $ctx): ?>
                                <option value="<?php echo $ctx; ?>"><?php echo ucfirst(str_replace('_', ' ', $ctx)); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div style="margin-bottom: 25px;">
                        <label style="display: block; margin-bottom: 8px; font-weight: 600; color: #475569;">Recipient
                            Name (optional)</label>
                        <input type="text" id="name" placeholder="e.g., Mr. Joseph"
                            style="width: 100%; padding: 12px; border: 1px solid #cbd5e1; border-radius: 8px; font-size: 1rem;">
                    </div>

                    <button onclick="getSuggestion()"
                        style="width: 100%; background: #2563eb; color: white; padding: 15px; border: none; border-radius: 8px; font-size: 1.1rem; font-weight: 600; cursor: pointer; transition: background 0.2s;">
                        ✨ Generate Perfect Message
                    </button>

                    <div id="suggestionResult"
                        style="margin-top: 30px; padding: 25px; background: #fff; border: 1px solid #e2e8f0; border-radius: 12px; display: none; position: relative;">
                        <p id="suggestionText"
                            style="font-size: 1.1rem; line-height: 1.6; color: #1e293b; margin: 0; font-style: italic;">
                        </p>
                        <button onclick="copySuggestion()"
                            style="margin-top: 15px; background: #f1f5f9; border: 1px solid #e2e8f0; padding: 8px 16px; border-radius: 6px; cursor: pointer; font-size: 0.9rem; font-weight: 600;">📋
                            Copy Message</button>
                    </div>
                </div>
            </div>

            <!-- Analysis Tab -->
            <div id="analysis" class="tab-content" style="display: none;">
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 30px;">
                    <div style="background: #f8fafc; padding: 25px; border-radius: 16px; border: 1px solid #e2e8f0;">
                        <h3 style="margin-top: 0;">🏷️ Keyword Extractor</h3>
                        <textarea id="keywordText" placeholder="Paste long text here..."
                            style="width: 100%; height: 120px; padding: 15px; border: 1px solid #cbd5e1; border-radius: 8px; margin-bottom: 15px; font-family: inherit; font-size: 0.95rem;"></textarea>
                        <button onclick="extractKeywords()"
                            style="background: #475569; color: white; padding: 10px 20px; border: none; border-radius: 6px; font-weight: 600; cursor: pointer;">Process
                            Text</button>
                        <div id="keywordResult" style="margin-top: 15px; display: none;">
                            <div id="keywordsList" style="display: flex; flex-wrap: wrap; gap: 10px;"></div>
                        </div>
                    </div>

                    <div style="background: #f8fafc; padding: 25px; border-radius: 16px; border: 1px solid #e2e8f0;">
                        <h3 style="margin-top: 0;">💭 Sentiment Analysis</h3>
                        <textarea id="sentimentText" placeholder="Analyze the mood of this text..."
                            style="width: 100%; height: 120px; padding: 15px; border: 1px solid #cbd5e1; border-radius: 8px; margin-bottom: 15px; font-family: inherit; font-size: 0.95rem;"></textarea>
                        <button onclick="analyzeSentiment()"
                            style="background: #475569; color: white; padding: 10px 20px; border: none; border-radius: 6px; font-weight: 600; cursor: pointer;">Analyze
                            Mood</button>
                        <div id="sentimentResult" style="margin-top: 15px; display: none;">
                            <div style="font-size: 1.2rem; font-weight: bold;" id="sentimentLabel"></div>
                            <div
                                style="background: #e2e8f0; height: 8px; border-radius: 4px; overflow: hidden; margin-top: 8px;">
                                <div id="sentimentBar" style="height: 100%; width: 0%; transition: width 0.5s;"></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
    .tab-button.active {
        background: #2563eb !important;
        color: #fff !important;
    }

    .tab-button:hover:not(.active) {
        background: #f1f5f9;
    }

    .bot-msg {
        align-self: flex-start;
        background: #fff;
        border: 1px solid #e2e8f0;
        padding: 12px 18px;
        border-radius: 16px 16px 16px 4px;
        max-width: 80%;
        box-shadow: 0 2px 4px rgba(0, 0, 0, 0.05);
    }

    .user-msg {
        align-self: flex-end;
        background: #2563eb;
        color: #fff;
        padding: 12px 18px;
        border-radius: 16px 16px 4px 16px;
        max-width: 80%;
        box-shadow: 0 2px 4px rgba(37, 99, 235, 0.2);
    }

    #chatInput:focus {
        border-color: #2563eb;
    }
</style>

<script>
    function switchTab(event, tabName) {
        document.querySelectorAll('.tab-content').forEach(tab => tab.style.display = 'none');
        document.querySelectorAll('.tab-button').forEach(btn => btn.classList.remove('active'));
        document.getElementById(tabName).style.display = 'block';
        event.currentTarget.classList.add('active');
    }

    function fillChat(text) {
        document.getElementById('chatInput').value = text;
        sendChat();
    }

    function sendChat() {
        const input = document.getElementById('chatInput');
        const text = input.value.trim();
        if (!text) return;

        const chatWindow = document.getElementById('chatWindow');
        const userMsg = document.createElement('div');
        userMsg.className = 'user-msg';
        userMsg.textContent = text;
        chatWindow.appendChild(userMsg);
        input.value = '';
        chatWindow.scrollTop = chatWindow.scrollHeight;

        fetch(`ai/api.php?action=chat&input=${encodeURIComponent(text)}`)
            .then(r => r.json())
            .then(data => {
                const botMsg = document.createElement('div');
                botMsg.className = 'bot-msg';
                botMsg.textContent = data.reply;
                chatWindow.appendChild(botMsg);
                chatWindow.scrollTop = chatWindow.scrollHeight;
            });
    }

    function getSuggestion() {
        const context = document.getElementById('context').value;
        const name = document.getElementById('name').value;
        if (!context) return alert('Select occasion');

        fetch(`ai/api.php?action=suggest&context=${context}&name=${name}`)
            .then(r => r.json())
            .then(data => {
                if (data.success) {
                    document.getElementById('suggestionText').textContent = data.suggestion;
                    document.getElementById('suggestionResult').style.display = 'block';
                }
            });
    }

    function copySuggestion() {
        navigator.clipboard.writeText(document.getElementById('suggestionText').textContent).then(() => alert('Copied!'));
    }

    function extractKeywords() {
        const text = document.getElementById('keywordText').value;
        if (!text.trim()) return;
        fetch(`ai/api.php?action=keywords&input=${encodeURIComponent(text)}`)
            .then(r => r.json())
            .then(data => {
                if (data.success) {
                    const list = document.getElementById('keywordsList');
                    list.innerHTML = data.keywords.map(kw => `<span style="background: #f1f5f9; padding: 5px 12px; border-radius: 20px; font-size: 0.9rem; border: 1px solid #e2e8f0;">${kw}</span>`).join('');
                    document.getElementById('keywordResult').style.display = 'block';
                }
            });
    }

    function analyzeSentiment() {
        const text = document.getElementById('sentimentText').value;
        if (!text.trim()) return;
        fetch(`ai/api.php?action=sentiment&input=${encodeURIComponent(text)}`)
            .then(r => r.json())
            .then(data => {
                if (data.success) {
                    const s = data.sentiment;
                    const label = document.getElementById('sentimentLabel');
                    const bar = document.getElementById('sentimentBar');
                    label.textContent = s.sentiment.toUpperCase() + ` (${s.confidence}%)`;
                    let color = '#3b82f6';
                    if (s.sentiment === 'positive') color = '#22c55e';
                    if (s.sentiment === 'negative') color = '#ef4444';
                    label.style.color = color;
                    bar.style.background = color;
                    bar.style.width = s.confidence + '%';
                    document.getElementById('sentimentResult').style.display = 'block';
                }
            });
    }
</script>

<?php include 'includes/footer.php'; ?>