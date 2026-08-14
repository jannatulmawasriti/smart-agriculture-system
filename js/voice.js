// ==========================================================
// ভয়েস ইনপুট সিস্টেম (Web Speech API)
// - startVoiceInput(id): ম্যানুয়ালি বসানো মাইক বাটনের জন্য (আগের মতোই কাজ করবে)
// - পেজ লোড হওয়ার সময় সাইটের প্রতিটি টেক্সট/নাম্বার/ইমেইল/ফোন ইনপুট ও
//   textarea-তে স্বয়ংক্রিয়ভাবে 🎤 মাইক বাটন যোগ হয়ে যায়, আলাদা করে
//   প্রতিটি পেজে কোড বসাতে হয় না।
// ==========================================================

const BANGLA_DIGITS = "০১২৩৪৫৬৭৮৯";

function convertBanglaDigitsToEnglish(str) {
    return str.replace(/[০-৯]/g, function (d) {
        return String(BANGLA_DIGITS.indexOf(d));
    });
}

function startVoiceInput(targetInputId) {
    if (!('webkitSpeechRecognition' in window) && !('SpeechRecognition' in window)) {
        alert('দুঃখিত, আপনার ব্রাউজার ভয়েস ইনপুট সাপোর্ট করে না। অনুগ্রহ করে Google Chrome ব্যবহার করুন।');
        return;
    }
    const SpeechRecognition = window.SpeechRecognition || window.webkitSpeechRecognition;
    const recognition = new SpeechRecognition();
    recognition.lang = 'bn-BD';
    recognition.continuous = false;
    recognition.interimResults = false;

    const input = document.getElementById(targetInputId);
    const btn = document.querySelector(`[data-target="${targetInputId}"]`);
    if (btn) { btn.classList.add('listening'); btn.innerText = '🔴'; }

    try {
        recognition.start();
    } catch (e) {
        if (btn) { btn.classList.remove('listening'); btn.innerText = '🎤'; }
        return;
    }

    recognition.onresult = function (event) {
        let transcript = event.results[0][0].transcript.trim();
        if (input) {
            if (input.tagName === 'INPUT' && input.type === 'number') {
                transcript = convertBanglaDigitsToEnglish(transcript);
                const match = transcript.match(/-?\d+(\.\d+)?/);
                input.value = match ? match[0] : '';
            } else {
                input.value = transcript;
            }
            input.dispatchEvent(new Event('input', { bubbles: true }));
            input.dispatchEvent(new Event('change', { bubbles: true }));
            input.focus();
        }
    };

    recognition.onerror = function () {
        alert('ভয়েস শনাক্ত করা যায়নি, আবার চেষ্টা করুন।');
    };

    recognition.onend = function () {
        if (btn) { btn.classList.remove('listening'); btn.innerText = '🎤'; }
    };
}

// ==========================================================
// স্বয়ংক্রিয় মাইক বাটন — সব ইনপুট ফিল্ডে
// ==========================================================
(function () {
    const VOICE_SUPPORTED = ('webkitSpeechRecognition' in window) || ('SpeechRecognition' in window);
    const ALLOWED_TYPES = ['text', 'search', 'tel', 'email', 'number', 'url'];
    let autoIdCounter = 0;

    function shouldSkip(el) {
        if (el.disabled || el.readOnly) return true;
        if (el.classList.contains('no-voice')) return true;
        if (el.closest('.input-with-voice')) return true; // আগে থেকেই ম্যানুয়ালি বসানো আছে
        if (el.tagName === 'INPUT' && ALLOWED_TYPES.indexOf(el.type) === -1) return true;
        return false;
    }

    function wrapWithMic(el) {
        if (!el.id) {
            autoIdCounter++;
            el.id = 'voice_auto_' + autoIdCounter;
        }
        const wrapper = document.createElement('div');
        wrapper.className = 'input-with-voice';
        el.parentNode.insertBefore(wrapper, el);
        wrapper.appendChild(el);

        const btn = document.createElement('button');
        btn.type = 'button';
        btn.className = 'voice-btn';
        btn.setAttribute('data-target', el.id);
        btn.setAttribute('title', 'ভয়েস দিয়ে লিখুন');
        btn.setAttribute('aria-label', 'ভয়েস দিয়ে লিখুন');
        btn.innerText = '🎤';
        btn.addEventListener('click', function () {
            startVoiceInput(el.id);
        });
        wrapper.appendChild(btn);
    }

    function attachAll() {
        if (!VOICE_SUPPORTED) return;
        document.querySelectorAll('input, textarea').forEach(function (el) {
            if (shouldSkip(el)) return;
            wrapWithMic(el);
        });
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', attachAll);
    } else {
        attachAll();
    }
})();
