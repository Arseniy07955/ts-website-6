$(function () {
    "use strict"

    var list = $("#faqaccordion")

    if (!list.length) {
        return
    }

    var items = list.children(".faq-item")
    var total = items.length
    var indexBlock = $(".faq-index-block")
    var indexLinks = indexBlock.find("a")
    var search = $("#faq-search")
    var searchClear = $(".faq-search-clear")
    var count = $("[data-faq-count]")
    var noResults = $(".faq-no-results")
    var indexList = indexBlock.find(".faq-index")

    // The side index lists the questions in the same order as the page
    function indexLinkOf(item) {
        return indexLinks.eq(items.index(item))
    }

    function markOpen(item, open) {
        indexLinkOf(item).toggleClass("is-current", open)
    }

    // Lets CSS fade the answer out while Bootstrap collapses its height, and keeps the
    // index in step. Answers can hold admin HTML with collapses of their own, so only react to our own
    list.on("show.bs.collapse hide.bs.collapse hidden.bs.collapse", ".faq-a", function (e) {
        if (e.target !== this) {
            return
        }

        var item = $(this).closest(".faq-item")
        item.toggleClass("is-closing", e.type === "hide")

        if (e.type !== "hidden") {
            markOpen(item, e.type === "show")
            // The visitor opened or closed it, so the search leaves it alone from now on
            item.removeData("searchOpened")
        }
    })

    // Search opens and closes answers at once, without the height animation
    function setOpenInstantly(item, open) {
        item.children(".faq-a").toggleClass("show", open)
        item.find(".faq-toggle").first().toggleClass("collapsed", !open).attr("aria-expanded", open ? "true" : "false")
        markOpen(item, open)
    }

    // A soft wash on the row that a link led to, so the eye finds it (faq.css)
    function wash(item) {
        item.removeClass("is-found")
        void item[0].offsetWidth
        item.addClass("is-found")
    }

    // Removed once it has played, or showing the row again after a search would replay it
    list.on("animationend", ".faq-item", function (e) {
        if (e.originalEvent && e.originalEvent.animationName === "faq-found") {
            $(this).removeClass("is-found")
        }
    })

    // A long index scrolls inside the sticky side column (faq.css); a fade at its lower edge
    // says there is more below, and goes away at the end of the list
    function updateIndexFade() {
        var el = indexList[0]

        if (el) {
            indexList.toggleClass("has-more", el.scrollTop + el.clientHeight < el.scrollHeight - 1)
        }
    }

    indexList.on("scroll", updateIndexFade)
    $(window).on("resize", updateIndexFade)
    updateIndexFade()

    // Heights change once the web font is in
    if (document.fonts && document.fonts.ready) {
        document.fonts.ready.then(updateIndexFade)
    }

    // START Search
    // Lower case, and "yo" matches "ye" the way Russian readers type it
    function normalize(text) {
        return text.toLowerCase().replace(/\u0451/g, "\u0435")
    }

    function unmark(root) {
        $(root).find("mark.faq-mark").each(function () {
            var parent = this.parentNode
            parent.replaceChild(document.createTextNode(this.textContent), this)
            parent.normalize()
        })
    }

    // Wraps every match inside the text nodes of root, so admin HTML (links, bold) stays intact
    function mark(root, terms) {
        var walker = document.createTreeWalker(root, NodeFilter.SHOW_TEXT, null)
        var nodes = []

        while (walker.nextNode()) {
            if (!$(walker.currentNode.parentNode).is("script, style, textarea")) {
                nodes.push(walker.currentNode)
            }
        }

        nodes.forEach(function (node) {
            var text = node.nodeValue
            var lower = normalize(text)
            var ranges = []

            // A few letters change length in lower case; offsets would no longer line up
            if (lower.length !== text.length) {
                return
            }

            terms.forEach(function (term) {
                var at = lower.indexOf(term)

                while (at !== -1) {
                    ranges.push([at, at + term.length])
                    at = lower.indexOf(term, at + term.length)
                }
            })

            if (!ranges.length) {
                return
            }

            ranges.sort(function (a, b) {
                return a[0] - b[0]
            })

            var fragment = document.createDocumentFragment()
            var pos = 0
            var merged = []

            ranges.forEach(function (range) {
                var last = merged[merged.length - 1]

                if (last && range[0] <= last[1]) {
                    last[1] = Math.max(last[1], range[1])
                } else {
                    merged.push(range.slice())
                }
            })

            merged.forEach(function (range) {
                if (range[0] > pos) {
                    fragment.appendChild(document.createTextNode(text.slice(pos, range[0])))
                }

                var el = document.createElement("mark")
                el.className = "faq-mark"
                el.textContent = text.slice(range[0], range[1])
                fragment.appendChild(el)
                pos = range[1]
            })

            if (pos < text.length) {
                fragment.appendChild(document.createTextNode(text.slice(pos)))
            }

            node.parentNode.replaceChild(fragment, node)
        })
    }

    // Filters without motion: rows, index entries and marks change in one go
    function applySearch() {
        var query = $.trim(search.val() || "")
        var terms = normalize(query).split(/\s+/).filter(Boolean)
        var shown = 0

        // Rows shown again would replay their entrance, and open answers their fade-in
        list.addClass("is-searched is-filtering")
        searchClear.prop("hidden", !query)

        items.each(function (i) {
            var item = $(this)
            var question = item.find(".faq-q-text")[0]
            var answer = item.find(".faq-a-inner")[0]
            var link = indexLinks[i]

            unmark(question)
            unmark(answer)
            link && unmark(link)

            var questionText = normalize(question.textContent)
            var answerText = normalize(answer.textContent)

            var matches = terms.every(function (term) {
                return questionText.indexOf(term) !== -1 || answerText.indexOf(term) !== -1
            })

            // Only an answer that holds a match is opened, the question alone is visible anyway
            var openForSearch = matches && terms.some(function (term) {
                return answerText.indexOf(term) !== -1
            })

            item.prop("hidden", !matches)
            link && $(link).parent().prop("hidden", !matches)

            if (matches) {
                shown++

                if (terms.length) {
                    mark(question, terms)
                    mark(answer, terms)
                    link && mark(link, terms)
                }
            }

            // Mid-animation rows are left to Bootstrap
            if (item.children(".faq-a").hasClass("collapsing")) {
                return
            }

            if (openForSearch && !item.children(".faq-a").hasClass("show")) {
                setOpenInstantly(item, true)
                item.data("searchOpened", true)
            } else if (!openForSearch && item.data("searchOpened")) {
                setOpenInstantly(item, false)
                item.removeData("searchOpened")
            }
        })

        list.prop("hidden", shown === 0)
        indexBlock.prop("hidden", shown === 0)
        noResults.prop("hidden", shown !== 0)

        if (shown === 0) {
            noResults.find("[data-faq-empty-title]").text(FAQ_LANG.search_empty.format(query))
        }

        count.text(terms.length ? FAQ_LANG.count_found.format(shown, total) : FAQ_LANG.count.format(total))
        updateIndexFade()

        // Transitions come back once the new state has been painted
        requestAnimationFrame(function () {
            requestAnimationFrame(function () {
                list.removeClass("is-filtering")
            })
        })
    }

    function clearSearch() {
        search.val("")
        applySearch()
    }

    search.on("input", applySearch)

    search.on("keydown", function (e) {
        if (e.key === "Escape" && search.val()) {
            e.preventDefault()
            clearSearch()
        }
    })

    $("[data-faq-clear]").click(function () {
        clearSearch()
        search.trigger("focus")
    })

    // The browser can bring a typed query back with the page
    if (search.val()) {
        applySearch()
    }
    // END Search

    // "#faqN" links (N starts at 1) open their question. On load it opens at once and the
    // row is scrolled into view; a link clicked later on the page opens it the normal way
    function openFromHash(onLoad) {
        var match = /^#faq(\d+)$/.exec(location.hash)

        if (!match) {
            return
        }

        var item = $("#faq" + parseInt(match[1], 10))
        var answer = item.find(".faq-a").first()

        if (!answer.length) {
            return
        }

        // The search hid it, so the browser could not scroll there
        var wasHidden = item.prop("hidden")

        if (wasHidden) {
            clearSearch()
        }

        wash(item)

        if (!onLoad) {
            answer.collapse("show")

            if (wasHidden) {
                item[0].scrollIntoView()
            }

            return
        }

        setOpenInstantly(item, true)

        // The row may still be rising in with its entrance animation, measure where it will settle
        var style = getComputedStyle(item[0])
        var shift = window.DOMMatrixReadOnly ? new DOMMatrixReadOnly(style.transform).m42 : 0
        var top = item[0].getBoundingClientRect().top + window.pageYOffset - shift - (parseFloat(style.scrollMarginTop) || 0)

        window.scrollTo(0, Math.max(0, top))
    }

    openFromHash(true)

    $(window).on("hashchange", function () {
        openFromHash(false)
    })

    // The same index entry twice: the browser scrolls back to the question but sends no "hashchange"
    indexLinks.click(function () {
        if (this.hash === location.hash) {
            openFromHash(false)
        }
    })

    // Copy a link to one answer, confirmed in the button's tooltip and by a check in place of the icon
    list.on("click", ".faq-copy", function () {
        var el = $(this)
        var url = location.href.split("#")[0] + "#faq" + (parseInt(el.data("faqid"), 10) + 1)

        copyText(url, function (copied) {
            el.toggleClass("is-copied", copied)
            updateTooltip(el, copied ? FAQ_LANG.copy_success : FAQ_LANG.copy_error)
            el.tooltip("show")

            // Touch screens have no mouseleave to hide it
            clearTimeout(el.data("tooltipTimeout"))
            el.data("tooltipTimeout", setTimeout(function () {
                el.tooltip("hide")
                el.removeClass("is-copied")
            }, 1600))
        })
    }).on("hidden.bs.tooltip", ".faq-copy", function () {
        updateTooltip($(this), FAQ_LANG.copy_link)
    })
})
