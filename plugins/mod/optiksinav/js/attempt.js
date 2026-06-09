// This file is part of the OptikSınav activity module for Moodle.
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.

/**
 * Client logic for the exam-taking page: server-anchored countdown, answer
 * autosave, double-click-to-clear, and live answered/blank counters.
 *
 * The countdown is a convenience only — the authoritative deadline is enforced
 * on the server (save.php rejects late writes; attempt.php/submit.php grade on
 * expiry). This script never decides the score.
 *
 * @module     mod_optiksinav/attempt
 * @copyright  2026 OptikSınav
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
(function () {
    'use strict';

    var cfg = window.OPTIKSINAV_CONFIG;
    if (!cfg) {
        return;
    }

    var form = document.getElementById('optiksinav-form');
    var submitBtn = document.getElementById('optiksinav-submit');
    var answeredEl = document.getElementById('optiksinav-answered');
    var blankEl = document.getElementById('optiksinav-blank');
    var timerEl = document.getElementById('optiksinav-timer');
    var submitting = false;

    /**
     * Format a duration in seconds as H:MM:SS (or MM:SS under an hour).
     * @param {number} total Seconds remaining.
     * @return {string}
     */
    function formatTime(total) {
        var h = Math.floor(total / 3600);
        var m = Math.floor((total % 3600) / 60);
        var s = total % 60;
        var mm = (m < 10 ? '0' : '') + m;
        var ss = (s < 10 ? '0' : '') + s;
        return h > 0 ? h + ':' + mm + ':' + ss : mm + ':' + ss;
    }

    /** Submit the form (auto on timeout or manual), guarding against double submits. */
    function doSubmit() {
        if (submitting) {
            return;
        }
        submitting = true;
        if (submitBtn) {
            submitBtn.disabled = true;
        }
        form.submit();
    }

    // --- Countdown (server-anchored: end = now + remaining) ---
    if (cfg.hasdeadline && timerEl) {
        var endAt = Date.now() + (cfg.remaining * 1000);
        var tick = function () {
            var left = Math.round((endAt - Date.now()) / 1000);
            if (left <= 0) {
                timerEl.textContent = formatTime(0);
                doSubmit();
                return;
            }
            timerEl.textContent = formatTime(left);
            if (left <= 60) {
                timerEl.classList.remove('alert-info');
                timerEl.classList.add('alert-danger');
            }
            window.setTimeout(tick, 250);
        };
        tick();
    }

    /**
     * Update the answered / blank counters from a server response.
     * @param {number} answered
     * @param {number} blank
     */
    function updateCounters(answered, blank) {
        if (answeredEl) {
            answeredEl.textContent = cfg.stranswered.replace('__N__', answered);
        }
        if (blankEl) {
            blankEl.textContent = cfg.strblank.replace('__N__', blank);
        }
    }

    /**
     * Persist one answer to the server.
     * @param {number} slot Question slot.
     * @param {string} answer Chosen letter, or '' to clear.
     */
    function saveAnswer(slot, answer) {
        var body = 'attemptid=' + encodeURIComponent(cfg.attemptid) +
            '&slot=' + encodeURIComponent(slot) +
            '&answer=' + encodeURIComponent(answer) +
            '&sesskey=' + encodeURIComponent(cfg.sesskey);

        fetch(cfg.saveurl, {
            method: 'POST',
            headers: {'Content-Type': 'application/x-www-form-urlencoded'},
            body: body,
            credentials: 'same-origin'
        }).then(function (resp) {
            return resp.json();
        }).then(function (data) {
            if (data.status === 'ok') {
                updateCounters(data.answered, data.blank);
            } else if (data.status === 'expired') {
                doSubmit();
            }
        }).catch(function () {
            // Sessiz geç — bir sonraki değişiklikte tekrar denenecek; teslimde sunucu yine puanlar.
        });
    }

    // --- Answer change → autosave ---
    var radios = form.querySelectorAll('.optiksinav-opt');
    radios.forEach(function (radio) {
        radio.addEventListener('change', function () {
            saveAnswer(parseInt(radio.dataset.slot, 10), radio.value);
        });
        // Çift tıkla işareti temizle (eski sistemdeki davranış).
        radio.addEventListener('dblclick', function () {
            if (radio.checked) {
                radio.checked = false;
                saveAnswer(parseInt(radio.dataset.slot, 10), '');
            }
        });
    });

    // --- Manual finish confirmation ---
    if (form) {
        form.addEventListener('submit', function (e) {
            if (submitting) {
                return;
            }
            if (!window.confirm(cfg.strconfirm)) {
                e.preventDefault();
                return;
            }
            submitting = true;
            if (submitBtn) {
                submitBtn.disabled = true;
            }
        });
    }
})();
