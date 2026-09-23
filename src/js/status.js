$(function () {
    "use strict"

    var containers = $("[data-server-status]")

    if (!containers.length) {
        return
    }

    var refreshMs = 10 * 1000
    var timeoutId = null
    var reduceMotion = window.matchMedia && window.matchMedia("(prefers-reduced-motion: reduce)").matches
    var countEl = $("[data-status-count]")

    checkStatus()

    // Don't poll the server while the tab is in the background
    document.addEventListener("visibilitychange", function () {
        if (document.hidden) {
            clearTimeout(timeoutId)
        } else {
            checkStatus()
        }
    })

    function checkStatus() {
        clearTimeout(timeoutId)

        $.ajax({
            url: "api/getstatus.php",
            dataType: "json",
            success: function (json) {
                if (!json.success) {
                    containers.attr("data-state", "error")
                    console.log(json)
                    return
                }

                render(json.data)
                containers.attr("data-state", "online")
            },
            error: function () {
                containers.attr("data-state", "error")
            },
            complete: function () {
                if (!document.hidden) {
                    timeoutId = setTimeout(checkStatus, refreshMs)
                }
            }
        })
    }

    function render(data) {
        if (countEl.length) {
            renderCount(countEl, data.clientsOnline)
            countEl.attr("aria-label", data.clientsOnline + " / " + data.maxClients)
        }

        setText("maxClients", data.maxClients)
        setText("reservedSlots", data.reservedSlots)
        setText("channelCount", data.channelCount)
        setText("freeSlots", Math.max(data.maxClients - data.clientsOnline, 0))

        var fill = data.maxClients > 0 ? Math.min(data.clientsOnline / data.maxClients, 1) : 0

        $("[data-status-level]")
            .css("--level", fill.toFixed(4))
            .attr({"aria-valuenow": data.clientsOnline, "aria-valuemax": data.maxClients})

        setText("name", data.name)
        setText("onlineRecord", data.onlineRecord)
        setText("platform", data.platform)

        if (window.dayjs) {
            updateTooltipWithTranslation(field("onlineRecord"), timestampToDate(data.onlineRecordDate, true))
            setText("onlineRecordDate", dayjs.unix(data.onlineRecordDate).format("LL"))
        }

        setText("uptime", formatUptime(data.uptime))
        setText("ping", formatNumber(data.averagePing, 1) + " " + TSW_LANG.unit_ms)
        setText("packetloss", formatNumber(data.averagePacketloss * 100, 2) + "%")

        // Pages without a separate platform line show it as an icon next to the version
        if (field("platform").length) {
            setText("version", data.version)
        } else {
            field("version").html(escapeHtml(data.version) + getPlatformIcon(data.platform))
            updateTooltipWithTranslation(field("version"), data.version, data.platform)
        }
    }

    function field(name) {
        return $('[data-status="' + name + '"]')
    }

    function setText(name, value) {
        var el = field(name)
        var text = String(value)

        if (el.text() !== text) {
            el.text(text)
        }
    }

    /*
     * The online counter works like a split-flap board: every digit sits in its own
     * clipped box, and a changed digit slides out while the new one slides in, upwards
     * when more people are online and downwards when fewer. It tells at a glance that
     * the number is live and which way it moved.
     */
    function renderCount(el, value) {
        var next = String(value)
        var previous = el.data("value")

        if (previous === next) {
            return
        }

        var firstRender = previous === undefined
        var up = firstRender || Number(next) > Number(previous)
        var prevDigits = firstRender ? [] : previous.split("")
        var offset = next.length - prevDigits.length

        el.data("value", next).empty()

        next.split("").forEach(function (digit, i) {
            var box = $('<span class="digit" aria-hidden="true"></span>')
            var current = $("<span></span>").text(digit)
            var old = prevDigits[i - offset]

            box.append(current)
            el.append(box)

            if (reduceMotion || !current[0].animate || (!firstRender && old === digit)) {
                return
            }

            var timing = {
                duration: firstRender ? 620 : 360,
                delay: firstRender ? 120 + i * 60 : 0,
                easing: "cubic-bezier(.23, 1, .32, 1)",
                fill: "backwards"
            }

            current[0].animate([
                {transform: "translateY(" + (up ? 100 : -100) + "%)", opacity: 0},
                {transform: "none", opacity: 1}
            ], timing)

            if (old !== undefined) {
                var leaving = $('<span class="digit-leaving"></span>').text(old)
                box.append(leaving)

                leaving[0].animate([
                    {transform: "none", opacity: 1},
                    {transform: "translateY(" + (up ? -100 : 100) + "%)", opacity: 0}
                ], {duration: 360, easing: "cubic-bezier(.23, 1, .32, 1)", fill: "forwards"}).onfinish = function () {
                    leaving.remove()
                }
            }
        })
    }

    function formatNumber(value, digits) {
        var number = Number(value) || 0

        try {
            return number.toLocaleString(document.documentElement.lang || undefined, {
                minimumFractionDigits: 0,
                maximumFractionDigits: digits
            })
        } catch (e) {
            return String(Math.round(number * Math.pow(10, digits)) / Math.pow(10, digits))
        }
    }

    function formatUptime(seconds) {
        seconds = Math.max(0, parseInt(seconds, 10) || 0)

        var days = Math.floor(seconds / 86400)
        var hours = Math.floor(seconds % 86400 / 3600)
        var minutes = Math.floor(seconds % 3600 / 60)
        var parts = []

        if (days) {
            parts.push(days + TSW_LANG.unit_days)
        }

        if (days || hours) {
            parts.push(hours + TSW_LANG.unit_hours)
        }

        parts.push(minutes + TSW_LANG.unit_minutes)

        return parts.join(" ")
    }

    function getPlatformIcon(platform) {
        var icons = {
            "windows": "windows-logo",
            "linux": "linux-logo",
            "os x": "apple-logo",
            "macos": "apple-logo"
        }

        var name = icons[String(platform).toLowerCase()]

        return name ? tswIcon(name) : " " + escapeHtml(platform)
    }
})
