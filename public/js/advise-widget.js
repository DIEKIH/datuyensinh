(function () {
    'use strict';
    var cfg = window.ADVISE_CONFIG || {};
    var CSRF_TOKEN = cfg.csrfToken || '';
    var RESET_URL = cfg.resetUrl || '';
    var SAVE_URL = cfg.saveUrl || '';
    var SAVE_PAIR_URL = cfg.savePairUrl || '';

    var TICKET_CHECK_URL = cfg.ticketCheckUrl || '';
    var TICKET_LOOKUP_URL = cfg.ticketLookupUrl || '';
    var ticketPollingTimer = null;
    // ── State ─────────────────────────────────────────────────────────────────
    var cpOpen = false;
    var threadId = sessionStorage.getItem('advise_thread_id') || null;
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

    function ensureThread() {
        if (threadId) return Promise.resolve(threadId);

        return fetch('/advise/thread', {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': CSRF_TOKEN,
                'Content-Type': 'application/json',
                'Accept': 'application/json'
            },
        })
            .then(function (r) {
                return parseJsonResponse(r).then(function (data) {
                    if (!r.ok || !data || !data.id) {
                        throw new Error((data && data.message) || getResponseErrorMessage(r, ''));
                    }

                    threadId = data.id;
                    sessionStorage.setItem('advise_thread_id', data.id);

                    return data.id;
                });
            });
    }

    // ── Queue save helper ─────────────────────────────────────────────────────
    function queueSave(role, content, replyToId) {
        fetch(SAVE_URL, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': CSRF_TOKEN,
            },
            body: JSON.stringify({
                thread_id: threadId,
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
        var buffer = '', fullText = '', firstChunk = true;
        var completedText = '';
        var runId = null;

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
                    thread_id: threadId,
                    user_message_id: userMessageId || null,
                    user_content: userText,
                    assistant_content: finalText,
                    input_type: 'text',
                }),
            })
                .then(function (r) {
                    return parseJsonResponse(r).then(function (data) {
                        if (!r.ok || !data || data.success === false) {
                            throw new Error((data && data.message) || getResponseErrorMessage(r, ''));
                        }

                        return data;
                    });
                })
                .catch(function (e) {
                    console.warn('[Advise] save pair failed', e);
                    return null;
                });
        }

        function fetchFallbackMessage() {
            return Promise.resolve('');
        }

        function handleStreamDone() {
            var finalText = fullText || completedText;

            if (finalText) {
                finalizeBubble(bubble, finalText);

                saveMessages(finalText).then(function (saved) {
                    if (saved && saved.assistant_content) {
                        finalizeBubble(bubble, saved.assistant_content);
                    }

                    if (saved && saved.is_handoff && saved.ticket) {
                        sessionStorage.setItem('advise_last_ticket_code', saved.ticket.ticket_code);
                        startTicketPolling();
                    }

                    onDone();
                });

            } else {
                fetchFallbackMessage().then(function (fetched) {
                    if (fetched) {
                        finalizeBubble(bubble, fetched);

                        saveMessages(fetched).then(function (saved) {
                            if (saved && saved.assistant_content) {
                                finalizeBubble(bubble, saved.assistant_content);
                            }

                            if (saved && saved.is_handoff && saved.ticket) {
                                sessionStorage.setItem('advise_last_ticket_code', saved.ticket.ticket_code);
                                startTicketPolling();
                            }

                            onDone();
                        });
                    } else {
                        finalizeBubble(bubble, '⚠️ Không nhận được phản hồi. Vui lòng thử lại.');
                        onDone();
                    }
                });
            }
        }

        function read() {
            return reader.read().then(function (chunk) {
                if (chunk.done) {
                    handleStreamDone();
                    return;
                }

                buffer += decoder.decode(chunk.value, { stream: true });
                var parts = buffer.split('\n\n');
                buffer = parts.pop();

                for (var p = 0; p < parts.length; p++) {
                    var dataLines = [], lines = parts[p].split('\n');
                    for (var l = 0; l < lines.length; l++) {
                        if (lines[l].startsWith('data: ')) {
                            dataLines.push(lines[l].slice(6).trim());
                        }
                    }

                    for (var d = 0; d < dataLines.length; d++) {
                        var dataLine = dataLines[d];
                        if (!dataLine || dataLine === '[DONE]') continue;

                        try {
                            var obj = JSON.parse(dataLine);

                            if (obj.object === 'thread.message.delta') {
                                var content = (obj.delta && obj.delta.content) || [];
                                for (var c = 0; c < content.length; c++) {
                                    if (content[c].type !== 'text') continue;
                                    var text = ((content[c].text && content[c].text.value) || '');
                                    if (!text) continue;
                                    if (firstChunk) { clearBubble(bubble); firstChunk = false; }
                                    fullText += text;
                                    appendStreamText(bubble, text);
                                }
                            }

                            if (obj.object === 'thread.message.completed') {
                                var cContent = (obj.content) || [];
                                var cText = '';
                                for (var ci = 0; ci < cContent.length; ci++) {
                                    if (cContent[ci].type === 'text') {
                                        cText += (cContent[ci].text && cContent[ci].text.value) || '';
                                    }
                                }
                                completedText = cText.replace(RE_CITATION, '').trim();
                                if (!fullText && completedText) {
                                    clearBubble(bubble);
                                    firstChunk = false;
                                    fullText = completedText;
                                    setStreamText(bubble, completedText);
                                }
                            }

                            if (obj.object === 'thread.run' && obj.id) {
                                runId = obj.id;
                            }

                            if (obj.object === 'thread.run' && obj.status === 'failed') {
                                var errMsg = (obj.last_error && obj.last_error.message) || 'Run failed';
                                finalizeBubble(bubble, '⚠️ ' + errMsg);
                                onDone(); reader.cancel(); return;
                            }

                            if (obj.object === 'thread.run' &&
                                (obj.status === 'cancelled' || obj.status === 'expired')) {
                                finalizeBubble(bubble, '⚠️ Phiên trả lời đã hết hạn. Vui lòng thử lại.');
                                onDone(); reader.cancel(); return;
                            }

                        } catch (_) { }
                    }
                }
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

        ensureThread()
            .then(function (tid) {
                // Bước 1: backend lưu user message vào DB trước,
                // sau đó backend mới gửi message sang OpenAI.
                return fetch('/advise/message', {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': CSRF_TOKEN,
                        'Content-Type': 'application/json',
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify({
                        thread_id: tid,
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
                                thread_id: tid,
                                user_message_id: lastUserMsgId
                            };
                        });
                    });
            })
            .then(function (payload) {
                // Bước 2: chỉ stream khi user message đã lưu DB
                // và đã gửi sang OpenAI thành công.
                return fetch('/tuyen-sinh/chat/stream', {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': CSRF_TOKEN,
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({
                        thread_id: payload.thread_id,
                        user_message_id: payload.user_message_id
                    }),
                })
                    .then(function (response) {
                        if (!response.ok) {
                            throw new Error('HTTP ' + response.status);
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
        var tid = threadId;
        threadId = null;
        sessionStorage.removeItem('advise_thread_id');
        sessionStorage.removeItem('advise_messages');
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
        if (!TICKET_CHECK_URL || !threadId) return;

        if (ticketPollingTimer) {
            clearInterval(ticketPollingTimer);
        }

        checkTicketAnswer();

        ticketPollingTimer = setInterval(function () {
            checkTicketAnswer();
        }, 15000);
    }

    function checkTicketAnswer() {
        if (!TICKET_CHECK_URL || !threadId) return;

        fetch(TICKET_CHECK_URL + '?thread_id=' + encodeURIComponent(threadId), {
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

                var msg = '📩 **Nhân viên tư vấn đã phản hồi**\n\n'
                    + ticket.answer
                    + '\n\n'
                    + '**Mã tra cứu:** ' + ticket.ticket_code;

                appendMsg('bot', msg);

                if (!cpOpen) {
                    badge.style.display = 'flex';
                    badge.textContent = '1';
                }

                if (ticketPollingTimer) {
                    clearInterval(ticketPollingTimer);
                    ticketPollingTimer = null;
                }
            })
            .catch(function (e) {
                console.warn('[Advise] check ticket failed', e);
            });
    }

    // ── Init: load lịch sử từ sessionStorage khi trang load ──────────────────
    loadMessagesFromStorage();


    if (threadId) {
        startTicketPolling();
    }

})();