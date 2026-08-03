(function () {
    'use strict';
    var cfg = window.ADVISE_CONFIG || {};
    var CSRF_TOKEN = cfg.csrfToken || '';
    var CONVERSATION_URL = cfg.conversationUrl || '/advise/conversation';
    var MESSAGE_URL = cfg.messageUrl || '/advise/message';
    var STREAM_URL = cfg.streamUrl || '/tuyen-sinh/chat/stream';
    var RESET_URL = cfg.resetUrl || '';
    var SAVE_URL = cfg.saveUrl || '';
    var SAVE_PAIR_URL = cfg.savePairUrl || '';

    var TICKET_CHECK_URL = cfg.ticketCheckUrl || '';
    var TICKET_LOOKUP_URL = cfg.ticketLookupUrl || '';
    var ticketPollingTimer = null;

    /*
     * Đổi phiên bản khi thay logic ngữ cảnh để không nạp lại HTML cũ
     * chứa các câu trả lời sai trước đây.
     */
    var STORAGE_VERSION = '20260803-context-v8-hybrid-semantic';
    var storedVersion = sessionStorage.getItem('advise_storage_version');

    if (storedVersion !== STORAGE_VERSION) {
        sessionStorage.removeItem('advise_messages');
        sessionStorage.removeItem('advise_conversation_id');
        sessionStorage.removeItem('advise_last_ticket_code');
        sessionStorage.removeItem('advise_delivered_ticket_codes');
        sessionStorage.setItem('advise_storage_version', STORAGE_VERSION);
    }

    function getDeliveredTicketCodes() {
        try {
            var value = JSON.parse(
                sessionStorage.getItem('advise_delivered_ticket_codes')
                || '[]'
            );

            return Array.isArray(value) ? value : [];
        } catch (_) {
            return [];
        }
    }

    function markTicketDelivered(code) {
        if (!code) return;

        var codes = getDeliveredTicketCodes();

        if (codes.indexOf(code) === -1) {
            codes.push(code);
        }

        if (codes.length > 20) {
            codes = codes.slice(codes.length - 20);
        }

        sessionStorage.setItem(
            'advise_delivered_ticket_codes',
            JSON.stringify(codes)
        );
    }
    // ── State ─────────────────────────────────────────────────────────────────
    var cpOpen = false;
    var conversationId = sessionStorage.getItem('advise_conversation_id') || null;
    var isBusy = false;
    var lastUserMsgId = null;

    // ── DOM refs ──────────────────────────────────────────────────────────────
    var panel = document.getElementById('chat-panel');
    var input = document.getElementById('cpInput');
    var sendBtn = document.getElementById('cpSendBtn');
    var msgs = document.getElementById('cpMessages');
    var badge = document.getElementById('chat-badge');
    var sugs = document.getElementById('cpSuggestions');
    var fabOpen = document.querySelector('#chat-fab .icon-open');
    var fabClose = document.querySelector('#chat-fab .icon-close');


    // ── SessionStorage helpers ────────────────────────────────────────────────
    function saveMessagesToStorage() {
        try {
            sessionStorage.setItem('advise_messages', msgs.innerHTML);
        } catch (e) {
            // sessionStorage đầy hoặc bị chặn — bỏ qua
        }
    }

    function loadMessagesFromStorage() {
        var saved = sessionStorage.getItem('advise_messages');
        if (saved) {
            msgs.innerHTML = saved;
            sugs.style.display = 'none';
        }
    }

    // ── Toggle panel ──────────────────────────────────────────────────────────
    window.toggleChat = function () {
        cpOpen = !cpOpen;
        panel.classList.toggle('open', cpOpen);
        fabOpen.style.display = cpOpen ? 'none' : '';
        fabClose.style.display = cpOpen ? '' : 'none';
        badge.style.display = 'none';
        if (cpOpen) input.focus();
    };

    // ── Suggestion chips ──────────────────────────────────────────────────────
    window.cpSendSuggestion = function (btn) {
        input.value = btn.textContent.trim().replace(/^[\p{Emoji}\s]+/u, '').trim();
        sugs.style.display = 'none';
        cpSend();
    };

    // ── Markdown → HTML ───────────────────────────────────────────────────────
    var RE_CITATION = /\u3010[^\u3011]*\u3011/g;

    function escHtml(s) {
        return s.replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;');
    }

    function getResponseErrorMessage(r, text) {
        if (r.status === 419) {
            return 'Phiên làm việc đã hết hạn. Vui lòng tải lại trang rồi thử lại.';
        }

        if (r.status === 401 || r.status === 403) {
            return 'Bạn không có quyền thực hiện thao tác này hoặc phiên truy cập đã hết hạn.';
        }

        if (r.status === 404) {
            return 'Không tìm thấy đường dẫn xử lý.';
        }

        if (r.status >= 500) {
            return 'Máy chủ đang gặp lỗi xử lý. Vui lòng thử lại sau.';
        }

        return 'Server trả về dữ liệu không hợp lệ. Vui lòng thử lại.';
    }

    function parseJsonResponse(r) {
        return r.text().then(function (text) {
            var data = null;

            try {
                data = text ? JSON.parse(text) : {};
            } catch (e) {
                console.error('[Advise] Response không phải JSON:', {
                    status: r.status,
                    contentType: r.headers.get('content-type'),
                    body: text ? text.slice(0, 500) : ''
                });

                throw new Error(getResponseErrorMessage(r, text));
            }

            return data;
        });
    }

    function mdToHtml(raw) {
        var s = String(raw).replace(RE_CITATION, '').replace(/\n{3,}/g, '\n\n');

        var linkPlaceholders = [];
        s = s.replace(/\[([^\]]+)\]\((https?:\/\/[^)\s]+)\)/g, function (_, label, url) {
            var idx = linkPlaceholders.length;
            linkPlaceholders.push({ label: label, url: url });
            return '\x00LINK' + idx + '\x00';
        });

        s = escHtml(s);

        s = s.replace(/\x00LINK(\d+)\x00/g, function (_, idx) {
            var p = linkPlaceholders[+idx];
            return '<a href="' + p.url + '" style="color:#1a56db;text-decoration:underline" target="_blank" rel="noopener">' + escHtml(p.label) + '</a>';
        });
        s = s.replace(/\*\*(.+?)\*\*/gs, '<strong>$1</strong>');
        s = s.replace(/\*([^*\n]+?)\*/g, '<em>$1</em>');
        s = s.replace(/`([^`\n]+?)`/g, '<code>$1</code>');

        var lines = s.split('\n'), out = [], i = 0;
        while (i < lines.length) {
            var trimmed = lines[i].trim();
            if (/^\d+\./.test(trimmed)) {
                var olItems = [];
                while (i < lines.length) {
                    var t = lines[i].trim();
                    if (!t) { i++; continue; }
                    if (!/^\d+\./.test(t)) break;
                    var header = t.replace(/^\d+\.\s*/, ''), subItems = [];
                    i++;
                    while (i < lines.length) {
                        var st = lines[i].trim();
                        if (!st) { i++; continue; }
                        if (!/^[-*]\s/.test(st)) break;
                        subItems.push('<li>' + st.replace(/^[-*]\s*/, '') + '</li>');
                        i++;
                    }
                    olItems.push('<li><strong>' + header + '</strong>'
                        + (subItems.length ? '<ul>' + subItems.join('') + '</ul>' : '') + '</li>');
                }
                out.push('<ol>' + olItems.join('') + '</ol>');
                continue;
            }
            if (/^[-*]\s/.test(trimmed)) {
                var ulItems = [];
                while (i < lines.length) {
                    var ut = lines[i].trim();
                    if (!ut) { i++; continue; }
                    if (!/^[-*]\s/.test(ut)) break;
                    ulItems.push('<li>' + ut.replace(/^[-*]\s*/, '') + '</li>');
                    i++;
                }
                out.push('<ul>' + ulItems.join('') + '</ul>');
                continue;
            }
            if (!trimmed) {
                if (out.length && out[out.length - 1] !== '<br>') out.push('<br>');
                i++; continue;
            }
            out.push(trimmed); i++;
        }

        var result = '';
        for (var j = 0; j < out.length; j++) {
            var cur = out[j], prev = out[j - 1] || '';
            var blockCur = /^<[ou]l[ >]/.test(cur);
            var blockPrv = /^<[ou]l[ >]/.test(prev) || /<\/[ou]l>$/.test(prev);
            if (j === 0 || cur === '<br>' || prev === '<br>' || blockCur || blockPrv)
                result += cur;
            else result += '<br>' + cur;
        }
        return result.replace(/(<br>){2,}/g, '<br>');
    }

    function ensureConversation() {
        if (conversationId) return Promise.resolve(conversationId);

        return fetch(CONVERSATION_URL, {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': CSRF_TOKEN,
                'Content-Type': 'application/json',
                'Accept': 'application/json'
            },
        })
            .then(function (r) {
                return parseJsonResponse(r).then(function (data) {
                    var id = data && (data.conversation_id || data.id);

                    if (!r.ok || !data || !id) {
                        throw new Error(
                            (data && data.message)
                            || getResponseErrorMessage(r, '')
                        );
                    }

                    conversationId = id;
                    sessionStorage.setItem(
                        'advise_conversation_id',
                        conversationId
                    );

                    return conversationId;
                });
            });
    }

    // ── Queue save helper ─────────────────────────────────────────────────────
    function queueSave(role, content, replyToId) {
        /*
         * Luồng chính đã dùng addMessage + savePair đồng bộ.
         * Hàm này chỉ giữ tương thích và không được gọi song song,
         * tránh lưu trùng làm sai thứ tự ngữ cảnh.
         */
        if (!SAVE_URL || SAVE_PAIR_URL) {
            return Promise.resolve(null);
        }

        return fetch(SAVE_URL, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': CSRF_TOKEN,
            },
            body: JSON.stringify({
                conversation_id: conversationId,
                role: role,
                content: content,
                input_type: 'text',
                reply_to_id: replyToId || null,
            }),
        }).catch(function (e) { console.warn('[Advise] queueSave failed', e); });
    }

    // ── Stream processor ──────────────────────────────────────────────────────
    function processStream(response, bubble, userText, userMessageId) {
        var reader = response.body.getReader();
        var decoder = new TextDecoder();
        var buffer = '';
        var fullText = '';
        var completedText = '';
        var firstChunk = true;
        var streamStopped = false;
        var answerSource = 'ai';
        var answerLibraryId = null;

        function saveMessages(finalText) {
            if (!SAVE_PAIR_URL) return Promise.resolve(null);

            return fetch(SAVE_PAIR_URL, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': CSRF_TOKEN,
                },
                body: JSON.stringify({
                    conversation_id: conversationId,
                    user_message_id: userMessageId || null,
                    user_content: userText,
                    assistant_content: finalText,
                    input_type: 'text',
                    answer_source: answerSource,
                    answer_library_id: answerLibraryId,
                }),
            })
                .then(function (r) {
                    return parseJsonResponse(r).then(function (data) {
                        if (!r.ok || !data || data.success === false) {
                            throw new Error(
                                (data && data.message)
                                || getResponseErrorMessage(r, '')
                            );
                        }

                        return data;
                    });
                })
                .catch(function (e) {
                    console.warn('[Advise] save pair failed', e);
                    return null;
                });
        }

        function extractCompletedText(responseObject) {
            var output = responseObject && responseObject.output;
            var text = '';

            if (!Array.isArray(output)) return text;

            for (var i = 0; i < output.length; i++) {
                var content = output[i] && output[i].content;

                if (!Array.isArray(content)) continue;

                for (var j = 0; j < content.length; j++) {
                    if (content[j] && content[j].type === 'output_text') {
                        text += content[j].text || '';
                    }

                    if (content[j] && content[j].type === 'refusal') {
                        text += content[j].refusal || '';
                    }
                }
            }

            return text;
        }

        function handleStreamDone() {
            if (streamStopped) return;

            var finalText = (fullText || completedText)
                .replace(RE_CITATION, '')
                .trim();

            if (!finalText) {
                finalizeBubble(
                    bubble,
                    '⚠️ Không nhận được phản hồi. Vui lòng thử lại.'
                );
                onDone();
                return;
            }

            finalizeBubble(bubble, finalText);

            saveMessages(finalText).then(function (saved) {
                if (saved && saved.assistant_content) {
                    finalizeBubble(bubble, saved.assistant_content);
                }

                if (saved && saved.is_handoff && saved.ticket) {
                    sessionStorage.setItem(
                        'advise_last_ticket_code',
                        saved.ticket.ticket_code
                    );
                    startTicketPolling();
                }

                onDone();
            });
        }

        function stopWithError(message) {
            if (streamStopped) return;

            streamStopped = true;
            finalizeBubble(bubble, '⚠️ ' + message);
            onDone();

            try {
                reader.cancel();
            } catch (_) { }
        }

        function appendDelta(delta) {
            if (!delta) return;

            if (firstChunk) {
                clearBubble(bubble);
                firstChunk = false;
            }

            fullText += delta;
            appendStreamText(bubble, delta);
        }

        function handleEvent(obj) {
            if (!obj || !obj.type) return;

            if (obj.type === 'response.answer_context') {
                answerSource = obj.answer_source || 'system';
                answerLibraryId = obj.answer_library_id
                    ? Number(obj.answer_library_id)
                    : null;
                return;
            }

            if (obj.type === 'response.output_text.delta') {
                appendDelta(obj.delta || '');
                return;
            }

            if (obj.type === 'response.refusal.delta') {
                appendDelta(obj.delta || '');
                return;
            }

            if (obj.type === 'response.output_text.done') {
                completedText = String(obj.text || '')
                    .replace(RE_CITATION, '')
                    .trim();

                if (!fullText && completedText) {
                    clearBubble(bubble);
                    firstChunk = false;
                    fullText = completedText;
                    setStreamText(bubble, completedText);
                }
                return;
            }

            if (obj.type === 'response.refusal.done') {
                completedText = String(obj.refusal || '').trim();

                if (!fullText && completedText) {
                    clearBubble(bubble);
                    firstChunk = false;
                    fullText = completedText;
                    setStreamText(bubble, completedText);
                }
                return;
            }

            if (obj.type === 'response.completed') {
                if (!fullText) {
                    completedText = extractCompletedText(obj.response)
                        .replace(RE_CITATION, '')
                        .trim();
                }
                return;
            }

            if (obj.type === 'response.failed') {
                var responseError = obj.response && obj.response.error;
                stopWithError(
                    (responseError && responseError.message)
                    || 'Không thể tạo phản hồi.'
                );
                return;
            }

            if (obj.type === 'response.incomplete') {
                var reason = obj.response
                    && obj.response.incomplete_details
                    && obj.response.incomplete_details.reason;

                stopWithError(
                    reason
                        ? 'Phản hồi chưa hoàn tất: ' + reason
                        : 'Phản hồi chưa hoàn tất.'
                );
                return;
            }

            if (obj.type === 'error') {
                stopWithError(
                    obj.message
                    || (obj.error && obj.error.message)
                    || 'Lỗi xử lý phản hồi.'
                );
            }
        }

        function processBufferedEvents(finalChunk) {
            var parts = buffer.split(/\r?\n\r?\n/);

            if (!finalChunk) {
                buffer = parts.pop();
            } else {
                buffer = '';
            }

            for (var p = 0; p < parts.length; p++) {
                var lines = parts[p].split(/\r?\n/);
                var dataLines = [];

                for (var l = 0; l < lines.length; l++) {
                    if (lines[l].indexOf('data:') === 0) {
                        dataLines.push(lines[l].slice(5).trim());
                    }
                }

                if (!dataLines.length) continue;

                var dataLine = dataLines.join('\n');

                if (!dataLine || dataLine === '[DONE]') continue;

                try {
                    handleEvent(JSON.parse(dataLine));
                } catch (e) {
                    console.warn('[Advise] SSE JSON không hợp lệ', e);
                }

                if (streamStopped) return;
            }
        }

        function read() {
            return reader.read().then(function (chunk) {
                if (chunk.done) {
                    buffer += decoder.decode();

                    if (buffer.trim() !== '') {
                        buffer += '\n\n';
                        processBufferedEvents(true);
                    }

                    handleStreamDone();
                    return;
                }

                buffer += decoder.decode(chunk.value, { stream: true });
                processBufferedEvents(false);

                if (streamStopped) return;

                return read();
            });
        }

        return read();
    }

    function extractTicketCode(text) {
        text = String(text || '').trim();

        var match = text.match(/\bTV\d{8}-[A-Z0-9]{6}\b/i);

        return match ? match[0].toUpperCase() : null;
    }

    function isTicketLookupMistake(text) {
        text = String(text || '').trim();

        var code = extractTicketCode(text);

        if (!code) return false;

        // Chỉ chặn khi người dùng nhập đúng mã tra cứu,
        // ví dụ: TV20260525-ABC123
        return text.toLowerCase() === code.toLowerCase();
    }

    function handleTicketLookupMistake(text) {
        var code = extractTicketCode(text);

        appendMsg('user', text);

        var msg = 'Mã bạn vừa nhập có vẻ là **mã tra cứu tư vấn**.\n\n'
            + 'Bạn vui lòng nhập mã này vào ô **“Tra cứu phản hồi bằng mã tư vấn”** bên dưới khung chat để xem phản hồi.\n\n'
            + 'Mã của bạn: **' + code + '**';

        appendMsg('bot', msg);

        var box = document.getElementById('cpTicketLookupBox');
        var inputCode = document.getElementById('cpTicketCodeInput');

        if (box) {
            box.classList.add('open');
        }

        if (inputCode) {
            inputCode.value = code;
            inputCode.focus();
        }

        saveMessagesToStorage();
    }
    // ── Main send ─────────────────────────────────────────────────────────────
    window.cpSend = function () {
        var text = input.value.trim();
        if (!text || isBusy) return;

        // Nếu người dùng nhập nhầm mã tra cứu vào ô chat,
        // không gửi vào AI, không tạo ticket mới.
        if (isTicketLookupMistake(text)) {
            input.value = '';
            input.style.height = 'auto';
            sugs.style.display = 'none';

            handleTicketLookupMistake(text);
            return;
        }

        isBusy = true;
        sendBtn.disabled = true;
        sugs.style.display = 'none';

        appendMsg('user', text);
        input.value = '';
        input.style.height = 'auto';

        var bubble = createBotBubble();

        ensureConversation()
            .then(function (cid) {
                // Bước 1: backend lưu user message vào CSDL trước.
                return fetch(MESSAGE_URL, {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': CSRF_TOKEN,
                        'Content-Type': 'application/json',
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify({
                        conversation_id: cid,
                        content: text
                    }),
                })
                    .then(function (r) {
                        return parseJsonResponse(r).then(function (data) {
                            if (!r.ok || !data || !data.success) {
                                throw new Error((data && data.message) || getResponseErrorMessage(r, ''));
                            }

                            lastUserMsgId = data.user_message_id || null;

                            return {
                                conversation_id: cid,
                                user_message_id: lastUserMsgId
                            };
                        });
                    });
            })
            .then(function (payload) {
                // Bước 2: chỉ gọi Responses API sau khi câu hỏi
                // đã được lưu CSDL thành công.
                return fetch(STREAM_URL, {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': CSRF_TOKEN,
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({
                        conversation_id: payload.conversation_id,
                        user_message_id: payload.user_message_id,
                        content: text
                    }),
                })
                    .then(function (response) {
                        if (!response.ok) {
                            return parseJsonResponse(response).then(function (data) {
                                throw new Error(
                                    (data && data.message)
                                    || getResponseErrorMessage(response, '')
                                );
                            });
                        }

                        return {
                            response: response,
                            user_message_id: payload.user_message_id
                        };
                    });
            })
            .then(function (payload) {
                return processStream(payload.response, bubble, text, payload.user_message_id);
            })
            .catch(function (err) {
                console.error('[Advise]', err);

                finalizeBubble(
                    bubble,
                    '⚠️ ' + (err.message || 'Lỗi kết nối. Vui lòng thử lại.')
                );

                onDone();
            });
    };

    // ── Reset ─────────────────────────────────────────────────────────────────
    window.cpReset = function () {
        conversationId = null;
        sessionStorage.removeItem('advise_conversation_id');
        sessionStorage.removeItem('advise_messages');
        sessionStorage.removeItem('advise_last_ticket_code');
        sessionStorage.removeItem('advise_delivered_ticket_codes');
        if (RESET_URL) {
            fetch(RESET_URL, {
                method: 'POST',
                headers: { 'X-CSRF-TOKEN': CSRF_TOKEN, 'Content-Type': 'application/json' },
            }).catch(function () { });
        }

        msgs.innerHTML = '';
        sugs.style.display = '';
        appendMsg('bot', 'Cuộc hội thoại đã được làm mới. Tôi có thể giúp gì cho bạn?');
    };

    // ── DOM helpers ───────────────────────────────────────────────────────────
    function createBotBubble() {
        var wrapper = document.createElement('div');
        wrapper.className = 'cp-msg bot';
        wrapper.innerHTML =
            '<div class="cp-msg-avatar">🎓</div>'
            + '<div class="cp-bubble">'
            + '<div class="cp-typing-dots"><span></span><span></span><span></span></div>'
            + '</div>';
        msgs.appendChild(wrapper);
        scrollToBottomSmart();
        return wrapper.querySelector('.cp-bubble');
    }

    function clearBubble(b) {
        b.innerHTML = '';
        b.classList.add('is-streaming');

        b._textNode = document.createTextNode('');

        b._cursor = document.createElement('span');
        b._cursor.className = 'cp-cursor';

        b.appendChild(b._textNode);
        b.appendChild(b._cursor);
    }

    function appendStreamText(b, delta) {
        if (!b._textNode) {
            clearBubble(b);
        }

        // Append đúng phần mới, không render lại toàn bộ câu trả lời
        b._textNode.appendData(delta);

        scrollToBottomSmart();
    }

    function setStreamText(b, text) {
        if (!b._textNode) {
            clearBubble(b);
        }

        b._textNode.nodeValue = text || '';

        scrollToBottomSmart();
    }

    function finalizeBubble(b, t) {
        b.classList.remove('is-streaming');

        b._textNode = null;
        b._cursor = null;

        // Chỉ parse markdown 1 lần khi kết thúc stream
        b.innerHTML = mdToHtml(t);

        scrollToBottomSmart();
        saveMessagesToStorage();
    }

    function appendMsg(role, text) {
        var div = document.createElement('div');
        div.className = 'cp-msg ' + role;
        var content = role === 'bot'
            ? mdToHtml(text)
            : escHtml(text).replace(/\n/g, '<br>');
        div.innerHTML =
            '<div class="cp-msg-avatar">' + (role === 'bot' ? '🎓' : '🙋') + '</div>'
            + '<div class="cp-bubble">' + content + '</div>';
        msgs.appendChild(div);
        scrollToBottomSmart();
        saveMessagesToStorage();
    }

    function onDone() {
        isBusy = false;
        sendBtn.disabled = false;
        input.focus();
        if (!cpOpen) { badge.style.display = 'flex'; badge.textContent = '1'; }
    }

    // ── Auto-resize textarea ──────────────────────────────────────────────────
    input.addEventListener('input', function () {
        input.style.height = 'auto';
        input.style.height = Math.min(input.scrollHeight, 100) + 'px';
    });

    input.addEventListener('keydown', function (e) {
        if (e.key === 'Enter' && !e.shiftKey) { e.preventDefault(); cpSend(); }
    });

    // ── Scroll ────────────────────────────────────────────────────────────────
    var scrollScheduled = false;

    function scrollToBottomSmart() {
        if (!msgs) return;

        var nearBottom = msgs.scrollHeight - msgs.scrollTop - msgs.clientHeight < 120;
        if (!nearBottom) return;

        if (scrollScheduled) return;

        scrollScheduled = true;

        requestAnimationFrame(function () {
            msgs.scrollTop = msgs.scrollHeight;
            scrollScheduled = false;
        });
    }


    window.cpToggleTicketLookup = function () {
        var box = document.getElementById('cpTicketLookupBox');
        if (!box) return;

        box.classList.toggle('open');

        var inputCode = document.getElementById('cpTicketCodeInput');
        if (box.classList.contains('open') && inputCode) {
            inputCode.focus();
        }
    };

    window.cpLookupTicket = function () {
        if (!TICKET_LOOKUP_URL) {
            appendMsg('bot', '⚠️ Chức năng tra cứu chưa được cấu hình.');
            return;
        }

        var inputCode = document.getElementById('cpTicketCodeInput');
        if (!inputCode) return;

        var code = inputCode.value.trim();

        if (!code) {
            appendMsg('bot', 'Bạn vui lòng nhập mã tư vấn để tra cứu.');
            return;
        }

        appendMsg('user', 'Tra cứu mã tư vấn: ' + code);

        fetch(TICKET_LOOKUP_URL + '?ticket_code=' + encodeURIComponent(code), {
            method: 'GET',
            headers: {
                'Accept': 'application/json',
                'X-CSRF-TOKEN': CSRF_TOKEN,
            },
        })
            .then(function (r) {
                return parseJsonResponse(r).then(function (data) {
                    return { ok: r.ok, data: data };
                });
            })
            .then(function (res) {
                if (!res.ok || !res.data || !res.data.success) {
                    appendMsg('bot', '⚠️ Không tìm thấy mã tư vấn này. Bạn vui lòng kiểm tra lại mã.');
                    return;
                }

                var ticket = res.data.ticket;

                var statusText = 'Đang chờ xử lý';
                if (ticket.status === 'answered') statusText = 'Đã trả lời';
                if (ticket.status === 'closed') statusText = 'Đã đóng';

                var msg = '📌 **Thông tin tra cứu**\n\n'
                    + '**Mã tư vấn:** ' + ticket.ticket_code + '\n\n'
                    + '**Câu hỏi:** ' + ticket.question + '\n\n'
                    + '**Trạng thái:** ' + statusText + '\n\n';

                if (ticket.status === 'answered' && ticket.answer) {
                    msg += '📩 **Phản hồi của nhân viên tư vấn:**\n\n'
                        + ticket.answer + '\n\n'
                        + '**Thời gian trả lời:** ' + (ticket.answered_at || 'Chưa cập nhật');
                } else {
                    msg += '⏳ Câu hỏi của bạn đang chờ nhân viên tư vấn phản hồi.';
                }

                appendMsg('bot', msg);
            })
            .catch(function (e) {
                console.warn('[Advise] lookup ticket failed', e);
                appendMsg('bot', '⚠️ Không thể tra cứu lúc này. Vui lòng thử lại sau.');
            });
    };

    function startTicketPolling() {
        if (!TICKET_CHECK_URL || !conversationId) return;

        if (ticketPollingTimer) {
            clearInterval(ticketPollingTimer);
        }

        checkTicketAnswer();

        ticketPollingTimer = setInterval(function () {
            checkTicketAnswer();
        }, 15000);
    }

    function acknowledgeTicket(ticketId) {
        if (!TICKET_CHECK_URL || !conversationId || !ticketId) {
            return Promise.resolve(false);
        }

        var url = TICKET_CHECK_URL
            + '?conversation_id=' + encodeURIComponent(conversationId)
            + '&ack_ticket_id=' + encodeURIComponent(ticketId);

        return fetch(url, {
            method: 'GET',
            headers: {
                'Accept': 'application/json',
                'X-CSRF-TOKEN': CSRF_TOKEN,
            },
        })
            .then(function (r) {
                return parseJsonResponse(r).then(function (data) {
                    return !!(
                        r.ok
                        && data
                        && data.success
                        && data.acknowledged
                    );
                });
            })
            .catch(function (e) {
                console.warn('[Advise] acknowledge ticket failed', e);
                return false;
            });
    }

    function checkTicketAnswer() {
        if (!TICKET_CHECK_URL || !conversationId) return;

        fetch(TICKET_CHECK_URL + '?conversation_id=' + encodeURIComponent(conversationId), {
            method: 'GET',
            headers: {
                'Accept': 'application/json',
                'X-CSRF-TOKEN': CSRF_TOKEN,
            },
        })
            .then(function (r) {
                return parseJsonResponse(r).then(function (data) {
                    if (!r.ok) {
                        throw new Error((data && data.message) || getResponseErrorMessage(r, ''));
                    }

                    return data;
                });
            })
            .then(function (data) {
                if (!data || !data.success || !data.has_answer || !data.ticket) return;

                var ticket = data.ticket;
                var deliveredCodes = getDeliveredTicketCodes();

                if (deliveredCodes.indexOf(ticket.ticket_code) !== -1) {
                    acknowledgeTicket(ticket.id).then(function (acknowledged) {
                        if (acknowledged && ticketPollingTimer) {
                            clearInterval(ticketPollingTimer);
                            ticketPollingTimer = null;
                        }
                    });
                    return;
                }

                var msg = '📩 **Nhân viên tư vấn đã phản hồi**\n\n'
                    + ticket.answer
                    + '\n\n'
                    + '**Mã tra cứu:** ' + ticket.ticket_code;

                appendMsg('bot', msg);
                markTicketDelivered(ticket.ticket_code);

                if (!cpOpen) {
                    badge.style.display = 'flex';
                    badge.textContent = '1';
                }

                acknowledgeTicket(ticket.id).then(function (acknowledged) {
                    if (acknowledged && ticketPollingTimer) {
                        clearInterval(ticketPollingTimer);
                        ticketPollingTimer = null;
                    }
                });
            })
            .catch(function (e) {
                console.warn('[Advise] check ticket failed', e);
            });
    }

    // ── Init: load lịch sử từ sessionStorage khi trang load ──────────────────
    loadMessagesFromStorage();


    if (conversationId) {
        startTicketPolling();
    }

})();