$(function () {
    "use strict"

    var tree = $(".viewer-container")

    if (!tree.length) {
        return
    }

    var reduceMotion = window.matchMedia && window.matchMedia("(prefers-reduced-motion: reduce)").matches
    var canHover = window.matchMedia && window.matchMedia("(hover: hover)").matches

    // START Hide empty channels
    // The saved choice is already applied by inline scripts in viewer.latte, before the first paint
    var hideSwitch = $("[data-hide-empty]")
    var storageKey = "tswebsite_viewer_hide_empty"
    var transitionId = 0
    // Rows that carry a view-transition-name for the running transition
    var namedRows = $()

    hideSwitch.click(function () {
        var hide = hideSwitch.attr("aria-checked") !== "true"

        hideSwitch.attr("aria-checked", hide ? "true" : "false")

        try {
            localStorage.setItem(storageKey, hide ? "1" : "0")
        } catch (e) {}

        setHideEmpty(hide)
    })

    function setHideEmpty(hide) {
        var apply = function () {
            tree.toggleClass("hide-empty", hide)

            // What the search can show depends on it
            if (query) {
                runSearch()
            }
        }

        hideTooltips()
        hidePopovers()

        // A transition still running is about to be skipped: its names go, so none is used twice
        clearRowNames()

        if (reduceMotion || !document.startViewTransition) {
            apply()
            return
        }

        // Many rows appear or disappear at once. Every channel and client near the screen gets its
        // own name for the transition, so the ones that stay glide to their new place instead of
        // jumping; the rest of the page crossfades. Channels are named as whole containers so their
        // hairline guide travels with them
        var id = ++transitionId
        var root = document.documentElement
        var reach = window.innerHeight * 2
        var nearScreen = function () {
            var rect = this.getBoundingClientRect()

            return rect.height > 0 && rect.bottom > -reach && rect.top < reach
        }

        nameRows(tree.find(".channel-container, .client-container").filter(nearScreen), "viewer-row-")

        root.classList.add("viewer-transition")

        var transition = document.startViewTransition(function () {
            apply()

            // Empty channels coming back were off screen a moment ago, so they had no name yet.
            // Named now, they exist only in the new state and fade in on their own
            // (css/pages/viewer.css) instead of arriving with the page crossfade. Everything inside
            // an empty channel is empty too, so the outermost one of each branch is enough
            if (!hide) {
                nameRows(tree.find(".channel-container.not-occupied").filter(function () {
                    return !$(this.parentNode).hasClass("not-occupied")
                }).filter(nearScreen), "viewer-row-new-")
            }
        })

        // A new click skips the running transition; that is expected, not an error
        transition.ready.catch(function () {})

        transition.finished.catch(function () {}).then(function () {
            if (id !== transitionId) {
                return
            }

            clearRowNames()
            root.classList.remove("viewer-transition")
        })
    }

    function nameRows(rows, prefix) {
        rows.each(function (i) {
            this.style.viewTransitionName = prefix + i
            this.style.viewTransitionClass = "viewer-row"
        })

        namedRows = namedRows.add(rows)
    }

    function clearRowNames() {
        namedRows.each(function () {
            this.style.viewTransitionName = ""
            this.style.viewTransitionClass = ""
        })

        namedRows = $()
    }
    // END Hide empty channels

    // START Search
    // Filters the tree as you type, by channel name or nickname. It runs on every key press,
    // so rows appear and disappear at once, without animation
    var main = $(".viewer-main")
    var searchInput = $("#viewer-search")
    var searchClear = $(".viewer-search-clear")
    var searchStatus = $("[data-search-status]")
    var searchEmpty = $("[data-search-empty]")
    var topLevel = tree.children(".channel-container").not(".is-server")
    var highlighted = tree.find(".channel-label, .client-nick")
    var query = ""

    searchInput.on("input", function () {
        applySearch(this.value)
    })

    searchInput.on("keydown", function (e) {
        if (e.key === "Escape" && this.value) {
            e.preventDefault()
            resetSearch()
        }

        // Enter jumps to the first match, the dialog or the details are one more key away
        if (e.key === "Enter" && query) {
            var first = tree.find(".is-match").filter(":visible").first()

            e.preventDefault()
            first.is(".client-container") ? first.trigger("focus") : first.children(".channel").trigger("focus")
        }
    })

    searchClear.click(resetSearch)
    searchEmpty.find("[data-search-reset]").click(resetSearch)

    searchEmpty.find("[data-search-show-empty]").click(function () {
        hideSwitch.trigger("click")
        searchInput.trigger("focus")
    })

    function resetSearch() {
        searchInput.val("")
        applySearch("")
        searchInput.trigger("focus")
    }

    function applySearch(value) {
        var next = fold(value.trim())
        var started = !query && next

        searchClear.prop("hidden", !value)

        if (next === query) {
            return
        }

        query = next
        hideTooltips()
        hidePopovers()
        runSearch()
        keepResultsInView(started)
    }

    function runSearch() {
        var result = {shown: 0, hidden: 0}

        tree.toggleClass("is-searching", query !== "")

        if (!query) {
            tree.find(".is-filtered, .is-match, .is-context").removeClass("is-filtered is-match is-context")
            highlighted.each(function () {
                mark($(this), "")
            })

            searchStatus.text("")
            searchEmpty.prop("hidden", true)
            return
        }

        var hideEmpty = tree.hasClass("hide-empty")

        topLevel.each(function () {
            filterChannel($(this), false, hideEmpty, result)
        })

        // A spacer is a section label: it stays when something in its section is shown.
        // Repeated spacers are only hairlines and step aside while searching
        var spacer = null

        topLevel.each(function () {
            var row = $(this)

            if (row.hasClass("is-spacer")) {
                spacer = row.hasClass("spacer-repeat") ? null : row
            } else if (spacer && !row.hasClass("is-filtered") && !(hideEmpty && row.hasClass("not-occupied"))) {
                spacer.removeClass("is-filtered")
                spacer = null
            }
        })

        searchStatus.text(VIEWER_LANG.search_matches.format(result.shown))
        searchEmpty.prop("hidden", result.shown > 0)

        // Nothing on screen: either nothing matches at all, or only empty channels that are hidden
        if (!result.shown) {
            searchEmpty.find("[data-search-empty-text]")
                .text(VIEWER_LANG.search_empty_text.format(searchInput.val().trim()))
                .prop("hidden", result.hidden > 0)
            searchEmpty.find("[data-search-empty-hidden], [data-search-show-empty]").prop("hidden", !result.hidden)
        }
    }

    /**
     * Shows a channel when its name matches, when something below it matches (so every match keeps
     * its parents), or when a parent matched (a matching channel shows who is in it and its
     * subchannels). Returns whether the channel ends up on screen.
     */
    function filterChannel(container, parentMatched, hideEmpty, result) {
        var isSpacer = container.hasClass("is-spacer")
        // "Hide empty channels" keeps these off screen, and everything below them is empty too
        var offScreen = hideEmpty && container.hasClass("not-occupied")
        var selfMatch = !isSpacer && mark(container.children(".channel").find(".channel-label"), query)
        var showAll = parentMatched || selfMatch
        var visible = showAll

        if (selfMatch) {
            offScreen ? result.hidden++ : result.shown++
        }

        container.children(".client-container").each(function () {
            var client = $(this)
            var match = mark(client.find(".client-nick"), query)

            if (match) {
                result.shown++
                visible = true
            }

            client.toggleClass("is-match", match).toggleClass("is-filtered", !showAll && !match)
        })

        container.children(".channel-container").each(function () {
            if (filterChannel($(this), showAll, hideEmpty, result)) {
                visible = true
            }
        })

        container
            .toggleClass("is-match", selfMatch)
            .toggleClass("is-context", visible && !showAll)
            .toggleClass("is-filtered", !visible)

        return visible && !offScreen
    }

    // Wraps every occurrence in <mark>. Returns whether the text contains the query
    function mark(el, needle) {
        var node = el[0]

        if (!node) {
            return false
        }

        var text = el.data("text")

        if (text === undefined) {
            text = node.textContent
            el.data("text", text)
        }

        var folded = fold(text)
        var index = needle ? folded.indexOf(needle) : -1

        // Lowercasing changed the length (a few rare letters do): match, but don't highlight
        if (index < 0 || folded.length !== text.length) {
            if (el.data("marked")) {
                node.textContent = text
                el.data("marked", false)
            }

            return index >= 0
        }

        var html = ""
        var from = 0

        while (index >= 0) {
            html += escapeHtml(text.slice(from, index)) + '<mark class="viewer-mark">' + escapeHtml(text.slice(index, index + needle.length)) + "</mark>"
            from = index + needle.length
            index = folded.indexOf(needle, from)
        }

        node.innerHTML = html + escapeHtml(text.slice(from))
        el.data("marked", true)

        return true
    }

    // Case-insensitive, and "е" finds "ё": nicknames are often typed without it
    function fold(text) {
        return text.toLocaleLowerCase().replace(/ё/g, "е")
    }

    // The side column stays on screen while the tree scrolls, so the search can be used far down
    // the list; when the results start above the screen, bring their top back into view.
    // In one column the search sits above the tree, and the results start below the fold (under
    // the on-screen keyboard on a phone): when a search starts and its first result is out of
    // sight, lift the field to the top of the screen so the filtered tree follows right under it.
    // Instant, like the filtering itself: it happens while typing
    var oneColumn = window.matchMedia("(max-width: 1000px)")

    function keepResultsInView(started) {
        var headerHeight = $(".site-header").outerHeight() || 0
        var top = main[0].getBoundingClientRect().top

        if (top < headerHeight) {
            window.scrollBy(0, top - headerHeight)
            return
        }

        if (!started || !oneColumn.matches) {
            return
        }

        var first = tree.find(".is-match").filter(":visible").first()

        if (!first.length) {
            first = searchEmpty.filter(":visible")
        }

        // The visual viewport shrinks when the keyboard opens, the layout viewport does not
        var viewport = window.visualViewport
        var visibleBottom = viewport ? viewport.offsetTop + viewport.height : window.innerHeight
        var lift = searchInput[0].getBoundingClientRect().top - headerHeight - 16

        if (first.length && lift > 0 && first[0].getBoundingClientRect().top + 24 > visibleBottom) {
            window.scrollBy(0, lift)
        }
    }
    // END Search

    // START Connect dialog
    var modal = $("#viewer-connect")
    var connectLink = modal.find("[data-connect-link]")
    var lastRow = null

    tree.on("click", ".channel[data-channelid]", function () {
        var row = $(this)
        var container = row.parent()

        if (container.hasClass("is-spacer")) {
            return // spacers are not channels you can join
        }

        var isServer = container.hasClass("is-server")
        var name = row.find(".channel-label").first().text()
        var href = "ts3server://" + TS3_DISPLAY_IP

        if (!isServer) {
            href += "/?cid=" + row.data("channelid")
        }

        // Where the channel sits: its parents, outermost first
        var path = container.parents(".channel-container").map(function () {
            return $(this).children(".channel").find(".channel-label").text()
        }).get().reverse().join(" / ")

        var topic = row.find(".channel-topic").text()

        modal.find("[data-connect-path]").text(path).prop("hidden", !path)
        modal.find("[data-connect-title]").text(isServer ? VIEWER_LANG.connect_server : VIEWER_LANG.connect_channel.format(name))
        modal.find("[data-connect-topic]").text(topic).prop("hidden", !topic)
        modal.find('[data-connect-note="password"]').prop("hidden", !container.hasClass("has-password"))
        modal.find('[data-connect-note="full"]').prop("hidden", !container.hasClass("is-full"))
        connectLink.attr("href", href)

        if (isServer) {
            // Everyone the tree shows; the count is the server's own, it includes hidden channels
            fillPeople(tree.find(".client-container"), VIEWER_LANG.on_server.format(row.find(".channel-count").text()), 8)
        } else if (container.hasClass("is-hidden")) {
            fillPeople($(), VIEWER_LANG.connect_hidden)
        } else {
            var members = container.children(".client-container")

            fillPeople(members, members.length ? VIEWER_LANG.in_channel.format(members.length) : VIEWER_LANG.connect_empty, 6)
        }

        lastRow = row
        hideTooltips()
        modal.modal("show")
    })

    function fillPeople(members, label, maxAvatars) {
        var avatars = modal.find("[data-connect-avatars]").empty()

        members.slice(0, maxAvatars).each(function () {
            avatars.append($(this).find(".avatar").first().clone().removeClass("viewer-avatar"))
        })

        modal.find("[data-connect-count]").text(label)
        modal.find("[data-connect-names]").text(members.map(function () {
            return $(this).find(".client-nick").text()
        }).get().join(", "))
        modal.find("[data-connect-crowd]").prop("hidden", !members.length)
    }

    // Enter or Space on a focused channel opens the dialog, like a button
    tree.on("keydown", ".channel[tabindex]", function (e) {
        if (e.key === "Enter" || e.key === " ") {
            e.preventDefault()
            $(this).trigger("click")
        }
    })

    modal.on("shown.bs.modal", function () {
        connectLink.trigger("focus")
    })

    modal.on("hidden.bs.modal", function () {
        if (lastRow) {
            lastRow.trigger("focus")
            lastRow = null
        }
    })

    // The link hands over to TeamSpeak; the page stays, so close the dialog behind it
    connectLink.click(function () {
        modal.modal("hide")
    })
    // END Connect dialog

    // START Client popover
    var popoverDebounceMs = 250
    var infoCache = {}
    var infoCacheMs = 30 * 1000

    // Bootstrap sanitizes popover content, allow the definition list
    var whiteList = $.extend({}, $.fn.popover.Constructor.Default.whiteList, {dl: [], dt: [], dd: []})

    // The popover belongs to the whole name line: nickname, group prefix and suffix, away message.
    // .client-name is only as wide as that text (css/pages/viewer.css), so "right" puts the
    // popover after all of it, and a cut name anchors it at the ellipsis
    var names = tree.find(".client-container .client-name")

    names.popover({
        title: function () {
            return escapeHtml($(this).find(".client-nick").text())
        },
        content: function () {
            return renderFacts(null)
        },
        html: true,
        whiteList: whiteList,
        template: '<div class="popover viewer-popover" role="tooltip"><h3 class="popover-header"></h3><div class="popover-body"></div></div>',
        // Beside the name where there is room. On phones under it, or above it when the name is
        // near the bottom of the screen: deciding that here instead of leaving it to Popper's flip
        // lets the entrance (css/pages/viewer.css) start on the side the popover really opens on.
        // 180px is the popover (about 150px with a wrapped value) plus its offset and the gutter
        placement: function (tip, name) {
            if (window.innerWidth > 640) {
                return "right"
            }

            var rect = name.getBoundingClientRect()
            var below = window.innerHeight - rect.bottom

            return below < 180 && rect.top > below ? "top" : "bottom"
        },
        boundary: "viewport",
        trigger: "manual",
        // Bootstrap replaces its own modifiers with these, so offset and flip are repeated here.
        // Popper only flips along the list when it starts from an entry of it, hence "right" first.
        // The padding keeps the popover inside the page's 16px gutter instead of Popper's 5px
        popperConfig: {
            modifiers: {
                offset: {offset: "0, 8"},
                flip: {behavior: ["right", "bottom", "top"], padding: 16},
                preventOverflow: {boundariesElement: "viewport", padding: 16}
            }
        }
    })

    if (canHover) {
        // Mouse in / out
        tree.find(".client-container").hover(function () {
            showPopover($(this).find(".client-name"))
        }, function () {
            hidePopover($(this).find(".client-name"))
        })
    } else {
        // Touch: a tap opens the popover, a tap anywhere else closes it
        tree.on("click", ".client-container", function () {
            showPopover($(this).find(".client-name"))
        })

        $(document).on("click", function (e) {
            var current = $(e.target).closest(".client-container").find(".client-name")

            names.filter("[aria-describedby]").not(current).each(function () {
                hidePopover($(this))
            })
        })
    }

    // Keyboard focus (TAB)
    tree.find(".client-container").on("focusin focusout", function (e) {
        if (e.type === "focusin") {
            showPopover($(this).find(".client-name"))
        } else {
            hidePopover($(this).find(".client-name"))
        }
    })

    // Bootstrap tears a popover down when it is shown again while still fading out, so a
    // quick leave and return waits for the fade to finish and then opens it again
    names.on("hidden.bs.popover", function () {
        if ($(this).data("wanted")) {
            showPopover($(this))
        }
    })

    function hidePopover(name) {
        name.data("wanted", false)
        name.popover("hide")
    }

    // Rows are about to move or disappear (search, hide empty channels)
    function hidePopovers() {
        names.filter("[aria-describedby]").each(function () {
            hidePopover($(this))
        })
    }

    function showPopover(name) {
        var shownId = name.attr("aria-describedby")

        name.data("wanted", true)

        // Already showing (focus and click can both ask for it), or still fading out
        if (shownId && $("#" + shownId).length) {
            return
        }

        var cldbid = name.closest("[data-clientdbid]").data("clientdbid")
        var cached = infoCache[cldbid]

        name.popover("show")

        if (!cldbid) {
            return
        }

        if (cached && Date.now() - cached.time < infoCacheMs) {
            updatePopover(name, cached.data)
            return
        }

        // Debounce the hovers
        setTimeout(function () {
            var popoverId = name.attr("aria-describedby")

            if (!popoverId || !$("#" + popoverId).length) {
                return
            }

            $.ajax({
                url: "api/getclientinfo.php",
                data: {cldbid: cldbid},
                dataType: "json",
                success: function (result) {
                    if (!result.success) {
                        updatePopover(name, null, true)
                        return
                    }

                    infoCache[cldbid] = {time: Date.now(), data: result.data}
                    updatePopover(name, result.data)
                },
                error: function () {
                    updatePopover(name, null, true)
                }
            })
        }, popoverDebounceMs)
    }

    function updatePopover(name, data, failed) {
        var id = name.attr("aria-describedby")

        if (!id) {
            return
        }

        var popover = $("#" + id)
        var body = failed ? '<p class="client-facts-error">' + escapeHtml(VIEWER_LANG.info_error) + '</p>' : renderFacts(data)

        if (data) {
            popover.find(".popover-header").text(data.client_nickname)
        }

        popover.find(".popover-body").html(body)
        name.popover("update")
    }

    // Three facts about a person; with no data yet the values are skeleton lines of the same size
    function renderFacts(data) {
        var rows = [
            [VIEWER_LANG.last_active, data && dayjs().subtract(Math.round(data.client_idle_time / 1000), "second").fromNow()],
            [VIEWER_LANG.online_time, data && dayjs.unix(data.client_lastconnected).fromNow(true)],
            [VIEWER_LANG.first_joined, data && dayjs.unix(data.client_created).fromNow()]
        ]

        var html = '<dl class="client-facts' + (data ? " is-loaded" : "") + '">'

        rows.forEach(function (row) {
            html += "<dt>" + escapeHtml(row[0]) + "</dt>"
            html += "<dd>" + (data ? escapeHtml(row[1]) : '<span class="skeleton"></span>') + "</dd>"
        })

        return html + "</dl>"
    }
    // END Client popover

    // A tooltip left open by a tap would float over the dialog or the moving rows
    function hideTooltips() {
        tree.find('[data-toggle="tooltip"][aria-describedby]').tooltip("hide")
    }

    // START Country names
    // The tooltip on the two-letter code gets the country's name in the page language
    var regionNames = null

    try {
        regionNames = new Intl.DisplayNames([document.documentElement.lang || "en"], {type: "region"})
    } catch (e) {}

    if (regionNames) {
        tree.find(".client-country").each(function () {
            var el = $(this)
            var name = null

            try {
                name = regionNames.of(el.text().trim())
            } catch (e) {}

            if (name) {
                updateTooltip(el, name)
            }
        })
    }
    // END Country names
})
