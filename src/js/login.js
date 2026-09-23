var openLoginModal

$(function () {
    "use strict"
    var lm = $("#loginModal")
    var stage = lm.find(".login-stage")
    var codeForm = lm.find("#loginModal-codeconfirm")
    var codeField = codeForm.find(".login-code-field")
    var codeInput = codeForm.find(".login-code-input")
    var codeSlots = codeForm.find(".login-code-slot")
    var codeFeedback = codeForm.find(".invalid-feedback")
    var codeNote = codeForm.find(".login-code-note")
    var resendButton = codeForm.find("[data-login-resend]")
    var accountList = lm.find(".login-accounts")
    var progress = lm.find(".login-progress")
    var progressSteps = progress.find(".login-progress-step")
    var progressMarker = progress.find(".login-progress-marker")
    var backButton = lm.find("[data-login-back]")
    var reduceMotion = window.matchMedia && window.matchMedia("(prefers-reduced-motion: reduce)").matches
    var easeOut = "cubic-bezier(.23, 1, .32, 1)" // --ease-out
    // Position of each step in the step row: connect, choose the account, enter the code
    var stepIndex = {"loading": 0, "not-connected": 0, "select-account": 1, "confirmation-code": 2}
    var errorIndex = 0 // the cell that shows the warning while the error step is up
    var isModalShown = false // from "show" until "hide"
    var isModalSettled = false // the entrance finished and Bootstrap has placed its own focus
    var currentStep = "loading"
    var transition = null
    var pollTimeout = null
    var generation = 0 // bumped on every opening and closing, answers meant for an earlier opening are dropped
    var isSubmitting = false
    var isResending = false
    var returnFocus = null
    var accounts
    var selectedCldbid
    var selectedNickname

    codeForm.submit(function (e) {
        e.preventDefault()

        // The field keeps digits only (see "input" below); this also covers a value set without an input event
        var code = codeInput.val().replace(/\D+/g, "")

        if (isSubmitting || isResending) {
            return
        }

        if (!code) {
            codeInput.trigger("focus")
            return
        }

        setSubmitting(true)

        callLoginApi("login", {cldbid: selectedCldbid, code: code}, function (json) {
            // Already signed in from another tab counts as a success too. reload() rather than
            // "location = location": it also reloads URLs with a #hash, and logged-out pages are never POST results
            if (json.success || json.code === "ALREADY_AUTHENTICATED") {
                location.reload()
                return
            }

            setSubmitting(false)
            setCodeMeta("invalid")
            codeInput.trigger("select")
        })
    })

    // Codes are six digits. A paste often brings spaces or the words around the code, keep the digits only
    codeInput.on("input", function () {
        var raw = this.value
        var digits = raw.replace(/\D+/g, "").slice(0, codeSlots.length)

        if (digits !== raw) {
            var caret = Math.min(raw.slice(0, this.selectionStart || 0).replace(/\D+/g, "").length, digits.length)
            this.value = digits
            this.setSelectionRange(caret, caret)
        }

        if (codeForm.hasClass("is-invalid")) {
            setCodeMeta("life")
        }

        paintCodeSlots()
    }).on("focus blur keyup mouseup select selectionchange", paintCodeSlots)

    // Back from the code to the account list; the code already sent stays valid for its account
    backButton.click(function () {
        if (isSubmitting || isResending) {
            return
        }

        resetCode()
        accountList.removeAttr("aria-busy").find(".login-account").removeAttr("aria-disabled").removeClass("is-pending")
        showStep("select-account")
    })

    /*
     * Offered after a wrong code. The server sends a new code only once the last one has expired,
     * otherwise it answers CODE_ALREADY_GENERATED, and the note says so: then the digits were mistyped
     */
    resendButton.click(function () {
        if (isSubmitting || isResending) {
            return
        }

        setResending(true)

        callLoginApi("selectaccount", {cldbid: selectedCldbid}, function (json) {
            setResending(false)

            if (!json.success && json.code !== "CODE_ALREADY_GENERATED") {
                showError("sendingcode")
                return
            }

            // The link is gone now, the field is where the next step happens. A new code makes the typed one useless
            if (json.success) {
                codeInput.val("")
            }

            setCodeMeta(json.success ? "sent" : "still-valid")
            codeInput.trigger("focus").trigger("select")
        })
    })

    lm.on("click", "[data-selectaccount]", function () {
        if (accountList.attr("aria-busy") === "true") {
            return
        }

        selectedCldbid = String($(this).data("selectaccount"))

        // Not "disabled": that would drop keyboard focus to the page behind the modal
        accountList.attr("aria-busy", "true")
        accountList.find(".login-account").attr("aria-disabled", "true")
        $(this).addClass("is-pending")

        selectAccount()
    })

    // Arrow keys walk the account list, Tab still works as usual
    lm.on("keydown", ".login-account", function (e) {
        if (e.key !== "ArrowDown" && e.key !== "ArrowUp") {
            return
        }

        var rows = accountList.find(".login-account")
        var index = rows.index(this) + (e.key === "ArrowDown" ? 1 : -1)

        e.preventDefault()

        if (index >= 0 && index < rows.length) {
            rows.eq(index).trigger("focus")
        }
    })

    lm.on("click", "[data-login-retry]", function () {
        showStep("loading")
        loadAccounts()
    })

    // A failed logout reloads too, so the page shows the session as it really is. The usual failure is an
    // expired session, which the CSRF check answers with 400 while the visitor is already logged out
    $(".logoutUser").click(function (e) {
        callLoginApi("logout", {}, reloadAfterLogout, reloadAfterLogout)
    })

    $("[data-openLoginModal]").click(function (e) {
        e.preventDefault()
        openLoginModal()
    })

    openLoginModal = function() {
        returnFocus = document.activeElement
        lm.modal("show")
    }

    lm.on("show.bs.modal", function () {
        generation++
        resetFlow() // still counts as closed here, so the reset to "loading" is instant
        isModalShown = true
        loadAccounts()
    }).on("shown.bs.modal", function () {
        isModalSettled = true
        focusStep()
    }).on("hide.bs.modal", function () {
        generation++ // answers still on their way belong to this opening: drop them, so no code is sent after closing
        isModalShown = false
        isModalSettled = false
        clearTimeout(pollTimeout)
    }).on("hidden.bs.modal", function () {
        // Opened from script, so Bootstrap does not give the focus back by itself.
        // The mobile menu closes as the modal opens, so its "Log in" item hands over to the menu button
        var target = returnFocus

        if (target && (!$(target).is(":visible") || $(target).closest("#mobile-menu").length)) {
            target = $(".menu-toggle:visible")[0]
        }

        if (target) {
            target.focus({preventScroll: true})
        }

        returnFocus = null
    })

    function loadAccounts() {
        clearTimeout(pollTimeout)

        callLoginApi("getclients", {}, function (json) {
            if (!json.success) {
                showError("generic")
                return
            }

            accounts = json.data
            var ids = Object.keys(accounts)

            if (ids.length === 0) {
                // Not connected yet: ask again every 2 seconds while the modal is open
                showStep("not-connected")

                if (isModalShown) {
                    pollTimeout = setTimeout(loadAccounts, 2000)
                }
            } else if (ids.length === 1) {
                // If only one account, auto-select it
                selectedCldbid = ids[0]
                selectAccount()
            } else {
                renderAccounts(ids)
                showStep("select-account")
            }
        })
    }

    function renderAccounts(ids) {
        var template = lm.find("#select-account-template").html()
        var html = ""

        ids.forEach(function (cldbid) {
            html += template.format(escapeHtml(accounts[cldbid]), escapeHtml(cldbid), avatarHtml(accounts[cldbid], "avatar-lg"))
        })

        accountList.removeAttr("aria-busy").html(html)
    }

    function selectAccount() {
        callLoginApi("selectaccount", {cldbid: selectedCldbid}, function (json) {
            if (!json.success && json.code !== "CODE_ALREADY_GENERATED") {
                showError("sendingcode")
                return
            }

            // get nickname by dbid
            selectedNickname = accounts[selectedCldbid]

            // A retry after a failed "Resend code" comes back here, so the step starts clean
            resetCode()

            lm.find(".selected-nickname").text(selectedNickname)
            lm.find(".login-who-cldbid").text(selectedCldbid)
            lm.find(".login-who-avatar").html(avatarHtml(selectedNickname, "avatar-lg"))
            backButton.prop("hidden", Object.keys(accounts).length < 2)
            showStep("confirmation-code")
        })
    }

    /*
     * Monogram avatar, the same as TemplateUtils::avatar() in PHP: the first letter or digit of the
     * nickname on a hue from crc32 of the lowercased name, so a person looks the same here as on the home page
     */
    var avatarLetter
    var crcTable

    try {
        avatarLetter = new RegExp("[\\p{L}\\p{N}]", "u")
    } catch (e) {
        avatarLetter = /[0-9A-Za-z\u00C0-\u024F\u0370-\u03FF\u0400-\u04FF]/ // no Unicode property escapes
    }

    function avatarHtml(nickname, extraClass) {
        var match = String(nickname).match(avatarLetter)
        var letter = match ? match[0].toUpperCase() : "?"
        var hue = crc32(String(nickname).toLowerCase()) % 360

        return '<span class="avatar' + (extraClass ? " " + extraClass : "") + '" style="--h: ' + hue + '" aria-hidden="true">' +
            escapeHtml(letter) + '</span>'
    }

    function crc32(text) {
        var bytes = new TextEncoder().encode(text)
        var crc = 0xFFFFFFFF

        if (!crcTable) {
            crcTable = []

            for (var n = 0; n < 256; n++) {
                var c = n

                for (var k = 0; k < 8; k++) {
                    c = c & 1 ? 0xEDB88320 ^ (c >>> 1) : c >>> 1
                }

                crcTable[n] = c >>> 0
            }
        }

        for (var i = 0; i < bytes.length; i++) {
            crc = crcTable[(crc ^ bytes[i]) & 0xFF] ^ (crc >>> 8)
        }

        return (crc ^ 0xFFFFFFFF) >>> 0
    }

    // "error" is optional, the default one shows the error step of the modal
    function callLoginApi(method, data, success, error) {
        var requestGeneration = generation

        data.method = method
        $.ajax({
            headers: {
                "X-CSRF-TOKEN": csrfToken
            },
            url: "api/login.php",
            method: "post",
            data: data,
            // A request that hangs ends on the error step, which offers "Try again"
            timeout: 15000,
            success: function (json) {
                // A successful login still reloads the page when the modal was closed meanwhile
                if (requestGeneration === generation || (method === "login" && json.success)) {
                    success(json)
                }
            },
            error: function (result) {
                console.log(result)

                if (error) {
                    error(result)
                } else if (requestGeneration === generation) {
                    showError("generic")
                }
            }
        })
    }

    /*
     * A GET to the same address, never reload(): a page that is a POST result (assigner.php) would be sent again.
     * The #hash goes, otherwise the browser only scrolls to it (the skip link leaves #main) and nothing reloads.
     * replace() keeps the logged-in page out of the history, so Back does not bring it back.
     * Logging out changes the header, not the page, so like the login reload it skips the page transition,
     * which Chromium aborts with an uncaught error on a navigation to the same address
     */
    function reloadAfterLogout() {
        window.addEventListener("pageswap", function (e) {
            if (e.viewTransition) {
                e.viewTransition.skipTransition()
            }
        }, {once: true})

        location.replace(location.href.split("#")[0])
    }

    function showError(type) {
        clearTimeout(pollTimeout)
        setSubmitting(false)
        setResending(false)
        lm.find(".error-generic").prop("hidden", type !== "generic")
        lm.find(".error-sendingcode").prop("hidden", type !== "sendingcode")

        // The warning goes on the step that failed: sending the code belongs to choosing the account
        if (type === "sendingcode") {
            errorIndex = stepIndex["select-account"]
        } else if (currentStep in stepIndex) {
            errorIndex = stepIndex[currentStep]
        }

        showStep("error")
    }

    function resetFlow() {
        clearTimeout(pollTimeout)
        setSubmitting(false)
        resetCode()
        accountList.removeAttr("aria-busy").empty()
        showStep("loading")
    }

    function resetCode() {
        codeInput.val("")
        setResending(false)
        applyCodeMeta("life")
        paintCodeSlots()
    }

    // The box that takes the next digit carries the focus ring; a selection marks every box it covers
    function paintCodeSlots() {
        var input = codeInput[0]
        var focused = document.activeElement === input
        var start = Math.min(input.selectionStart || 0, codeSlots.length - 1)
        var end = input.selectionEnd || 0
        var isRange = end > start + 1

        codeSlots.each(function (i) {
            var active = focused && (isRange ? i >= start && i < end : i === start)
            this.classList.toggle("is-active", active)
            this.classList.toggle("is-selected", active && isRange)
        })

        // Browsers without "overflow: clip" scroll the field to show the caret after the last digit
        codeField[0].scrollLeft = 0
    }

    function setSubmitting(busy) {
        isSubmitting = busy
        codeForm.toggleClass("is-busy", busy).attr("aria-busy", busy ? "true" : "false")
    }

    // Not "disabled", for the same reason as the account rows: focus stays on the link while it waits
    function setResending(busy) {
        isResending = busy
        resendButton.toggleClass("is-pending", busy).attr("aria-disabled", busy ? "true" : null)
    }

    /*
     * The line under the boxes: "life" is the rule for codes, "invalid" the error with the "Resend code"
     * link, "sent" and "still-valid" the answer to that link. Only the text of one line changes, so the
     * modal keeps its height; the new line fades in and a wrong code shakes the boxes
     */
    function setCodeMeta(state) {
        resizeStage(function () {
            applyCodeMeta(state)
        })

        if (!isModalShown || !codeFeedback[0].animate) {
            return
        }

        var line = state === "invalid" ? codeFeedback.add(resendButton) : state === "life" ? $() : codeNote

        line.each(function () {
            this.animate(reduceMotion
                ? [{opacity: 0}, {opacity: 1}]
                : [{opacity: 0, transform: "translateY(-4px)"}, {opacity: 1, transform: "none"}], {duration: 180, easing: easeOut})
        })

        // A short shake of the boxes says "no" the way a login field does; the red boxes carry it without motion
        if (state === "invalid" && !reduceMotion) {
            codeField[0].animate([
                {transform: "none"},
                {transform: "translateX(-5px)", offset: .2},
                {transform: "translateX(4px)", offset: .45},
                {transform: "translateX(-2px)", offset: .7},
                {transform: "none"}
            ], {duration: 280, easing: easeOut})
        }
    }

    function applyCodeMeta(state) {
        var invalid = state === "invalid"
        var note = state === "sent" || state === "still-valid"

        codeForm.toggleClass("is-invalid", invalid).toggleClass("has-note", note)
        codeInput.toggleClass("is-invalid", invalid).attr({
            "aria-invalid": invalid ? "true" : "false",
            // A hidden element named here is still read out, so the field names only the line on screen
            "aria-describedby": "loginModal-code-sent " + (invalid ? "loginModal-code-error" : note ? "loginModal-code-note" : "loginModal-code-life")
        })
        codeFeedback.prop("hidden", !invalid)
        resendButton.prop("hidden", !invalid)
        codeNote.html(note ? tswIcon(state === "sent" ? "check" : "timer", "i-sm") + escapeHtml(codeNote.attr("data-" + state)) : "")
    }

    // Runs a change that alters the height of the modal and eases the panel to its new size
    function resizeStage(change) {
        var startHeight = stage[0].getBoundingClientRect().height

        change()

        if (!isModalShown || reduceMotion || transition || !stage[0].animate) {
            return
        }

        var endHeight = stage[0].getBoundingClientRect().height

        if (Math.abs(endHeight - startHeight) > 1) {
            stage.addClass("is-animating")
            stage[0].animate([
                {height: startHeight + "px"},
                {height: endHeight + "px"}
            ], {duration: 200, easing: easeOut}).onfinish = function () {
                if (!transition) {
                    stage.removeClass("is-animating")
                }
            }
        }
    }

    function getStep(name) {
        return stage.children('[data-step="' + name + '"]')
    }

    /*
     * One step is visible at a time. A change crossfades: the old step fades out on top,
     * the new one rises 6px into place and the panel eases to its new height, so nothing
     * in the modal jumps. Repeated calls for the step already shown (the "not connected"
     * polling) change nothing. Reduced motion keeps only the fade.
     */
    function showStep(name) {
        if (name === currentStep) {
            showProgress()
            return
        }

        var from = getStep(currentStep)
        var to = getStep(name)
        var startHeight = stage[0].getBoundingClientRect().height

        settleTransition()
        currentStep = name
        showProgress()
        from.prop("hidden", true)
        to.prop("hidden", false)

        // Nothing to animate while the modal is closed, it resets on the next opening
        if (isModalShown && to[0].animate) {
            crossfade(from, to, startHeight)
        }

        focusStep()
    }

    function crossfade(from, to, startHeight) {
        var easing = easeOut
        var endHeight = stage[0].getBoundingClientRect().height
        var step = {from: from, animations: []}
        var enter

        if (reduceMotion) {
            enter = to[0].animate([{opacity: 0}, {opacity: 1}], {duration: 200, easing: easing})
            step.animations.push(enter)
        } else {
            from.prop("hidden", false).addClass("is-leaving").attr({"inert": "", "aria-hidden": "true"})
            stage.addClass("is-animating")

            // The new step starts 40ms late, so two blocks of text never sit on top of each other at half opacity
            enter = to[0].animate([
                {opacity: 0, transform: "translateY(6px)"},
                {opacity: 1, transform: "none"}
            ], {duration: 200, delay: 40, easing: easing, fill: "backwards"})

            step.animations.push(enter, from[0].animate([{opacity: 1}, {opacity: 0}], {duration: 120, easing: easing, fill: "forwards"}))

            if (Math.abs(endHeight - startHeight) > 1) {
                step.animations.push(stage[0].animate([
                    {height: startHeight + "px"},
                    {height: endHeight + "px"}
                ], {duration: 200, easing: easing}))
            }
        }

        transition = step
        enter.onfinish = function () {
            if (transition === step) {
                settleTransition()
            }
        }
    }

    /*
     * The step row follows the real state: steps before the current one get a check, the current
     * one is in full color with the ink marker under it, a failed one shows a warning. The marker
     * slides to its cell (a transition, so quick changes retarget); while the modal is closed
     * everything jumps, so a new opening starts on the first step without a slide
     */
    function showProgress() {
        var isError = currentStep === "error"
        var index = isError ? errorIndex : stepIndex[currentStep]
        var instant = !isModalShown

        progress.toggleClass("is-instant", instant)

        progressSteps.each(function (i) {
            $(this).toggleClass("is-done", i < index)
                .toggleClass("is-current", i === index)
                .toggleClass("is-error", isError && i === index)
                .attr("aria-current", i === index ? "step" : null)
        })

        // Cells are a third of the row minus the 1px hairlines between them
        progressMarker[0].style.transform = "translateX(calc(" + index + " * (100% + 1px)))"

        if (instant) {
            progressMarker[0].getBoundingClientRect() // apply the jump before transitions come back
            progress.removeClass("is-instant")
        }
    }

    // Jumps a running step change to its end, used before starting the next one
    function settleTransition() {
        if (!transition) {
            return
        }

        var step = transition
        transition = null

        step.animations.forEach(function (animation) {
            animation.cancel()
        })

        step.from.removeClass("is-leaving").removeAttr("inert aria-hidden")
        step.from.prop("hidden", step.from.data("step") !== currentStep)
        stage.removeClass("is-animating")
    }

    // Focus goes where the next action is: the connect link, the first account, the code field
    function focusStep() {
        if (!isModalSettled) {
            return
        }

        var step = getStep(currentStep)
        var target = currentStep === "select-account"
            ? step.find(".login-account").first()
            : step.find("[data-login-focus]").first()

        // Loading has nothing to act on: keep focus in the dialog, not on a button that just disappeared
        var element = target.length ? target[0] : lm[0]
        element.focus({preventScroll: true})
    }
})
