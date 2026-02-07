<?php
require_once 'db.php';
include 'includes/header.php';
?>

<div class="guide-container">
    <div class="guide-header">
        <div class="guide-badge">Project Helper</div>
        <h1>How can I help you today?</h1>
        <p>Your simple, offline guide to mastering the Parish Management System.</p>
    </div>

    <div class="guide-layout">
        <!-- Sidebar: Navigation -->
        <div class="guide-sidebar">
            <div class="card sidebar-card">
                <h3>Quick Topics</h3>
                <ul class="topic-list">
                    <li onclick="showTopic('getting_started')">🚀 Getting Started</li>
                    <li onclick="showTopic('families')">🏡 Managing Families</li>
                    <li onclick="showTopic('parishioners')">👥 Parishioners & Members</li>
                    <li onclick="showTopic('sacraments')">🕊️ Recording Sacraments</li>
                    <li onclick="showTopic('subscriptions')">💰 Subscriptions & Dues</li>
                    <li onclick="showTopic('reports')">🖨️ Generating Reports</li>
                    <li onclick="showTopic('backup')">💾 Database & Backup</li>
                </ul>
            </div>
        </div>

        <!-- Main Content: AI Chat / Instructions -->
        <div class="guide-main">
            <div class="card chat-card" id="chat-card">
                <div class="chat-header">
                    <div style="display: flex; align-items: center; gap: 0.75rem;">
                        <span class="bot-icon">🤖</span>
                        <div>
                            <strong>AI Guide</strong>
                            <span class="status-online">Online (Offline Mode)</span>
                        </div>
                    </div>
                </div>

                <div class="chat-messages" id="chat-messages">
                    <div class="message bot-message">
                        Hello! I'm your project assistant. I can help you understand how to use this system.
                        Try clicking a topic on the left or type a question below!
                    </div>
                </div>

                <div class="chat-input-area">
                    <div class="input-wrapper">
                        <input type="text" id="user-input" placeholder="Type a question (e.g., 'How to add family?')"
                            onkeypress="if(event.key === 'Enter') sendMessage()">
                        <button onclick="sendMessage()" class="send-btn">Send</button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
    :root {
        --guide-bg: #f8fafc;
        --guide-primary: #6366f1;
        --guide-secondary: #f1f5f9;
        --guide-text: #1e293b;
        --guide-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.1), 0 8px 10px -6px rgba(0, 0, 0, 0.1);
    }

    .guide-container {
        max-width: 1200px;
        margin: 2rem auto;
        padding: 0 1rem;
        animation: fadeIn 0.5s ease-out;
    }

    @keyframes fadeIn {
        from {
            opacity: 0;
            transform: translateY(10px);
        }

        to {
            opacity: 1;
            transform: translateY(0);
        }
    }

    .guide-header {
        text-align: center;
        margin-bottom: 3rem;
    }

    .guide-badge {
        display: inline-block;
        padding: 0.4rem 1rem;
        background: #e0e7ff;
        color: var(--guide-primary);
        border-radius: 99px;
        font-size: 0.8rem;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.05em;
        margin-bottom: 1rem;
    }

    .guide-header h1 {
        font-size: 2.5rem;
        font-weight: 800;
        color: var(--guide-text);
        margin: 0;
        letter-spacing: -0.025em;
    }

    .guide-header p {
        color: #64748b;
        font-size: 1.1rem;
        margin-top: 0.5rem;
    }

    .guide-layout {
        display: grid;
        grid-template-columns: 300px 1fr;
        gap: 2rem;
    }

    .sidebar-card {
        padding: 1.5rem;
        border-radius: 20px;
        background: white;
        box-shadow: var(--guide-shadow);
    }

    .topic-list {
        list-style: none;
        padding: 0;
        margin: 0;
    }

    .topic-list li {
        padding: 0.85rem 1.25rem;
        margin-bottom: 0.5rem;
        border-radius: 12px;
        cursor: pointer;
        transition: all 0.2s;
        color: var(--guide-text);
        font-weight: 500;
    }

    .topic-list li:hover {
        background: #f1f5f9;
        color: var(--guide-primary);
        transform: translateX(5px);
    }

    .chat-card {
        display: flex;
        flex-direction: column;
        height: 600px;
        padding: 0;
        overflow: hidden;
        border-radius: 24px;
        background: white;
        box-shadow: var(--guide-shadow);
        border: none;
    }

    .chat-header {
        padding: 1.5rem 2rem;
        background: white;
        border-bottom: 1px solid #f1f5f9;
    }

    .bot-icon {
        width: 44px;
        height: 44px;
        background: linear-gradient(135deg, #6366f1, #4f46e5);
        border-radius: 14px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.5rem;
    }

    .status-online {
        display: block;
        font-size: 0.75rem;
        color: #10b981;
        font-weight: 600;
    }

    .chat-messages {
        flex: 1;
        padding: 2rem;
        overflow-y: auto;
        background: #f8fafc;
        display: flex;
        flex-direction: column;
        gap: 1.5rem;
    }

    .message {
        max-width: 80%;
        padding: 1.25rem 1.5rem;
        border-radius: 20px;
        font-size: 1rem;
        line-height: 1.5;
        position: relative;
    }

    .bot-message {
        background: white;
        color: var(--guide-text);
        align-self: flex-start;
        border-bottom-left-radius: 4px;
        box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05);
    }

    .user-message {
        background: var(--guide-primary);
        color: white;
        align-self: flex-end;
        border-bottom-right-radius: 4px;
    }

    .chat-input-area {
        padding: 1.5rem 2rem;
        background: white;
        border-top: 1px solid #f1f5f9;
    }

    .input-wrapper {
        display: flex;
        gap: 1rem;
        background: #f1f5f9;
        padding: 0.5rem;
        border-radius: 16px;
    }

    .input-wrapper input {
        flex: 1;
        background: transparent;
        border: none;
        padding: 0.75rem 1rem;
        font-size: 1rem;
        outline: none;
    }

    .send-btn {
        background: var(--guide-primary);
        color: white;
        border: none;
        padding: 0 1.5rem;
        border-radius: 12px;
        font-weight: 600;
        cursor: pointer;
        transition: background 0.2s;
    }

    .send-btn:hover {
        background: #4f46e5;
    }

    @media (max-width: 900px) {
        .guide-layout {
            grid-template-columns: 1fr;
        }
    }
</style>

<script>
    const KnowledgeBase = {
        getting_started: {
            title: "🚀 Getting Started",
            content: "Welcome to the Parish Management System! This tool is designed to help you manage your parish records efficiently. You can navigate through Families, Parishioners, Sacraments, and Subscriptions using the top menu. Start by completing your <strong>Parish Profile</strong> to customize the church name and print headers."
        },
        families: {
            title: "🏡 Managing Families",
            content: "To add a new family, go to <strong>Families</strong> > <strong>Add New Family</strong>. Each family has a unique ID and can be grouped by <strong>Anbiyam</strong>. You can also upload a family photo and record their primary address and phone number."
        },
        parishioners: {
            title: "👥 Parishioners & Members",
            content: "Parishioners are individual members of a family. You can add them from the <strong>Family View</strong> page. Each member can have their own education, occupation, and pious association details recorded."
        },
        sacraments: {
            title: "🕊️ Recording Sacraments",
            content: "You can track Baptism, First Communion, Confirmation, and Marriage dates for each parishioner. To record an event, find the member in the <strong>Parishioners</strong> list or via their <strong>Family View</strong> and click 'Edit' or go to the 'Sacraments' tab."
        },
        subscriptions: {
            title: "💰 Subscriptions & Dues",
            content: "The system helps track annual or monthly subscriptions. You can record payments in the <strong>Subscriptions</strong> section. The system automatically calculates outstanding dues based on the family's start date and amount."
        },
        reports: {
            title: "🖨️ Generating Reports",
            content: "The <strong>Reports Hub</strong> allows you to generate various lists (Gender-wise, Age-wise, Anbiyam-wise). You can also print Certificates (Baptism, Confirmation, etc.) from the <strong>Certificates Hub</strong>."
        },
        backup: {
            title: "💾 Database & Backup",
            content: "Protect your data! Go to <strong>DB Mgmt</strong> to create manual backups. The system also performs automatic backups. You can restore data from a previous backup file if needed."
        }
    };

    const Responses = [
        { keywords: ['add', 'new', 'family'], response: "To add a family, navigate to the 'Families' page and click on the 'Add New Family' button." },
        { keywords: ['member', 'parishioner', 'add'], response: "To add a member, first find or create their family, then use the 'Add Member' button on the family's profile page." },
        { keywords: ['report', 'print', 'pdf'], response: "You can find all reporting options in the 'Reports' section. For certificates, check the 'Certificates Hub'." },
        { keywords: ['anbium', 'anbiyam'], response: "We recently renamed 'Anbium' to 'Anbiyam'. You can filter your family lists and reports by Anbiyam name." },
        { keywords: ['sacrament', 'baptism', 'marriage'], response: "Sacramental records can be updated by editing a parishioner's profile or via the 'Sacraments' page." },
        { keywords: ['backup', 'database', 'save'], response: "Go to the 'DB Mgmt' page (Database Management) to create backups or restore previous data." },
        { keywords: ['money', 'subscription', 'due', 'pay'], response: "Payments are managed in the 'Subscriptions' section. You can view total dues and record new payments there." },
        { keywords: ['hi', 'hello', 'hey'], response: "Hello! I'm here to help. You can ask me how to use the different sections of this parish system." }
    ];

    function sendMessage() {
        const input = document.getElementById('user-input');
        const text = input.value.trim().toLowerCase();
        if (!text) return;

        addMessage(input.value, 'user');
        input.value = '';

        setTimeout(() => {
            let bestResponse = "I'm not exactly sure how to answer that. Try asking about families, parishioners, reports, or backups!";

            for (const item of Responses) {
                if (item.keywords.some(kw => text.includes(kw))) {
                    bestResponse = item.response;
                    break;
                }
            }

            addMessage(bestResponse, 'bot');
        }, 500);
    }

    function addMessage(text, sender) {
        const container = document.getElementById('chat-messages');
        const msgDiv = document.createElement('div');
        msgDiv.className = `message ${sender}-message`;
        msgDiv.innerHTML = text;
        container.appendChild(msgDiv);
        container.scrollTop = container.scrollHeight;
    }

    function showTopic(key) {
        const topic = KnowledgeBase[key];
        if (topic) {
            addMessage(`<strong>${topic.title}</strong><br><br>${topic.content}`, 'bot');
        }
    }
</script>

<?php include 'includes/footer.php'; ?>