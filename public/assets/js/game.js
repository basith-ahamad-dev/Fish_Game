/* ==========================================================================
   Game client. The browser only ever receives the picture and sends a guess;
   timing, scoring, XP and badges are all decided by the server (GameService).
   ========================================================================== */
(() => {
    'use strict';
    const { $, $$, api, toast, openModal, closeModal, countUp, formatNumber, motionOn } = window.DC;

    const root = $('[data-game]');
    if (!root) return;

    const urls = {
        start: root.dataset.startUrl,
        round: root.dataset.roundUrl,
        answer: root.dataset.answerUrl,
        abandon: root.dataset.abandonUrl,
        result: root.dataset.resultUrl,
    };

    const screens = { select: $('[data-screen="select"]', root), play: $('[data-screen="play"]', root) };
    const hud = Object.fromEntries($$('[data-hud]', root).map((n) => [n.dataset.hud, n]));
    const strip = Object.fromEntries($$('[data-strip]', root).map((n) => [n.dataset.strip, n]));
    const aquarium = $('[data-aquarium]', root);
    const img = $('[data-puzzle]', root);
    const overlays = Object.fromEntries($$('[data-overlay]', root).map((n) => [n.dataset.overlay, n]));
    const form = $('[data-answer-form]', root);
    const input = $('[data-answer-input]', root);
    const submitBtn = $('[data-answer-submit]', root);
    const resultModal = $('#result-modal');
    const badgeIcons = JSON.parse($('#badge-icons')?.textContent || '{}');

    const HEART = '<svg class="icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M12 20s-7.5-4.6-9.2-9.4C1.6 7 4 4 7.2 4c2 0 3.6 1.1 4.8 2.7C13.2 5.1 14.8 4 16.8 4 20 4 22.4 7 21.2 10.6 19.5 15.4 12 20 12 20Z"/></svg>';
    const FEEDBACK_MS = 1500;

    const state = {
        game: null,          // config returned by /game/start
        phase: 'select',     // select | loading | answering | submitting | feedback | error | done
        score: 0,
        lives: 0,
        timer: null,
        deadline: 0,
        lastTick: null,
        lastAction: null,    // what RETRY should repeat
    };

    /* ---------- Sound (synthesised; respects the player's preference) ---------- */
    const sound = (() => {
        const enabled = document.body.dataset.sound === 'on';
        let ctx = null;
        const tone = (freq, start, dur, type = 'sine', gain = 0.12) => {
            const osc = ctx.createOscillator();
            const g = ctx.createGain();
            osc.type = type;
            osc.frequency.value = freq;
            g.gain.setValueAtTime(0, ctx.currentTime + start);
            g.gain.linearRampToValueAtTime(gain, ctx.currentTime + start + 0.01);
            g.gain.exponentialRampToValueAtTime(0.0001, ctx.currentTime + start + dur);
            osc.connect(g).connect(ctx.destination);
            osc.start(ctx.currentTime + start);
            osc.stop(ctx.currentTime + start + dur + 0.05);
        };
        const play = (notes) => {
            if (!enabled) return;
            try {
                ctx ??= new (window.AudioContext || window.webkitAudioContext)();
                notes.forEach(([f, s, d, t, g]) => tone(f, s, d, t, g));
            } catch { /* audio unavailable */ }
        };
        return {
            correct: () => play([[660, 0, .12], [990, .09, .22]]),
            wrong: () => play([[220, 0, .25, 'sawtooth', .06], [165, .12, .3, 'sawtooth', .06]]),
            tick: () => play([[1200, 0, .05, 'square', .03]]),
            win: () => play([[523, 0, .15], [659, .12, .15], [784, .24, .15], [1047, .36, .4]]),
            lose: () => play([[392, 0, .25, 'triangle'], [330, .2, .25, 'triangle'], [262, .4, .5, 'triangle']]),
            unlock: () => play([[1319, 0, .1], [1568, .08, .1], [2093, .16, .35]]),
        };
    })();

    /* ---------- Screens & overlays ---------- */
    function showScreen(name) {
        Object.entries(screens).forEach(([key, el]) => { el.hidden = key !== name; });
        window.scrollTo({ top: 0, behavior: motionOn() ? 'smooth' : 'auto' });
    }

    function showOverlay(name) {
        Object.entries(overlays).forEach(([key, el]) => { el.hidden = key !== name; });
    }

    function setAnswering(enabled) {
        form.classList.toggle('is-disabled', !enabled);
        input.disabled = !enabled;
        submitBtn.disabled = !enabled;
        $$('.keypad__key', form).forEach((k) => { k.disabled = !enabled; });
        if (enabled) {
            input.value = '';
            // Avoid popping the on-screen keyboard over the picture on touch devices
            if (!window.matchMedia('(pointer: coarse)').matches) input.focus();
        }
    }

    function renderLives(lives, total) {
        hud.lives.innerHTML = '';
        for (let i = 0; i < total; i++) {
            const span = document.createElement('span');
            span.innerHTML = HEART;
            if (i >= lives) span.firstChild.classList.add('is-lost');
            hud.lives.appendChild(span.firstChild);
        }
        hud.lives.setAttribute('aria-label', `${lives} of ${total} lives remaining`);
    }

    function loseLife() {
        const hearts = $$('.icon:not(.is-lost)', hud.lives);
        hearts[hearts.length - 1]?.classList.add('is-lost', 'just-lost');
        hud.lives.setAttribute('aria-label', `${hearts.length - 1} of ${state.game.lives} lives remaining`);
    }

    function setScore(score, bump = false) {
        state.score = score;
        hud.score.textContent = formatNumber(score);
        strip.score.textContent = formatNumber(score);
        if (bump) {
            hud.score.classList.remove('is-bumped');
            void hud.score.offsetWidth;
            hud.score.classList.add('is-bumped');
        }
    }

    function showError(message, retryAction) {
        stopTimer();
        state.phase = 'error';
        state.lastAction = retryAction;
        $('[data-error-message]', overlays.error).textContent = message || 'Please try again.';
        showOverlay('error');
        setAnswering(false);
        $('[data-action="retry"]', overlays.error).focus();
    }

    /* ---------- Timer (display only — the server keeps the authoritative clock) ---------- */
    function startTimer(seconds) {
        stopTimer();
        const ring = $('.hud__timer-ring', hud.timer);
        state.deadline = performance.now() + seconds * 1000;
        state.lastTick = null;

        const frame = (now) => {
            const left = Math.max(0, state.deadline - now);
            const pct = left / (seconds * 1000);
            const secs = Math.ceil(left / 1000);
            hud.seconds.textContent = String(secs);
            ring.style.strokeDashoffset = String(100 - pct * 100);
            hud.timer.classList.toggle('is-warning', pct <= 0.5 && pct > 0.25);
            hud.timer.classList.toggle('is-danger', pct <= 0.25);
            if (secs <= 3 && secs > 0 && state.lastTick !== secs) { state.lastTick = secs; sound.tick(); }

            if (left <= 0) {
                state.timer = null;
                submit(null);
                return;
            }
            state.timer = requestAnimationFrame(frame);
        };
        state.timer = requestAnimationFrame(frame);
    }

    function stopTimer() {
        if (state.timer) cancelAnimationFrame(state.timer);
        state.timer = null;
    }

    /* ---------- Flow ---------- */
    function selectedDifficulty() {
        return $('input[name="difficulty"]:checked', screens.select)?.value || 'easy';
    }

    async function start(difficulty = selectedDifficulty()) {
        const btn = $('[data-action="start"]', root);
        btn.classList.add('is-loading');
        try {
            const game = await api(urls.start, { difficulty });
            state.game = game;
            state.lives = game.lives;
            setScore(0);
            hud.difficulty.innerHTML = '';
            const pill = document.createElement('span');
            pill.className = `diff-pill diff-pill--${game.difficulty}`;
            pill.innerHTML = `<span class="diff-pill__stars" aria-hidden="true">${'★'.repeat({ easy: 1, medium: 2, hard: 3 }[game.difficulty])}</span>`;
            pill.append(game.label);
            hud.difficulty.appendChild(pill);
            hud.rounds.textContent = game.rounds;
            hud.round.textContent = '1';
            renderLives(game.lives, game.lives);
            showScreen('play');
            if (game.abandoned_previous) toast('Your unfinished game was recorded as abandoned.', 'info');
            nextRound();
        } catch (err) {
            toast(err.message, 'error');
        } finally {
            btn.classList.remove('is-loading');
        }
    }

    async function nextRound() {
        state.phase = 'loading';
        aquarium.classList.remove('is-correct', 'is-wrong');
        img.hidden = true;
        hud.seconds.textContent = String(state.game.time_limit);
        $('.hud__timer-ring', hud.timer).style.strokeDashoffset = '0';
        hud.timer.classList.remove('is-warning', 'is-danger');
        showOverlay('loading');
        setAnswering(false);

        let data;
        try {
            data = await api(urls.round, { game_id: state.game.game_id });
        } catch (err) {
            if (err.status === 409 && /finished/i.test(err.message)) return fetchResult();
            return showError(err.status === 503 ? 'Please try again.' : err.message, nextRound);
        }

        if (data.finished) {
            // A picture left open (e.g. after a refresh) was forfeited and ended the game.
            return showResult(data.forfeited.summary);
        }
        if (data.forfeited) toast('An unanswered picture was counted as a miss.', 'info');

        hud.round.textContent = data.round;
        state.lives = data.lives;
        renderLives(data.lives, state.game.lives);

        img.onload = () => {
            img.hidden = false;
            showOverlay(null);
            state.phase = 'answering';
            setAnswering(true);
            startTimer(data.time_limit);
        };
        img.onerror = () => showError('The picture could not be displayed.', nextRound);
        img.src = data.image;
    }

    async function submit(value) {
        if (state.phase !== 'answering') return;
        state.phase = 'submitting';
        stopTimer();
        setAnswering(false);

        let res;
        try {
            res = await api(urls.answer, { game_id: state.game.game_id, answer: value === null ? null : Number(value) });
        } catch (err) {
            if (err.status === 422) {
                toast(err.message, 'error');
                state.phase = 'answering';
                setAnswering(true);
                startTimer(Math.max(1, (state.deadline - performance.now()) / 1000));
                return;
            }
            return showError(err.message, () => { state.phase = 'answering'; submit(value); });
        }

        showFeedback(res);
        setTimeout(() => (res.finished ? showResult(res.summary) : nextRound()), FEEDBACK_MS);
    }

    function showFeedback(res) {
        state.phase = 'feedback';
        const box = $('.feedback', overlays.feedback);
        box.classList.toggle('is-correct', res.correct);
        box.classList.toggle('is-wrong', !res.correct);
        aquarium.classList.add(res.correct ? 'is-correct' : 'is-wrong');

        const fish = `${res.solution} fish`;
        $('[data-feedback-icon]', box).textContent = res.correct ? '🎣' : (res.timed_out ? '⏱️' : '🐟');
        $('[data-feedback-title]', box).textContent = res.correct ? 'Correct!' : (res.timed_out ? "Time's up!" : 'Not quite!');
        $('[data-feedback-detail]', box).textContent = res.correct
            ? `You counted all ${fish}`
            : `There ${res.solution === 1 ? 'was' : 'were'} ${fish}${res.chests ? ` (and ${res.chests} chest${res.chests === 1 ? '' : 's'})` : ''}`;
        $('[data-feedback-points]', box).textContent = res.correct ? `+${formatNumber(res.points)}` : '−1 life';
        showOverlay('feedback');

        if (res.correct) {
            sound.correct();
            setScore(res.score, true);
        } else {
            sound.wrong();
            loseLife();
        }
    }

    async function fetchResult() {
        try {
            showResult(await api(urls.result, { game_id: state.game.game_id }));
        } catch (err) {
            showError(err.message, fetchResult);
        }
    }

    /* ---------- Result modal ---------- */
    function showResult(s) {
        state.phase = 'done';
        stopTimer();
        showOverlay(null);
        const r = (key) => $(`[data-r="${key}"]`, resultModal);

        const titles = {
            won: s.perfect ? ['🏆', 'PERFECT CATCH!'] : ['🎣', 'GAME COMPLETE!'],
            lost: ['🌊', 'OUT OF LIVES'],
            abandoned: ['⚓', 'GAME ENDED'],
        };
        const [emoji, title] = titles[s.result] || titles.won;
        r('emoji').textContent = emoji;
        r('title').textContent = title;
        r('subtitle').textContent = `${s.difficulty_label} · ${s.correct} of ${s.rounds_played} pictures counted correctly`
            + (s.perfect_bonus ? ` · +${formatNumber(s.perfect_bonus)} perfect bonus` : '');
        r('xp').textContent = `+${formatNumber(s.xp_earned)} XP`;
        r('pb').hidden = !s.personal_best;
        r('correct').textContent = `${s.correct}/${s.rounds_played}`;
        r('fish').textContent = formatNumber(s.fish_caught);
        r('rank').textContent = s.rank ? `#${s.rank}` : '—';
        r('level').textContent = s.progress.level;
        r('level-numbers').textContent = `${formatNumber(s.progress.current)} / ${formatNumber(s.progress.needed)} XP`;
        r('levelup').hidden = !s.leveled_up;
        r('new-level').textContent = s.level_after;
        r('details').href = `${urls.result}/${s.game_id}`;

        const bar = r('level-bar');
        bar.style.setProperty('--progress', '0%');

        const badges = r('badges');
        badges.innerHTML = '';
        const tpl = $('#badge-unlock-template');
        (s.badges || []).forEach((b, i) => {
            const node = tpl.content.firstElementChild.cloneNode(true);
            node.style.animationDelay = `${0.6 + i * 0.25}s`;
            $('.badge-medal', node).innerHTML = badgeIcons[b.icon] || '';
            $('.unlock__name', node).textContent = b.name;
            $('.unlock__desc', node).textContent = b.description;
            badges.appendChild(node);
        });
        badges.hidden = !(s.badges || []).length;

        openModal(resultModal);
        countUp(r('score'), s.score, 1300);
        requestAnimationFrame(() => setTimeout(() => bar.style.setProperty('--progress', `${s.progress.percent}%`), 200));

        if (s.result === 'won') {
            sound.win();
            confetti();
        } else {
            sound.lose();
        }
        if ((s.badges || []).length) setTimeout(sound.unlock, 900);
        if (s.leveled_up) toast(`Level up! You are now level ${s.level_after}.`, 'gold');

        updateStrip(s);
        window.removeEventListener('beforeunload', guardUnload);
    }

    function updateStrip(s) {
        if (s.personal_best) {
            strip.best.textContent = formatNumber(s.score);
            root.dataset.best = s.score;
        }
        strip.level.textContent = s.progress.level;
        strip.xp.textContent = formatNumber(s.progress.current);
        strip['xp-needed'].textContent = formatNumber(s.progress.needed);
        $('.progress__fill', strip['xp-bar'])?.style.setProperty('--progress', `${s.progress.percent}%`);
    }

    function confetti() {
        if (!motionOn()) return;
        const host = $('.result__confetti', resultModal);
        host.innerHTML = '';
        const colors = ['#25D9FF', '#1597E5', '#F5C542', '#22C55E', '#FB7185', '#A78BFA'];
        for (let i = 0; i < 60; i++) {
            const p = document.createElement('span');
            p.className = 'confetti-piece';
            p.style.left = `${Math.random() * 100}%`;
            p.style.background = colors[i % colors.length];
            p.style.animationDelay = `${Math.random() * 0.6}s`;
            p.style.animationDuration = `${1.8 + Math.random() * 1.4}s`;
            p.style.transform = `rotate(${Math.random() * 360}deg)`;
            host.appendChild(p);
        }
    }

    /* ---------- Input handling ---------- */
    function guardUnload(e) {
        if (['loading', 'answering', 'submitting', 'feedback', 'error'].includes(state.phase)) {
            e.preventDefault();
            e.returnValue = '';
        }
    }

    function pressKey(key) {
        if (state.phase !== 'answering') return;
        if (key === 'clear') input.value = '';
        else if (key === 'back') input.value = input.value.slice(0, -1);
        else if (input.value.length < 2) input.value = (input.value + key).replace(/^0(\d)/, '$1');
        const btn = $(`.keypad__key[data-key="${key}"]`, form);
        if (btn) {
            btn.classList.add('is-pressed');
            setTimeout(() => btn.classList.remove('is-pressed'), 120);
        }
    }

    form.addEventListener('submit', (e) => {
        e.preventDefault();
        const value = input.value.trim();
        if (value === '' || !/^\d{1,2}$/.test(value)) {
            input.focus();
            toast('Enter how many fish you counted.', 'info', { timeout: 2000 });
            return;
        }
        submit(value);
    });

    input.addEventListener('input', () => { input.value = input.value.replace(/\D/g, '').slice(0, 2); });

    form.addEventListener('click', (e) => {
        const key = e.target.closest('[data-key]');
        if (key) {
            pressKey(key.dataset.key);
            if (!window.matchMedia('(pointer: coarse)').matches) input.focus();
        }
    });

    // Typing digits anywhere on the page goes to the answer box
    document.addEventListener('keydown', (e) => {
        if (state.phase !== 'answering' || e.target === input || e.metaKey || e.ctrlKey || e.altKey) return;
        if (/^\d$/.test(e.key)) { e.preventDefault(); pressKey(e.key); }
        else if (e.key === 'Backspace') { e.preventDefault(); pressKey('back'); }
        else if (e.key === 'Enter' && input.value) { e.preventDefault(); form.requestSubmit(); }
    });

    root.addEventListener('click', (e) => {
        const action = e.target.closest('[data-action]')?.dataset.action;
        if (action === 'start') {
            window.addEventListener('beforeunload', guardUnload);
            start();
        }
        if (action === 'retry') state.lastAction?.();
        if (action === 'quit') openModal('quit-modal');
    });

    resultModal.addEventListener('click', (e) => {
        if (e.target.closest('[data-action="again"]')) {
            closeModal(resultModal);
            window.addEventListener('beforeunload', guardUnload);
            start(state.game.difficulty);
        }
    });

    // Quitting closes the game on the server as abandoned before leaving.
    $('[data-quit-confirm]')?.addEventListener('click', async (e) => {
        e.preventDefault();
        const href = e.currentTarget.href;
        stopTimer();
        window.removeEventListener('beforeunload', guardUnload);
        if (state.game && state.phase !== 'done') {
            try { await api(urls.abandon, { game_id: state.game.game_id }); } catch { /* started next time anyway */ }
        }
        window.location.href = href;
    });

    // Keep the difficulty cards in sync with ?difficulty= links
    $$('input[name="difficulty"]', screens.select).forEach((radio) => {
        radio.addEventListener('change', () => history.replaceState(null, '', `?difficulty=${radio.value}`));
    });

    // Pause-proofing: the server clock keeps running, so warn when the tab is hidden mid-round
    document.addEventListener('visibilitychange', () => {
        if (document.hidden && state.phase === 'answering') {
            toast('The clock keeps ticking while you are away!', 'info', { timeout: 3000 });
        }
    });
})();
