var csrfToken = $('meta[name="csrf-token"]').attr("content")

$(function () {
    "use strict"

    // START string.format
    // parts from https://stackoverflow.com/a/4673436/5381375
    // Replace placeholders like [0] or {0} with arguments
    String.prototype.format = function() {
        var args = arguments
        return this.replace(/({|\[)(\d+)(]|})/g, function(match, x, number) {
            return typeof args[number] != 'undefined' ? args[number] : match
        })
    }
    // END string.format

    // START Time functions
    // Everything below keeps working (menus, theme, copy) if the date library failed to load from the CDN
    if (window.dayjs) {
        dayjs.extend(window.dayjs_plugin_localizedFormat)
        dayjs.extend(window.dayjs_plugin_relativeTime)

        console.log("Day.js locale set to " + dayjs.locale());

        updateRelativeTime();

        setInterval(function () {
            updateRelativeTime();
        }, 1000 * 60);
    }
    // END Time functions

    // START Cookies
    if (!Cookies.get("tswebsite_cookie_consent")) {
        $(".cookiealert").addClass("show");
    }

    $(".acceptcookies").click(function () {
        Cookies.set("tswebsite_cookie_consent", true, {expires: 365});
        $(".cookiealert").removeClass("show");
    });
    // END Cookies

    $('*[data-connectionproblem="trigger"]').click(function (e) {
        e.preventDefault()
        $(this).siblings('*[data-connectionproblem="hidden"]').show();
        $(this).hide();
    });

    // Check if browser supports CSS variables, if not, display an old browser warning
    // Taken from Modernizr, MIT license
    var supportsFn = (window.CSS && window.CSS.supports.bind(window.CSS)) || (window.supportsCSS);
    if (!(!!supportsFn && (supportsFn('--f:0') || supportsFn('--f', 0)))) {
        $(".oldbrowser-alert").show()
    }

    // Add CSRF token to ajax requests
    $.ajaxSetup({
        headers: {
            // Disabled for now - problems with DataTables and dynamic language loading
            // "X-CSRF-TOKEN": getCsrfToken()
        }
    });

    // Initialise tooltips and popovers
    $('[data-toggle="tooltip"]').tooltip();
    $('[data-toggle="popover"]').popover();

    // Display or hide offline admins by default
    $(".admin-status").each(function (key, status) {
        var hide = $(status).data("hidebydefault")

        if (hide) {
            adminStatusDisplayOffline(false)
        }
    })

    // Show / hide offline admins from admin status
    $("[data-adminstatusoffline]").click(function (e) {
        var el = $(this)
        var show = el.data("adminstatusoffline") === "show"
        el.tooltip("hide")
        $("[data-adminstatusoffline]").show()
        el.hide()
        adminStatusDisplayOffline(show)
        $("[data-adminstatusoffline]:visible").trigger("focus")
    })

    // News edit date tooltip
    $('.news-edited').each(function() {
        if (window.dayjs) {
            updateTooltipWithTranslation($(this), timestampToDate($(this).data('timestamp'), true))
        }
    });

    // START Copy to clipboard buttons
    // The big address on the home page confirms inline, small icon buttons use a tooltip
    $("[data-copy]").click(function () {
        var el = $(this)

        copyText(el.data("copy"), function (copied) {
            if (el.hasClass("address")) {
                el.toggleClass("is-copied", copied)
                clearTimeout(el.data("copiedTimeout"))
                el.data("copiedTimeout", setTimeout(function () {
                    el.removeClass("is-copied")
                }, 1800))
                return
            }

            updateTooltip(el, copied ? TSW_LANG.copied : TSW_LANG.copy_error)
            el.tooltip("show")
        })
    }).on("hidden.bs.tooltip", function () {
        var el = $(this)
        updateTooltip(el, el.attr("aria-label"))
    })
    // END Copy to clipboard buttons

    // START Mobile menu
    var menuToggle = $(".menu-toggle")
    var mobileMenu = $("#mobile-menu")

    function setMenuOpen(open) {
        menuToggle.attr("aria-expanded", open ? "true" : "false")
        $("body").toggleClass("menu-open", open)
        clearTimeout(mobileMenu.data("hideTimeout"))

        if (open) {
            mobileMenu.prop("hidden", false)
            mobileMenu[0].offsetWidth // let the closed state apply before the transition starts
            mobileMenu.addClass("is-open")
        } else {
            mobileMenu.removeClass("is-open")
            // Keep it rendered until the fade-out has finished
            mobileMenu.data("hideTimeout", setTimeout(function () {
                mobileMenu.prop("hidden", true)
            }, 200))
        }
    }

    menuToggle.click(function () {
        setMenuOpen(menuToggle.attr("aria-expanded") !== "true")
    })

    $(document).on("keydown", function (e) {
        if (e.key === "Escape" && menuToggle.attr("aria-expanded") === "true") {
            setMenuOpen(false)
            menuToggle.trigger("focus")
        }
    })

    // Close the sheet when the layout goes back to desktop, or when the login modal opens from it
    if (window.matchMedia) {
        var desktop = window.matchMedia("(min-width: 961px)")
        var onDesktopChange = function () {
            if (desktop.matches) {
                setMenuOpen(false)
            }
        }

        desktop.addEventListener ? desktop.addEventListener("change", onDesktopChange) : desktop.addListener(onDesktopChange)
    }

    mobileMenu.find("[data-openLoginModal]").click(function () {
        setMenuOpen(false)
    })
    // END Mobile menu

    // START Theme switch
    var root = document.documentElement
    var themeButtons = $("[data-theme-set]")
    var colorSchemeQuery = window.matchMedia ? window.matchMedia("(prefers-color-scheme: light)") : null

    function getThemeMode() {
        var mode = null

        try {
            mode = localStorage.getItem("tswebsite_theme")
        } catch (e) {}

        return mode || root.getAttribute("data-theme-default") || "dark"
    }

    function applyTheme() {
        var mode = getThemeMode()
        var resolved = mode

        if (mode === "auto") {
            resolved = colorSchemeQuery && colorSchemeQuery.matches ? "light" : "dark"
        }

        root.setAttribute("data-theme", resolved === "light" ? "light" : "dark")
        $('meta[name="theme-color"]').attr("content", getComputedStyle(root).getPropertyValue("--bg").trim())

        themeButtons.each(function () {
            $(this).attr("aria-pressed", $(this).data("theme-set") === mode ? "true" : "false")
        })
    }

    themeButtons.click(function (e) {
        var before = root.getAttribute("data-theme")

        try {
            localStorage.setItem("tswebsite_theme", $(this).data("theme-set"))
        } catch (err) {}

        var reduceMotion = window.matchMedia && window.matchMedia("(prefers-reduced-motion: reduce)").matches

        if (!document.startViewTransition || reduceMotion) {
            applyTheme()
            return
        }

        // Only the rare, deliberate theme change gets a flourish: the new theme grows
        // as a circle out of the button that was pressed
        var rect = this.getBoundingClientRect()
        var x = e.clientX || rect.left + rect.width / 2
        var y = e.clientY || rect.top + rect.height / 2

        root.classList.add("theme-transition")

        var transition = document.startViewTransition(applyTheme)

        transition.ready.then(function () {
            if (root.getAttribute("data-theme") === before) {
                return
            }

            var radius = Math.hypot(Math.max(x, innerWidth - x), Math.max(y, innerHeight - y))

            root.animate({
                clipPath: ["circle(0 at " + x + "px " + y + "px)", "circle(" + radius + "px at " + x + "px " + y + "px)"]
            }, {
                duration: 560,
                easing: "cubic-bezier(.77, 0, .175, 1)",
                pseudoElement: "::view-transition-new(root)"
            })
        })

        // A skipped transition (e.g. the tab got hidden) rejects "ready", that is fine
        transition.ready.catch(function () {})

        transition.finished.then(function () {
            root.classList.remove("theme-transition")
        }, function () {
            root.classList.remove("theme-transition")
        })
    })

    if (colorSchemeQuery) {
        var onSchemeChange = function () {
            if (getThemeMode() === "auto") {
                applyTheme()
            }
        }

        colorSchemeQuery.addEventListener ? colorSchemeQuery.addEventListener("change", onSchemeChange) : colorSchemeQuery.addListener(onSchemeChange)
    }

    applyTheme()
    // END Theme switch

    // START Table of contents
    // Long texts (rules, imprint) get an "on this page" list built from their headings,
    // with the section being read highlighted
    var tocSource = $("[data-toc-source]")
    var toc = $("[data-toc]")
    var headings = tocSource.find("h2, h3")

    if (toc.length && headings.length >= 3) {
        headings.each(function (i) {
            var heading = $(this)

            if (!this.id) {
                this.id = "section-" + (i + 1)
            }

            $("<li></li>")
                .toggleClass("toc-sub", this.tagName === "H3")
                .append($("<a></a>").attr("href", "#" + this.id).text(heading.text()))
                .appendTo(toc)
        })

        toc.closest("[hidden]").prop("hidden", false)

        if ("IntersectionObserver" in window) {
            var tocObserver = new IntersectionObserver(function (entries) {
                entries.forEach(function (entry) {
                    if (entry.isIntersecting) {
                        toc.find("a").removeClass("is-current")
                            .filter('[href="#' + entry.target.id + '"]').addClass("is-current")
                    }
                })
            }, {rootMargin: "-20% 0px -70% 0px"})

            headings.each(function () {
                tocObserver.observe(this)
            })
        }
    }
    // END Table of contents

    // START Scroll reveal
    var revealTargets = $(".js-scroll-reveal")

    if ("IntersectionObserver" in window) {
        var revealObserver = new IntersectionObserver(function (entries) {
            entries.forEach(function (entry) {
                if (entry.isIntersecting) {
                    entry.target.classList.add("is-visible")
                    revealObserver.unobserve(entry.target)
                }
            })
        }, {rootMargin: "0px 0px -8% 0px", threshold: .08})

        revealTargets.each(function () {
            revealObserver.observe(this)
        })
    } else {
        revealTargets.addClass("is-visible")
    }
    // END Scroll reveal

    function adminStatusDisplayOffline(show) {
        var offlineAdmins = $(".admin-status .status-offline")
        show ? offlineAdmins.show() : offlineAdmins.hide()
    }

    // Functions
    function updateRelativeTime() {
        $('[data-relativetime]').each(function () {
            var el = $(this);
            var mode = el.data("relativetime");
            var timestamp = el.data("timestamp");

            var fulldate = timestampToDate(timestamp, true)
            var fuzzydate = timestampToDate(timestamp, false)

            if (mode == "fuzzydate") {
                el.attr("data-toggle", "tooltip");
                el.attr("title", fulldate);
                el.html(fuzzydate);
            } else if (mode == "fulldate") {
                el.html(fulldate);
            }
        });
    }
});

function timestampToDate(timestamp, full) {
    "use strict"

    var fuzzydate = dayjs.unix(timestamp).fromNow()
    var fulldate = dayjs.unix(timestamp).format("LLL")
    return full ? fulldate : fuzzydate
}

function escapeHtml(text) {
    var map = {
        '&': '&amp;',
        '<': '&lt;',
        '>': '&gt;',
        '"': '&quot;',
        "'": '&#039;'
    };

    return text.toString().replace(/[&<>"']/g, function(m) { return map[m]; });
}

// Markup for an icon from img/icons.svg, same as the "icon" function in templates
function tswIcon(name, extraClass) {
    var sprite = $('svg.i use').first().attr("href") || "img/icons.svg"
    var href = sprite.split("#")[0] + "#i-" + name

    return '<svg class="i' + (extraClass ? " " + extraClass : "") + '" aria-hidden="true" focusable="false">' +
        '<use href="' + escapeHtml(href) + '"></use></svg>'
}

function updateTooltip(el, text) {
    if ($(el).attr("title")) {
        $(el).attr("title", text)
    }

    $(el).attr("data-original-title", text)

    var id = $(el).attr("aria-describedby")
    if (id) {
        $("#" + id + " .tooltip-inner").text(text)
    }
}

function updateTooltipWithTranslation(el) {
    var args = Array.prototype.slice.call(arguments);
    args.shift()

    // Pages may leave out some of the fields a script knows how to fill
    if (!el.length || typeof el.data("translation") !== "string") {
        return
    }

    updateTooltip(el, "".format.apply(el.data("translation"), args))
}

// Copies with the async clipboard API when available (needs HTTPS), falls back to execCommand
function copyText(text, callback) {
    if (navigator.clipboard && window.isSecureContext) {
        navigator.clipboard.writeText(text).then(function () {
            callback(true)
        }, function () {
            callback(copyTextToClipboard(text))
        })
    } else {
        callback(copyTextToClipboard(text))
    }
}

function copyTextToClipboard(text) {
    var textArea = document.createElement("textarea")
    textArea.style.position = "fixed"
    textArea.style.top = -999999999
    textArea.style.left = -999999999
    textArea.style.opacity = 0
    textArea.value = text
    document.body.appendChild(textArea)
    textArea.select()

    var success = false

    try {
        success = document.execCommand('copy')
    } catch (err) {}

    document.body.removeChild(textArea)
    return success
}
