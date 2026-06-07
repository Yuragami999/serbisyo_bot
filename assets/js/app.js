'use strict';

const SerbisyoBot = (() => {
  let currentLang  = document.documentElement.dataset.lang || 'fil';
  let isTyping     = false;
  let isChatOpen   = false;
  let chatInitialized = false;
  let messageHistory = [];

  const fab        = document.getElementById('chatFab');
  const chatWin    = document.getElementById('chatWindow');
  const closeBtn   = document.getElementById('chatCloseBtn');
  const messages   = document.getElementById('chatMessages');
  const input      = document.getElementById('chatInput');
  const sendBtn    = document.getElementById('chatSendBtn');
  const searchInput = document.getElementById('serviceSearch');

  const i18n = {
    en:  { typing: 'SerbisyoBot is typing...', helpful: '👍 Helpful', notHelpful: '👎 Not Helpful', thanksPos: 'Thanks for your feedback!', thanksNeg: 'Sorry about that. We\'ll improve!' },
    fil: { typing: 'Nagsusulat ang SerbisyoBot...', helpful: '👍 Nakatulong', notHelpful: '👎 Hindi Nakatulong', thanksPos: 'Salamat sa iyong feedback!', thanksNeg: 'Paumanhin. Magpapabuti kami!' },
    mrw: { typing: 'Isusulat so SerbisyoBot...', helpful: '👍 Nakatulong', notHelpful: '👎 Di Nakatulong', thanksPos: 'Salamat sa feedback!', thanksNeg: 'Pasensya. Magiging mas mabuti kami!' },
  };

  function T(key) {
    return (i18n[currentLang] || i18n.fil)[key] || key;
  }

  function openChat() {
    isChatOpen = true;
    chatWin.classList.remove('hidden');
    fab.style.transform = 'scale(0.85)';
    fab.style.opacity   = '0.7';
    if (!chatInitialized) {
      chatInitialized = true;
      showWelcomeMessage();
    }
    setTimeout(() => input.focus(), 300);
    // Remove notification badge
    const notif = fab.querySelector('.fab-notif');
    if (notif) notif.remove();
  }

  function closeChat() {
    isChatOpen = false;
    chatWin.classList.add('hidden');
    fab.style.transform = '';
    fab.style.opacity   = '';
  }

  function showWelcomeMessage() {
    const welcomeKey = 'chat_welcome';
    const welcomeTexts = {
      en:  'Hello! I am SerbisyoBot, your government services assistant for Marawi City. How can I help you today?',
      fil: 'Kamusta! Ako si SerbisyoBot, ang iyong gabay sa mga serbisyong pambayan ng Lungsod ng Marawi. Paano kita matutulungan ngayon? Maaari mo akong tanungin tungkol sa mga kinakailangang dokumento, lokasyon ng opisina, bayarin, at marami pa.',
      mrw: 'Assalamu Alaikum! Ako si SerbisyoBot, so imo alagad ko mga serbisyo a gobyerno sa Ranao a Baya. Antonaa so tabangan tano anka karon?',
    };
    const text = welcomeTexts[currentLang] || welcomeTexts.fil;
    appendBotMessage(text, false);
  }

  function appendUserMessage(text) {
    const row = document.createElement('div');
    row.className = 'msg-row user';
    row.innerHTML = `
      <div class="msg-avatar-sm user-av">👤</div>
      <div class="msg-group">
        <div class="msg-bubble user">${escapeHtml(text)}</div>
      </div>
    `;
    messages.appendChild(row);
    scrollToBottom();
  }

  function appendBotMessage(text, showFeedback = true) {
    const row = document.createElement('div');
    row.className = 'msg-row bot';
    const msgId = 'msg-' + Date.now();

    row.innerHTML = `
      <div class="msg-avatar-sm">🤖</div>
      <div class="msg-group">
        <div class="msg-bubble bot" id="${msgId}">${formatBotText(text)}</div>
        ${showFeedback ? `
        <div class="msg-feedback">
          <button class="fb-btn" onclick="SerbisyoBot.feedback(this, true, '${msgId}')">${T('helpful')}</button>
          <button class="fb-btn" onclick="SerbisyoBot.feedback(this, false, '${msgId}')">${T('notHelpful')}</button>
        </div>` : ''}
      </div>
    `;
    messages.appendChild(row);
    scrollToBottom();
  }

  function showTypingIndicator() {
    removeTypingIndicator();
    const row = document.createElement('div');
    row.className = 'msg-row bot';
    row.id = 'typing-row';
    row.innerHTML = `
      <div class="msg-avatar-sm">🤖</div>
      <div class="msg-group">
        <div class="msg-bubble bot">
          <div class="typing-indicator">
            <div class="dots">
              <div class="dot"></div><div class="dot"></div><div class="dot"></div>
            </div>
            <span class="typing-text">${T('typing')}</span>
          </div>
        </div>
      </div>
    `;
    messages.appendChild(row);
    scrollToBottom();
  }

  function removeTypingIndicator() {
    const el = document.getElementById('typing-row');
    if (el) el.remove();
  }

  function scrollToBottom() {
    setTimeout(() => {
      messages.scrollTop = messages.scrollHeight;
    }, 50);
  }

  function formatBotText(text) {
    let html = escapeHtml(text);

    // Bold **text**
    html = html.replace(/\*\*(.*?)\*\*/g, '<strong>$1</strong>');

    // Numbered list lines
    html = html.replace(/^(\d+\.\s.+)$/gm, '<li>$1</li>');
    html = html.replace(/(<li>.*<\/li>)/s, '<ol>$1</ol>');

    // Bullet list lines
    html = html.replace(/^[•\-]\s(.+)$/gm, '<li>$1</li>');

    // Wrap paragraphs (double newline = paragraph break)
    html = html.replace(/\n\n/g, '</p><p>');
    html = '<p>' + html + '</p>';

    // Single newlines → <br>
    html = html.replace(/\n/g, '<br>');

    // Clean up empty p tags
    html = html.replace(/<p><\/p>/g, '');
    html = html.replace(/<p>(<[ou]l>)/g, '$1');
    html = html.replace(/(<\/[ou]l>)<\/p>/g, '$1');

    return html;
  }

  function escapeHtml(text) {
    const map = { '&':'&amp;', '<':'&lt;', '>':'&gt;', '"':'&quot;', "'":'&#039;' };
    return String(text).replace(/[&<>"']/g, m => map[m]);
  }

  // ============================================================
  // SEND MESSAGE
  // ============================================================
  async function sendMessage(text) {
    text = text.trim();
    if (!text || isTyping) return;

    // Open chat if closed
    if (!isChatOpen) openChat();

    appendUserMessage(text);
    input.value = '';
    autoResize();

    isTyping = true;
    sendBtn.disabled = true;
    showTypingIndicator();

    try {
      const response = await fetchBotResponse(text);
      removeTypingIndicator();
      appendBotMessage(response);
    } catch (err) {
      removeTypingIndicator();
      const errMsg = currentLang === 'fil'
        ? 'Paumanhin, nagkaroon ng problema. Pakisubukang muli.'
        : currentLang === 'mrw'
        ? 'Pasensya, mayda problema. Pakisubukan oman.'
        : 'Sorry, something went wrong. Please try again.';
      appendBotMessage(errMsg, false);
    } finally {
      isTyping = false;
      sendBtn.disabled = false;
      input.focus();
    }
  }

  // ============================================================
  // API CALL TO chat-handler.php
  // ============================================================
  async function fetchBotResponse(message) {
    const payload = { message, language: currentLang };

    const res = await fetch('./api/chat-handler.php', {
      method:  'POST',
      headers: { 'Content-Type': 'application/json' },
      body:    JSON.stringify(payload),
    });

    if (!res.ok) {
      throw new Error(`HTTP ${res.status}`);
    }

    const data = await res.json();
    if (data.error) throw new Error(data.error);
    return data.response || '...';
  }

  // ============================================================
  // FEEDBACK
  // ============================================================
  function feedback(btn, isPositive, msgId) {
    const row     = btn.closest('.msg-feedback');
    if (!row) return;

    const buttons = row.querySelectorAll('.fb-btn');
    buttons.forEach(b => b.disabled = true);
    btn.classList.add(isPositive ? 'active-pos' : 'active-neg');

    // Show thank-you inline
    const thanks = document.createElement('span');
    thanks.style.cssText = 'font-size:.72rem;color:var(--muted);font-style:italic;margin-left:.3rem;';
    thanks.textContent   = isPositive ? T('thanksPos') : T('thanksNeg');
    row.appendChild(thanks);

    // POST feedback to server (best-effort)
    fetch('./api/feedback-handler.php', {
      method:  'POST',
      headers: { 'Content-Type': 'application/json' },
      body:    JSON.stringify({ msg_id: msgId, is_positive: isPositive }),
    }).catch(() => {/* silently ignore */});
  }

  // ============================================================
  // LANGUAGE SWITCHING
  // ============================================================
  function switchLanguage(lang) {
    if (!['en','fil','mrw'].includes(lang)) return;
    currentLang = lang;

    // Sync all language buttons
    document.querySelectorAll('[data-lang-btn]').forEach(btn => {
      btn.classList.toggle('active', btn.dataset.langBtn === lang);
    });

    // Persist via page reload with query param (PHP will update session)
    const url = new URL(window.location.href);
    url.searchParams.set('lang', lang);
    window.location.href = url.toString();
  }

  // ============================================================
  // SERVICE DIRECTORY SEARCH
  // ============================================================
  function initSearch() {
    if (!searchInput) return;
    searchInput.addEventListener('input', () => {
      const q = searchInput.value.toLowerCase().trim();
      filterServices(q);
    });
  }

  function filterServices(query) {
    const cards        = document.querySelectorAll('.svc-card');
    const catHeadings  = document.querySelectorAll('.cat-section');
    const noResults    = document.getElementById('noResults');
    let   anyVisible   = false;

    catHeadings.forEach(section => {
      const sectionCards = section.querySelectorAll('.svc-card');
      let   sectionHit   = false;

      sectionCards.forEach(card => {
        const text = card.dataset.search || '';
        const show = !query || text.includes(query);
        card.style.display = show ? '' : 'none';
        if (show) { sectionHit = true; anyVisible = true; }
      });

      // Hide entire category section if no cards match
      const heading = section.querySelector('.category-heading');
      if (heading) heading.style.display = sectionHit ? '' : 'none';
    });

    if (noResults) noResults.style.display = anyVisible ? 'none' : '';
  }

  // ============================================================
  // QUICK REPLY PILLS
  // ============================================================
  function sendQuickReply(text) {
    input.value = text;
    sendMessage(text);
  }

  // ============================================================
  // AUTO-RESIZE TEXTAREA
  // ============================================================
  function autoResize() {
    if (!input) return;
    input.style.height = 'auto';
    input.style.height = Math.min(input.scrollHeight, 100) + 'px';
  }

  // ============================================================
  // INIT
  // ============================================================
  function init() {
    // FAB click
    if (fab) fab.addEventListener('click', () => isChatOpen ? closeChat() : openChat());

    // Close button
    if (closeBtn) closeBtn.addEventListener('click', closeChat);

    // Send button
    if (sendBtn) sendBtn.addEventListener('click', () => sendMessage(input.value));

    // Enter to send (Shift+Enter = newline)
    if (input) {
      input.addEventListener('keydown', e => {
        if (e.key === 'Enter' && !e.shiftKey) {
          e.preventDefault();
          sendMessage(input.value);
        }
      });
      input.addEventListener('input', autoResize);
    }

    // Language buttons (navbar)
    document.querySelectorAll('[data-lang-btn]').forEach(btn => {
      btn.addEventListener('click', () => switchLanguage(btn.dataset.langBtn));
    });

    // Quick reply pills (delegated)
    document.addEventListener('click', e => {
      if (e.target.classList.contains('qr-pill')) {
        sendQuickReply(e.target.dataset.query || e.target.textContent.trim());
      }
      if (e.target.classList.contains('svc-ask-btn')) {
        const q = e.target.dataset.query;
        if (q) sendQuickReply(q);
      }
    });

    // Service search
    initSearch();

    // Hero CTA button
    const heroCta = document.getElementById('heroCta');
    if (heroCta) heroCta.addEventListener('click', openChat);
  }

  // Public API
  return { init, sendQuickReply, feedback, switchLanguage };
})();

document.addEventListener('DOMContentLoaded', SerbisyoBot.init);
