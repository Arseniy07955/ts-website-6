$(function () {
    "use strict"

    var root = document.documentElement
    var reduceMotion = window.matchMedia && window.matchMedia("(prefers-reduced-motion: reduce)").matches
    var lang = window.ASSIGNER_LANG || {}

    var form = $("#assigner-form")
    var categories = form.find(".assigner-category")
    var inputs = form.find(".assigner-input")
    var total = $(".assigner-total")
    var bar = form.find(".assigner-bar")
    var barAnchor = form.find(".assigner-bar-anchor")
    var saveButton = form.find(".assigner-save")
    var resetButton = form.find(".assigner-reset")
    var idleNote = form.find(".assigner-idle")
    var dirtyNote = form.find(".assigner-dirty")
    var dirtyText = form.find(".assigner-dirty-text")
    var invalidNote = form.find(".invalid-groups-alert")
    var savingNote = form.find(".assigner-saving")

    var locked = form.hasClass("is-locked")
    var dirty = false
    var invalid = false
    var saving = false
    // Save bar state; declared up here because the first recalculation below already sets it
    var barActive = false
    var leaveTimer = null

    // Bound to every input rather than delegated: scripts that dispatch a plain
    // (non-bubbling) "change" event should still update the counters
    inputs.on("change", function () {
        recalculateGroups()
    })

    // Back to what the server has. The ticks come from the markup (defaultChecked):
    // browsers may restore the last unsaved ticks into the page on reload
    resetButton.on("click", function () {
        var changed = changedInputs()

        changed.each(function () {
            this.checked = this.defaultChecked
        })

        recalculateGroups()

        // The button is about to disappear with the bar; keep the keyboard where the edits were
        if (changed.length && document.activeElement === this) {
            changed[0].focus({preventScroll: true})
        }
    })

    form.on("submit", function (e) {
        if (!saveButton.length || saveButton.prop("disabled") || saveButton.hasClass("is-saving")) {
            e.preventDefault()
            return
        }

        setSaving(true)
        skipPageTransition()
    })

    // The form posts to this same address. Chromium aborts a cross-document view transition
    // to the same address with an uncaught error, and the page it would animate to is this one
    function skipPageTransition() {
        window.addEventListener("pageswap", function (e) {
            if (e.viewTransition) {
                e.viewTransition.skipTransition()
            }
        }, {once: true})
    }

    // Coming back with the back button restores the page as it was left, spinner included
    window.addEventListener("pageshow", function (e) {
        if (e.persisted) {
            setSaving(false)
            recalculateGroups()
        }
    })

    recalculateGroups(true)

    function recalculateGroups(initial) {
        var selected = 0
        invalid = false

        categories.each(function () {
            var category = $(this)
            // The max from the config, capped by how many groups the category has (the template works it out)
            var limit = Number(category.data("limit"))
            var usedGroups = category.find(".assigner-input:checked").length
            var over = usedGroups > limit

            selected += usedGroups
            renderCount(category.find(".assigner-used"), usedGroups)

            category.toggleClass("is-over", over)
            category.find(".assigner-slot").each(function (i) {
                this.classList.toggle("is-filled", i < usedGroups)
            })
            category.find(".assigner-meter.is-continuous").each(function () {
                this.style.setProperty("--fill", Math.min(usedGroups / limit, 1))
            })

            // A locked form (cooldown) keeps the plain limit the server printed
            if (!locked) {
                category.find(".assigner-hint").text(hintFor(usedGroups, limit))
            }

            if (over) {
                invalid = true
            }
        })

        renderCount(total, selected)

        var changes = changedInputs().length
        dirty = changes > 0

        // Mark the lines that differ from the server, so the count in the bar has a face
        inputs.each(function () {
            this.parentNode.classList.toggle("is-changed", this.checked !== this.defaultChecked)
        })

        if (changes && lang.changes) {
            dirtyText.text(lang.changes.format(changes))
        }

        form.toggleClass("is-dirty", dirty)
        saveButton.prop("disabled", invalid)
        resetButton.prop("disabled", saving || !dirty)
        renderNotes()
        setBarActive(dirty || invalid, initial)
    }

    function changedInputs() {
        return inputs.filter(function () {
            return this.checked !== this.defaultChecked
        })
    }

    function hintFor(used, limit) {
        if (used > limit) {
            return lang.hintOver ? lang.hintOver.format(used - limit) : ""
        }

        if (used === limit) {
            return lang.hintFull || ""
        }

        return lang.hintLeft ? lang.hintLeft.format(limit - used) : ""
    }

    function renderNotes() {
        idleNote.toggleClass("is-visible", !saving && !dirty && !invalid)
        dirtyNote.toggleClass("is-visible", !saving && dirty && !invalid)
        invalidNote.toggleClass("is-visible", !saving && invalid)
        savingNote.toggleClass("is-visible", saving)
    }

    function setSaving(value) {
        saving = value
        saveButton.toggleClass("is-saving", saving).attr("aria-disabled", saving ? "true" : null)
        form.attr("aria-busy", saving ? "true" : null)
        // The request is on its way; undoing the ticks now would only mislead
        resetButton.prop("disabled", saving || !dirty)
        renderNotes()
    }

    // START Save bar
    /*
     * The bar is the last row of the form. While there is something to save it turns
     * sticky, so it stays at the bottom of the viewport however far down the groups go.
     * When that makes it appear (its own place is still below the fold) it slides up
     * from the bottom edge, and it slides back down once the changes are gone.
     * Transitions, not keyframes: ticking and unticking quickly retargets the slide
     */
    function barIsOffscreen() {
        // The anchor sits right after the bar: its top is where the bar ends in the page
        return barAnchor.length && barAnchor[0].getBoundingClientRect().top > window.innerHeight + 1
    }

    function setBarActive(active, instant) {
        if (!bar.length || active === barActive) {
            return
        }

        barActive = active
        clearTimeout(leaveTimer)

        if (active) {
            if (bar.hasClass("is-leaving")) {
                // Turned around halfway down: the transition carries it back up from there
                bar.removeClass("is-leaving")
                reserveBarSpace()
                return
            }

            var slideIn = !instant && barIsOffscreen()
            bar.addClass("is-sticky")
            reserveBarSpace()

            if (slideIn) {
                bar.addClass("is-entering")
                bar[0].offsetWidth // commit the start position before the transition runs
                bar.removeClass("is-entering")
            }

            return
        }

        reserveBarSpace()

        if (instant || !barIsOffscreen()) {
            bar.removeClass("is-sticky is-leaving")
            return
        }

        bar.addClass("is-leaving")
        leaveTimer = setTimeout(settle, 200)
    }

    /*
     * While the bar covers the bottom of the viewport, keyboard focus (and anything else that
     * scrolls into view) has to stop above it, or a focused group ends up hidden behind it.
     * The height changes when the bar wraps (narrow screens, the longer notes), so it is measured
     */
    function reserveBarSpace() {
        root.style.scrollPaddingBottom = barActive ? bar[0].offsetHeight + 16 + "px" : ""
    }

    if (bar.length && window.ResizeObserver) {
        new ResizeObserver(reserveBarSpace).observe(bar[0])
    }

    // Back to its own place below the grid, out of view: no transition on the way there
    function settle() {
        bar.addClass("is-instant").removeClass("is-sticky is-leaving")
        bar[0].offsetWidth
        bar.removeClass("is-instant")
    }
    // END Save bar

    /*
     * A count rolls in from the direction it moved, upwards when a group was added
     * and downwards when one was removed, the way the online counter on the home
     * page does. It ties the click on a line to the number that limits it.
     */
    function renderCount(el, value) {
        if (!el.length) {
            return
        }

        var next = String(value)
        var previous = String(el.data("value"))

        if (previous === next) {
            return
        }

        var up = Number(next) > Number(previous)
        var current = $("<span></span>").text(next)

        // Emptying drops a roll still in flight, so rapid clicks never queue up
        el.data("value", next).empty().append(current)

        if (reduceMotion || !current[0].animate) {
            return
        }

        var leaving = $('<span class="is-leaving" aria-hidden="true"></span>').text(previous)
        el.append(leaving)

        var timing = {duration: 180, easing: "cubic-bezier(.23, 1, .32, 1)"}

        current[0].animate([
            {transform: "translateY(" + (up ? 100 : -100) + "%)", opacity: 0},
            {transform: "none", opacity: 1}
        ], timing)

        leaving[0].animate([
            {transform: "none", opacity: 1},
            {transform: "translateY(" + (up ? -100 : 100) + "%)", opacity: 0}
        ], $.extend({fill: "forwards"}, timing)).onfinish = function () {
            leaving.remove()
        }
    }

    // START Notices
    // Dismissing a notice would make everything under it jump up. A same-document view
    // transition lets it fade while the rest glides into place; without support (or with
    // reduced motion) Bootstrap's own fade-and-remove runs
    $(".assigner-notice").on("close.bs.alert", function (e) {
        if (!document.startViewTransition || reduceMotion) {
            return
        }

        e.preventDefault()

        var notice = $(this)
        // Its neighbours in the hero column, the side column, and every section below the hero
        var moving = notice.nextAll()
            .add(notice.closest(".assigner-hero").children(".split-side"))
            .add(notice.closest(".assigner-hero").nextAll())
        var stickyBar = bar.filter(".is-sticky")

        this.style.viewTransitionName = "assigner-vt-leaving"
        moving.each(function (i) {
            this.style.viewTransitionName = "assigner-vt-" + i
        })

        // A bar stuck to the viewport stays where it is instead of riding along with the form
        if (stickyBar.length) {
            stickyBar[0].style.viewTransitionName = "assigner-vt-bar"
        }

        root.classList.add("assigner-vt")

        var transition = document.startViewTransition(function () {
            notice.remove()
            keepFocus()
        })

        // Another transition (a theme change, say) may skip this one; that is not an error
        transition.ready.catch(function () {})

        transition.finished.then(function () {
            moving.add(stickyBar).each(function () {
                this.style.viewTransitionName = ""
            })

            root.classList.remove("assigner-vt")
        })
    }).on("closed.bs.alert", keepFocus)

    // The close button took focus with it; continue from the top of the page content
    // so the next Tab lands on the first group instead of the start of the document
    function keepFocus() {
        if (!document.activeElement || document.activeElement === document.body) {
            document.getElementById("main").focus({preventScroll: true})
        }
    }
    // END Notices

    // START Cooldown
    var cooldownElement = $("#cooldown-timer")

    if (cooldownElement.length) {
        // Count against the clock, not the ticks: background tabs throttle timers
        var endsAt = Date.now() + Number(cooldownElement.data("seconds")) * 1000

        var updateTimer = function () {
            var secondsLeft = Math.max(0, Math.ceil((endsAt - Date.now()) / 1000))
            var minutes = Math.floor(secondsLeft / 60).toString().padStart(2, "0")
            var seconds = Math.floor(secondsLeft % 60).toString().padStart(2, "0")

            cooldownElement.text(minutes + ":" + seconds)
            return secondsLeft
        }

        var interval = setInterval(function () {
            if (updateTimer() <= 0) {
                clearInterval(interval)
                // A GET of the same address, so a page that came from a save isn't posted again
                skipPageTransition()
                location.replace(location.href.split("#")[0])
            }
        }, 1000)
    }
    // END Cooldown
})
