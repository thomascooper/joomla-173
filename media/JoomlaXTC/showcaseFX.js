if (typeof showcaseFX != "function") {
    function showcaseFX(a, b) {
        var c = $(a);
        if (c) {
            var d = c.getElement(".showcase" + a);
            if (d) {
                var e = d.getElements(".sframe" + a);
                if (e.length > 1) {
                    var f = e.length - 1;
                    var g = 0;
                    var h = 0;
                    var i = 0;
                    var j = {};
                    var k;
                    var l = b.fxpause == -1 ? true : false;
                    var m;
                    d.setStyles({
                        position: "relative",
                        display: "block",
                        visibility: "visible",
                        overflow: "hidden",
                        "z-index": b.fxlayer
                    });
                    e.each(function (a, b) {
                        h = a.getSize().y > h ? a.getSize().y : h;
                        i = a.getSize().x > i ? a.getSize().x : i
                    });
                    e.setStyles({
                        position: "absolute",
                        display: "block",
                        visibility: "visible"
                    });
                    d.setStyles({
                        width: i,
                        height: h
                    });
                    e.setStyles({
                        width: i,
                        height: h
                    });
                    if (window.ie) {
                        var n = d.getElements("img.imgpngfix");
                        n.each(function (a) {
                            var b = a.src.toUpperCase();
                            if (b.substring(b.length - 3, b.length) == "PNG") {
                                var c = a;
                                var d = c.width;
                                var e = c.height;
                                var f = c.src;
                                var g = c.id ? "id='" + c.id + "' " : "";
                                var h = c.className ? "class='" + c.className + "' " : "";
                                var i = c.title ? "title='" + c.title + "' " : "title='" + c.alt + "' ";
                                var j = "display:inline-block;" + c.style.cssText;
                                if (c.align == "left") j = "float:left;" + j;
                                if (c.align == "right") j = "float:right;" + j;
                                if (c.parentElement.href) j = "cursor:hand;" + j;
                                var k = "<span " + g + h + i + ' style="' + "width:" + d + "px; height:" + e + "px;" + j + ";" + "filter:progid:DXImageTransform.Microsoft.AlphaImageLoader" + "(src='" + c.src + "', sizingMethod='scale');\"></span>";
                                c.outerHTML = k
                            }
                        })
                    }
                    var o = c.getElements(".prev" + a);
                    var p = c.getElements(".next" + a);
                    var q = c.getElements(".pag" + a);
                    switch (b.fxmode) {
                        case "fade":
                            k = new Fx.Elements(e, {
                                duration: b.fxspeed,
                                wait: true,
                                transition: b.fxtype,
                                onComplete: function () {
                                    l = true
                                }
                            });
                            var r = 0;
                            var s = 1;
                            e.setStyles({
                                opacity: r
                            });
                            e[0].setStyles({
                                opacity: s
                            });
                            var t = {
                                opacity: s
                            };
                            var u = {
                                opacity: r
                            };
                            var v = {
                                opacity: s
                            };
                            var w = {
                                opacity: r
                            };
                            break;
                        case "slideHor":
                            k = new Fx.Elements(e, {
                                duration: b.fxspeed,
                                wait: true,
                                transition: b.fxtype,
                                onComplete: function () {
                                    l = true
                                }
                            });
                            var x = b.fxflow == "LR" ? 1 : -1;
                            var r = x * i;
                            var y = 0;
                            var s = -r;
                            e.setStyles({
                                left: r
                            });
                            e[0].setStyles({
                                left: y
                            });
                            var t = {
                                left: [r, y]
                            };
                            var u = {
                                left: [y, s]
                            };
                            var v = {
                                left: [s, y]
                            };
                            var w = {
                                left: [y, r]
                            };
                            break;
                        case "slideVer":
                            k = new Fx.Elements(e, {
                                duration: b.fxspeed,
                                wait: true,
                                transition: b.fxtype,
                                onComplete: function () {
                                    l = true
                                }
                            });
                            var x = b.fxflow == "TB" ? 1 : -1;
                            var r = -x * h;
                            var y = 0;
                            var s = -r;
                            e.setStyles({
                                top: r
                            });
                            e[0].setStyles({
                                top: y
                            });
                            var t = {
                                top: [r, y]
                            };
                            var u = {
                                top: [y, s]
                            };
                            var v = {
                                top: [s, y]
                            };
                            var w = {
                                top: [y, r]
                            };
                            break
                    }
                    e.each(function (a, b) {
                        a.setStyles({
                            "z-index": b
                        })
                    });

                    function z(a) {
                        if (q.length > 1) {
                            q.each(function (a, b) {
                                a.removeClass("current")
                            });
                            q[a].addClass("current")
                        }
                    }
                    function A() {
                        f = f == e.length - 1 ? 0 : f + 1;
                        g = g == e.length - 1 ? 0 : g + 1;
                        j = {};
                        j[f] = u;
                        j[g] = t;
                        k.start(j);
                        z(g)
                    }
                    function B() {
                        j = {};
                        j[f] = v;
                        j[g] = w;
                        k.start(j);
                        f--;
                        g--;
                        f = f < 0 ? e.length - 1 : f;
                        g = g < 0 ? e.length - 1 : g;
                        z(g)
                    }
                    function C(a) {
                        j = {};
                        j[g] = u;
                        j[a] = t;
                        k.start(j);
                        f = a - 1;
                        g = a;
                        f = f < 0 ? e.length - 1 : f;
                        g = g < 0 ? e.length - 1 : g;
                        z(g)
                    }
                    function D() {
                        A();
                        l = false
                    }
                    o.each(function (a, c) {
                        a.addEvent("click", function (a) {
                            a = (new Event(a)).stop();
                            if (l) {
                                B();
                                $clear(m);
                                if (b.fxpause != -1) m = D.periodical(b.fxspeed + b.fxpause);
                                l = false
                            }
                        })
                    });
                    p.each(function (a, c) {
                        a.addEvent("click", function (a) {
                            a = (new Event(a)).stop();
                            if (l) {
                                A();
                                $clear(m);
                                if (b.fxpause > 0) m = D.periodical(b.fxspeed + b.fxpause);
                                l = false
                            }
                        })
                    });
                    q.each(function (a, c) {
                        a.addEvent("click", function (a) {
                            a = (new Event(a)).stop();
                            if (l) {
                                if (c != g) {
                                    C(c);
                                    $clear(m);
                                    if (b.fxpause > 0) m = D.periodical(b.fxspeed + b.fxpause);
                                    l = false
                                }
                            }
                        })
                    });

                    function E() {
                        if (b.fxpause > 0) {
                            m = D.periodical(b.fxspeed + b.fxpause)
                        }
                    }
                    E();
                    z(0)
                } else {
                    d.setStyles({
                        position: "relative",
                        display: "block",
                        visibility: "visible",
                        "z-index": b.fxlayer
                    })
                }
            }
        }
    }
}
if (typeof jxtchover != "function") {
    function jxtchover(a, b, c) {
        var e = $(a);
	if (e == null) {
		return;
	}else{
		var d = e.getElements(".js_hover");
	}
        d.each(function (a) {
            a.setStyles({
                "background-color": "#" + c
            });
            var d = new Fx.Morph(a, {
                duration: 200,
                wait: false
            });
            a.addEvent("mouseenter", function () {
                d.start({
                    "background-color": "#" + b
                })
            });
            a.addEvent("mouseleave", function () {
                d.start({
                    "background-color": "#" + c
                })
            })
        })
    }
}
if (typeof jxtcpops != "function") {
    function jxtcpops(a, b) {
        var c = $(a);
	if (c == null) {
		return;
	}else{
        	var d = c.getElements(".popuphover");
	}
        var e = 0;
        var f = 0;
        var g = 0;
        var h = 0;
        var i = new Element("div", {
            styles: {
                opacity: 0,
                display: "none"
            }
        });
        i.injectInside(document.body);
        i.addClass("jxtcpopup");
        var j = new Element("div");
        j.addClass("jxtcinner");
        var k = new Element("div", {
            title: "Close"
        });
        k.addClass("jxtcpopupclose");
        var l = new Element("div", {
            title: "Move"
        });
        l.addClass("jxtcpopupdrag");
        k.injectInside(i);
        l.injectInside(i);
        j.injectInside(i);
        var m = new Fx.Morph(i, {
            duration: b.durationin,
            transition: b.fxtype,
            wait: false
        });
        k.addEvent("click", function () {
            m.start({
                top: window.getScrollTop() + e,
                left: window.getScrollLeft() + g,
                opacity: b.opacityout
            }).chain(function () {
                i.setStyles({
                    display: "none"
                })
            })
        });
        d.each(function (a, c) {
            var d = a.getElement(".pop");
            d.setStyles({
                display: "none"
            });
            k.addEvent("click", function () {
                (function () {
                    d.setStyles({
                        display: "none"
                    });
                    a.adopt(d)
                }).delay(b.durationin)
            });
            a.addEvent("click", function () {
                i.setStyles({
                    position: "absolute",
                    display: "block"
                });
                i.makeDraggable();
                j.adopt(d);
                d.setStyles({
                    display: "block"
                });
                i.setStyles({
                    height: "auto",
                    top: window.getScrollTop() + b.verticalout + "px",
                    left: window.getScrollLeft() + b.horizontalout + "px"
                });
                if (b.centered == "1") {
                    var a = i.getSize().x;
                    var c = i.getSize().y;
                    i.setStyles({
                        top: window.getScrollTop() + (window.getHeight() - i.getSize().y) / 2 + "px",
                        left: (window.getScrollLeft() + window.getWidth() - a) / 2 + "px"
                    });
                    e = f = (window.getHeight() - i.getSize().y) / 2;
                    g = h = (window.getScrollLeft() + window.getWidth() - a) / 2
                }
                m.start({
                    top: window.getScrollTop() + f,
                    left: window.getScrollLeft() + h,
                    opacity: b.opacityin
                });
                j.setStyles({
                    width: j.getSize().x,
                    height: j.getSize().y
                })
            })
        })
    }
}
if (typeof jxtctips != "function") {
    function jxtctips(a, b) {
        var c = $(a);
	if (c == null) {
		return;
	}else{
        	var d = c.getElements(".jxtctooltip");
	}
        d.each(function (a, c) {
            var d = a.getElement(".tip");
            if (d != null) {
                a.setStyles({
                    position: "relative"
                });
                d.setStyles({
                    opacity: 0,
                    display: "block",
                    position: "absolute",
                    "z-index": 9999,
                    top: b.verticalout,
                    left: b.horizontalout
                });
                var e = new Fx.Morph(d, {
                    duration: b.durationin,
                    transtion: b.fxtype,
                    wait: false
                });
                var f = new Fx.Morph(d, {
                    duration: b.durationout,
                    transtion: b.fxtype,
                    wait: false
                });
                var g = new Fx.Morph(d, {
                    duration: b.pause,
                    wait: true
                });
                a.addEvent("mouseenter", function () {
                    e.start({
                        opacity: b.opacityin,
                        top: b.verticalin + "px",
                        left: b.horizontalin + "px"
                    })
                });
                a.addEvent("mouseleave", function () {
                    g.start({}).chain(function () {
                        f.start({
                            opacity: b.opacityout,
                            top: b.verticalout + "px",
                            left: b.horizontalout + "px"
                        })
                    })
                });
                d.addEvent("mouseenter", function () {
                    g.pause();
                    e.start({
                        opacity: b.opacityin,
                        top: b.verticalin + "px",
                        left: b.horizontalin + "px"
                    })
                })
            }
        })
    }
}
if (typeof slidebox != "function") {
    function slidebox(a, b, c, d) {
        var e = $(a);
	if (e == null) {
		return;
	}else{
        	var f = e.getElements(".slidebox");
	}
        var g = c;
        f.each(function (a, c) {
            a.setStyles({
                overflow: "hidden",
                position: "relative"
            });
            var e = a.getElement(".slidepanel");
            e.setStyles({
                position: "relative"
            });
            (function () {
                var c = a.getSize().size;
                switch (b) {
                    case "RSO":
                        g.xi = c.x;
                        g.xo = 0;
                        g.yi = 0;
                        g.yo = 0;
                        break;
                    case "RSI":
                        g.xo = c.x;
                        g.xi = 0;
                        g.yi = 0;
                        g.yo = 0;
                        break;
                    case "LSO":
                        g.xi = -c.x;
                        g.xo = 0;
                        g.yi = 0;
                        g.yo = 0;
                        break;
                    case "LSI":
                        g.xo = -c.x;
                        g, xi = 0;
                        g.yi = 0;
                        g.yo = 0;
                        break;
                    case "BSO":
                        g.yi = c.y;
                        g.yo = 0;
                        g.xi = 0;
                        g.xo = 0;
                        break;
                    case "BSI":
                        g.yo = c.y;
                        g.yi = 0;
                        g.xi = 0;
                        g.xo = 0;
                        break;
                    case "TSO":
                        g.yo = c.y;
                        g.yi = 0;
                        g.xi = 0;
                        g.xo = 0;
                        break;
                    case "TSI":
                        g.yo = -c.y;
                        g.yi = 0;
                        g.xi = 0;
                        g.xo = 0;
                        break;
                    case "TRSO":
                        g.xi = c.x;
                        g.xo = 0;
                        g.yi = -c.y;
                        g.yo = 0;
                        break;
                    case "TRSI":
                        g.xo = c.x;
                        g.xi = 0;
                        g.yo = -c.y;
                        g.yi = 0;
                        break;
                    case "TLSO":
                        g.xi = -c.x;
                        g.xo = 0;
                        g.yi = -c.y;
                        g.yo = 0;
                        break;
                    case "TLSI":
                        g.xo = -c.x;
                        g.xi = 0;
                        g.yo = -c.y;
                        g.yi = 0;
                        break;
                    case "BRSO":
                        g.xi = c.x;
                        g.xo = 0;
                        g.yi = c.y;
                        g.yo = 0;
                        break;
                    case "BRSI":
                        g.xo = c.x;
                        g.xi = 0;
                        g.yo = c.y;
                        g.yi = 0;
                        break;
                    case "BLSO":
                        g.xi = -c.x;
                        g.xo = 0;
                        g.yi = c.y;
                        g.yo = 0;
                        break;
                    case "BLSI":
                        g.xo = -c.x;
                        g.xi = 0;
                        g.yo = c.y;
                        g.yi = 0;
                        break
                }
                e.setStyles({
                    top: g.yo,
                    left: g.xo
                })
            }).delay(100);
            var f = new Fx.Morph(e, {
                duration: d.dura,
                fps: d.frames,
                transition: d.fxtype,
                link: "cancel"
            });
            a.addEvent("mouseenter", function () {
                f.start({
                    top: g.yi,
                    left: g.xi
                })
            });
            a.addEvent("mouseleave", function () {
                f.start({
                    top: g.yo,
                    left: g.xo
                })
            })
        })
    }
}
if (typeof wallfx != "function") {
    function wallfx(a, b, c, d) {
        var d = typeof d != "undefined" ? d : 0;
        var e = $(a);
        if (e) {
            var f = e.getSize();
            var g = e.getElement(".showcase" + a);
            if (g) {
                g.setStyles({
                    position: "relative",
                    display: "block",
                    visibility: "visible",
                    top: 0,
                    left: 0,
                    width: b + "px",
                    height: c + "px"
                });
                var h = g.getSize();
                var i = g.getElement(".sframe" + a);
                if (i) {
                    i.setStyles({
                        position: "absolute",
                        display: "block"
                    });
                    switch (d) {
                        case 0:
                            g.setStyles({
                                overflow: "hidden"
                            });
                            var j = i.getElement(".table" + a);
                            var k = j.getSize().size;
                            j.setStyles({
                                position: "relative",
                                width: k.x,
                                height: k.y,
                                top: k.y - c,
                                left: k.x - b
                            });
                            i.setStyles({
                                left: -(k.x - b),
                                top: -(k.y - c),
                                width: k.x - b + k.x,
                                height: k.y - c + k.y
                            });
                            j.makeDraggable({
                                container: i
                            });
                            break;
                        case 1:
                            var j = i.getElement(".table" + a);
                            var k = j.getSize().size;
                            j.setStyles({
                                position: "relative",
                                top: 0,
                                left: 0
                            });
                            i.setStyles({
                                overflow: "hidden",
                                position: "relative",
                                width: b,
                                height: c
                            });
                            var l = h.size.x * .05;
                            var m = h.size.y * .05;
                            var n = k.x - h.size.x;
                            var o = k.y - h.size.y;
                            var p = n / (h.size.x - l * 2);
                            var q = o / (h.size.y - m * 2);
                            g.addEvent("mousemove", function (a) {
                                var a = new Event(a);
                                var b = a.page.x - g.getPosition().x;
                                var c = a.page.y - g.getPosition().y;
                                var d = parseInt(p * (b - l));
                                if (d < 0) {
                                    d = 0
                                }
                                if (d > k.x - h.size.x) {
                                    d = k.x - h.size.x
                                }
                                var e = parseInt(q * (c - m));
                                if (e < 0) {
                                    e = 0
                                }
                                if (e > k.y - h.size.y) {
                                    e = k.y - h.size.y
                                }
                                j.style.left = -d + "px";
                                j.style.top = -e + "px"
                            });
                            break
                    }
                }
            }
        }
    }
}
