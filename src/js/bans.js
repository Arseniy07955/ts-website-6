$(function () {
    "use strict"

    var table = $("#banlist")

    if (!table.length) {
        return
    }

    var list = $(".bans-list")
    var loader = $("#banlist-loader")
    var tip = $("#responsive-table-details-tip")
    var search = $("#bans-search")
    var searchClear = $(".bans-search-clear")
    var toolbar = $(".bans-toolbar")
    var filterOptions = $("[data-ban-filter]")
    // Ban type the table is narrowed to (ip, uid, name, mytsid), empty for all of them
    var typeFilter = ""
    var bansTable = null
    var pagerHadFocus = false
    // Same test as the hover styles in the CSS: a mouse, rather than a finger, is doing the pointing
    var finePointer = window.matchMedia("(hover: hover) and (pointer: fine)")

    formatDates()

    // DataTables comes from a CDN. Without it the bans still show, as a plain table
    if (!$.fn.DataTable) {
        table.find(".ban-col-control, .ban-control").remove()
        list.addClass("is-plain")
        showList()
        toolbar.hide()
        return
    }

    // The type filter: rows carry their ban type in data-ban-type
    $.fn.dataTable.ext.search.push(function (settings, searchData, dataIndex) {
        if (settings.nTable !== table[0] || !typeFilter) {
            return true
        }

        return settings.aoData[dataIndex].nTr.getAttribute("data-ban-type") === typeFilter
    })

    loadLanguage(function (cdnLanguage) {
        // Our own strings sit underneath the CDN translation, so a failed download still reads right
        // (when they are in the visitor's language there is no download at all)
        var language = $.extend(true, {}, BANS_LANG.table, cdnLanguage)

        bansTable = table.DataTable({
            // Only the table, the info line and the pager: search and the type filter live in the toolbar above
            dom: "t<'bans-foot'ip>",
            autoWidth: false,
            order: [
                [3, "desc"]
            ],
            columnDefs: [
                {targets: -1, orderable: false, searchable: false}
            ],
            responsive: {
                details: {
                    type: "column",
                    target: "tr",
                    display: displayDetails,
                    renderer: renderDetails
                }
            },
            language: $.extend(true, {}, language, {
                search: "",
                paginate: {
                    previous: tswIcon("arrow-left") + '<span class="sr-only">' + escapeHtml(language.paginate.previous) + "</span>",
                    next: '<span class="sr-only">' + escapeHtml(language.paginate.next) + "</span>" + tswIcon("arrow-right")
                }
            }),
            infoCallback: formatInfo,
            preDrawCallback: function () {
                // Rows about to leave the page must not leave a tooltip behind
                table.find(".ban-type").tooltip("hide")

                pagerHadFocus = $(document.activeElement).closest(".dataTables_paginate").length > 0
            },
            drawCallback: onDraw,
            initComplete: onReady
        })

        // The "draw" event comes after the pager has been rebuilt, the drawCallback before
        bansTable.on("draw", keepPagerFocus)

        bansTable.on("responsive-resize", updateTip)

        bansTable.on("responsive-display", function (e, api, row, showHide) {
            $(row.node()).find(".ban-toggle").attr("aria-expanded", showHide ? "true" : "false")

            if (showHide) {
                $(row.child()).find('[data-toggle="tooltip"]').tooltip()
            }
        })
    })

    // Tapping the ban type opens the row, whose details carry the exact value: a tooltip on top of
    // that would only get in the way, and the next tap to close it would close the row as well
    table.on("show.bs.tooltip", ".ban-type", function (e) {
        if (table.hasClass("collapsed") && !finePointer.matches) {
            e.preventDefault()
        }
    })

    // START Search
    search.on("input", function () {
        searchClear.prop("hidden", !this.value)

        // Typing before the table is ready is kept and applied in onReady
        if (bansTable) {
            bansTable.search(this.value).draw()
        }
    })

    search.on("keydown", function (e) {
        if (e.key === "Escape" && this.value) {
            e.preventDefault()
            search.val("").trigger("input")
        }
    })

    searchClear.click(function () {
        search.val("").trigger("input").trigger("focus")
    })
    // END Search

    // START Type filter. Frequent, so the table changes at once, without motion
    filterOptions.click(function () {
        setTypeFilter($(this).attr("data-ban-filter"))
    })

    // "Search all bans" under an empty result of a narrowed search
    table.on("click", ".bans-filter-reset", function () {
        setTypeFilter("")
        filterOptions.filter('[data-ban-filter=""]').trigger("focus")
    })

    function setTypeFilter(type) {
        typeFilter = type || ""

        filterOptions.each(function () {
            $(this).attr("aria-pressed", $(this).attr("data-ban-filter") === typeFilter ? "true" : "false")
        })

        // Clicked before the table is ready: onReady applies it
        if (bansTable) {
            bansTable.draw()
        }
    }
    // END Type filter

    // START Responsive tip, dismissed for a year with a cookie
    tip.find(".bans-tip-close").click(function () {
        var hadFocus = $.contains(tip[0], document.activeElement)

        Cookies.set("tswebsite_banrowtip_hide", true, {expires: 365})

        // Fade and fold away (bans.css), then leave the layout for good
        tip.addClass("is-dismissed")
        afterTransition(tip, function () {
            tip.prop("hidden", true)
        })

        // The button is going away, hand focus to the first row control it was talking about
        if (hadFocus) {
            table.find(".ban-toggle:visible").first().trigger("focus")
        }
    })
    // END Responsive tip

    function onReady() {
        // Without a network request for the translation, this runs before DataTable() has returned
        var api = bansTable = this.api()

        showList()

        // Responsive measured the columns while the list was hidden
        api.columns.adjust().responsive.recalc()

        if (search.val() || typeFilter) {
            api.search(search.val()).draw()
        }

        updateTip()
    }

    function onDraw() {
        var api = this.api()
        var info = api.page.info()
        var container = $(api.table().container())

        // A single page needs no pager, and no results need no count: the message below says it
        container.find(".dataTables_paginate").prop("hidden", info.pages <= 1)
        container.find(".dataTables_info").prop("hidden", !info.recordsDisplay)

        // Nothing matches the search: say what was searched for, and where search looks. When the
        // search was narrowed to one ban type, say that too and offer the whole list
        if (!info.recordsDisplay && info.recordsTotal) {
            var message = '<p class="bans-no-results-title">' + "".format.apply(BANS_LANG.searchEmpty, [escapeHtml(api.search())]) + "</p>"

            if (typeFilter) {
                message += '<p class="bans-no-results-hint">' +
                    "".format.apply(BANS_LANG.searchEmptyFiltered, [escapeHtml(BANS_LANG.types[typeFilter] || typeFilter)]) + "</p>" +
                    '<button type="button" class="btn btn-secondary btn-sm bans-filter-reset">' + escapeHtml(BANS_LANG.filterReset) + "</button>"
            } else {
                message += '<p class="bans-no-results-hint">' + BANS_LANG.searchEmptyHint + "</p>"
            }

            table.find("td.dataTables_empty").html(message)
        }
    }

    // Where the site ships the table strings in the visitor's language, the count reads naturally:
    // everything fits on one page, a search found some of the bans, or this is one page of many
    function formatInfo(settings, start, end, max, total, pre) {
        if (!BANS_LANG.ownStrings) {
            return pre
        }

        var text = total < max ? BANS_LANG.infoMatches : start === 1 && end === total ? BANS_LANG.infoAll : pre

        return text.replace(/_TOTAL_/g, settings.fnFormatNumber.call(settings, total))
            .replace(/_MAX_/g, settings.fnFormatNumber.call(settings, max))
    }

    // The pager is rebuilt on every draw. Paging with an arrow can leave that arrow unavailable, which
    // bans.css hides, so DataTables can't give it focus back and focus would fall to the page body.
    // The current page number takes it instead
    function keepPagerFocus() {
        var active = document.activeElement

        if (pagerHadFocus && (!active || active === document.body)) {
            $(bansTable.table().container()).find(".page-item.active .page-link").trigger("focus")
        }

        pagerHadFocus = false
    }

    function showList() {
        loader.remove()
        list.prop("hidden", false)
    }

    function updateTip() {
        if (!bansTable || tip.hasClass("is-dismissed")) {
            return
        }

        // Responsive's hasHidden() also counts the control column, which is hidden exactly when nothing
        // else is. The "collapsed" class it sets on the table leaves the control column out
        tip.prop("hidden", !table.hasClass("collapsed") || !!Cookies.get("tswebsite_banrowtip_hide"))
    }

    // Child rows open and close like an accordion (bans.css). Transitions rather than keyframes,
    // so tapping a row again halfway turns the motion around instead of starting over.
    // Same contract as Responsive's own display.childRow
    function displayDetails(row, update, render) {
        var node = $(row.node())
        var wrap = row.child.isShown() ? $(row.child()).find(".ban-details-wrap") : $()

        // Paging, searching and resizing re-render an open row in place, without motion
        if (update) {
            if (node.hasClass("parent")) {
                row.child(render(), "child").show()
                return true
            }

            return
        }

        if (node.hasClass("parent")) {
            node.removeClass("parent")
            wrap.addClass("is-collapsed")
            afterTransition(wrap, function () {
                if (wrap.hasClass("is-collapsed")) {
                    row.child(false)
                }
            })
            return false
        }

        node.addClass("parent")

        // Still folding away from a moment ago: unfold it from where it is
        if (wrap.length) {
            wrap.removeClass("is-collapsed")
            return true
        }

        row.child(render(), "child").show()
        wrap = $(row.child()).find(".ban-details-wrap")
        wrap.addClass("is-collapsed")
        wrap[0].offsetHeight // commit the folded state so the unfolding transitions
        wrap.removeClass("is-collapsed")
        return true
    }

    // Calls back once the fold has finished (the fade, where reduced motion leaves no fold), or after
    // a fallback delay: an element paged away mid-transition never reports back
    function afterTransition(el, callback) {
        var property = getComputedStyle(el[0]).transitionProperty.indexOf("grid-template-rows") !== -1 ? "grid-template-rows" : "opacity"
        var fallback = setTimeout(done, 400)

        el.off("transitionend.bans").on("transitionend.bans", function (e) {
            if (e.target === this && e.originalEvent.propertyName === property) {
                done()
            }
        })

        function done() {
            clearTimeout(fallback)
            el.off("transitionend.bans")
            callback()
        }
    }

    // Child row: the hidden columns as a "label | value" list, like the rows elsewhere on the site
    function renderDetails(api, rowIdx, columns) {
        var items = $.map(columns, function (col) {
            if (!col.hidden) {
                return null
            }

            var cellClass = $(api.cell(rowIdx, col.columnIndex).node()).attr("class") || ""

            return '<div class="ban-detail"><dt>' + $.trim(col.title) + '</dt>' +
                '<dd class="' + escapeHtml(cellClass) + '">' + col.data + "</dd></div>"
        }).join("")

        if (!items) {
            return false
        }

        // The exact IP, UID or MyTSID: behind the ban type's tooltip when the nickname is known, and cut
        // to one line on phones (bans.css) when the ID stands in for the name
        var target = $(api.cell(rowIdx, 0).node())
        var type = target.find(".ban-type[data-toggle]")
        var id = target.find(".ban-name.ban-id")

        if (type.length) {
            items += '<div class="ban-detail"><dt>' + escapeHtml(type.text()) + '</dt>' +
                '<dd class="ban-detail-id">' + escapeHtml(type.attr("data-original-title") || type.attr("title")) + "</dd></div>"
        } else if (id.length) {
            items += '<div class="ban-detail"><dt>' + escapeHtml(target.find(".ban-type").text()) + '</dt>' +
                '<dd class="ban-detail-id">' + escapeHtml(id.text()) + "</dd></div>"
        }

        // The clip is what folds: the list keeps its spacing inside it, so the row folds all the way to its edge
        return $('<div class="ban-details-wrap"><div class="ban-details-clip"><dl class="ban-details"></dl></div></div>').find("dl").append(items).end()
    }

    // Ban date in the site language: the day, and the time under it
    function formatDates() {
        table.find("[data-ban-date]").each(function () {
            var el = $(this)
            var date = dayjs.unix(el.data("ban-date"))

            el.find(".ban-day").text(date.format("ll"))
            el.find(".ban-time").text(date.format("LT"))
        })
    }

    // DataTables' translation lives on its CDN, needed only for languages the site doesn't ship
    // the table strings in. A missing or slow file must not keep the skeleton on screen, so it
    // gets a timeout and the table goes on with BANS_LANG.table
    function loadLanguage(done) {
        var name = typeof DATATABLES_LANGUAGE_NAME === "string" ? DATATABLES_LANGUAGE_NAME : ""

        if (!name || name === "English" || BANS_LANG.ownStrings) {
            done({})
            return
        }

        $.ajax({
            url: "https://cdn.datatables.net/plug-ins/1.10.19/i18n/" + encodeURIComponent(name) + ".json",
            dataType: "json",
            timeout: 4000
        }).done(function (json) {
            done($.isPlainObject(json) ? json : {})
        }).fail(function () {
            console.log("DataTables translation \"" + name + "\" could not be loaded, using the built-in strings")
            done({})
        })
    }
})
