{{-- resources/views/partials/advise-widget.blade.php --}}
{{-- @include('partials.advise-widget') vào cuối layout trước </body> --}}

<link href="https://fonts.googleapis.com/css2?family=Be+Vietnam+Pro:wght@400;500;600;700&display=swap" rel="stylesheet">

<style>
    #chat-fab {
        position: fixed;
        bottom: 28px;
        right: 28px;
        width: 60px;
        height: 60px;
        background: linear-gradient(135deg, #1a56db, #4f8ef7);
        border: none;
        border-radius: 50%;
        cursor: pointer;
        box-shadow: 0 6px 24px rgba(26, 86, 219, .45);
        display: flex;
        align-items: center;
        justify-content: center;
        z-index: 9998;
        transition: transform .2s, box-shadow .2s;
    }

    #chat-fab:hover {
        transform: scale(1.08);
        box-shadow: 0 8px 28px rgba(26, 86, 219, .55);
    }

    #chat-fab svg {
        width: 28px;
        height: 28px;
        fill: #fff;
    }

    #chat-fab .icon-close {
        display: none;
    }

    #chat-badge {
        position: absolute;
        top: -4px;
        right: -4px;
        background: #ef4444;
        color: #fff;
        font-size: 11px;
        font-weight: 700;
        width: 20px;
        height: 20px;
        border-radius: 50%;
        align-items: center;
        justify-content: center;
        border: 2px solid #fff;
        display: none;
    }

    #chat-panel {
        position: fixed;
        bottom: 100px;
        right: 28px;
        width: 380px;
        height: 560px;
        background: #fff;
        border-radius: 20px;
        box-shadow: 0 12px 48px rgba(26, 86, 219, .18), 0 2px 8px rgba(0, 0, 0, .08);
        border: 1px solid #dde3f5;
        display: flex;
        flex-direction: column;
        overflow: hidden;
        z-index: 9999;
        font-family: 'Be Vietnam Pro', sans-serif;
        opacity: 0;
        transform: translateY(20px) scale(.96);
        pointer-events: none;
        transition: opacity .25s ease, transform .25s ease;
    }

    #chat-panel.open {
        opacity: 1;
        transform: translateY(0) scale(1);
        pointer-events: all;
    }

    .cp-header {
        background: linear-gradient(135deg, #1a56db 0%, #4f8ef7 100%);
        padding: 16px 18px;
        display: flex;
        align-items: center;
        gap: 12px;
        flex-shrink: 0;
    }

    .cp-avatar {
        width: 40px;
        height: 40px;
        background: rgba(255, 255, 255, .2);
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 20px;
        flex-shrink: 0;
    }

    .cp-header-info h3 {
        color: #fff;
        font-size: 15px;
        font-weight: 700;
        margin: 0;
    }

    .cp-header-info p {
        color: rgba(255, 255, 255, .8);
        font-size: 12px;
        margin: 2px 0 0;
    }

    .cp-status-dot {
        width: 8px;
        height: 8px;
        background: #4ade80;
        border-radius: 50%;
        display: inline-block;
        margin-right: 5px;
        box-shadow: 0 0 0 2px rgba(74, 222, 128, .3);
    }

    .cp-close {
        margin-left: auto;
        background: rgba(255, 255, 255, .15);
        border: none;
        color: #fff;
        width: 30px;
        height: 30px;
        border-radius: 50%;
        cursor: pointer;
        font-size: 16px;
        display: flex;
        align-items: center;
        justify-content: center;
        flex-shrink: 0;
        transition: background .15s;
    }

    .cp-close:hover {
        background: rgba(255, 255, 255, .3);
    }

    .cp-messages {
        flex: 1;
        overflow-y: auto;
        padding: 16px 14px;
        display: flex;
        flex-direction: column;
        gap: 12px;
        /* scroll-behavior: smooth; */
    }

    .cp-messages::-webkit-scrollbar {
        width: 4px;
    }

    .cp-messages::-webkit-scrollbar-thumb {
        background: #dde3f5;
        border-radius: 99px;
    }

    .cp-msg {
        display: flex;
        align-items: flex-end;
        gap: 8px;
        max-width: 88%;
        animation: cpFade .22s ease;
    }

    @keyframes cpFade {
        from {
            opacity: 0;
            transform: translateY(8px);
        }

        to {
            opacity: 1;
            transform: translateY(0);
        }
    }

    .cp-msg.user {
        margin-left: auto;
        flex-direction: row-reverse;
    }

    .cp-msg.bot {
        margin-right: auto;
    }

    .cp-msg-avatar {
        width: 28px;
        height: 28px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 13px;
        flex-shrink: 0;
    }

    .cp-msg.bot .cp-msg-avatar {
        background: #e8f0fe;
    }

    .cp-msg.user .cp-msg-avatar {
        background: #c7d9fb;
    }

    .cp-bubble {
        padding: 10px 14px;
        border-radius: 14px;
        font-size: 13.5px;
        line-height: 1.6;
        word-break: break-word;
    }

    .cp-msg.bot .cp-bubble {
        background: #fff;
        color: #1a1f36;
        border: 1px solid #dde3f5;
        border-bottom-left-radius: 4px;
        box-shadow: 0 2px 6px rgba(0, 0, 0, .04);
    }

    .cp-msg.user .cp-bubble {
        background: #1a56db;
        color: #fff;
        border-bottom-right-radius: 4px;
    }


        /* Markdown elements */
    .cp-bubble strong {
        font-weight: 700;
    }

    .cp-bubble em {
        font-style: italic;
    }

    .cp-bubble code {
        background: #f0f4ff;
        padding: 1px 5px;
        border-radius: 4px;
        font-size: 12.5px;
        font-family: 'Courier New', monospace;
    }

    .cp-bubble ol {
        margin: 6px 0 6px 18px;
        padding: 0;
        list-style-type: decimal;
    }

    .cp-bubble ul {
        margin: 6px 0 6px 18px;
        padding: 0;
        list-style-type: disc;
    }

    .cp-bubble li {
        margin-bottom: 3px;
    }

    /* ── Typing dots ── */
    .cp-typing-dots {
        display: flex;
        gap: 5px;
        align-items: center;
        height: 20px;
        padding: 2px 4px;
    }

    .cp-typing-dots span {
        width: 7px;
        height: 7px;
        background: #94a3b8;
        border-radius: 50%;
        animation: cpBounce 1.4s infinite ease-in-out both;
    }

    .cp-typing-dots span:nth-child(1) {
        animation-delay: 0s;
    }

    .cp-typing-dots span:nth-child(2) {
        animation-delay: .18s;
    }

    .cp-typing-dots span:nth-child(3) {
        animation-delay: .36s;
    }

    @keyframes cpBounce {

        0%,
        60%,
        100% {
            transform: translateY(0);
            opacity: .5;
        }

        30% {
            transform: translateY(-6px);
            opacity: 1;
        }
    }

    /* Con trỏ nhấp nháy khi stream */
    .cp-cursor {
        display: inline-block;
        width: 2px;
        height: 14px;
        background: #1a56db;
        margin-left: 2px;
        vertical-align: middle;
        animation: cpBlink .6s step-end infinite;
    }

    @keyframes cpBlink {

        0%,
        100% {
            opacity: 1;
        }

        50% {
            opacity: 0;
        }
    }

    .cp-suggestions {
        padding: 0 14px 10px;
        display: flex;
        flex-wrap: wrap;
        gap: 6px;
    }

    .cp-sug-btn {
        background: #eff4ff;
        color: #1a56db;
        border: 1px solid #c7d9fb;
        border-radius: 99px;
        padding: 5px 12px;
        font-size: 12px;
        font-family: inherit;
        cursor: pointer;
        transition: all .15s;
        white-space: nowrap;
    }

    .cp-sug-btn:hover {
        background: #1a56db;
        color: #fff;
        border-color: #1a56db;
    }

    .cp-input-area {
        padding: 12px 14px;
        border-top: 1px solid #dde3f5;
        display: flex;
        gap: 8px;
        align-items: flex-end;
        flex-shrink: 0;
    }

    #cpInput {
        flex: 1;
        border: 1.5px solid #dde3f5;
        border-radius: 12px;
        padding: 9px 13px;
        font-size: 13.5px;
        font-family: inherit;
        resize: none;
        outline: none;
        line-height: 1.5;
        max-height: 100px;
        transition: border-color .18s;
        color: #1a1f36;
    }

    #cpInput:focus {
        border-color: #1a56db;
    }

    #cpInput::placeholder {
        color: #b0b8cc;
    }

    #cpSendBtn {
        width: 40px;
        height: 40px;
        background: #1a56db;
        border: none;
        border-radius: 10px;
        cursor: pointer;
        display: flex;
        align-items: center;
        justify-content: center;
        flex-shrink: 0;
        transition: background .15s, transform .12s;
    }

    #cpSendBtn:hover:not(:disabled) {
        background: #1344b0;
        transform: scale(1.05);
    }

    #cpSendBtn:disabled {
        background: #b0bcd8;
        cursor: not-allowed;
    }

    #cpSendBtn svg {
        width: 18px;
        height: 18px;
        fill: #fff;
    }

    @media (max-width: 480px) {
        #chat-panel {
            right: 10px;
            left: 10px;
            width: auto;
            height: 70vh;
            bottom: 90px;
        }

        #chat-fab {
            bottom: 18px;
            right: 18px;
        }
    }


        .cp-bubble.is-streaming {
        white-space: pre-wrap;
    }




    .cp-ticket-lookup {
    padding: 0 14px 10px;
    display: flex;
    flex-direction: column;
    gap: 8px;
}

.cp-ticket-toggle {
    width: 100%;
    background: #f8fafc;
    color: #1a56db;
    border: 1px dashed #b7c7ea;
    border-radius: 12px;
    padding: 8px 10px;
    font-size: 12.5px;
    font-weight: 600;
    font-family: inherit;
    cursor: pointer;
}

.cp-ticket-toggle:hover {
    background: #eff4ff;
    border-color: #1a56db;
}

.cp-ticket-box {
    display: none;
    gap: 6px;
    align-items: center;
}

.cp-ticket-box.open {
    display: flex;
}

#cpTicketCodeInput {
    flex: 1;
    border: 1.5px solid #dde3f5;
    border-radius: 10px;
    padding: 8px 10px;
    font-size: 12.5px;
    font-family: inherit;
    outline: none;
}

#cpTicketCodeInput:focus {
    border-color: #1a56db;
}

.cp-ticket-search-btn {
    background: #1a56db;
    color: #fff;
    border: none;
    border-radius: 10px;
    padding: 8px 10px;
    font-size: 12.5px;
    font-weight: 600;
    font-family: inherit;
    cursor: pointer;
    white-space: nowrap;
}
</style>

{{-- Floating button --}}
<button id="chat-fab" onclick="toggleChat()" title="Tư vấn tuyển sinh">
    <span id="chat-badge"></span>
    <svg class="icon-open" viewBox="0 0 24 24">
        <path d="M20 2H4c-1.1 0-2 .9-2 2v18l4-4h14c1.1 0 2-.9 2-2V4c0-1.1-.9-2-2-2z" />
    </svg>
    <svg class="icon-close" viewBox="0 0 24 24">
        <path
            d="M19 6.41L17.59 5 12 10.59 6.41 5 5 6.41 10.59 12 5 17.59 6.41 19 12 13.41 17.59 19 19 17.59 13.41 12z" />
    </svg>
</button>

{{-- Chat panel --}}
<div id="chat-panel">
    <div class="cp-header">
        <div class="cp-avatar">🎓</div>
        <div class="cp-header-info">
            <h3>Tư Vấn Tuyển Sinh</h3>
            <p><span class="cp-status-dot"></span>Trợ lý AI · Luôn sẵn sàng</p>
        </div>
        <button class="cp-close" onclick="toggleChat()">✕</button>
    </div>

    <div class="cp-messages" id="cpMessages">
        <div class="cp-msg bot">
            <div class="cp-msg-avatar">🎓</div>
            <div class="cp-bubble">Xin chào! 👋 Tôi có thể giúp bạn tìm hiểu thông tin tuyển sinh 2025. Bạn muốn hỏi về
                điều gì?</div>
        </div>
    </div>

    <div class="cp-suggestions" id="cpSuggestions">
        <button class="cp-sug-btn" onclick="cpSendSuggestion(this)">📋 Ngành đào tạo</button>
        <button class="cp-sug-btn" onclick="cpSendSuggestion(this)">📊 Điểm chuẩn</button>
        <button class="cp-sug-btn" onclick="cpSendSuggestion(this)">💰 Học phí</button>
    </div>
    <div class="cp-ticket-lookup">
    <button type="button" class="cp-ticket-toggle" onclick="cpToggleTicketLookup()">
        🔎 Tra cứu phản hồi bằng mã tư vấn
    </button>

    <div class="cp-ticket-box" id="cpTicketLookupBox">
        <input
            type="text"
            id="cpTicketCodeInput"
            placeholder="Nhập mã, ví dụ: TV20260525-ABC123"
            autocomplete="off"
        >
        <button type="button" class="cp-ticket-search-btn" onclick="cpLookupTicket()">
            Tra cứu
        </button>
    </div>
</div>

    <div class="cp-input-area">
        <textarea id="cpInput" rows="1" placeholder="Nhập câu hỏi..."></textarea>
<button id="cpSendBtn" onclick="cpSend()">
            <svg viewBox="0 0 24 24">
                <path d="M2.01 21L23 12 2.01 3 2 10l15 2-15 2z" />
            </svg>
        </button>

    </div>
</div>

<script>
    window.ADVISE_CONFIG = {
        conversationUrl: @json(url('/advise/conversation')),
        messageUrl: @json(url('/advise/message')),
        streamUrl: @json(route('advise.stream')),
        resetUrl: @json(route('advise.reset')),
        saveUrl: @json(route('advise.save')),
        savePairUrl: @json(route('advise.savePair')),
        ticketCheckUrl: @json(route('advise.tickets.check')),
        ticketLookupUrl: @json(route('advise.tickets.lookup')),
        csrfToken: @json(csrf_token())
    };
</script>

<script src="{{ asset('js/advise-widget.js') }}?v={{ time() }}"></script>

