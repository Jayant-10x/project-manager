/**
 * ============================================================================
 *  10X AGENCY | PROJECT COORDINATOR CRM
 *  public/js/crm-app.js
 * ============================================================================
 */
const LOGO = "assets/logo.png";
const CONFIG = window.PJT_CONFIG || {
    API_URL: "/api.php",
    APP_NAME: "10X Agency | Project Coordinator",
    APP_SHORT: "PJT CRM",
    POLL_MS: 60000,
    MAX_UPLOAD_KB: 10240
};

const API = {
    token: null,
    async call(action, payload) {
        const body = Object.assign({action: action, token: API.token}, payload || {});
        let res;
        try {
            res = await fetch(CONFIG.API_URL, {
                method: "POST",
                headers: {"Content-Type": "application/json;charset=utf-8"},
                body: JSON.stringify(body)
            });
        } catch (e) {
            throw new Error("Could not connect to the server. Check your connection and try again.");
        }

        const text = await res.text();
        let data;
        try {
            data = JSON.parse(text);
        } catch (e) {
            throw new Error("The server returned an invalid response (HTTP " + res.status + "). Check server logs.");
        }

        if (data.ok === false) {
            if (data.error === "AUTH") {
                Session.clear();
                if (pollId) {
                    clearInterval(pollId);
                    pollId = null;
                }
                renderLogin("Your session expired. Please sign in again.");
                const ae = new Error(data.message || "Your session expired. Please sign in again.");
                ae.auth = true;
                throw ae;
            }
            const err = new Error(data.error || data.message || "Request failed");
            err.needForce = !!data.needForce;
            throw err;
        }
        return data;
    },
    login: (e, p) => API.call("login", {email: e, password: p}),
    forgot: (e) => API.call("forgotPassword", {email: e}),
    checkReset: (r) => API.call("checkReset", {reset: r}),
    resetPassword: (r, p) => API.call("resetPassword", {reset: r, password: p}),
    changePassword: (o, n) => API.call("changePassword", {oldPassword: o, newPassword: n}),
    load: () => API.call("load"),
    dashboard: () => API.call("dashboard"),
    logout: () => API.call("logout"),
    search: (q) => API.call("search", {q: q}),
    audit: () => API.call("audit"),
    saveCustomer: (d) => API.call("saveCustomer", {data: d}),
    delCustomer: (id, force) => API.call("deleteCustomer", {id: id, force: !!force}),
    saveProject: (d) => API.call("saveProject", {data: d}),
    delProject: (id) => API.call("deleteProject", {id: id}),
    saveTerms: (projectId, terms) => API.call("saveTerms", {projectId: projectId, terms: terms}),
    genRecurring: (p) => API.call("generateRecurring", p),
    delTerm: (id) => API.call("deleteTerm", {id: id}),
    savePayment: (d) => API.call("savePayment", {data: d}),
    delPayment: (id) => API.call("deletePayment", {id: id}),
    saveMilestones: (projectId, milestones) => API.call("saveMilestones", {
        projectId: projectId,
        milestones: milestones
    }),
    uploadDoc: (p) => API.call("uploadDocument", p),
    delDoc: (id) => API.call("deleteDocument", {id: id}),
    saveFollowup: (d) => API.call("saveFollowup", {data: d}),
    delFollowup: (id) => API.call("deleteFollowup", {id: id}),
    saveTask: (d) => API.call("saveTask", {data: d}),
    toggleTask: (id) => API.call("toggleTask", {id: id}),
    delTask: (id) => API.call("deleteTask", {id: id}),
    saveSales: (d) => API.call("saveSales", {data: d}),
    delSales: (id) => API.call("deleteSales", {id: id}),
    saveUser: (d) => API.call("saveUser", {data: d}),
    delUser: (id) => API.call("deleteUser", {id: id}),
    notifRead: (id) => API.call("notifRead", id ? {id: id} : {}),
    saveSettings: (d) => API.call("saveSettings", d),
    bulkImport: (rows, sp) => API.call("bulkImport", {rows: rows, salesPerson: sp})
};

const Session = {
    K: "pjt_session_v1",
    get() {
        try {
            return JSON.parse(localStorage.getItem(Session.K));
        } catch (e) {
            return null;
        }
    },
    set(s) {
        localStorage.setItem(Session.K, JSON.stringify(s));
        API.token = s.token;
    },
    clear() {
        localStorage.removeItem(Session.K);
        API.token = null;
    }
};

let DB = {
    users: [], sales: [], customers: [], projects: [], terms: [], payments: [],
    documents: [], followups: [], milestones: [], tasks: [], notifications: [],
    audit: [], me: null, unread: 0, settings: {}
};
let DASH = null;
let ME = null, VIEW = "dashboard", FOCUS = null, pollId = null;
let RESET_TOKEN = null, FORCE_CHANGE = false;

/* ---------------- Helpers ---------------- */
const $ = (s, r) => (r || document).querySelector(s);
const $$ = (s, r) => Array.prototype.slice.call((r || document).querySelectorAll(s));
const esc = (s) => String(s == null ? "" : s).replace(/[&<>"']/g, (c) => ({
    "&": "&amp;",
    "<": "&lt;",
    ">": "&gt;",
    '"': "&quot;",
    "'": "&#39;"
})[c]);
const AV = ["#0b3d91", "#5aa236", "#e0a13a", "#d94b4b", "#7a5af8", "#2e75b6", "#c2410c", "#0369a1"];

function hIdx(s, m) {
    let h = 0;
    s = String(s);
    for (let i = 0; i < s.length; i++) h = (h * 31 + s.charCodeAt(i)) >>> 0;
    return h % m;
}

function initials(n) {
    const p = String(n || "?").trim().split(/\s+/);
    return ((p[0] || "").charAt(0) + (p[1] || "").charAt(0)).toUpperCase();
}

function avatar(n, cls) {
    return '<div class="avatar ' + (cls || "") + '" style="background:' + AV[hIdx(n, AV.length)] + '">' + esc(initials(n)) + '</div>';
}

function todayISO() {
    const d = new Date();
    return d.getFullYear() + "-" + String(d.getMonth() + 1).padStart(2, "0") + "-" + String(d.getDate()).padStart(2, "0");
}

function fmtDate(v) {
    if (!v) return "—";
    const d = new Date(String(v).slice(0, 10) + "T00:00:00");
    return isNaN(d) ? v : d.toLocaleDateString("en-IN", {day: "2-digit", month: "short", year: "numeric"});
}

function fmtShort(v) {
    if (!v) return "—";
    const d = new Date(String(v).slice(0, 10) + "T00:00:00");
    return isNaN(d) ? v : d.toLocaleDateString("en-IN", {day: "2-digit", month: "short"});
}

function fmtDT(v) {
    if (!v) return "—";
    const d = new Date(String(v).replace(" ", "T"));
    return isNaN(d) ? v : d.toLocaleString("en-IN", {
        day: "2-digit",
        month: "short",
        hour: "2-digit",
        minute: "2-digit"
    });
}

let CUR = localStorage.getItem("pjt_cur") || "INR";
const CUR_RATE = 83;

function setCurrency(c) {
    CUR = (c === "USD") ? "USD" : "INR";
    localStorage.setItem("pjt_cur", CUR);
    renderShell();
    render();
}

function money(v, short) {
    let n = Number(v || 0);
    if (CUR === "USD") {
        n = n / CUR_RATE;
        if (short && Math.abs(n) >= 1000) return "$" + (n / 1000).toFixed(n % 1000 === 0 ? 0 : 1) + "K";
        return "$" + n.toLocaleString("en-US", {maximumFractionDigits: n < 100 ? 2 : 0});
    }
    if (short && Math.abs(n) >= 100000) return "₹" + (n / 100000).toFixed(n % 100000 === 0 ? 0 : 1) + "L";
    return "₹" + n.toLocaleString("en-IN", {maximumFractionDigits: 0});
}

function daysBetween(a, b) {
    return Math.round((new Date(String(b).slice(0, 10)) - new Date(String(a).slice(0, 10))) / 86400000);
}

function pct(a, b) {
    return b > 0 ? Math.min(100, Math.round(a / b * 100)) : 0;
}

function daysLabel(d) {
    if (d === null || d === undefined) return "";
    if (d === 0) return "today";
    if (d > 0) return d + " days";
    return Math.abs(d) + " days late";
}

function pwProblem(p) {
    if (!p || p.length < 8) return "At least 8 characters required.";
    if (!/[A-Z]/.test(p)) return "One capital letter required.";
    if (!/[a-z]/.test(p)) return "One lowercase letter required.";
    if (!/[0-9]/.test(p)) return "One number required.";
    return null;
}

const HEALTH = {
    "On Track": {cls: "h-track", label: "On Track", color: "#5aa236"},
    "At Risk": {cls: "h-risk", label: "At Risk", color: "#e0a13a"},
    "Delayed": {cls: "h-late", label: "Delayed", color: "#d94b4b"},
    "Completed": {cls: "h-done", label: "Completed", color: "#2e75b6"}
};
const T_STATUS = {
    "Pending": "b-grey",
    "Partial": "b-amber",
    "Paid": "b-green",
    "Overdue": "b-red",
    "Cancelled": "b-grey"
};
const TRI_LABEL = {fixed: "Fixed date", milestone: "Milestone linked", recurring: "Recurring monthly"};
const MODES = ["Cash", "UPI", "NEFT", "RTGS", "Cheque", "Card", "Other"];
const DOC_CATS = ["Contract", "KYC/GST", "Invoice", "Proposal", "Quotation", "Delivery Proof", "AMC", "Other"];
const FU_KINDS = ["Call", "Email", "Meeting", "WhatsApp", "Note"];

function healthBadge(h) {
    const x = HEALTH[h] || HEALTH["On Track"];
    return '<span class="health ' + x.cls + '"><i></i>' + esc(x.label) + '</span>';
}

function termCls(st) {
    return st === "Paid" ? "paid" : st === "Overdue" ? "over" : st === "Partial" ? "part" : "";
}

function toast(msg, kind) {
    let w = $(".toast-wrap");
    if (!w) {
        w = document.createElement("div");
        w.className = "toast-wrap";
        document.body.appendChild(w);
    }
    const t = document.createElement("div");
    t.className = "toast " + (kind === "err" ? "err" : "ok");
    t.innerHTML = icon(kind === "err" ? "alert" : "check") + "<div>" + esc(msg) + "</div>";
    w.appendChild(t);
    setTimeout(() => {
        t.style.transition = ".22s";
        t.style.opacity = "0";
        setTimeout(() => t.remove(), 240);
    }, 3600);
}

const ICONS = {
    dash: '<path d="M3 13h8V3H3v10zm10 8h8V11h-8v10zM3 21h8v-6H3v6zm10-12h8V3h-8v6z"/>',
    cust: '<path d="M16 21v-2a4 4 0 00-4-4H6a4 4 0 00-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M22 21v-2a4 4 0 00-3-3.87"/>',
    proj: '<path d="M3 7a2 2 0 012-2h4l2 2h8a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V7z"/>',
    cash: '<rect x="2" y="6" width="20" height="12" rx="2"/><circle cx="12" cy="12" r="2.5"/><path d="M6 12h.01M18 12h.01"/>',
    cal: '<rect x="3" y="4" width="18" height="18" rx="2"/><path d="M16 2v4M8 2v4M3 10h18"/>',
    doc: '<path d="M14 2H6a2 2 0 00-2 2v16a2 2 0 002 2h12a2 2 0 002-2V8z"/><path d="M14 2v6h6"/>',
    bell: '<path d="M18 8a6 6 0 10-12 0c0 7-3 9-3 9h18s-3-2-3-9"/><path d="M13.7 21a2 2 0 01-3.4 0"/>',
    gear: '<circle cx="12" cy="12" r="3"/><path d="M12 2v3M12 19v3M2 12h3M19 12h3M4.9 4.9l2.1 2.1M17 17l2.1 2.1M19.1 4.9L17 7M7 17l-2.1 2.1"/>',
    users: '<path d="M17 21v-2a4 4 0 00-4-4H5a4 4 0 00-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 00-3-3.87"/>',
    list: '<path d="M8 6h13M8 12h13M8 18h13M3 6h.01M3 12h.01M3 18h.01"/>',
    search: '<circle cx="11" cy="11" r="7"/><path d="M21 21l-4.3-4.3"/>',
    plus: '<path d="M12 5v14M5 12h14"/>',
    edit: '<path d="M12 20h9"/><path d="M16.5 3.5a2.1 2.1 0 013 3L7 19l-4 1 1-4 12.5-12.5z"/>',
    del: '<path d="M3 6h18"/><path d="M8 6V4a1 1 0 011-1h6a1 1 0 011 1v2"/><path d="M19 6l-1 14a2 2 0 01-2 2H8a2 2 0 01-2-2L5 6"/>',
    eye: '<path d="M1 12s4-7 11-7 11 7 11 7-4 7-11 7S1 12 1 12z"/><circle cx="12" cy="12" r="3"/>',
    clock: '<circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 2"/>',
    chev: '<path d="M9 18l6-6-6-6"/>',
    back: '<path d="M19 12H5"/><path d="M12 19l-7-7 7-7"/>',
    alert: '<circle cx="12" cy="12" r="9"/><path d="M12 8v5M12 16.5v.5"/>',
    check: '<path d="M20 6L9 17l-5-5"/>',
    refresh: '<path d="M21 12a9 9 0 11-3-6.7"/><path d="M21 3v6h-6"/>',
    menu: '<path d="M3 12h18M3 6h18M3 18h18"/>',
    down: '<path d="M21 15v4a2 2 0 01-2 2H5a2 2 0 01-2-2v-4"/><path d="M7 10l5 5 5-5"/><path d="M12 15V3"/>',
    up: '<path d="M12 19V5M5 12l7-7 7 7"/>',
    lock: '<rect x="4" y="10" width="16" height="11" rx="2"/><path d="M8 10V7a4 4 0 018 0v3"/>',
    mail: '<rect x="2" y="4" width="20" height="16" rx="2"/><path d="M22 6l-10 7L2 6"/>',
    key: '<circle cx="8" cy="15" r="4"/><path d="M10.8 12.2L20 3M17 6l3 3M14 9l2 2"/>',
    x: '<path d="M18 6L6 18M6 6l12 12"/>',
    out: '<path d="M9 21H5a2 2 0 01-2-2V5a2 2 0 012-2h4"/><path d="M16 17l5-5-5-5"/><path d="M21 12H9"/>',
    chat: '<path d="M21 15a2 2 0 01-2 2H7l-4 4V5a2 2 0 012-2h14a2 2 0 012 2z"/>',
    phone: '<path d="M22 16.9v3a2 2 0 01-2.2 2 19.8 19.8 0 01-8.6-3.1 19.5 19.5 0 01-6-6A19.8 19.8 0 012.1 4.2 2 2 0 014.1 2h3a2 2 0 012 1.7c.1 1 .4 1.9.7 2.8a2 2 0 01-.5 2.1L8.1 9.9a16 16 0 006 6l1.3-1.3a2 2 0 012.1-.4c.9.3 1.8.6 2.8.7a2 2 0 011.7 2z"/>',
    flag: '<path d="M4 15s1-1 4-1 5 2 8 2 4-1 4-1V3s-1 1-4 1-5-2-8-2-4 1-4 1z"/><path d="M4 22v-7"/>',
    layers: '<path d="M12 2l9 5-9 5-9-5 9-5z"/><path d="M3 12l9 5 9-5M3 17l9 5 9-5"/>',
    chart: '<path d="M3 3v18h18"/><rect x="7" y="11" width="3" height="6"/><rect x="12" y="7" width="3" height="10"/><rect x="17" y="13" width="3" height="4"/>',
    import: '<path d="M21 15v4a2 2 0 01-2 2H5a2 2 0 01-2-2v-4"/><path d="M7 10l5-5 5 5"/><path d="M12 5v12"/>'
};

function icon(n, cls) {
    return '<svg class="' + (cls || "") + '" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9">' + (ICONS[n] || "") + '</svg>';
}

function custById(id) {
    return DB.customers.find((c) => String(c.id) === String(id));
}

function projById(id) {
    return DB.projects.find((p) => String(p.id) === String(id));
}

function termsOf(pid) {
    return DB.terms.filter((t) => String(t.projectId) === String(pid));
}

function milestonesOf(pid) {
    return DB.milestones.filter((m) => String(m.projectId) === String(pid));
}

function paymentsOf(pid) {
    return DB.payments.filter((p) => String(p.projectId) === String(pid));
}

function docsOf(opts) {
    return DB.documents.filter((d) => {
        if (opts.customerId && String(d.customerId) !== String(opts.customerId)) return false;
        if (opts.projectId && String(d.projectId) !== String(opts.projectId)) return false;
        return true;
    });
}

function followupsOf(cid) {
    return DB.followups.filter((f) => String(f.customerId) === String(cid));
}

function isCoord() {
    return ME && (ME.role === "Admin" || ME.role === "Coordinator");
}

function debounce(fn, ms) {
    let t;
    return function (...args) {
        clearTimeout(t);
        t = setTimeout(() => fn.apply(null, args), ms);
    };
}

/* ---------------- Lifecycle ---------------- */
document.addEventListener("DOMContentLoaded", boot);

async function boot() {
    const qs = new URLSearchParams(location.search);
    const rt = qs.get("reset");
    if (rt) {
        RESET_TOKEN = rt;
        return renderReset();
    }

    const s = Session.get();
    if (!s || !s.token) return renderLogin();
    API.token = s.token;
    ME = s.user;
    try {
        await refresh(true);
        if (DB.me && Number(DB.me.mustChange) === 1) return renderForceChange();
        startPoll();
    } catch (e) {
        Session.clear();
        renderLogin(e.message);
    }
}

function startPoll() {
    if (pollId) clearInterval(pollId);
    pollId = setInterval(async () => {
        if (document.hidden || !API.token || $("#overlay")) return;
        try {
            const r = await API.load();
            DB = r.data;
            paintBadges();
        } catch (e) {
        }
    }, CONFIG.POLL_MS);
}

async function refresh(silent) {
    const r = await API.load();
    DB = r.data;
    if (DB.me) ME = DB.me;
    DASH = null;
    renderShell();
    render();
    if (!silent) toast("Refreshed");
}

function paintBadges() {
    const b = $("#bellN");
    if (b) {
        b.textContent = DB.unread || "";
        b.style.display = DB.unread ? "" : "none";
    }
}

function authShell(inner) {
    return '<div class="login-wrap"><div class="login-card">' +
        '<div class="login-logo-plate"><img class="login-logo" src="' + LOGO + '" alt="10X Agency"></div>' +
        inner + '</div></div>';
}

function renderLogin(msg) {
    const root = document.getElementById("root");
    if (!root) return;

    const alertHtml = msg ? '<div class="alert-box">' + icon("alert") + '<span>' + esc(msg) + '</span></div>' : "";
    root.innerHTML = authShell(
        '<div class="login-title">Project Coordinator CRM</div>' +
        '<div class="login-sub">10X Agency</div>' +
        alertHtml +
        '<label class="f" style="display:block;margin-top:22px"><span>Email address</span>' +
        '<input class="i" id="liEmail" type="email" placeholder="you@the10xagency.in" autocomplete="email"></label>' +
        '<label class="f" style="display:block;margin-top:14px"><span>Password</span>' +
        '<input class="i" id="liPass" type="password" placeholder="Your password" autocomplete="current-password"></label>' +
        '<div style="display:flex;justify-content:flex-end;margin-top:9px">' +
        '<span class="link" id="toForgot" style="font-size:12.3px">Forgot password?</span></div>' +
        '<button class="btn btn-primary" id="liBtn" style="width:100%;justify-content:center;margin-top:16px;padding:11px">' + icon("lock") + ' Sign in</button>' +
        '<div class="login-hint">Sign in with your company email and password.<br>Coordinator accounts only.</div>'
    );

    const go = async () => {
        const e = $("#liEmail").value.trim(), p = $("#liPass").value;
        if (!e || !p) return toast("Enter both email and password", "err");
        const b = $("#liBtn");
        b.disabled = true;
        b.innerHTML = icon("refresh") + " Signing in...";
        try {
            const r = await API.login(e, p);
            Session.set({token: r.token, user: r.user, mustChange: r.mustChange});
            ME = r.user;
            VIEW = "dashboard";
            FOCUS = null;
            await refresh(true);
            if (Number(r.mustChange) === 1) {
                FORCE_CHANGE = true;
                return renderForceChange();
            }
            startPoll();
        } catch (err) {
            b.disabled = false;
            b.innerHTML = icon("lock") + " Sign in";
            renderLogin(err.message);
        }
    };

    $("#liBtn").onclick = go;
    $("#liPass").onkeydown = (e) => {
        if (e.key === "Enter") go();
    };
    $("#liEmail").onkeydown = (e) => {
        if (e.key === "Enter") $("#liPass").focus();
    };
    $("#toForgot").onclick = () => renderForgot();
}

function renderForgot(msg, done) {
    document.getElementById("root").innerHTML = authShell(
        '<div class="login-title">Reset your password</div>' +
        '<div class="login-sub">Forgot your password</div>' +
        (msg ? '<div class="alert-box ' + (done ? "ok" : "") + '">' + icon(done ? "check" : "alert") + '<span>' + esc(msg) + '</span></div>' : "") +
        '<div style="font-size:12.6px;color:var(--text2);line-height:1.6;margin-top:20px">' +
        'Enter your email and we will send a reset link. It stays valid for 2 hours.</div>' +
        '<label class="f" style="display:block;margin-top:16px"><span>Email address</span>' +
        '<input class="i" id="fEmail" type="email" placeholder="you@the10xagency.in" autocomplete="email"></label>' +
        '<button class="btn btn-primary" id="fBtn" style="width:100%;justify-content:center;margin-top:16px;padding:11px">' + icon("mail") + ' Send reset link</button>' +
        '<div class="login-hint"><span class="link" id="toLogin">Back to sign in</span></div>'
    );
    const go = async () => {
        const e = $("#fEmail").value.trim();
        if (!e) return toast("Enter your email", "err");
        const b = $("#fBtn");
        b.disabled = true;
        b.innerHTML = icon("refresh") + " Sending...";
        try {
            const r = await API.forgot(e);
            renderForgot(r.message || "Reset link sent.", true);
        } catch (err) {
            b.disabled = false;
            b.innerHTML = icon("mail") + " Send reset link";
            toast(err.message, "err");
        }
    };
    $("#fBtn").onclick = go;
    $("#fEmail").onkeydown = (e) => {
        if (e.key === "Enter") go();
    };
    $("#toLogin").onclick = () => renderLogin();
}

async function renderReset(msg) {
    document.getElementById("root").innerHTML = authShell(
        '<div class="login-title">Set a new password</div><div class="login-sub">Almost done</div>' +
        '<div style="display:flex;justify-content:center;padding:24px 0"><div class="spinner"></div></div>'
    );
    let who = null;
    try {
        who = await API.checkReset(RESET_TOKEN);
    } catch (e) {
        document.getElementById("root").innerHTML = authShell(
            '<div class="login-title">This link is not working</div>' +
            '<div class="alert-box" style="margin-top:18px">' + icon("alert") + '<span>' + esc(e.message) + '</span></div>' +
            '<button class="btn btn-primary" id="againBtn" style="width:100%;justify-content:center;margin-top:16px;padding:11px">Request a new link</button>' +
            '<div class="login-hint"><span class="link" id="toLogin">Back to sign in</span></div>'
        );
        $("#againBtn").onclick = () => {
            RESET_TOKEN = null;
            history.replaceState(null, "", location.pathname);
            renderForgot();
        };
        $("#toLogin").onclick = () => {
            RESET_TOKEN = null;
            history.replaceState(null, "", location.pathname);
            renderLogin();
        };
        return;
    }
    document.getElementById("root").innerHTML = authShell(
        '<div class="login-title">Set a new password</div><div class="login-sub">' + esc(who.email || "") + '</div>' +
        (msg ? '<div class="alert-box">' + icon("alert") + '<span>' + esc(msg) + '</span></div>' : "") +
        '<div style="font-size:12.5px;color:var(--text2);margin-top:18px;line-height:1.6">Hi <b>' + esc(who.name) + '</b>, set your new password.</div>' +
        '<label class="f" style="display:block;margin-top:14px"><span>New password</span><input class="i" id="rP1" type="password" placeholder="Min 8 chars, 1 capital, 1 number"></label>' +
        '<label class="f" style="display:block;margin-top:12px"><span>Confirm password</span><input class="i" id="rP2" type="password" placeholder="Repeat the new password"></label>' +
        '<button class="btn btn-primary" id="rBtn" style="width:100%;justify-content:center;margin-top:16px;padding:11px">' + icon("key") + ' Set password</button>'
    );
    $("#rBtn").onclick = async function () {
        const p1 = $("#rP1").value, p2 = $("#rP2").value;
        const prob = pwProblem(p1);
        if (prob) return renderReset(prob);
        if (p1 !== p2) return renderReset("The two passwords do not match.");
        this.disabled = true;
        this.innerHTML = icon("refresh") + " Saving...";
        try {
            await API.resetPassword(RESET_TOKEN, p1);
            RESET_TOKEN = null;
            history.replaceState(null, "", location.pathname);
            renderLogin("Password set. Sign in with your new password.");
        } catch (e) {
            renderReset(e.message);
        }
    };
}

function renderForceChange() {
    document.getElementById("root").innerHTML = authShell(
        '<div class="login-title">Set your own password</div><div class="login-sub">One required step</div>' +
        '<div style="font-size:12.7px;color:var(--text2);line-height:1.65;margin-top:20px">' +
        'Your admin gave you a temporary password. Change it and set your own password.</div>' +
        '<label class="f" style="display:block;margin-top:16px"><span>Current password</span><input class="i" id="cOld" type="password" placeholder="Temporary password from your admin"></label>' +
        '<label class="f" style="display:block;margin-top:12px"><span>New password</span><input class="i" id="cNew" type="password" placeholder="Min 8 chars, 1 capital, 1 number"></label>' +
        '<label class="f" style="display:block;margin-top:12px"><span>Confirm new password</span><input class="i" id="cNew2" type="password"></label>' +
        '<button class="btn btn-primary" id="cBtn" style="width:100%;justify-content:center;margin-top:16px;padding:11px">' + icon("key") + ' Save and continue</button>' +
        '<div class="login-hint"><span class="link" id="cOut">Sign out</span></div>'
    );
    $("#cOut").onclick = () => {
        API.logout().catch(() => {
        });
        Session.clear();
        ME = null;
        FORCE_CHANGE = false;
        renderLogin();
    };
    $("#cBtn").onclick = async function () {
        const o = $("#cOld").value, n = $("#cNew").value, n2 = $("#cNew2").value;
        const prob = pwProblem(n);
        if (prob) return toast(prob, "err");
        if (n !== n2) return toast("The two passwords do not match", "err");
        this.disabled = true;
        this.innerHTML = icon("refresh") + " Saving...";
        try {
            await API.changePassword(o, n);
            FORCE_CHANGE = false;
            toast("Password set");
            await refresh(true);
            startPoll();
        } catch (e) {
            this.disabled = false;
            this.innerHTML = icon("key") + " Save and continue";
            toast(e.message, "err");
        }
    };
}

/* ---------------- Navigation & Shell ---------------- */
function NAVITEMS() {
    const overdue = (DASH ? DASH.overdue.length : 0);
    const fup = (DASH ? DASH.followups.length : 0);
    const openTasks = DB.tasks.filter((t) => !Number(t.done)).length;
    return [
        {g: "Overview"},
        {id: "dashboard", label: "Dashboard", icon: "dash"},
        {id: "today", label: "Today's Work", icon: "flag", c: () => overdue + fup},
        {g: "Customers"},
        {id: "customers", label: "Customers", icon: "cust", c: () => DB.customers.length},
        {id: "projects", label: "Projects", icon: "proj", c: () => DB.projects.length},
        {id: "tasks", label: "My Tasks", icon: "list", c: () => openTasks || null},
        {g: "Money"},
        {id: "payments", label: "Payments", icon: "cash"},
        {id: "schedule", label: "Payment Schedule", icon: "cal"},
        {id: "reports", label: "Reports", icon: "chart"},
        {g: "Records"},
        {id: "documents", label: "Documents", icon: "doc"},
        {id: "followups", label: "Follow-ups", icon: "chat"},
        {id: "sales", label: "Sales Team", icon: "users"},
        {g: "System"},
        {id: "notifications", label: "Notifications", icon: "bell", dot: true},
        {id: "audit", label: "Activity Log", icon: "clock"},
        {id: "settings", label: "Settings", icon: "gear"}
    ];
}

const PAGE_SUB = {
    dashboard: "Receivables, projects and everything due today",
    today: "Who to follow up with today, and what money is coming in",
    customers: "Onboarded customers, their projects and payment status",
    projects: "Timeline, milestones and delivery health",
    tasks: "Your personal task list",
    payments: "Every receipt — with mode and reference",
    schedule: "Full installment schedule across all projects",
    reports: "Collection, ageing and sales performance",
    documents: "Contracts, KYC, invoices and delivery proofs",
    followups: "Conversation history and what to do next",
    sales: "Who onboarded how many customers, and how much was collected",
    notifications: "Payment due, overdue, AMC and follow-up alerts",
    audit: "Who changed what, and when",
    settings: "Alert days, company name and your password"
};

function renderShell() {
    const items = NAVITEMS().filter((n) => n.g || !n.show || n.show());
    const unread = DB.unread || 0;
    const navHtml = items.map((n, i) => {
        if (n.g) {
            const next = items[i + 1];
            if (!next || next.g) return "";
            return '<div class="nav-label">' + n.g + '</div>';
        }
        const c = n.c ? n.c() : null;
        const badge = (c || c === 0) && c > 0 ? '<span class="nav-count' + (n.id === "today" ? " warn" : "") + '">' + c + '</span>' : "";
        const dot = n.dot && unread ? '<span class="nav-count warn">' + unread + '</span>' : "";
        return '<button class="nav-item ' + (VIEW === n.id ? "active" : "") + '" data-id="' + n.id + '">' +
            '<span class="nav-ico">' + icon(n.icon) + '</span><span class="lbl">' + n.label + '</span>' + badge + dot + '</button>';
    }).join("");

    document.getElementById("root").innerHTML =
        '<div class="app">' +
        '<aside class="sidebar" id="sb">' +
        '<div class="sb-head"><div class="logo-plate"><img src="' + LOGO + '" alt="10X Agency"></div></div>' +
        '<div class="sb-name">' + esc(CONFIG.APP_SHORT) + '</div>' +
        '<nav class="nav" id="nav">' + navHtml + '</nav>' +
        '<div class="sb-foot"><div class="me">' + avatar(ME.name) +
        '<div class="me-txt"><strong>' + esc(ME.name) + '</strong><span>' + esc(ME.designation || ME.role) + '</span></div>' +
        '<button class="sb-btn" id="logoutBtn" title="Sign out">' + icon("out") + '</button>' +
        '</div></div>' +
        '</aside>' +
        '<div class="main">' +
        '<header class="topbar">' +
        '<button class="icon-sq" id="burger" title="Menu">' + icon("menu") + '</button>' +
        '<div><div class="page-title" id="pTitle">Dashboard</div><div class="page-sub" id="pSub"></div></div>' +
        '<div class="top-actions">' +
        '<div class="gsearch" id="gsw">' + icon("search") + '<input id="gsearch" placeholder="Customer, project, phone..." autocomplete="off"></div>' +
        '<button class="icon-sq" id="bellBtn" style="position:relative">' + icon("bell") +
        '<span id="bellN" class="nav-count" style="position:absolute;top:-5px;right:-5px;margin:0;background:#d94b4b;color:#fff;' + (unread ? "" : "display:none;") + '">' + (unread || "") + '</span>' +
        '</button>' +
        '<button class="btn btn-ghost btn-sm" id="curBtn" title="Currency">' + esc(CUR) + '</button>' +
        '<button class="btn btn-ghost btn-sm" id="refreshBtn" title="Refresh">' + icon("refresh") + '</button>' +
        '<button class="btn btn-primary" id="newCustBtn">' + icon("plus") + ' New Customer</button>' +
        '</div>' +
        '</header>' +
        '<main class="content" id="content"></main>' +
        '</div>' +
        '</div><div class="toast-wrap"></div>';

    $("#nav").onclick = (e) => {
        const b = e.target.closest(".nav-item");
        if (!b) return;
        VIEW = b.dataset.id;
        FOCUS = null;
        renderShell();
        render();
    };
    $("#burger").onclick = () => $("#sb").classList.toggle("collapsed");
    $("#logoutBtn").onclick = async () => {
        try {
            await API.logout();
        } catch (e) {
        }
        if (pollId) clearInterval(pollId);
        Session.clear();
        ME = null;
        renderLogin();
    };
    $("#bellBtn").onclick = () => {
        VIEW = "notifications";
        FOCUS = null;
        renderShell();
        render();
    };
    $("#refreshBtn").onclick = () => refresh();
    const cb = $("#curBtn");
    if (cb) cb.onclick = () => setCurrency(CUR === "INR" ? "USD" : "INR");
    $("#newCustBtn").onclick = () => openCustomer(null);
    const gs = $("#gsearch");
    gs.oninput = debounce(() => globalSearch(gs.value), 260);
    gs.onkeydown = (e) => {
        if (e.key === "Escape") {
            gs.value = "";
            killSearch();
        }
    };
}

function render() {
    if (FOCUS && FOCUS.type === "customer") return renderCustomer(FOCUS.id);
    if (FOCUS && FOCUS.type === "project") return renderProject(FOCUS.id);

    const items = NAVITEMS().filter((n) => n.g || !n.show || n.show());
    const cur = items.find((n) => n.id === VIEW);
    $("#pTitle").textContent = cur ? cur.label : "Dashboard";
    $("#pSub").textContent = PAGE_SUB[VIEW] || "";
    if (!cur && VIEW !== "dashboard") {
        VIEW = "dashboard";
        renderShell();
        return render();
    }

    const fns = {
        dashboard: viewDashboard, today: viewToday, customers: viewCustomers, projects: viewProjects,
        tasks: viewTasks, payments: viewPayments, schedule: viewSchedule, reports: viewReports,
        documents: viewDocuments, followups: viewFollowups, sales: viewSales,
        notifications: viewNotifications, audit: viewAudit, settings: viewSettings
    };
    $("#content").innerHTML = (fns[VIEW] || viewDashboard)();
    bindContent();
}

/* ---------------- Dashboard Views ---------------- */
function viewDashboard() {
    if (!DASH) {
        API.dashboard().then((r) => {
            DASH = r.data;
            if (VIEW === "dashboard" && !FOCUS && $("#content")) {
                renderShell();
                render();
            }
        }).catch((e) => {
            if (!e.auth && VIEW === "dashboard" && !FOCUS && $("#content")) $("#content").innerHTML = deny(e.message);
        });
        return '<div class="card"><div class="card-body">' +
            '<div class="sk sk-card"></div><div class="sk sk-card"></div><div class="sk sk-card"></div></div></div>';
    }
    const d = DASH, m = d.money;
    const coll = m.collectionRate;

    return '' +
        '<div class="kpis">' +
        kpi("Total Receivable", money(m.scheduled, true), "Across all projects", "cash", "#0b3d91", "#e8eefc") +
        kpi("Received", money(m.received, true), coll + "% collected", "check", "#5aa236", "#eef7e8") +
        kpi("Due", money(m.due, true), "Still to be collected", "clock", "#e0a13a", "#fdf3e2") +
        kpi("Overdue", money(m.overdue, true), d.overdue.length + " installments late", "alert", "#d94b4b", "#fdeaea") +
        kpi("This Month", money(m.thisMonth, true), "Collection this month", "cal", "#2e75b6", "#e8f1fa") +
        '</div>' +

        '<div class="grid-2">' +
        '<div class="card"><div class="card-head">' +
        '<div><h3>Today&#39;s Work</h3><div class="sub">Check these first</div></div>' +
        '<div class="right"><button class="btn btn-ghost btn-sm" data-go="today">Full list ' + icon("chev") + '</button></div>' +
        '</div><div class="card-body">' +
        (d.overdue.length || d.followups.length || d.late.length ? '' :
            '<div class="empty">' + icon("check") + '<p>Everything is on track</p><div class="sm">Nothing pending for today</div></div>') +
        (d.overdue.slice(0, 3).map((t) => '<div class="mini-item clickable" data-project="' + t.projectId + '">' +
            '<span style="width:30px;height:30px;border-radius:9px;display:grid;place-items:center;background:#fdeaea;color:#d94b4b;flex:0 0 30px">' + icon("alert") + '</span>' +
            '<div><div class="t">' + esc(t.company) + ' — ' + money(t.balance) + '</div>' +
            '<div class="s">' + esc(t.label) + ' · due ' + fmtShort(t.dueDate) + ' · ' + Math.abs(t.days) + ' days late</div></div>' +
            '<div class="r"><span class="badge b-red">Overdue</span></div></div>').join("")) +
        (d.followups.slice(0, 3).map((f) => '<div class="mini-item clickable" data-customer="' + f.customerId + '">' +
            '<span style="width:30px;height:30px;border-radius:9px;display:grid;place-items:center;background:#e8eefc;color:#0b3d91;flex:0 0 30px">' + icon("chat") + '</span>' +
            '<div><div class="t">' + esc(f.company) + '</div><div class="s">' + esc(f.nextNote || f.kind) + ' · ' + fmtShort(f.nextDate) + '</div></div>' +
            '<div class="r"><span class="badge b-navy">' + esc(f.kind) + '</span></div></div>').join("")) +
        (d.late.slice(0, 2).map((p) => '<div class="mini-item clickable" data-project="' + p.id + '">' +
            '<span style="width:30px;height:30px;border-radius:9px;display:grid;place-items:center;background:#fdf3e2;color:#e0a13a;flex:0 0 30px">' + icon("clock") + '</span>' +
            '<div><div class="t">' + esc(p.name) + '</div><div class="s">' + esc(p.company) + ' · ' + esc(p.delayReason || p.health) + '</div></div>' +
            '<div class="r">' + healthBadge(p.health) + '</div></div>').join("")) +
        '</div></div>' +

        '<div>' +
        '<div class="card" style="margin-bottom:15px"><div class="card-head">' +
        '<div><h3>Project Health</h3><div class="sub">' + d.counts.projects + ' projects</div></div></div>' +
        '<div class="card-body"><div class="stackrow" style="gap:14px;flex-wrap:wrap">' +
        healthPill("On Track", d.projects["On Track"], d.counts.projects, "#5aa236") +
        healthPill("At Risk", d.projects["At Risk"], d.counts.projects, "#e0a13a") +
        healthPill("Delayed", d.projects["Delayed"], d.counts.projects, "#d94b4b") +
        healthPill("Completed", d.projects["Completed"], d.counts.projects, "#2e75b6") +
        '</div></div></div>' +
        '<div class="card"><div class="card-head"><div><h3>Collection Trend</h3><div class="sub">Last 6 months</div></div></div>' +
        '<div class="card-body">' + spark(d.trend) + '</div></div>' +
        '</div>' +
        '</div>' +

        '<div class="grid-2e" style="margin-bottom:15px">' +
        '<div class="card"><div class="card-head">' +
        '<div><h3>Upcoming Payments</h3><div class="sub">Next ' + DB.settings.paymentAlertDays + ' days</div></div></div>' +
        '<div class="card-body">' + termList(d.upcoming, true) + '</div></div>' +
        '<div class="card"><div class="card-head">' +
        '<div><h3>Overdue Payments</h3><div class="sub">Needs immediate follow-up</div></div></div>' +
        '<div class="card-body">' + (termList(d.overdue, false) || emptyBox("alert", "No overdue payments", "All payments are on time")) + '</div></div>' +
        '</div>' +

        '<div class="grid-2e" style="margin-bottom:15px">' +
        '<div class="card"><div class="card-head"><div><h3>Sales-wise performance</h3>' +
        '<div class="sub">Revenue and collection by salesperson</div></div></div>' +
        '<div class="table-wrap"><table><thead><tr><th>Salesperson</th><th>Customers</th><th>Projects</th>' +
        '<th class="num">Value</th><th class="num">Received</th><th>Collection</th></tr></thead><tbody>' +
        (d.sales.length ? d.sales.map((s) => {
            const r = pct(s.received, s.value);
            return '<tr><td class="strong">' + esc(s.name) + '</td><td>' + s.customers + '</td><td>' + s.projects + '</td>' +
                '<td class="num">' + money(s.value) + '</td><td class="num">' + money(s.received) + '</td>' +
                '<td style="min-width:120px"><div class="bar' + (r >= 80 ? " green" : r >= 40 ? " amber" : "") + '"><i style="width:' + r + '%"></i></div>' +
                '<span class="muted" style="font-size:10.8px">' + r + '%</span></td></tr>';
        }).join("") : '<tr><td colspan="6">' + emptyBox("users", "No data yet", "Add a customer and it will show up here") + '</td></tr>') +
        '</tbody></table></div></div>' +

        '<div>' +
        '<div class="card" style="margin-bottom:15px"><div class="card-head"><div><h3>AMC / Renewal</h3>' +
        '<div class="sub">' + DB.settings.amcAlertDays + ' days before alert</div></div></div>' +
        '<div class="card-body">' + (d.amc.length ? d.amc.map((a) => '<div class="mini-item clickable" data-project="' + a.id + '">' +
            '<span style="width:30px;height:30px;border-radius:9px;display:grid;place-items:center;background:#eef7e8;color:#5aa236;flex:0 0 30px">' + icon("refresh") + '</span>' +
            '<div><div class="t">' + esc(a.company) + '</div><div class="s">' + esc(a.name) + ' · AMC ends ' + fmtShort(a.amcEnd) + '</div></div>' +
            '<div class="r"><span class="badge ' + (a.days < 0 ? "b-red" : "b-amber") + '">' + (a.days < 0 ? Math.abs(a.days) + " days late" : daysLabel(a.days)) + '</span></div></div>').join("") : emptyBox("refresh", "No renewals pending", "Add AMC dates to a project")) + '</div></div>' +
        '<div class="card"><div class="card-head"><div><h3>Handover Pending</h3>' +
        '<div class="sub">Delivered but checklist incomplete</div></div></div>' +
        '<div class="card-body">' + (d.handover.length ? d.handover.map((h) => '<div class="mini-item clickable" data-project="' + h.id + '">' + icon("flag") +
            '<div style="margin-left:10px"><div class="t">' + esc(h.name) + '</div><div class="s">' + esc(h.company) + ' · ' + esc(h.code) + '</div></div>' +
            '<div class="r"><span class="badge b-amber">Pending</span></div></div>').join("") : emptyBox("flag", "All handovers complete", "Nothing left to do")) + '</div></div>' +
        '</div>' +
        '</div>' +

        '<div class="card"><div class="card-head"><div><h3>Payment Ageing</h3>' +
        '<div class="sub">How old the overdue amounts are</div></div></div>' +
        '<div class="card-body">' + hbar(d.ageing.map((a) => ({
            label: a.label + " days",
            value: a.value,
            money: true
        }))) + '</div></div>';
}

function kpi(label, value, foot, ico, color, soft, go) {
    return '<div class="kpi' + (go ? " click" : "") + '"' + (go ? ' data-go="' + go + '"' : "") + '>' +
        '<div class="kpi-top"><span class="kpi-ico" style="background:' + soft + ';color:' + color + '">' + icon(ico) + '</span>' +
        '<span class="kpi-label">' + label + '</span></div>' +
        '<div class="kpi-value">' + value + '</div>' +
        '<div class="kpi-foot">' + esc(foot) + '</div></div>';
}

function healthPill(label, n, total, color) {
    const p = pct(n, total);
    return '<div style="flex:1;min-width:110px">' +
        '<div style="display:flex;align-items:center;gap:6px;font-size:12px;font-weight:650;margin-bottom:5px">' +
        '<i style="width:8px;height:8px;border-radius:50%;background:' + color + ';display:inline-block"></i>' + label + '</div>' +
        '<div style="font-size:20px;font-weight:750;letter-spacing:-.6px">' + n + '</div>' +
        '<div class="bar" style="height:5px;margin-top:6px"><i style="width:' + p + '%;background:' + color + '"></i></div></div>';
}

function spark(trend) {
    const max = Math.max(...trend.map((t) => t.value), 1);
    return '<div class="spark">' + trend.map((t) => '<i style="height:' + Math.max(4, (t.value / max) * 100) + '%" title="' + t.label + ': ' + money(t.value) + '"></i>').join("") + '</div>' +
        '<div style="display:flex;gap:6px;margin-top:8px">' + trend.map((t) => '<div style="flex:1;text-align:center;font-size:10.6px;color:var(--muted);font-weight:650">' + esc(t.label) + '</div>').join("") + '</div>';
}

function hbar(data) {
    const max = Math.max(...data.map((d) => d.value), 1);
    return '<div style="display:flex;flex-direction:column;gap:13px">' + data.map((d) => {
        const col = d.label.startsWith("90+") ? "#d94b4b" : d.label.startsWith("61") ? "#e0a13a" :
            d.label.startsWith("31") ? "#2e75b6" : "#5aa236";
        return '<div><div style="display:flex;justify-content:space-between;font-size:12.3px;margin-bottom:5px">' +
            '<span style="font-weight:600">' + esc(d.label) + '</span>' +
            '<span class="muted" style="font-weight:650">' + (d.money ? money(d.value) : d.value) + '</span></div>' +
            '<div class="bar" style="height:8px"><i style="width:' + ((d.value / max) * 100).toFixed(1) + '%;background:' + col + '"></i></div></div>';
    }).join("") + '</div>';
}

function emptyBox(ico, title, sub) {
    return '<div class="empty">' + icon(ico) + '<p>' + esc(title) + '</p><div class="sm">' + esc(sub) + '</div></div>';
}

function termList(list, upcoming) {
    if (!list.length) return upcoming ? emptyBox("cal", "Nothing due in the next few days", "All clear") : "";
    return list.slice(0, 6).map((t) => {
        const late = t.days !== null && t.days < 0;
        return '<div class="term ' + (late ? "over" : "") + ' clickable" data-project="' + t.projectId + '">' +
            '<div class="term-body">' +
            '<div class="term-title">' + esc(t.company) + '</div>' +
            '<div class="term-meta"><span>' + esc(t.label) + '</span>' +
            '<span>' + (t.dueDate ? "Due " + fmtShort(t.dueDate) : "No date") + '</span>' +
            (t.days !== null ? '<span style="color:' + (late ? "#d94b4b" : "inherit") + ';font-weight:600">' + daysLabel(t.days) + '</span>' : "") +
            '</div>' +
            '</div>' +
            '<div class="term-amt"><div class="a">' + money(t.balance) + '</div>' +
            '<div class="b">' + esc(t.projectCode) + '</div></div></div>';
    }).join("");
}

function viewToday() {
    const today = todayISO();
    const overdue = DB.terms.filter((t) => t.status === "Overdue")
        .sort((a, b) => String(a.dueDate).localeCompare(String(b.dueDate)));
    const soon = DB.terms.filter((t) => (t.status === "Pending" || t.status === "Partial") && t.dueDate &&
        t.dueDate >= today && t.dueDate <= addDays(today, Number(DB.settings.paymentAlertDays) || 7))
        .sort((a, b) => String(a.dueDate).localeCompare(String(b.dueDate)));
    const fups = DB.followups.filter((f) => f.nextDate && f.nextDate <= addDays(today, 7))
        .sort((a, b) => String(a.nextDate).localeCompare(String(b.nextDate)));
    const openTasks = DB.tasks.filter((t) => !Number(t.done));
    const lateProjects = DB.projects.filter((p) => p.health === "Delayed" || p.health === "At Risk");

    const totalOver = overdue.reduce((s, t) => s + t.balance, 0);
    const totalSoon = soon.reduce((s, t) => s + t.balance, 0);

    return '' +
        '<div class="kpis four">' +
        kpi("Overdue", money(totalOver, true), overdue.length + " installments", "alert", "#d94b4b", "#fdeaea") +
        kpi("Due in " + (DB.settings.paymentAlertDays || 7) + " days", money(totalSoon, true), soon.length + " installments", "clock", "#e0a13a", "#fdf3e2") +
        kpi("Follow-ups", fups.length, "Pending conversations", "chat", "#0b3d91", "#e8eefc") +
        kpi("My open tasks", openTasks.length, "In your checklist", "list", "#5aa236", "#eef7e8") +
        '</div>' +

        '<div class="grid-2e">' +
        '<div class="card"><div class="card-head"><div><h3>Overdue Payments</h3>' +
        '<div class="sub">' + overdue.length + ' installments · ' + money(totalOver) + '</div></div></div>' +
        '<div class="card-body">' + (overdue.length ? overdue.map((t) => {
            const p = projById(t.projectId), c = p ? custById(p.customerId) : null;
            return '<div class="term over clickable" data-project="' + t.projectId + '">' +
                '<div class="term-no" style="background:#fdeaea;color:#d94b4b">' + t.seq + '</div>' +
                '<div class="term-body"><div class="term-title">' + esc(c ? c.company : "") + '</div>' +
                '<div class="term-meta"><span>' + esc(t.label) + '</span><span>' + esc(p ? p.code : "") + '</span>' +
                '<span style="color:#d94b4b;font-weight:650">' + Math.abs(daysBetween(today, t.dueDate)) + ' days late</span></div></div>' +
                '<div class="term-amt"><div class="a">' + money(t.balance) + '</div>' +
                '<button class="btn btn-green btn-sm" data-pay="' + t.projectId + '" data-term="' + t.id + '">' + icon("plus") + ' Payment</button></div></div>';
        }).join("") : emptyBox("check", "No overdue payments", "All payments are on track")) + '</div></div>' +

        '<div>' +
        '<div class="card" style="margin-bottom:15px"><div class="card-head"><div><h3>Upcoming payments</h3>' +
        '<div class="sub">Next ' + (DB.settings.paymentAlertDays || 7) + ' days</div></div></div>' +
        '<div class="card-body">' + (soon.length ? soon.map((t) => {
            const p = projById(t.projectId), c = p ? custById(p.customerId) : null;
            return '<div class="mini-item clickable" data-project="' + t.projectId + '">' +
                '<div><div class="t">' + esc(c ? c.company : "") + ' — ' + money(t.balance) + '</div>' +
                '<div class="s">' + esc(t.label) + ' · ' + esc(p ? p.code : "") + '</div></div>' +
                '<div class="r"><span class="badge b-amber">' + fmtShort(t.dueDate) + '</span></div></div>';
        }).join("") : emptyBox("cal", "Nothing due", "The next few days are clear")) + '</div></div>' +
        '<div class="card"><div class="card-head"><div><h3>Follow-up calls</h3>' +
        '<div class="sub">' + fups.length + ' pending</div></div></div>' +
        '<div class="card-body">' + (fups.length ? fups.map((f) => '<div class="mini-item clickable" data-customer="' + f.customerId + '">' +
            '<div><div class="t">' + esc(f.customer) + '</div>' +
            '<div class="s">' + esc(f.nextNote || f.summary) + '</div></div>' +
            '<div class="r"><span class="badge ' + (f.nextDate < today ? "b-red" : "b-navy") + '">' + fmtShort(f.nextDate) + '</span></div></div>').join("") : emptyBox("chat", "No pending follow-ups", "Log one from a customer page")) + '</div></div>' +
        '</div>' +
        '</div>' +

        '<div class="card"><div class="card-head"><div><h3>Projects Needing Attention</h3>' +
        '<div class="sub">At risk and delayed</div></div></div>' +
        '<div class="table-wrap"><table><thead><tr><th>Project</th><th>Customer</th><th>Health</th>' +
        '<th>Delivery</th><th>Reason</th><th class="num">Due</th></tr></thead><tbody>' +
        (lateProjects.length ? lateProjects.map((p) => '<tr class="clickable" data-project="' + p.id + '">' +
            '<td class="strong">' + esc(p.name) + '<div class="muted" style="font-size:10.8px">' + esc(p.code) + '</div></td>' +
            '<td>' + esc(p.customer) + '</td><td>' + healthBadge(p.health) + '</td>' +
            '<td class="nowrap muted">' + fmtShort(p.deliveryDate) + '</td>' +
            '<td class="muted">' + esc(p.delayReason || "—") + '</td>' +
            '<td class="num">' + money(p.money.due) + '</td></tr>').join("") : '<tr><td colspan="6">' + emptyBox("check", "All projects on track", "Nothing at risk") + '</td></tr>') +
        '</tbody></table></div></div>';
}

function addDays(iso, n) {
    const d = new Date(iso + "T00:00:00");
    d.setDate(d.getDate() + n);
    return d.getFullYear() + "-" + String(d.getMonth() + 1).padStart(2, "0") + "-" + String(d.getDate()).padStart(2, "0");
}

function deny(m) {
    return '<div class="deny">' + icon("alert") + '<div><b>Access limited.</b><br>' + esc(m) + '</div></div>';
}

/* ---------------- Customers ---------------- */
let CF = {q: "", sales: "", city: "", pay: "", status: ""};

function customerMoney(cid) {
    const projs = DB.projects.filter((p) => String(p.customerId) === String(cid));
    let sched = 0, rec = 0, tds = 0, over = 0;
    projs.forEach((p) => {
        sched += p.money.scheduled;
        rec += p.money.received;
        tds += p.money.tds;
        over += p.money.overdue;
    });
    return {scheduled: sched, received: rec, tds: tds, overdue: over, due: Math.max(0, sched - rec - tds)};
}

function viewCustomers() {
    const q = (CF.q || "").trim().toLowerCase();
    let rows = DB.customers.slice();
    if (q) rows = rows.filter((c) => (c.company + " " + c.code + " " + c.contactName + " " + c.phone + " " + c.email + " " + c.city + " " + c.gstin).toLowerCase().includes(q));
    if (CF.sales) rows = rows.filter((c) => c.onboardedBy === CF.sales);
    if (CF.city) rows = rows.filter((c) => c.city === CF.city);
    if (CF.pay) rows = rows.filter((c) => {
        const m = customerMoney(c.id);
        if (CF.pay === "due") return m.due > 0;
        if (CF.pay === "clear") return m.due <= 0 && m.scheduled > 0;
        if (CF.pay === "over") return m.overdue > 0;
        return true;
    });
    if (CF.status) rows = rows.filter((c) => c.status === CF.status);

    const cities = Array.from(new Set(DB.customers.map((c) => c.city).filter(Boolean))).sort();
    const totalDue = rows.reduce((s, c) => s + customerMoney(c.id).due, 0);

    return '' +
        '<div class="page-head">' +
        '<span class="info">' + rows.length + ' customer' + (rows.length === 1 ? "" : "s") +
        ' · total due <b>' + money(totalDue) + '</b></span><span class="spacer"></span>' +
        '<button class="btn btn-ghost btn-sm" id="expCust">' + icon("down") + ' Export CSV</button>' +
        '<button class="btn btn-ghost btn-sm" id="impCust">' + icon("import") + ' Bulk Import</button>' +
        '<button class="btn btn-primary btn-sm" id="addCust">' + icon("plus") + ' New Customer</button>' +
        '</div>' +

        '<div class="card" style="margin-bottom:14px"><div class="card-body" style="padding:13px 16px">' +
        '<div class="filterbar">' +
        '<div class="gsearch" style="width:240px">' + icon("search") + '<input id="cSearch" placeholder="Company, contact, phone, GST..." value="' + esc(CF.q) + '"></div>' +
        '<select id="cSales"><option value="">All salespeople</option>' + DB.sales.map((s) => '<option ' + (CF.sales === s.name ? "selected" : "") + '>' + esc(s.name) + '</option>').join("") + '</select>' +
        '<select id="cCity"><option value="">All cities</option>' + cities.map((x) => '<option ' + (CF.city === x ? "selected" : "") + '>' + esc(x) + '</option>').join("") + '</select>' +
        '<select id="cPay">' + ['', 'due', 'over', 'clear'].map((v) => {
            const lbl = v === "" ? "Any payment status" : v === "due" ? "Payment pending" : v === "over" ? "Overdue" : "Fully paid";
            return '<option value="' + v + '" ' + (CF.pay === v ? "selected" : "") + '>' + lbl + '</option>';
        }).join("") + '</select>' +
        '<select id="cStatus"><option value="">Any status</option>' + ["Active", "On Hold", "Completed", "Churned"].map((s) => '<option ' + (CF.status === s ? "selected" : "") + '>' + s + '</option>').join("") + '</select>' +
        '<button class="btn btn-ghost btn-sm" id="cClear">' + icon("x") + ' Clear</button>' +
        '</div>' +
        '</div></div>' +

        '<div class="card"><div class="table-wrap"><table>' +
        '<thead><tr><th>Customer</th><th>Onboarded By</th><th>Projects</th>' +
        '<th class="num">Value</th><th class="num">Received</th><th class="num">Due</th><th>Payment</th><th>Status</th><th></th></tr></thead><tbody>' +
        (rows.length ? rows.map((c) => {
            const m = customerMoney(c.id);
            const projs = DB.projects.filter((p) => String(p.customerId) === String(c.id));
            const r = pct(m.received, m.scheduled);
            const payCls = m.overdue > 0 ? "b-red" : m.due > 0 ? "b-amber" : m.scheduled > 0 ? "b-green" : "b-grey";
            const payLbl = m.overdue > 0 ? "Overdue" : m.due > 0 ? "Pending" : m.scheduled > 0 ? "Cleared" : "—";
            return '<tr class="clickable" data-customer="' + c.id + '">' +
                '<td><div class="strong">' + esc(c.company) + '</div>' +
                '<div class="muted" style="font-size:10.9px">' + esc(c.code) +
                (c.contactName ? ' · ' + esc(c.contactName) : '') + (c.city ? ' · ' + esc(c.city) : '') + '</div></td>' +
                '<td>' + (c.onboardedBy ? '<span class="pill">' + esc(c.onboardedBy) + '</span>' : '<span class="muted">—</span>') + '</td>' +
                '<td>' + projs.length + '</td>' +
                '<td class="num">' + money(m.scheduled) + '</td>' +
                '<td class="num" style="color:#3d7a24">' + money(m.received) + '</td>' +
                '<td class="num" style="color:' + (m.due > 0 ? "#96660f" : "inherit") + ';font-weight:650">' + money(m.due) + '</td>' +
                '<td style="min-width:120px"><span class="badge dot ' + payCls + '">' + payLbl + '</span>' +
                '<div class="bar" style="height:5px;margin-top:6px"><i style="width:' + r + '%"></i></div></td>' +
                '<td><span class="badge ' + (c.status === "Active" ? "b-green" : c.status === "Churned" ? "b-red" : "b-grey") + '">' + esc(c.status) + '</span></td>' +
                '<td><div class="row-actions">' +
                '<button class="mini-btn" data-customer="' + c.id + '">' + icon("eye") + '</button>' +
                '<button class="mini-btn" data-editcust="' + c.id + '">' + icon("edit") + '</button>' +
                '</div></td></tr>';
        }).join("") : '<tr><td colspan="9">' + emptyBox("cust", "No customers found", "Add a customer or change the filters") + '</td></tr>') +
        '</tbody></table></div></div>';
}

let CTAB = "overview";

function renderCustomer(cid) {
    const c = custById(cid);
    if (!c) {
        FOCUS = null;
        return render();
    }
    $("#pTitle").textContent = c.company;
    $("#pSub").textContent = c.code + (c.city ? " · " + c.city : "") + (c.onboardedBy ? " · onboarded by " + c.onboardedBy : "");
    const m = customerMoney(cid);
    const projs = DB.projects.filter((p) => String(p.customerId) === String(cid));
    const docs = docsOf({customerId: cid});
    const fups = followupsOf(cid);
    const r = pct(m.received, m.scheduled);
    const tab = CTAB || "overview";

    $("#content").innerHTML =
        '<button class="btn btn-ghost btn-sm" id="backBtn" style="margin-bottom:14px">' + icon("back") + ' Back</button>' +

        '<div class="card" style="margin-bottom:15px"><div class="card-body">' +
        '<div style="display:flex;align-items:flex-start;gap:14px;flex-wrap:wrap">' +
        '<div style="width:54px;height:54px;border-radius:14px;background:linear-gradient(135deg,#0b3d91,#5aa236);display:grid;place-items:center;color:#fff;font-size:18px;font-weight:750">' + esc(initials(c.company)) + '</div>' +
        '<div style="flex:1;min-width:240px">' +
        '<h2 style="font-size:19px;font-weight:700;letter-spacing:-.3px">' + esc(c.company) + '</h2>' +
        '<div class="muted" style="font-size:12.5px;margin-top:3px">' + esc(c.code) +
        (c.contactName ? ' · ' + esc(c.contactName) : '') + (c.phone ? ' · ' + esc(c.phone) : '') +
        (c.email ? ' · ' + esc(c.email) : '') + '</div>' +
        '<div style="margin-top:8px;display:flex;gap:7px;flex-wrap:wrap">' +
        '<span class="badge ' + (c.status === "Active" ? "b-green" : c.status === "Churned" ? "b-red" : "b-grey") + '">' + esc(c.status) + '</span>' +
        (c.source ? '<span class="pill">' + esc(c.source) + '</span>' : '') +
        (c.gstin ? '<span class="pill">GST ' + esc(c.gstin) + '</span>' : '') +
        (c.onboardedBy ? '<span class="pill">Sales: ' + esc(c.onboardedBy) + '</span>' : '') +
        '</div>' +
        '</div>' +
        '<div style="display:flex;gap:8px;flex-wrap:wrap">' +
        '<button class="btn btn-ghost btn-sm" id="editCust">' + icon("edit") + ' Edit</button>' +
        '<button class="btn btn-ghost btn-sm" id="fuBtn">' + icon("chat") + ' Log Follow-up</button>' +
        '<button class="btn btn-primary btn-sm" id="newProj">' + icon("plus") + ' New Project</button>' +
        (ME && ME.role === "Admin" ? '<button class="btn btn-ghost btn-sm" id="delCust" title="Delete customer" style="color:#d94b4b">' + icon("del") + '</button>' : '') +
        '</div>' +
        '</div>' +
        '<div class="divider"></div>' +
        '<div class="money-grid">' +
        '<div class="money-cell"><div class="k">Total Value</div><div class="v">' + money(m.scheduled) + '</div></div>' +
        '<div class="money-cell rec"><div class="k">Received</div><div class="v">' + money(m.received) + '</div></div>' +
        '<div class="money-cell due"><div class="k">Due</div><div class="v">' + money(m.due) + '</div></div>' +
        '<div class="money-cell over"><div class="k">Overdue</div><div class="v">' + money(m.overdue) + '</div></div>' +
        '</div>' +
        '<div style="margin-top:14px"><div class="progress-row"><span>Collection progress</span><b>' + r + '%</b></div>' +
        '<div class="bar' + (r >= 80 ? " green" : r >= 40 ? "" : " amber") + '" style="height:8px"><i style="width:' + r + '%"></i></div></div>' +
        '</div></div>' +

        '<div class="card" style="margin-bottom:15px"><div class="tabs">' +
        [["overview", "Overview"], ["projects", "Projects", projs.length], ["payments", "Payments", DB.payments.filter((p) => String(p.customerId) === String(cid)).length],
            ["docs", "Documents", docs.length], ["followups", "Follow-ups", fups.length]]
            .map((t) => '<button class="tab ' + (tab === t[0] ? "active" : "") + '" data-ctab="' + t[0] + '">' + t[1] + (t[2] ? '<span class="n">' + t[2] + '</span>' : "") + '</button>').join("") +
        '</div><div class="card-body">' + customerTab(c, projs, docs, fups, tab) + '</div></div>';

    $("#backBtn").onclick = () => {
        FOCUS = null;
        render();
    };
    $("#editCust").onclick = () => openCustomer(c.id);
    $("#fuBtn").onclick = () => openFollowup(null, c.id);
    $("#newProj").onclick = () => openProject(null, c.id);
    if ($("#delCust")) $("#delCust").onclick = () => askDelete("customer", c.id);
    $$("[data-ctab]").forEach((b) => {
        b.onclick = () => {
            CTAB = b.dataset.ctab;
            renderCustomer(cid);
        };
    });
    bindContent(true);
}

function customerTab(c, projs, docs, fups, tab) {
    if (tab === "overview") {
        return '<div class="grid-3" style="gap:14px">' +
            '<div><div class="field"><div class="k">Contact person</div><div class="v">' + esc(c.contactName || "—") + '</div></div>' +
            '<div class="field"><div class="k">Phone</div><div class="v">' + esc(c.phone || "—") + (c.altPhone ? " / " + esc(c.altPhone) : "") + '</div></div>' +
            '<div class="field"><div class="k">Email</div><div class="v">' + esc(c.email || "—") + '</div></div></div>' +
            '<div><div class="field"><div class="k">City / State</div><div class="v">' + esc([c.city, c.state].filter(Boolean).join(", ") || "—") + '</div></div>' +
            '<div class="field"><div class="k">GSTIN</div><div class="v">' + esc(c.gstin || "—") + '</div></div>' +
            '<div class="field"><div class="k">PAN</div><div class="v">' + esc(c.pan || "—") + '</div></div></div>' +
            '<div><div class="field"><div class="k">Source</div><div class="v">' + esc(c.source || "—") + '</div></div>' +
            '<div class="field"><div class="k">Industry</div><div class="v">' + esc(c.industry || "—") + '</div></div>' +
            '<div class="field"><div class="k">Onboarded by</div><div class="v">' + esc(c.onboardedBy || "—") + '</div></div></div>' +
            '</div>' +
            (c.address ? '<div class="divider"></div><div class="field"><div class="k">Address</div><div class="v">' + esc(c.address) + '</div></div>' : "") +
            (c.billingName || c.billingAddr ? '<div class="field"><div class="k">Billing</div><div class="v">' + esc(c.billingName || "") + (c.billingAddr ? '<br>' + esc(c.billingAddr) : "") + '</div></div>' : "") +
            (c.internalNotes ? '<div class="divider"></div><div class="notice warn">' + icon("lock") + '<div><b>Internal notes</b> (not visible to the customer)<br>' + esc(c.internalNotes) + '</div></div>' : "");
    }

    if (tab === "projects") {
        if (!projs.length) return emptyBox("proj", "No projects yet", "Click New Project to get started");
        return '<div class="grid-3">' + projs.map((p) => {
            const ms = milestonesOf(p.id);
            const doneMs = ms.filter((x) => Number(x.done)).length;
            return '<div class="card clickable" data-project="' + p.id + '" style="padding:16px 17px">' +
                '<div style="display:flex;align-items:flex-start;gap:10px;margin-bottom:10px">' +
                '<div style="flex:1"><div class="muted" style="font-size:10.8px;font-weight:750;letter-spacing:.5px">' + esc(p.code) + '</div>' +
                '<div style="font-size:14.6px;font-weight:660;margin-top:3px">' + esc(p.name) + '</div></div>' +
                healthBadge(p.health) + '</div>' +
                '<div class="money-grid" style="grid-template-columns:1fr 1fr;gap:9px">' +
                '<div class="money-cell" style="padding:9px 11px"><div class="k">Value</div><div class="v" style="font-size:14.5px">' + money(p.money.scheduled) + '</div></div>' +
                '<div class="money-cell due" style="padding:9px 11px"><div class="k">Due</div><div class="v" style="font-size:14.5px">' + money(p.money.due) + '</div></div>' +
                '</div>' +
                '<div style="margin-top:11px"><div class="progress-row"><span>Milestones</span><b>' + doneMs + '/' + ms.length + '</b></div>' +
                '<div class="bar' + (ms.length && doneMs === ms.length ? " green" : "") + '" style="height:6px"><i style="width:' + pct(doneMs, ms.length) + '%"></i></div></div>' +
                '<div style="display:flex;gap:10px;margin-top:10px;font-size:11.4px;color:var(--text2)">' +
                '<span>Due ' + fmtShort(p.deliveryDate) + '</span><span>·</span><span>' + esc(p.status) + '</span></div>' +
                '</div>';
        }).join("") + '</div>';
    }

    if (tab === "payments") {
        const pays = DB.payments.filter((p) => String(p.customerId) === String(c.id));
        const terms = DB.terms.filter((t) => {
            const p = projById(t.projectId);
            return p && String(p.customerId) === String(c.id);
        });
        return '<div class="card-head" style="padding:0 0 12px;border:none"><div><h3>Payment schedule</h3><div class="sub">' + terms.length + ' installments</div></div></div>' +
            (terms.length ? terms.map((t) => {
                const p = projById(t.projectId);
                return '<div class="term ' + termCls(t.status) + '">' +
                    '<div class="term-no">' + t.seq + '</div>' +
                    '<div class="term-body"><div class="term-title">' + esc(p ? p.code : "") + ' — ' + esc(t.label) + '</div>' +
                    '<div class="term-meta"><span>' + esc(TRI_LABEL[t.trigger] || "") + '</span>' +
                    '<span>Due ' + (t.dueDate ? fmtShort(t.dueDate) : "—") + '</span>' +
                    '<span class="badge ' + (T_STATUS[t.status] || "b-grey") + '">' + esc(t.status) + '</span></div></div>' +
                    '<div class="term-amt"><div class="a">' + money(t.amount) + '</div>' +
                    '<div class="b">' + (t.balance > 0 ? money(t.balance) + " remaining" : "full paid") + '</div></div></div>';
            }).join("") : emptyBox("cal", "No schedule yet", "Create a payment schedule in the project")) +
            '<div class="divider"></div>' +
            '<div class="card-head" style="padding:0 0 12px;border:none"><div><h3>Receipts</h3><div class="sub">' + pays.length + ' payments · ' + money(pays.reduce((s, x) => s + x.amount, 0)) + '</div></div></div>' +
            (pays.length ? '<div class="table-wrap"><table><thead><tr><th>Receipt</th><th>Date</th><th>Project</th>' +
                '<th class="num">Amount</th><th>Mode</th><th>Reference</th><th></th></tr></thead><tbody>' +
                pays.map((x) => '<tr><td class="strong">' + esc(x.code) + '</td><td class="nowrap">' + fmtShort(x.paidOn) + '</td>' +
                    '<td class="muted">' + esc(x.projectCode) + '</td>' +
                    '<td class="num" style="color:#3d7a24;font-weight:650">' + money(x.amount) + '</td>' +
                    '<td><span class="pill">' + esc(x.mode) + '</span></td>' +
                    '<td class="muted" style="font-size:11.4px">' + esc(x.reference || "—") + (x.bank ? ' · ' + esc(x.bank) : "") + '</td>' +
                    '<td><div class="row-actions"><button class="mini-btn danger" data-delpay="' + x.id + '">' + icon("del") + '</button></div></td></tr>').join("") + '</tbody></table></div>' : emptyBox("cash", "No receipts yet", "Record a payment and it will appear here"));
    }

    if (tab === "docs") {
        return '<div style="display:flex;justify-content:flex-end;margin-bottom:12px">' +
            '<button class="btn btn-primary btn-sm" id="upDoc">' + icon("up") + ' Upload document</button></div>' +
            (docs.length ? docs.map(docRow).join("") : emptyBox("doc", "No documents yet", "Upload a contract, KYC or invoice"));
    }

    if (tab === "followups") {
        return '<div style="display:flex;justify-content:flex-end;margin-bottom:12px">' +
            '<button class="btn btn-primary btn-sm" id="fuBtn2">' + icon("plus") + ' Log Follow-up</button></div>' +
            (fups.length ? fups.map(fuRow).join("") : emptyBox("chat", "No follow-ups yet", "Log your first conversation"));
    }

    return "";
}

function docRow(d) {
    const catColors = {
        "Contract": "#0b3d91", "KYC/GST": "#5aa236", "Invoice": "#e0a13a",
        "Proposal": "#7a5af8", "Quotation": "#2e75b6", "Delivery Proof": "#5aa236",
        "AMC": "#d94b4b", "Other": "#8894ac"
    };
    const col = catColors[d.category] || "#8894ac";
    const short = (d.name || "").split(".").pop().toUpperCase().slice(0, 4);
    return '<div class="doc">' +
        '<span class="doc-ico" style="background:' + col + '1a;color:' + col + '">' + esc(short) + '</span>' +
        '<div style="flex:1;min-width:0"><div class="doc-n">' + esc(d.title) + '</div>' +
        '<div class="doc-m">' + esc(d.name) + ' · ' + d.sizeKb + ' KB · ' + esc(d.by || "") + ' · ' + esc(fmtDT(d.at)) + '</div></div>' +
        '<span class="pill">' + esc(d.category) + '</span>' +
        '<button class="mini-btn" data-preview="' + d.id + '" title="Open">' + icon("eye") + '</button>' +
        '<a class="mini-btn" href="' + esc(d.url) + '" target="_blank" rel="noopener" download title="Download">' + icon("down") + '</a>' +
        '<button class="mini-btn danger" data-deldoc="' + d.id + '" title="Remove">' + icon("del") + '</button>' +
        '</div>';
}

function fuRow(f) {
    const c = {
        Call: "#0b3d91",
        Email: "#2e75b6",
        Meeting: "#7a5af8",
        WhatsApp: "#5aa236",
        Note: "#8894ac"
    }[f.kind] || "#8894ac";
    return '<div class="fu">' +
        '<span class="fu-ico" style="background:' + c + '1a;color:' + c + '">' + icon(f.kind === "Meeting" ? "users" : f.kind === "Email" ? "mail" : f.kind === "WhatsApp" ? "chat" : "phone") + '</span>' +
        '<div style="flex:1;min-width:0"><div class="fu-s">' + esc(f.summary) + '</div>' +
        (f.detail ? '<div class="fu-t">' + esc(f.detail) + '</div>' : "") +
        '<div class="fu-t">' + esc(f.customer) + ' · ' + esc(f.kind) + ' · ' + esc(fmtDT(f.happenedOn)) + (f.by ? ' · ' + esc(f.by) : "") + '</div>' +
        (f.nextDate ? '<div style="margin-top:6px"><span class="badge ' + (f.nextDate < todayISO() ? "b-red" : "b-navy") + '">Next: ' + fmtShort(f.nextDate) + (f.nextNote ? ' — ' + esc(f.nextNote) : "") + '</span></div>' : "") +
        '</div>' +
        '<button class="mini-btn danger" data-delfu="' + f.id + '" title="Remove">' + icon("del") + '</button>' +
        '</div>';
}

/* ---------------- Projects ---------------- */
let PF = {q: "", health: "", status: ""};

function viewProjects() {
    const q = (PF.q || "").toLowerCase();
    let rows = DB.projects.slice();
    if (q) rows = rows.filter((p) => (p.name + " " + p.code + " " + p.customer + " " + p.type + " " + p.coordinator).toLowerCase().includes(q));
    if (PF.health) rows = rows.filter((p) => p.health === PF.health);
    if (PF.status) rows = rows.filter((p) => p.status === PF.status);

    const totDue = rows.reduce((s, p) => s + p.money.due, 0);
    const totOver = rows.reduce((s, p) => s + p.money.overdue, 0);

    return '' +
        '<div class="page-head"><span class="info">' + rows.length + ' projects · due <b>' + money(totDue) + '</b>' +
        (totOver > 0 ? ' · <b style="color:#d94b4b">' + money(totOver) + ' overdue</b>' : "") + '</span><span class="spacer"></span>' +
        '<button class="btn btn-primary btn-sm" id="addProj">' + icon("plus") + ' New Project</button></div>' +

        '<div class="card" style="margin-bottom:14px"><div class="card-body" style="padding:13px 16px">' +
        '<div class="filterbar">' +
        '<div class="gsearch" style="width:230px">' + icon("search") + '<input id="pSearch" placeholder="Project, customer, code..." value="' + esc(PF.q) + '"></div>' +
        '<select id="pHealth"><option value="">Any health</option>' + ["On Track", "At Risk", "Delayed", "Completed"].map((h) => '<option ' + (PF.health === h ? "selected" : "") + '>' + h + '</option>').join("") + '</select>' +
        '<select id="pStatus"><option value="">Any status</option>' + ["Onboarding", "In Progress", "On Hold", "Delivered", "Cancelled"].map((s) => '<option ' + (PF.status === s ? "selected" : "") + '>' + s + '</option>').join("") + '</select>' +
        '<button class="btn btn-ghost btn-sm" id="pClear">' + icon("x") + ' Clear</button>' +
        '</div>' +
        '</div></div>' +

        '<div class="card"><div class="table-wrap"><table>' +
        '<thead><tr><th>Project</th><th>Customer</th><th>Coordinator</th><th>Health</th>' +
        '<th class="num">Value</th><th class="num">Received</th><th class="num">Due</th><th>Delivery</th><th></th></tr></thead><tbody>' +
        (rows.length ? rows.map((p) => {
            const ms = milestonesOf(p.id);
            const doneMs = ms.filter((x) => Number(x.done)).length;
            return '<tr class="clickable" data-project="' + p.id + '">' +
                '<td><div class="strong">' + esc(p.name) + '</div>' +
                '<div class="muted" style="font-size:10.9px">' + esc(p.code) + (p.type ? " · " + esc(p.type) : "") +
                (ms.length ? " · " + doneMs + "/" + ms.length + " milestones" : "") + '</div></td>' +
                '<td>' + esc(p.customer) + '</td>' +
                '<td class="muted">' + esc(p.coordinator || "—") + '</td>' +
                '<td>' + healthBadge(p.health) + '</td>' +
                '<td class="num">' + money(p.money.scheduled) + '</td>' +
                '<td class="num" style="color:#3d7a24">' + money(p.money.received) + '</td>' +
                '<td class="num" style="color:' + (p.money.overdue > 0 ? "#d94b4b" : p.money.due > 0 ? "#96660f" : "inherit") + ';font-weight:650">' + money(p.money.due) + '</td>' +
                '<td class="nowrap muted">' + fmtShort(p.deliveryDate) + '</td>' +
                '<td><div class="row-actions">' +
                '<button class="mini-btn" data-project="' + p.id + '">' + icon("eye") + '</button>' +
                '<button class="mini-btn" data-editproj="' + p.id + '">' + icon("edit") + '</button>' +
                '</div></td></tr>';
        }).join("") : '<tr><td colspan="9">' + emptyBox("proj", "No projects found", "Change the filters or create a new project") + '</td></tr>') +
        '</tbody></table></div></div>';
}

let PTAB = "overview";

function renderProject(pid) {
    const p = projById(pid);
    if (!p) {
        FOCUS = null;
        return render();
    }
    const c = custById(p.customerId);
    $("#pTitle").textContent = p.name;
    $("#pSub").textContent = p.code + " · " + p.customer + (p.coordinator ? " · " + p.coordinator : "");
    const m = p.money;
    const terms = termsOf(p.id).sort((a, b) => a.seq - b.seq);
    const ms = milestonesOf(p.id).sort((a, b) => a.sortOrder - b.sortOrder);
    const pays = paymentsOf(p.id);
    const docs = docsOf({projectId: p.id});
    const r = pct(m.received, m.scheduled);
    const tab = PTAB || "overview";

    $("#content").innerHTML =
        '<button class="btn btn-ghost btn-sm" id="backBtn" style="margin-bottom:14px">' + icon("back") + ' Back</button>' +

        '<div class="notice' + (p.health === "Delayed" ? " warn" : "") + '">' + icon(p.health === "Delayed" ? "alert" : "flag") +
        '<div><b>' + esc(p.health) + '</b> · ' + esc(p.status) +
        (p.delayReason ? '<br>Reason: ' + esc(p.delayReason) : "") +
        (p.deliveryDate ? '<br>Delivery target: ' + fmtDate(p.deliveryDate) +
            (daysBetween(todayISO(), p.deliveryDate) < 0 && p.status !== "Delivered" ? ' · <b style="color:#d94b4b">' + Math.abs(daysBetween(todayISO(), p.deliveryDate)) + ' days late</b>' : "") : "") +
        '</div></div>' +

        '<div class="card" style="margin-bottom:15px"><div class="card-body">' +
        '<div style="display:flex;align-items:flex-start;gap:14px;flex-wrap:wrap;margin-bottom:14px">' +
        '<div style="flex:1;min-width:240px">' +
        '<h2 style="font-size:19px;font-weight:700;letter-spacing:-.3px">' + esc(p.name) + '</h2>' +
        '<div class="muted" style="font-size:12.4px;margin-top:3px">' +
        (c ? '<span class="link" data-customer="' + c.id + '">' + esc(c.company) + '</span> · ' : "") +
        esc(p.type || "—") + ' · start ' + fmtShort(p.startDate) + ' · delivery ' + fmtShort(p.deliveryDate) +
        ' · cost ' + money(p.cost) + '</div>' +
        '</div>' +
        '<div style="display:flex;gap:8px;flex-wrap:wrap">' +
        '<button class="btn btn-ghost btn-sm" id="msBtn">' + icon("layers") + ' Milestones</button>' +
        '<button class="btn btn-ghost btn-sm" id="schedBtn">' + icon("cal") + ' Schedule</button>' +
        '<button class="btn btn-green btn-sm" id="payBtn">' + icon("plus") + ' Payment</button>' +
        '<button class="btn btn-ghost btn-sm" id="editP">' + icon("edit") + '</button>' +
        (ME && ME.role === "Admin" ? '<button class="btn btn-ghost btn-sm" id="delP" title="Delete project" style="color:#d94b4b">' + icon("del") + '</button>' : '') +
        '</div>' +
        '</div>' +
        '<div class="money-grid">' +
        '<div class="money-cell"><div class="k">Project Value</div><div class="v">' + money(m.scheduled) + '</div></div>' +
        '<div class="money-cell rec"><div class="k">Received</div><div class="v">' + money(m.received) + '</div></div>' +
        '<div class="money-cell due"><div class="k">Due</div><div class="v">' + money(m.due) + '</div></div>' +
        '<div class="money-cell over"><div class="k">Overdue</div><div class="v">' + money(m.overdue) + '</div></div>' +
        '</div>' +
        '<div style="margin-top:14px"><div class="progress-row"><span>Collection</span><b>' + r + '%</b></div>' +
        '<div class="bar' + (r >= 80 ? " green" : r >= 40 ? "" : " amber") + '" style="height:8px"><i style="width:' + r + '%"></i></div></div>' +
        (p.amcRequired ? '<div class="divider"></div><div class="money-grid" style="grid-template-columns:repeat(3,1fr)">' +
            '<div class="money-cell"><div class="k">AMC Start</div><div class="v" style="font-size:14px">' + fmtShort(p.amcStart) + '</div></div>' +
            '<div class="money-cell"><div class="k">AMC End</div><div class="v" style="font-size:14px">' + fmtShort(p.amcEnd) + '</div></div>' +
            '<div class="money-cell"><div class="k">AMC Amount</div><div class="v" style="font-size:14px">' + money(p.amcAmount) + '</div></div>' +
            '</div>' : "") +
        (p.handoverDone ? '<div style="margin-top:12px"><span class="badge b-green">' + icon("check") + ' Handover complete</span></div>'
            : '<div style="margin-top:12px"><span class="badge b-amber">Handover Pending</span></div>') +
        '</div></div>' +

        '<div class="card" style="margin-bottom:15px"><div class="tabs">' +
        [["overview", "Overview"], ["milestones", "Milestones", ms.length], ["schedule", "Payment schedule", terms.length],
            ["payments", "Receipts", pays.length], ["docs", "Documents", docs.length]]
            .map((t) => '<button class="tab ' + (tab === t[0] ? "active" : "") + '" data-ptab="' + t[0] + '">' + t[1] + (t[2] ? '<span class="n">' + t[2] + '</span>' : "") + '</button>').join("") +
        '</div><div class="card-body">' + projectTab(p, ms, terms, pays, docs, tab) + '</div></div>';

    $("#backBtn").onclick = () => {
        FOCUS = null;
        render();
    };
    $("#editP").onclick = () => openProject(p.id);
    if ($("#delP")) $("#delP").onclick = () => askDelete("project", p.id);
    $("#msBtn").onclick = () => openMilestones(p.id);
    $("#schedBtn").onclick = () => openSchedule(p.id);
    $("#payBtn").onclick = () => openPayment(p.id, null);
    $$("[data-ptab]").forEach((b) => {
        b.onclick = () => {
            PTAB = b.dataset.ptab;
            renderProject(pid);
        };
    });
    bindContent(true);
}

function projectTab(p, ms, terms, pays, docs, tab) {
    if (tab === "overview") {
        const doneMs = ms.filter((x) => Number(x.done)).length;
        return (p.description ? '<div style="font-size:13.2px;color:var(--text2);line-height:1.65">' + esc(p.description) + '</div><div class="divider"></div>' : "") +
            '<div class="grid-3" style="gap:14px">' +
            '<div><div class="field"><div class="k">Status</div><div class="v">' + esc(p.status) + '</div></div>' +
            '<div class="field"><div class="k">Health</div><div class="v">' + healthBadge(p.health) + '</div></div></div>' +
            '<div><div class="field"><div class="k">Coordinator</div><div class="v">' + esc(p.coordinator || "—") + '</div></div>' +
            '<div class="field"><div class="k">Delay reason</div><div class="v">' + esc(p.delayReason || "—") + '</div></div></div>' +
            '<div><div class="field"><div class="k">Milestones done</div><div class="v">' + doneMs + " / " + ms.length + '</div></div>' +
            '<div class="field"><div class="k">Collection</div><div class="v">' + pct(p.money.received, p.money.scheduled) + '%</div></div></div>' +
            '</div>' +
            (p.handoverNotes ? '<div class="divider"></div><div class="field"><div class="k">Handover notes</div><div class="v">' + esc(p.handoverNotes) + '</div></div>' : "") +
            (p.notes ? '<div class="field"><div class="k">Notes</div><div class="v">' + esc(p.notes) + '</div></div>' : "") +
            (ms.length ? '<div class="divider"></div><div class="card-head" style="padding:0 0 8px;border:none"><div><h3>Timeline</h3><div class="sub">' + doneMs + ' of ' + ms.length + ' complete</div></div></div>' + msList(ms) : "");
    }

    if (tab === "milestones") {
        return '<div style="display:flex;justify-content:flex-end;margin-bottom:12px">' +
            '<button class="btn btn-primary btn-sm" id="msBtn2">' + icon("edit") + ' Edit milestones</button></div>' +
            (ms.length ? msList(ms) : emptyBox("layers", "No milestones yet", "Build a timeline — from kickoff to delivery"));
    }

    if (tab === "schedule") {
        const rec = terms.reduce((s, t) => s + t.amount, 0);
        const paid = terms.reduce((s, t) => s + t.received, 0);
        return '<div style="display:flex;gap:10px;align-items:center;margin-bottom:12px;flex-wrap:wrap">' +
            '<span class="muted" style="font-size:12.4px">' + terms.length + ' installments · ' + money(rec) + ' scheduled · ' + money(paid) + ' received</span>' +
            '<div style="margin-left:auto;display:flex;gap:8px">' +
            '<button class="btn btn-ghost btn-sm" id="genRec">' + icon("refresh") + ' Generate monthly</button>' +
            '<button class="btn btn-primary btn-sm" id="schedBtn2">' + icon("edit") + ' Edit schedule</button></div></div>' +
            (terms.length ? terms.map((t) => '<div class="term ' + termCls(t.status) + '">' +
                '<div class="term-no">' + t.seq + '</div>' +
                '<div class="term-body"><div class="term-title">' + esc(t.label) + ' · ' + money(t.amount) + '</div>' +
                '<div class="term-meta"><span>' + esc(TRI_LABEL[t.trigger] || "") + '</span>' +
                (t.dueDate ? '<span>Due ' + fmtDate(t.dueDate) + (t.graceDays ? " (+" + t.graceDays + "d grace)" : "") + '</span>' : '<span class="muted">No date set</span>') +
                (t.received > 0 ? '<span style="color:#3d7a24">Received ' + money(t.received) + '</span>' : "") +
                '</div></div>' +
                '<div class="term-amt"><span class="badge ' + (T_STATUS[t.status] || "b-grey") + '">' + esc(t.status) + '</span>' +
                (t.balance > 0 ? '<div class="b" style="margin-top:5px">' + money(t.balance) + ' remaining</div>' : '<div class="b" style="margin-top:5px;color:#3d7a24">paid</div>') +
                '</div>' +
                '<div style="display:flex;flex-direction:column;gap:4px">' +
                (t.balance > 0 ? '<button class="mini-btn" data-pay="' + p.id + '" data-term="' + t.id + '" title="Record payment">' + icon("plus") + '</button>' : "") +
                '<button class="mini-btn danger" data-delterm="' + t.id + '" title="Remove">' + icon("del") + '</button>' +
                '</div></div>').join("") : emptyBox("cal", "No schedule yet", "Create a payment schedule — fixed, milestone or recurring"));
    }

    if (tab === "payments") {
        return '<div style="display:flex;justify-content:flex-end;margin-bottom:12px">' +
            '<button class="btn btn-green btn-sm" id="payBtn2">' + icon("plus") + ' Record payment</button></div>' +
            (pays.length ? '<div class="table-wrap"><table><thead><tr><th>Receipt</th><th>Date</th><th class="num">Amount</th>' +
                '<th class="num">TDS</th><th>Mode</th><th>Reference</th><th>Entered by</th><th></th></tr></thead><tbody>' +
                pays.map((x) => '<tr><td class="strong">' + esc(x.code) + '</td><td class="nowrap">' + fmtShort(x.paidOn) + '</td>' +
                    '<td class="num" style="color:#3d7a24;font-weight:650">' + money(x.amount) + '</td>' +
                    '<td class="num muted">' + (x.tds ? money(x.tds) : "—") + '</td>' +
                    '<td><span class="pill">' + esc(x.mode) + '</span></td>' +
                    '<td class="muted" style="font-size:11.3px">' + esc(x.reference || "—") + (x.bank ? '<br>' + esc(x.bank) : "") + '</td>' +
                    '<td class="muted nowrap">' + esc(x.enteredBy || "—") + '</td>' +
                    '<td><div class="row-actions"><button class="mini-btn danger" data-delpay="' + x.id + '">' + icon("del") + '</button></div></td></tr>').join("") + '</tbody></table></div>' : emptyBox("cash", "No receipts yet", "Record a payment when it comes in"));
    }

    if (tab === "docs") {
        return '<div style="display:flex;justify-content:flex-end;margin-bottom:12px">' +
            '<button class="btn btn-primary btn-sm" id="upDoc">' + icon("up") + ' Upload document</button></div>' +
            (docs.length ? docs.map(docRow).join("") : emptyBox("doc", "No documents yet", "Upload a contract, invoice or delivery proof"));
    }
    return "";
}

function msList(ms) {
    return ms.map((x, i) => {
        const cls = Number(x.done) ? "done" : x.status === "In Progress" ? "prog" : x.status === "Blocked" ? "block" : "";
        const late = !Number(x.done) && x.dueDate && x.dueDate < todayISO();
        return '<div class="ms-row">' +
            '<span class="ms-dot ' + cls + '">' + (Number(x.done) ? "✓" : (i + 1)) + '</span>' +
            '<div class="ms-body"><div class="ms-name">' + esc(x.name) + '</div>' +
            '<div class="ms-meta">' + (x.dueDate ? "Due " + fmtDate(x.dueDate) : "No date") +
            (Number(x.done) && x.doneOn ? " · done " + fmtShort(x.doneOn) : "") +
            ' · ' + esc(x.status) +
            (late ? ' · <b style="color:#d94b4b">' + Math.abs(daysBetween(todayISO(), x.dueDate)) + ' days late</b>' : "") +
            (x.notes ? '<br>' + esc(x.notes) : "") + '</div></div></div>';
    }).join("");
}

/* ---------------- Tasks, Payments, Schedule, etc. ---------------- */
function viewTasks() {
    const open = DB.tasks.filter((t) => !Number(t.done));
    const done = DB.tasks.filter((t) => Number(t.done));
    return '<div class="page-head"><span class="info">' + open.length + ' pending · ' + done.length + ' done</span>' +
        '<span class="spacer"></span><button class="btn btn-primary btn-sm" id="addTask">' + icon("plus") + ' New task</button></div>' +
        '<div class="card"><div class="card-body">' +
        (DB.tasks.length ? DB.tasks.map((t) => {
            const late = t.dueDate && t.dueDate < todayISO() && !Number(t.done);
            return '<div class="mini-item">' +
                '<input type="checkbox" data-toggletask="' + t.id + '" ' + (Number(t.done) ? "checked" : "") +
                ' style="width:16px;height:16px;accent-color:#0b3d91;cursor:pointer">' +
                '<div style="flex:1;min-width:0"><div class="t" style="' + (Number(t.done) ? "text-decoration:line-through;color:#8894ac" : "") + '">' + esc(t.title) + '</div>' +
                '<div class="s">' + (t.projectCode ? esc(t.projectCode) + ' · ' : "") + (t.customer ? esc(t.customer) + ' · ' : "") +
                (t.dueDate ? (late ? '<b style="color:#d94b4b">due ' + fmtShort(t.dueDate) + ' (' + Math.abs(daysBetween(todayISO(), t.dueDate)) + ' days late)</b>' : 'due ' + fmtShort(t.dueDate)) : "no date") +
                ' · ' + esc(t.assignedTo || "") + '</div></div>' +
                '<span class="badge ' + (t.priority === "Urgent" ? "b-red" : t.priority === "High" ? "b-amber" : t.priority === "Medium" ? "b-navy" : "b-grey") + '">' + esc(t.priority) + '</span>' +
                '<button class="mini-btn danger" data-deltask="' + t.id + '">' + icon("del") + '</button></div>';
        }).join("") : emptyBox("list", "No tasks", "Create your own checklist")) +
        '</div></div>';
}

let PAYF = {q: "", mode: ""};

function viewPayments() {
    const list = DB.payments.slice().sort((a, b) => String(b.paidOn).localeCompare(String(a.paidOn)));
    const q = (PAYF.q || "").toLowerCase();
    let rows = list;
    if (q) rows = rows.filter((p) => (p.code + " " + p.customer + " " + p.projectCode + " " + p.reference + " " + p.mode).toLowerCase().includes(q));
    if (PAYF.mode) rows = rows.filter((p) => p.mode === PAYF.mode);
    const tot = rows.reduce((s, p) => s + p.amount, 0);
    const tdsTot = rows.reduce((s, p) => s + p.tds, 0);

    return '<div class="page-head"><span class="info">' + rows.length + ' receipts · <b>' + money(tot) + '</b>' +
        (tdsTot ? ' · TDS ' + money(tdsTot) : "") + '</span><span class="spacer"></span>' +
        '<button class="btn btn-ghost btn-sm" id="expPay">' + icon("down") + ' Export CSV</button>' +
        '<button class="btn btn-green btn-sm" id="payBtn3">' + icon("plus") + ' Record payment</button></div>' +

        '<div class="card" style="margin-bottom:14px"><div class="card-body" style="padding:13px 16px"><div class="filterbar">' +
        '<div class="gsearch" style="width:250px">' + icon("search") + '<input id="paySearch" placeholder="Receipt, customer, UTR..." value="' + esc(PAYF.q) + '"></div>' +
        '<select id="payMode"><option value="">All modes</option>' + MODES.map((m) => '<option ' + (PAYF.mode === m ? "selected" : "") + '>' + m + '</option>').join("") + '</select>' +
        '<button class="btn btn-ghost btn-sm" id="payClear">' + icon("x") + ' Clear</button>' +
        '</div></div></div>' +

        '<div class="card"><div class="table-wrap"><table><thead><tr><th>Receipt</th><th>Date</th><th>Customer</th>' +
        '<th>Project</th><th class="num">Amount</th><th class="num">TDS</th><th>Mode</th><th>Reference</th><th class="num">Balance</th><th></th></tr></thead><tbody>' +
        (rows.length ? rows.map((p) => {
            const proj = projById(p.projectId);
            const term = p.termId ? DB.terms.find((t) => String(t.id) === String(p.termId)) : null;
            return '<tr>' +
                '<td class="strong">' + esc(p.code) + '</td>' +
                '<td class="nowrap">' + fmtShort(p.paidOn) + '</td>' +
                '<td class="clickable" data-customer="' + p.customerId + '">' + esc(p.customer) + '</td>' +
                '<td class="muted"><span class="clickable link" data-project="' + p.projectId + '">' + esc(p.projectCode) + '</span>' +
                (term ? '<div style="font-size:10.6px">' + esc(term.label) + '</div>' : "") + '</td>' +
                '<td class="num" style="color:#3d7a24;font-weight:650">' + money(p.amount) + '</td>' +
                '<td class="num muted">' + (p.tds ? money(p.tds) : "—") + '</td>' +
                '<td><span class="pill">' + esc(p.mode) + '</span></td>' +
                '<td class="muted" style="font-size:11.2px">' + esc(p.reference || "—") + (p.bank ? '<br>' + esc(p.bank) : "") + '</td>' +
                '<td class="num">' + money(proj ? proj.money.due : 0) + '</td>' +
                '<td><div class="row-actions"><button class="mini-btn danger" data-delpay="' + p.id + '">' + icon("del") + '</button></div></td></tr>';
        }).join("") : '<tr><td colspan="10">' + emptyBox("cash", "No receipts yet", "Recorded payments will appear here") + '</td></tr>') +
        '</tbody></table></div></div>';
}

function viewSchedule() {
    const today = todayISO();
    const upcoming = DB.terms.filter((t) => t.status === "Pending" || t.status === "Partial")
        .sort((a, b) => String(a.dueDate || "9999").localeCompare(String(b.dueDate || "9999")));
    const over = DB.terms.filter((t) => t.status === "Overdue");
    const paid = DB.terms.filter((t) => t.status === "Paid");
    const sum = (l) => l.reduce((s, t) => s + t.balance, 0);

    return '<div class="page-head"><span class="info">' + DB.terms.length + ' installments across ' + DB.projects.length + ' projects</span><span class="spacer"></span>' +
        '<button class="btn btn-ghost btn-sm" id="expSched">' + icon("down") + ' Export CSV</button></div>' +

        '<div class="kpis four">' +
        kpi("Upcoming", money(sum(upcoming), true), upcoming.length + " installments", "cal", "#0b3d91", "#e8eefc") +
        kpi("Overdue", money(sum(over), true), over.length + " installments", "alert", "#d94b4b", "#fdeaea") +
        kpi("Collected", money(paid.reduce((s, t) => s + t.received, 0), true), paid.length + " fully paid", "check", "#5aa236", "#eef7e8") +
        kpi("Total scheduled", money(DB.terms.reduce((s, t) => s + t.amount, 0), true), "All projects", "cash", "#7a5af8", "#f0ecff") +
        '</div>' +

        '<div class="card"><div class="card-head"><div><h3>Poora schedule</h3><div class="sub">Sorted by due date, most urgent first</div></div></div>' +
        '<div class="table-wrap"><table><thead><tr><th>Due</th><th>Customer</th><th>Project</th><th>Installment</th>' +
        '<th>Trigger</th><th class="num">Amount</th><th class="num">Balance</th><th>Status</th><th></th></tr></thead><tbody>' +
        (DB.terms.length ? DB.terms.slice().sort((a, b) => {
            const rank = {Overdue: 0, Pending: 1, Partial: 2, Paid: 3, Cancelled: 4};
            const r = rank[a.status] - rank[b.status];
            if (r !== 0) return r;
            return String(a.dueDate || "9999").localeCompare(String(b.dueDate || "9999"));
        }).map((t) => {
            const p = projById(t.projectId), c = p ? custById(p.customerId) : null;
            const late = t.status === "Overdue";
            return '<tr>' +
                '<td class="nowrap" style="' + (late ? "color:#d94b4b;font-weight:650" : "") + '">' +
                (t.dueDate ? fmtShort(t.dueDate) : "—") +
                (late ? '<div style="font-size:10.5px">' + Math.abs(daysBetween(today, t.dueDate)) + ' days late</div>' : "") + '</td>' +
                '<td class="clickable" data-customer="' + (c ? c.id : "") + '">' + esc(c ? c.company : "—") + '</td>' +
                '<td class="muted"><span class="link" data-project="' + t.projectId + '">' + esc(p ? p.code : "") + '</span></td>' +
                '<td>' + esc(t.label) + '</td>' +
                '<td><span class="pill">' + esc(TRI_LABEL[t.trigger] || "") + '</span></td>' +
                '<td class="num">' + money(t.amount) + '</td>' +
                '<td class="num" style="font-weight:650">' + money(t.balance) + '</td>' +
                '<td><span class="badge dot ' + (T_STATUS[t.status] || "b-grey") + '">' + esc(t.status) + '</span></td>' +
                '<td>' + (t.balance > 0 ? '<button class="mini-btn" data-pay="' + t.projectId + '" data-term="' + t.id + '" title="Payment">' + icon("plus") + '</button>' : "") + '</td></tr>';
        }).join("") : '<tr><td colspan="9">' + emptyBox("cal", "No installments yet", "Create a payment schedule in the project") + '</td></tr>') +
        '</tbody></table></div></div>';
}

let DF = {q: "", cat: ""};

function viewDocuments() {
    const q = (DF.q || "").toLowerCase();
    let rows = DB.documents.slice();
    if (DF.cat) rows = rows.filter((d) => d.category === DF.cat);
    if (q) rows = rows.filter((d) => {
        const c = custById(d.customerId), p = projById(d.projectId);
        return (d.title + " " + d.name + " " + d.category + " " + (c ? c.company : "") + " " + (p ? p.name : "")).toLowerCase().includes(q);
    });
    return '<div class="page-head"><span class="info">' + rows.length + ' documents</span><span class="spacer"></span>' +
        '<button class="btn btn-primary btn-sm" id="upDoc2">' + icon("up") + ' Upload document</button></div>' +
        '<div class="card" style="margin-bottom:14px"><div class="card-body" style="padding:13px 16px"><div class="filterbar">' +
        '<div class="gsearch" style="width:250px">' + icon("search") + '<input id="dSearch" placeholder="Title, file, customer..." value="' + esc(DF.q) + '"></div>' +
        '<select id="dCat"><option value="">All categories</option>' + DOC_CATS.map((x) => '<option ' + (DF.cat === x ? "selected" : "") + '>' + x + '</option>').join("") + '</select>' +
        '<button class="btn btn-ghost btn-sm" id="dClear">' + icon("x") + ' Clear</button>' +
        '</div></div></div>' +
        '<div class="card"><div class="card-body">' +
        (rows.length ? rows.map((d) => {
            const c = custById(d.customerId), p = projById(d.projectId);
            return '<div class="doc">' +
                '<span class="doc-ico">' + icon("doc") + '</span>' +
                '<div style="flex:1;min-width:0"><div class="doc-n">' + esc(d.title) + '</div>' +
                '<div class="doc-m">' + esc(d.name) + ' · ' + d.sizeKb + ' KB · ' +
                (c ? esc(c.company) : "") + (p ? ' / ' + esc(p.code) : "") + ' · ' + esc(d.by || "") + ' · ' + esc(fmtDT(d.at)) + '</div></div>' +
                '<span class="pill">' + esc(d.category) + '</span>' +
                '<button class="mini-btn" data-preview="' + d.id + '">' + icon("eye") + '</button>' +
                '<a class="mini-btn" href="' + esc(d.url) + '" target="_blank" rel="noopener" download>' + icon("down") + '</a>' +
                '<button class="mini-btn danger" data-deldoc="' + d.id + '">' + icon("del") + '</button></div>';
        }).join("") : emptyBox("doc", "No documents yet", "Upload a contract, KYC, invoice or delivery proof")) +
        '</div></div>';
}

function viewFollowups() {
    const today = todayISO();
    const list = DB.followups.slice().sort((a, b) => String(b.happenedOn).localeCompare(String(a.happenedOn)));
    const due = DB.followups.filter((f) => f.nextDate && f.nextDate <= today);
    return '<div class="page-head"><span class="info">' + list.length + ' follow-ups · <b style="color:#d94b4b">' + due.length + ' pending</b></span><span class="spacer"></span>' +
        '<button class="btn btn-primary btn-sm" id="fuAdd">' + icon("plus") + ' Log Follow-up</button></div>' +
        (due.length ? '<div class="notice warn">' + icon("alert") + '<div><b>' + due.length + ' follow-ups are pending.</b><br>' +
            due.slice(0, 4).map((f) => esc(f.customer) + ' — ' + esc(f.nextNote || f.summary)).join("<br>") + '</div></div>' : "") +
        '<div class="card"><div class="card-body">' +
        (list.length ? list.map(fuRow) : emptyBox("chat", "No follow-ups yet", "Talk to a customer and log it")) +
        '</div></div>';
}

function viewSales() {
    const rows = DB.sales.map((s) => {
        const custs = DB.customers.filter((c) => c.onboardedBy === s.name);
        const ids = custs.map((c) => String(c.id));
        const projs = DB.projects.filter((p) => ids.includes(String(p.customerId)));
        const value = projs.reduce((a, p) => a + p.money.scheduled, 0);
        const received = projs.reduce((a, p) => a + p.money.received, 0);
        const over = projs.reduce((a, p) => a + p.money.overdue, 0);
        return {s: s, custs: custs.length, projs: projs.length, value: value, received: received, over: over};
    }).sort((a, b) => b.value - a.value);

    return '<div class="page-head"><span class="info">' + DB.sales.length + ' salespeople · ' +
        DB.customers.filter((c) => c.onboardedBy).length + ' onboarded customers</span>' +
        '<span class="spacer"></span><button class="btn btn-ghost btn-sm" id="expSales">' + icon("down") + ' Export CSV</button>' +
        '<button class="btn btn-primary btn-sm" id="addSales">' + icon("plus") + ' Add Salesperson</button></div>' +

        '<div class="grid-3" style="margin-bottom:15px">' + rows.map((r) => {
            const rate = pct(r.received, r.value);
            return '<div class="card" style="padding:17px 18px">' +
                '<div style="display:flex;align-items:center;gap:12px;margin-bottom:13px">' + avatar(r.s.name, "lg") +
                '<div style="min-width:0"><div style="font-size:15px;font-weight:670">' + esc(r.s.name) + '</div>' +
                '<div class="muted" style="font-size:12.1px">' + esc(r.s.phone || r.s.email || "—") + '</div>' +
                '<div style="margin-top:6px"><span class="badge ' + (r.s.status === "Active" ? "b-green" : "b-grey") + '">' + esc(r.s.status) + '</span></div></div></div>' +
                '<div class="money-grid" style="grid-template-columns:1fr 1fr;gap:9px">' +
                '<div class="money-cell" style="padding:9px 11px"><div class="k">Onboarded</div><div class="v" style="font-size:14.5px">' + r.custs + '</div></div>' +
                '<div class="money-cell" style="padding:9px 11px"><div class="k">Projects</div><div class="v" style="font-size:14.5px">' + r.projs + '</div></div>' +
                '</div>' +
                '<div style="margin-top:12px"><div class="progress-row"><span>Collected</span><b>' + money(r.received) + ' / ' + money(r.value) + '</b></div>' +
                '<div class="bar' + (rate >= 80 ? " green" : rate >= 40 ? "" : " amber") + '" style="height:6px"><i style="width:' + rate + '%"></i></div></div>' +
                (r.over > 0 ? '<div style="margin-top:10px"><span class="badge b-red">' + money(r.over) + ' overdue</span></div>' : "") +
                (Number(r.s.target) > 0 ? '<div style="margin-top:8px;font-size:11.4px;color:var(--muted)">Target ' + money(r.s.target) + '</div>' : "") +
                '<div class="row-actions" style="margin-top:10px;justify-content:flex-start">' +
                '<button class="mini-btn" data-editsales="' + r.s.id + '">' + icon("edit") + '</button>' +
                '<button class="mini-btn danger" data-delsales="' + r.s.id + '">' + icon("del") + '</button></div>' +
                '</div>';
        }).join("") + '</div>' +

        '<div class="card"><div class="card-head"><div><h3>Collection by salesperson</h3><div class="sub">Revenue and collection by salesperson</div></div></div>' +
        '<div class="table-wrap"><table><thead><tr><th>Salesperson</th><th>Customers</th><th>Projects</th>' +
        '<th class="num">Total Value</th><th class="num">Received</th><th class="num">Overdue</th><th>Collection</th></tr></thead><tbody>' +
        rows.map((r) => {
            const rate = pct(r.received, r.value);
            return '<tr><td class="strong">' + esc(r.s.name) + '</td><td>' + r.custs + '</td><td>' + r.projs + '</td>' +
                '<td class="num">' + money(r.value) + '</td><td class="num" style="color:#3d7a24">' + money(r.received) + '</td>' +
                '<td class="num" style="color:' + (r.over ? "#d94b4b" : "inherit") + '">' + money(r.over) + '</td>' +
                '<td style="min-width:130px"><div class="bar' + (rate >= 80 ? " green" : rate >= 40 ? "" : " amber") + '"><i style="width:' + rate + '%"></i></div>' +
                '<span class="muted" style="font-size:10.8px">' + rate + '%</span></td></tr>';
        }).join("") + '</tbody></table></div></div>';
}

function viewNotifications() {
    const list = DB.notifications || [];
    const look = (k) => {
        const m = {
            payment_due: {i: "clock", c: "#e0a13a"}, overdue: {i: "alert", c: "#d94b4b"},
            amc: {i: "refresh", c: "#5aa236"}, followup: {i: "chat", c: "#0b3d91"},
            handover: {i: "flag", c: "#7a5af8"}, payment: {i: "cash", c: "#5aa236"},
            customer: {i: "cust", c: "#0b3d91"}, project: {i: "proj", c: "#2e75b6"}
        };
        return m[k] || {i: "bell", c: "#8894ac"};
    };
    return '<div class="page-head"><span class="info">' + list.length + ' notifications' +
        (DB.unread ? ' · <b style="color:#d94b4b">' + DB.unread + ' unread</b>' : "") + '</span><span class="spacer"></span>' +
        (DB.unread ? '<button class="btn btn-ghost btn-sm" id="markAll">' + icon("check") + ' Mark all read</button>' : "") + '</div>' +
        '<div class="card" style="overflow:hidden">' +
        (list.length ? list.map((n) => {
            const L = look(n.kind);
            return '<div class="mini-item" style="padding:13px 16px;cursor:pointer;' +
                (n.read ? "" : "background:#f6f9ff;box-shadow:inset 3px 0 0 #0b3d91;") + '" data-notif="' + n.id + '"' +
                (n.linkType === "project" && n.linkId ? ' data-nproj="' + n.linkId + '"' : '') +
                (n.linkType === "customer" && n.linkId ? ' data-ncust="' + n.linkId + '"' : '') + '>' +
                '<span style="width:36px;height:36px;border-radius:10px;display:grid;place-items:center;flex:0 0 36px;background:' + L.c + '1a;color:' + L.c + '">' + icon(L.i) + '</span>' +
                '<div style="flex:1;min-width:0"><div class="t">' + esc(n.title) + '</div>' +
                '<div class="s">' + esc(n.body || "") + '</div>' +
                '<div class="s" style="margin-top:3px;opacity:.8">' + esc(fmtDT(n.at)) + '</div></div>' +
                (n.read ? "" : '<span class="badge b-navy">New</span>') + '</div>';
        }).join("") : emptyBox("bell", "No notifications", "Payment due, overdue and follow-up alerts appear here")) +
        '</div>';
}

function viewAudit() {
    return '<div class="page-head"><span class="info">' + DB.audit.length + ' recent entries</span></div>' +
        '<div class="card"><div class="card-body">' +
        (DB.audit.length ? DB.audit.map((a) => {
            const dot = a.action === "payment" ? "g" : a.action === "delete" ? "r" : a.action === "create" ? "n" : a.action === "upload" ? "v" : "a";
            return '<div class="tl"><i class="dot ' + dot + '"></i><div style="flex:1">' +
                '<div class="t"><b>' + esc(a.who) + '</b> · ' + esc(a.action) + (a.entity_code ? ' <span class="pill">' + esc(a.entity_code) + '</span>' : "") +
                (a.detail ? '<br>' + esc(a.detail) : "") + '</div>' +
                '<div class="w">' + esc(fmtDT(a.at)) + '</div></div></div>';
        }).join("") : emptyBox("clock", "No activity yet", "Activity will be recorded here")) +
        '</div></div>';
}

function viewReports() {
    const p = DB.payments;
    const totalRec = p.reduce((s, x) => s + x.amount, 0);
    const totalTds = p.reduce((s, x) => s + x.tds, 0);
    const sched = DB.terms.filter((t) => t.status !== "Cancelled").reduce((s, t) => s + t.amount, 0);
    const over = DB.terms.filter((t) => t.status === "Overdue");
    const overAmt = over.reduce((s, t) => s + t.balance, 0);

    const today = todayISO();
    const age = {"0-30": 0, "31-60": 0, "61-90": 0, "90+": 0};
    over.forEach((t) => {
        const d = Math.abs(daysBetween(today, t.dueDate));
        if (d <= 30) age["0-30"] += t.balance;
        else if (d <= 60) age["31-60"] += t.balance;
        else if (d <= 90) age["61-90"] += t.balance;
        else age["90+"] += t.balance;
    });

    const months = [];
    for (let i = 5; i >= 0; i--) {
        const d = new Date();
        d.setDate(1);
        d.setMonth(d.getMonth() - i);
        const pre = d.getFullYear() + "-" + String(d.getMonth() + 1).padStart(2, "0");
        const val = p.filter((x) => String(x.paidOn).slice(0, 7) === pre).reduce((s, x) => s + x.amount, 0);
        months.push({label: d.toLocaleDateString("en-IN", {month: "short"}), value: val});
    }

    const byMode = {};
    p.forEach((x) => {
        byMode[x.mode] = (byMode[x.mode] || 0) + x.amount;
    });

    const health = {"On Track": 0, "At Risk": 0, "Delayed": 0, "Completed": 0};
    DB.projects.forEach((x) => {
        if (health[x.health] !== undefined) health[x.health]++;
    });

    const rows = DB.projects.map((x) => {
        const doneMs = milestonesOf(x.id).filter((m2) => Number(m2.done)).length;
        const totMs = milestonesOf(x.id).length;
        const msPct = pct(doneMs, totMs);
        const moneyPct = pct(x.money.received, x.money.scheduled);
        return {p: x, msPct: msPct, moneyPct: moneyPct, gap: msPct - moneyPct};
    }).sort((a, b) => b.gap - a.gap);

    return '' +
        '<div class="kpis four">' +
        kpi("Collection rate", pct(totalRec, sched) + "%", money(totalRec) + " of " + money(sched), "chart", "#5aa236", "#eef7e8") +
        kpi("Overdue", money(overAmt, true), over.length + " installments", "alert", "#d94b4b", "#fdeaea") +
        kpi("TDS deducted", money(totalTds), "Received against invoices", "cash", "#7a5af8", "#f0ecff") +
        kpi("Average project value", money(DB.projects.length ? sched / DB.projects.length : 0, true), DB.projects.length + " projects", "proj", "#0b3d91", "#e8eefc") +
        '</div>' +

        '<div class="grid-2e" style="margin-bottom:15px">' +
        '<div class="card"><div class="card-head"><div><h3>Monthly collection</h3><div class="sub">Last 6 months</div></div></div>' +
        '<div class="card-body">' + spark(months) + '</div></div>' +
        '<div class="card"><div class="card-head"><div><h3>Payment mode</h3><div class="sub">How the money came in</div></div></div>' +
        '<div class="card-body">' + hbar(Object.keys(byMode).map((k) => ({
            label: k,
            value: byMode[k],
            money: true
        }))) + '</div></div>' +
        '</div>' +

        '<div class="grid-2e" style="margin-bottom:15px">' +
        '<div class="card"><div class="card-head"><div><h3>Payment Ageing</h3><div class="sub">How old the overdue amounts are</div></div></div>' +
        '<div class="card-body">' + hbar(Object.keys(age).map((k) => ({
            label: k + " days",
            value: age[k],
            money: true
        }))) + '</div></div>' +
        '<div class="card"><div class="card-head"><div><h3>Project Health</h3><div class="sub">All projects</div></div></div>' +
        '<div class="card-body">' + hbar([
            {label: "On Track", value: health["On Track"], color: "#5aa236"},
            {label: "At Risk", value: health["At Risk"], color: "#e0a13a"},
            {label: "Delayed", value: health["Delayed"], color: "#d94b4b"},
            {label: "Completed", value: health["Completed"], color: "#2e75b6"}
        ]) + '</div></div>' +
        '</div>' +

        '<div class="card"><div class="card-head"><div><h3>Milestone vs Collection</h3>' +
        '<div class="sub">Where work is ahead but payment is behind — watch these</div></div></div>' +
        '<div class="table-wrap"><table><thead><tr><th>Project</th><th>Customer</th><th>Milestones</th>' +
        '<th>Collected</th><th>Gap</th><th class="num">Due</th><th>Health</th></tr></thead><tbody>' +
        (rows.length ? rows.map((r) => '<tr class="clickable" data-project="' + r.p.id + '">' +
            '<td class="strong">' + esc(r.p.name) + '<div class="muted" style="font-size:10.8px">' + esc(r.p.code) + '</div></td>' +
            '<td class="muted">' + esc(r.p.customer) + '</td>' +
            '<td style="min-width:110px"><div class="bar green"><i style="width:' + r.msPct + '%"></i></div><span class="muted" style="font-size:10.8px">' + r.msPct + '%</span></td>' +
            '<td style="min-width:110px"><div class="bar"><i style="width:' + r.moneyPct + '%"></i></div><span class="muted" style="font-size:10.8px">' + r.moneyPct + '%</span></td>' +
            '<td><span class="badge ' + (r.gap > 30 ? "b-red" : r.gap > 10 ? "b-amber" : "b-green") + '">' + (r.gap > 0 ? "+" : "") + r.gap + '%</span></td>' +
            '<td class="num">' + money(r.p.money.due) + '</td>' +
            '<td>' + healthBadge(r.p.health) + '</td></tr>').join("") : '<tr><td colspan="7">' + emptyBox("chart", "No data yet", "Create projects and the analysis will appear here") + '</td></tr>') +
        '</tbody></table></div></div>';
}

function viewSettings() {
    const s = DB.settings;
    const isAdmin = ME.role === "Admin";
    return '<div class="card" style="margin-bottom:15px"><div class="card-head">' +
        '<div><h3>Change my password</h3><div class="sub">At least 8 characters, one capital letter, one number</div></div></div>' +
        '<div class="card-body"><div class="form-grid">' +
        '<label class="f"><span>Current password</span><input class="i" id="pwOld" type="password" placeholder="Current password"></label>' +
        '<label class="f"><span>New password</span><input class="i" id="pwNew" type="password" placeholder="New password"></label>' +
        '<label class="f full"><span>Confirm new password</span><input class="i" id="pwNew2" type="password" placeholder="Repeat the new password"></label>' +
        '</div><div style="display:flex;justify-content:flex-end;margin-top:14px">' +
        '<button class="btn btn-primary btn-sm" id="pwSave">' + icon("key") + ' Update password</button>' +
        '</div></div></div>' +
        '<div class="grid-2e">' +
        '<div>' +
        (isAdmin ? '<div class="card" style="margin-bottom:15px"><div class="card-head">' +
            '<div><h3>Alert Settings</h3><div class="sub">How many days before an alert</div></div></div>' +
            '<div class="card-body"><div class="form-grid">' +
            '<label class="f"><span>Payment due alert</span><input class="i" type="number" min="1" max="180" id="stPay" value="' + (s.paymentAlertDays || 7) + '"></label>' +
            '<label class="f"><span>AMC / renewal alert</span><input class="i" type="number" min="1" max="180" id="stAmc" value="' + (s.amcAlertDays || 7) + '"></label>' +
            '<label class="f"><span>Follow-up alert</span><input class="i" type="number" min="1" max="180" id="stFu" value="' + (s.followupAlertDays || 1) + '"></label>' +
            '<label class="f"><span>Company name</span><input class="i" id="stComp" value="' + esc(s.companyName || "10X Agency") + '"></label>' +
            '<label class="f"><span>Display currency</span><select class="i" id="stCur">' +
            ["INR", "USD"].map((c) => '<option value="' + c + '" ' + (CUR === c ? "selected" : "") + '>' + c + (c === "INR" ? " (₹)" : " ($)") + '</option>').join("") +
            '</select><div class="hint">Amounts are stored in INR. USD is only a display conversion.</div></label>' +
            '</div><div style="display:flex;justify-content:flex-end;margin-top:14px">' +
            '<button class="btn btn-primary btn-sm" id="stSave">' + icon("check") + ' Save settings</button>' +
            '</div></div></div>' : "") +
        '<div class="card"><div class="card-head"><div><h3>My Profile</h3><div class="sub">How you appear in the workspace</div></div></div>' +
        '<div class="card-body">' +
        '<div class="person" style="margin-bottom:14px">' + avatar(ME.name, "lg") +
        '<div><div style="font-size:15.5px;font-weight:670">' + esc(ME.name) + '</div>' +
        '<div class="muted" style="font-size:12.2px">' + esc(ME.designation || ME.role) + '</div>' +
        '<div style="margin-top:6px"><span class="role-pill ' + (ME.role === "Admin" ? "rp-admin" : "rp-manager") + '">' + esc(ME.role) + '</span></div></div></div>' +
        '<div class="field"><div class="k">Email</div><div class="v">' + esc(ME.email) + '</div></div>' +
        '<button class="btn btn-danger btn-sm" id="logout2" style="margin-top:14px">' + icon("out") + ' Sign out</button>' +
        '</div></div>' +
        '</div>' +
        '<div class="card"><div class="card-head"><div><h3>Users</h3><div class="sub">' + DB.users.length + ' accounts</div></div>' +
        '<div class="right">' + (isAdmin ? '<button class="btn btn-primary btn-sm" id="addUser">' + icon("plus") + ' Add user</button>' : "") + '</div></div>' +
        '<div class="card-body"><div class="mini-list">' + DB.users.map((u) => '<div class="mini-item">' + avatar(u.name) +
            '<div style="flex:1;min-width:0"><div class="t">' + esc(u.name) + '</div>' +
            '<div class="s">' + esc(u.email) + ' · ' + esc(u.designation || u.role) + '</div></div>' +
            '<span class="badge ' + (u.status === "Active" ? "b-green" : "b-grey") + '">' + esc(u.status) + '</span>' +
            '<span class="role-pill ' + (u.role === "Admin" ? "rp-admin" : "rp-manager") + '">' + esc(u.role) + '</span>' +
            (isAdmin ? '<button class="mini-btn" data-edituser="' + u.id + '">' + icon("edit") + '</button>' : "") +
            (isAdmin && u.id !== ME.id ? '<button class="mini-btn danger" data-deluser="' + u.id + '">' + icon("del") + '</button>' : "") +
            '</div>').join("") + '</div></div></div>' +
        '</div>';
}

/* ---------------- Modals ---------------- */
function modal(o) {
    closeModal();
    const ov = document.createElement("div");
    ov.className = "overlay";
    ov.id = "overlay";
    ov.innerHTML = '<div class="modal ' + (o.wide ? "wide" : "") + '">' +
        '<div class="modal-head"><div><h3>' + esc(o.title) + '</h3>' + (o.sub ? '<div class="sub">' + esc(o.sub) + '</div>' : "") + '</div>' +
        '<button class="x" id="mX">&#10005;</button></div>' +
        '<div class="modal-body">' + o.body + '</div>' +
        '<div class="modal-foot">' + o.foot + '</div></div>';
    document.body.appendChild(ov);
    ov.addEventListener("click", (e) => {
        if (e.target === ov) closeModal();
    });
    const x = ov.querySelector("#mX");
    if (x) x.onclick = closeModal;
    const cx = ov.querySelector("#mCancel");
    if (cx) cx.onclick = closeModal;
    if (o.mount) o.mount(ov);
}

function closeModal() {
    const o = $("#overlay");
    if (o) o.remove();
}

function confirmBox(title, html, label, fn) {
    modal({
        title: title, body: html,
        foot: '<button class="btn btn-ghost" id="mCancel">Cancel</button><button class="btn btn-danger" id="okx">' + esc(label) + '</button>',
        mount(ov) {
            ov.querySelector("#okx").onclick = async function () {
                this.disabled = true;
                this.textContent = "Please wait...";
                try {
                    await fn();
                    closeModal();
                } catch (e) {
                    this.disabled = false;
                    this.textContent = "Retry";
                    toast(e.message, "err");
                }
            };
        }
    });
}

function openCustomer(id) {
    if (!isCoord()) return toast("Only a Coordinator can add customers", "err");
    const c = id ? custById(id) : null;
    const d = c || {
        company: "", contactName: "", email: "", phone: "", altPhone: "", city: "", state: "",
        address: "", gstin: "", pan: "", billingName: "", billingAddr: "", source: "",
        industry: "", onboardedBy: "", status: "Active", internalNotes: ""
    };

    modal({
        wide: true,
        title: c ? "Edit customer — " + c.code : "New Customer",
        sub: c ? "Changes are saved immediately" : "Onboard a customer and create a project",
        body: '<div class="form-grid">' +
            '<label class="f full"><span>Company name *</span><input class="i" id="cCo" value="' + esc(d.company) + '" placeholder="e.g. Sunrise Mart Pvt Ltd"></label>' +
            '<label class="f"><span>Contact person</span><input class="i" id="cCn" value="' + esc(d.contactName) + '" placeholder="e.g. Rakesh Gupta"></label>' +
            '<label class="f"><span>Phone</span><input class="i" id="cPh" value="' + esc(d.phone) + '" placeholder="+91 ..."></label>' +
            '<label class="f"><span>Alternate phone</span><input class="i" id="cPh2" value="' + esc(d.altPhone) + '"></label>' +
            '<label class="f"><span>Email</span><input class="i" id="cEm" type="email" value="' + esc(d.email) + '" placeholder="name@company.in"></label>' +
            '<label class="f"><span>City</span><input class="i" id="cCi" value="' + esc(d.city) + '"></label>' +
            '<label class="f"><span>State</span><input class="i" id="cSt" value="' + esc(d.state) + '"></label>' +
            '<label class="f"><span>GSTIN</span><input class="i" id="cGst" value="' + esc(d.gstin) + '" placeholder="27AABCS1429P1ZV"></label>' +
            '<label class="f"><span>PAN</span><input class="i" id="cPan" value="' + esc(d.pan) + '"></label>' +
            '<label class="f"><span>Source</span><input class="i" id="cSrc" value="' + esc(d.source) + '" placeholder="Referral / Website / Cold call"></label>' +
            '<label class="f"><span>Industry</span><input class="i" id="cInd" value="' + esc(d.industry) + '" placeholder="Retail / FMCG / IT"></label>' +
            '<label class="f"><span>Onboarded by (salesperson)</span><select class="i" id="cOb"><option value="">— select —</option>' +
            DB.sales.map((s) => '<option ' + (d.onboardedBy === s.name ? "selected" : "") + '>' + esc(s.name) + '</option>').join("") +
            '</select><div class="hint">Salespeople do not have a login — this is a record only</div></label>' +
            '<label class="f"><span>Status</span><select class="i" id="cStat">' +
            ["Active", "On Hold", "Completed", "Churned"].map((x) => '<option ' + (d.status === x ? "selected" : "") + '>' + x + '</option>').join("") +
            '</select></label>' +
            '<label class="f full"><span>Address</span><textarea class="i" id="cAd" style="min-height:56px">' + esc(d.address) + '</textarea></label>' +
            '<label class="f full"><span>Billing name &amp; address</span><textarea class="i" id="cBill" style="min-height:56px">' + esc(d.billingAddr) + '</textarea></label>' +
            '<label class="f full"><span>Internal notes (not visible to the customer)</span><textarea class="i" id="cNote" style="min-height:56px">' + esc(d.internalNotes) + '</textarea></label>' +
            '</div>',
        foot: '<button class="btn btn-ghost" id="mCancel">Cancel</button>' +
            '<button class="btn btn-primary" id="cSave">' + (c ? "Save changes" : "Create customer") + '</button>',
        mount(ov) {
            ov.querySelector("#cSave").onclick = async function () {
                const data = {
                    id: c ? c.id : 0,
                    company: ov.querySelector("#cCo").value.trim(),
                    contactName: ov.querySelector("#cCn").value, email: ov.querySelector("#cEm").value,
                    phone: ov.querySelector("#cPh").value, altPhone: ov.querySelector("#cPh2").value,
                    city: ov.querySelector("#cCi").value, state: ov.querySelector("#cSt").value,
                    gstin: ov.querySelector("#cGst").value, pan: ov.querySelector("#cPan").value,
                    source: ov.querySelector("#cSrc").value, industry: ov.querySelector("#cInd").value,
                    onboardedBy: ov.querySelector("#cOb").value, status: ov.querySelector("#cStat").value,
                    address: ov.querySelector("#cAd").value, billingAddr: ov.querySelector("#cBill").value,
                    internalNotes: ov.querySelector("#cNote").value
                };
                if (!data.company) return toast("Company name is required", "err");
                this.disabled = true;
                this.textContent = "Saving...";
                try {
                    await API.saveCustomer(data);
                    closeModal();
                    await refresh(true);
                    toast("Customer saved");
                } catch (e) {
                    this.disabled = false;
                    this.textContent = "Retry";
                    toast(e.message, "err");
                }
            };
        }
    });
}

function openProject(id, presetCustomer) {
    if (!isCoord()) return toast("Only a Coordinator can manage projects", "err");
    const p = id ? projById(id) : null;
    const d = p || {
        customerId: presetCustomer || "", name: "", description: "", type: "", coordinator: ME.name,
        value: 0, cost: 0, startDate: todayISO(), deliveryDate: "", actualEnd: "",
        status: "Onboarding", health: "On Track", delayReason: "", amcRequired: 0,
        amcStart: "", amcEnd: "", amcAmount: 0, renewAlertDays: 7, handoverDone: 0, handoverNotes: "", notes: ""
    };

    modal({
        wide: true,
        title: p ? "Edit project — " + p.code : "New Project",
        body: '<div class="form-grid">' +
            '<label class="f full"><span>Project name *</span><input class="i" id="pN" value="' + esc(d.name) + '" placeholder="e.g. E-commerce website"></label>' +
            '<label class="f"><span>Customer *</span><select class="i" id="pC">' +
            DB.customers.map((c) => '<option value="' + c.id + '" ' + (String(d.customerId) === String(c.id) ? "selected" : "") + '>' + esc(c.company) + '</option>').join("") +
            '</select></label>' +
            '<label class="f"><span>Type</span><input class="i" id="pT" value="' + esc(d.type) + '" placeholder="Website / SEO / Ads / App"></label>' +
            '<label class="f"><span>Project value (₹) *</span><input class="i" type="number" id="pV" value="' + (d.value || 0) + '"></label>' +
            '<label class="f"><span>Internal cost (₹)</span><input class="i" type="number" id="pCost" value="' + (d.cost || 0) + '"><div class="hint">For margin tracking — not visible to the customer</div></label>' +
            '<label class="f"><span>Coordinator</span><select class="i" id="pCoord">' +
            DB.users.map((u) => '<option ' + (d.coordinator === u.name ? "selected" : "") + '>' + esc(u.name) + '</option>').join("") +
            '</select></label>' +
            '<label class="f"><span>Status</span><select class="i" id="pS">' +
            ["Onboarding", "In Progress", "On Hold", "Delivered", "Cancelled"].map((x) => '<option ' + (d.status === x ? "selected" : "") + '>' + x + '</option>').join("") +
            '</select></label>' +
            '<label class="f"><span>Health</span><select class="i" id="pH">' +
            ["On Track", "At Risk", "Delayed", "Completed"].map((x) => '<option ' + (d.health === x ? "selected" : "") + '>' + x + '</option>').join("") +
            '</select></label>' +
            '<label class="f"><span>Delay reason</span><input class="i" id="pDR" value="' + esc(d.delayReason) + '" placeholder="e.g. Client approval pending"></label>' +
            '<label class="f"><span>Start date</span><input class="i" type="date" id="pSD" value="' + esc(String(d.startDate || "").slice(0, 10)) + '"></label>' +
            '<label class="f"><span>Delivery date</span><input class="i" type="date" id="pDD" value="' + esc(String(d.deliveryDate || "").slice(0, 10)) + '"></label>' +
            '<label class="f full"><span>Description</span><textarea class="i" id="pDesc">' + esc(d.description) + '</textarea></label>' +
            '</div>' +
            '<div class="divider"></div>' +
            '<div style="font-size:12.4px;font-weight:700;color:var(--text2);text-transform:uppercase;letter-spacing:.5px;margin-bottom:10px">AMC / Renewal</div>' +
            '<div class="form-grid">' +
            '<label class="f full"><div class="checkline"><input type="checkbox" id="pAmcOn" ' + (Number(d.amcRequired) ? "checked" : "") + '><span>This project has an AMC / renewal</span></div></label>' +
            '<label class="f"><span>AMC start</span><input class="i" type="date" id="pAS" value="' + esc(String(d.amcStart || "").slice(0, 10)) + '"></label>' +
            '<label class="f"><span>AMC end</span><input class="i" type="date" id="pAE" value="' + esc(String(d.amcEnd || "").slice(0, 10)) + '"></label>' +
            '<label class="f"><span>AMC amount (₹)</span><input class="i" type="number" id="pAA" value="' + (d.amcAmount || 0) + '"></label>' +
            '<label class="f"><span>Alert how many days before</span><input class="i" type="number" min="1" max="180" id="pRD" value="' + (d.renewAlertDays || 7) + '"></label>' +
            '</div>' +
            '<div class="divider"></div>' +
            '<div style="font-size:12.4px;font-weight:700;color:var(--text2);text-transform:uppercase;letter-spacing:.5px;margin-bottom:10px">Handover</div>' +
            '<div class="checkline"><input type="checkbox" id="pHo" ' + (Number(d.handoverDone) ? "checked" : "") + '><span>Handover complete (documents, final payment, sign-off)</span></div>' +
            '<label class="f full" style="margin-top:10px"><span>Handover notes</span><textarea class="i" id="pHoN" style="min-height:56px">' + esc(d.handoverNotes) + '</textarea></label>' +
            '<label class="f full" style="margin-top:12px"><span>Internal notes</span><textarea class="i" id="pNotes" style="min-height:56px">' + esc(d.notes) + '</textarea></label>',
        foot: '<button class="btn btn-ghost" id="mCancel">Cancel</button>' +
            '<button class="btn btn-primary" id="pSave">' + (p ? "Save changes" : "Create project") + '</button>',
        mount(ov) {
            ov.querySelector("#pSave").onclick = async function () {
                const data = {
                    id: p ? p.id : 0,
                    customerId: Number(ov.querySelector("#pC").value),
                    name: ov.querySelector("#pN").value.trim(),
                    type: ov.querySelector("#pT").value, value: ov.querySelector("#pV").value,
                    cost: ov.querySelector("#pCost").value, coordinator: ov.querySelector("#pCoord").value,
                    status: ov.querySelector("#pS").value, health: ov.querySelector("#pH").value,
                    delayReason: ov.querySelector("#pDR").value,
                    startDate: ov.querySelector("#pSD").value, deliveryDate: ov.querySelector("#pDD").value,
                    description: ov.querySelector("#pDesc").value,
                    amcRequired: ov.querySelector("#pAmcOn").checked ? 1 : 0,
                    amcStart: ov.querySelector("#pAS").value, amcEnd: ov.querySelector("#pAE").value,
                    amcAmount: ov.querySelector("#pAA").value, renewAlertDays: ov.querySelector("#pRD").value,
                    handoverDone: ov.querySelector("#pHo").checked ? 1 : 0,
                    handoverNotes: ov.querySelector("#pHoN").value, notes: ov.querySelector("#pNotes").value
                };
                if (!data.name) return toast("Project name is required", "err");
                if (!data.customerId) return toast("Select a customer", "err");
                this.disabled = true;
                this.textContent = "Saving...";
                try {
                    const r = await API.saveProject(data);
                    closeModal();
                    await refresh(true);
                    toast("Project saved");
                    if (r.created) setTimeout(() => openSchedule(r.id), 200);
                } catch (e) {
                    this.disabled = false;
                    this.textContent = "Retry";
                    toast(e.message, "err");
                }
            };
        }
    });
}

function openSchedule(pid) {
    const p = projById(pid);
    if (!p) return;
    const terms = termsOf(pid).sort((a, b) => a.seq - b.seq);
    const ms = milestonesOf(pid);
    let list = terms.map((t) => ({
        id: t.id, label: t.label, amount: t.amount, trigger: t.trigger, milestoneId: t.milestoneId,
        dueDate: t.dueDate, graceDays: t.graceDays, locked: t.received > 0 || t.status === "Paid"
    }));

    modal({
        wide: true,
        title: "Payment schedule — " + p.code,
        sub: "Project value " + money(p.value) + " · three modes: fixed date, milestone linked or recurring",
        body: '<div id="tWrap"></div>' +
            '<div style="display:flex;gap:8px;margin-top:12px;flex-wrap:wrap">' +
            '<button class="btn btn-ghost btn-sm" id="tAdd">' + icon("plus") + ' Add installment</button>' +
            '<button class="btn btn-ghost btn-sm" id="tSplit">Auto-split (30/40/30)</button>' +
            '<button class="btn btn-ghost btn-sm" id="tRec">' + icon("refresh") + ' Monthly recurring</button>' +
            '</div>' +
            '<div class="notice" id="tInfo" style="margin-top:14px">' + icon("alert") +
            '<div>Installments that already have payments are locked — you cannot edit them here.<br>' +
            'Trigger types: <b>Fixed date</b> = you set the date · <b>Milestone linked</b> = linked to a milestone · <b>Recurring</b> = monthly.</div></div>',
        foot: '<button class="btn btn-ghost" id="mCancel">Cancel</button>' +
            '<button class="btn btn-primary" id="tSave">Save schedule</button>',
        mount(ov) {
            const wrap = ov.querySelector("#tWrap");

            function redraw() {
                const tot = list.reduce((s, t) => s + Number(t.amount || 0), 0);
                const diff = Number(p.value) - tot;
                wrap.innerHTML =
                    '<div class="notice' + (Math.abs(diff) < 1 ? "" : " warn") + '">' + icon(Math.abs(diff) < 1 ? "check" : "alert") +
                    '<div>Schedule total <b>' + money(tot) + '</b> · Project value <b>' + money(p.value) + '</b>' +
                    (Math.abs(diff) < 1 ? ' — match' : ' — <b>' + money(Math.abs(diff)) + (diff > 0 ? " short" : " over") + '</b>') + '</div></div>' +
                    list.map((t, i) => '<div class="term ' + (t.locked ? "paid" : "") + '" style="align-items:stretch">' +
                        '<div class="term-no">' + (i + 1) + '</div>' +
                        '<div style="flex:1;display:grid;grid-template-columns:1.6fr 1fr 1.2fr 1.1fr 0.7fr;gap:8px;align-items:end">' +
                        '<label class="f" style="margin:0"><span>Label</span><input class="i" data-f="label" data-i="' + i + '" value="' + esc(t.label) + '" ' + (t.locked ? "disabled" : "") + '></label>' +
                        '<label class="f" style="margin:0"><span>Amount ₹</span><input class="i" type="number" data-f="amount" data-i="' + i + '" value="' + t.amount + '" ' + (t.locked ? "disabled" : "") + '></label>' +
                        '<label class="f" style="margin:0"><span>Mode</span><select class="i" data-f="trigger" data-i="' + i + '" ' + (t.locked ? "disabled" : "") + '>' +
                        ["fixed", "milestone", "recurring"].map((x) => '<option value="' + x + '" ' + (t.trigger === x ? "selected" : "") + '>' + (TRI_LABEL[x]) + '</option>').join("") +
                        '</select></label>' +
                        '<label class="f" style="margin:0"><span>Due date</span><input class="i" type="date" data-f="dueDate" data-i="' + i + '" value="' + esc(String(t.dueDate || "").slice(0, 10)) + '" ' + (t.locked ? "disabled" : "") + '></label>' +
                        (t.trigger === "milestone" && ms.length
                            ? '<label class="f" style="margin:0"><span>Milestone</span><select class="i" data-f="milestoneId" data-i="' + i + '"><option value="">—</option>' +
                            ms.map((m) => '<option value="' + m.id + '" ' + (String(t.milestoneId) === String(m.id) ? "selected" : "") + '>' + esc(m.name) + '</option>').join("") + '</select></label>'
                            : '<label class="f" style="margin:0"><span>Grace (days)</span><input class="i" type="number" data-f="graceDays" data-i="' + i + '" value="' + (t.graceDays || 0) + '" ' + (t.locked ? "disabled" : "") + '></label>') +
                        '</div>' +
                        '<div style="display:flex;flex-direction:column;gap:4px;justify-content:center">' +
                        (t.locked ? '<span class="badge b-green">paid</span>' : '<button class="mini-btn danger" data-tdel="' + i + '">' + icon("del") + '</button>') +
                        '</div></div>').join("");

                wrap.querySelectorAll("[data-f]").forEach((el) => {
                    el.onchange = () => {
                        const i = Number(el.dataset.i), f = el.dataset.f;
                        list[i][f] = (f === "amount" || f === "graceDays") ? Number(el.value) : el.value;
                        if (f === "trigger") redraw();
                    };
                });
                wrap.querySelectorAll("[data-tdel]").forEach((b) => {
                    b.onclick = () => {
                        list.splice(Number(b.dataset.tdel), 1);
                        redraw();
                    };
                });
            }

            ov.querySelector("#tAdd").onclick = () => {
                list.push({
                    label: "Installment " + (list.length + 1),
                    amount: 0,
                    trigger: "fixed",
                    milestoneId: null,
                    dueDate: "",
                    graceDays: 0,
                    locked: false
                });
                redraw();
            };
            ov.querySelector("#tSplit").onclick = () => {
                if (list.some((t) => t.locked)) return toast("There are paid installments — auto-split is not available", "err");
                const v = Number(p.value) || 0;
                list = [
                    {
                        label: "Advance",
                        amount: Math.round(v * 0.3),
                        trigger: "fixed",
                        milestoneId: null,
                        dueDate: p.startDate || todayISO(),
                        graceDays: 0,
                        locked: false
                    },
                    {
                        label: "Mid project",
                        amount: Math.round(v * 0.4),
                        trigger: "milestone",
                        milestoneId: ms[1] ? ms[1].id : null,
                        dueDate: "",
                        graceDays: 7,
                        locked: false
                    },
                    {
                        label: "On delivery",
                        amount: v - Math.round(v * 0.3) - Math.round(v * 0.4),
                        trigger: "milestone",
                        milestoneId: ms[ms.length - 1] ? ms[ms.length - 1].id : null,
                        dueDate: p.deliveryDate || "",
                        graceDays: 7,
                        locked: false
                    }
                ];
                redraw();
                toast("Applied a 30/40/30 split");
            };
            ov.querySelector("#tRec").onclick = () => {
                closeModal();
                openRecurring(pid);
            };
            ov.querySelector("#tSave").onclick = async function () {
                this.disabled = true;
                this.textContent = "Saving...";
                try {
                    await API.saveTerms(pid, list.filter((t) => !t.locked).map((t) => ({
                        id: t.id || null,
                        label: t.label,
                        amount: t.amount,
                        trigger: t.trigger,
                        milestoneId: t.milestoneId,
                        dueDate: t.dueDate,
                        graceDays: t.graceDays
                    })));
                    closeModal();
                    PTAB = "schedule";
                    await refresh(true);
                    toast("Payment schedule saved");
                } catch (e) {
                    this.disabled = false;
                    this.textContent = "Retry";
                    toast(e.message, "err");
                }
            };
            redraw();
        }
    });
}

function openRecurring(pid) {
    const p = projById(pid);
    modal({
        title: "Monthly recurring — " + p.code,
        sub: "Same amount on the same date every month",
        body: '<div class="form-grid">' +
            '<label class="f"><span>Monthly amount (₹)</span><input class="i" type="number" id="rA" value="' + Math.round((p.value || 0) / 6) + '"></label>' +
            '<label class="f"><span>Number of months</span><input class="i" type="number" id="rM" value="6" min="1" max="60"></label>' +
            '<label class="f"><span>First date</span><input class="i" type="date" id="rD" value="' + esc(String(p.startDate || todayISO()).slice(0, 10)) + '"></label>' +
            '<label class="f"><span>Label</span><input class="i" id="rL" value="Monthly retainer"></label>' +
            '</div><div class="hint" style="margin-top:10px">Each installment will include the month name, for example "Monthly retainer — Oct 2026".</div>',
        foot: '<button class="btn btn-ghost" id="mCancel">Cancel</button><button class="btn btn-primary" id="rGo">Generate</button>',
        mount(ov) {
            ov.querySelector("#rGo").onclick = async function () {
                this.disabled = true;
                this.textContent = "Generating...";
                try {
                    const r = await API.genRecurring({
                        projectId: pid, amount: ov.querySelector("#rA").value,
                        months: ov.querySelector("#rM").value, startDate: ov.querySelector("#rD").value,
                        label: ov.querySelector("#rL").value
                    });
                    closeModal();
                    PTAB = "schedule";
                    await refresh(true);
                    toast(r.count + " installments created");
                } catch (e) {
                    this.disabled = false;
                    this.textContent = "Retry";
                    toast(e.message, "err");
                }
            };
        }
    });
}

function openPayment(pid, termId) {
    const p = projById(pid);
    if (!p) return;
    const terms = termsOf(pid).filter((t) => t.balance > 0).sort((a, b) => a.seq - b.seq);
    const sel = termId ? terms.find((t) => String(t.id) === String(termId)) : terms[0];
    const c = custById(p.customerId);

    modal({
        title: "Record payment — " + p.code,
        sub: (c ? c.company + " · " : "") + "due " + money(p.money.due),
        body: '<div class="form-grid">' +
            '<label class="f full"><span>Against which installment</span><select class="i" id="yTerm">' +
            '<option value="">— no installment (on account) —</option>' +
            terms.map((t) => '<option value="' + t.id + '" ' + (sel && String(sel.id) === String(t.id) ? "selected" : "") + '>' +
                '#' + t.seq + ' ' + esc(t.label) + ' — ' + money(t.balance) + ' remaining' +
                (t.dueDate ? ' (due ' + fmtShort(t.dueDate) + ')' : '') + '</option>').join("") + '</select></label>' +
            '<label class="f"><span>Amount received (₹) *</span><input class="i" type="number" step="0.01" id="yAmt" value="' + (sel ? sel.balance : "") + '"></label>' +
            '<label class="f"><span>TDS deducted (₹)</span><input class="i" type="number" step="0.01" id="yTds" value="0"><div class="hint">Enter this if the customer deducted TDS</div></label>' +
            '<label class="f"><span>Payment date</span><input class="i" type="date" id="yDate" value="' + todayISO() + '"></label>' +
            '<label class="f"><span>Mode</span><select class="i" id="yMode">' + MODES.map((m) => '<option ' + (m === "NEFT" ? "selected" : "") + '>' + m + '</option>').join("") + '</select></label>' +
            '<label class="f"><span>Reference (UTR / cheque no)</span><input class="i" id="yRef" placeholder="e.g. UTR8891234001"></label>' +
            '<label class="f"><span>Bank</span><input class="i" id="yBank" placeholder="e.g. HDFC Bank"></label>' +
            '<label class="f full"><span>Note</span><input class="i" id="yNote" placeholder="e.g. Advance against PO 4412"></label>' +
            '</div>',
        foot: '<button class="btn btn-ghost" id="mCancel">Cancel</button><button class="btn btn-green" id="ySave">' + icon("check") + ' Save payment</button>',
        mount(ov) {
            const selEl = ov.querySelector("#yTerm");
            selEl.onchange = () => {
                const t = terms.find((x) => String(x.id) === String(selEl.value));
                if (t) ov.querySelector("#yAmt").value = t.balance;
            };
            ov.querySelector("#ySave").onclick = async function () {
                const data = {
                    projectId: pid, termId: Number(ov.querySelector("#yTerm").value) || 0,
                    amount: ov.querySelector("#yAmt").value, tds: ov.querySelector("#yTds").value,
                    paidOn: ov.querySelector("#yDate").value, mode: ov.querySelector("#yMode").value,
                    reference: ov.querySelector("#yRef").value, bank: ov.querySelector("#yBank").value,
                    note: ov.querySelector("#yNote").value
                };
                if (!data.amount || Number(data.amount) <= 0) return toast("Enter an amount", "err");
                this.disabled = true;
                this.innerHTML = icon("refresh") + " Saving...";
                try {
                    await API.savePayment(data);
                    closeModal();
                    await refresh(true);
                    toast("Payment recorded");
                    FOCUS = {type: "project", id: pid};
                    PTAB = "payments";
                    DASH = null;
                    render();
                } catch (e) {
                    this.disabled = false;
                    this.textContent = "Retry";
                    toast(e.message, "err");
                }
            };
        }
    });
}

function openMilestones(pid) {
    const p = projById(pid);
    let list = milestonesOf(pid).map((m) => ({
        id: m.id,
        name: m.name,
        dueDate: m.dueDate,
        doneOn: m.doneOn,
        done: !!Number(m.done),
        status: m.status,
        notes: m.notes
    }));
    if (!list.length) {
        list = [
            {name: "Kickoff & requirement gathering", dueDate: "", done: false, status: "Pending", notes: ""},
            {name: "Design approval", dueDate: "", done: false, status: "Pending", notes: ""},
            {name: "Development complete", dueDate: "", done: false, status: "Pending", notes: ""},
            {name: "UAT & sign-off", dueDate: "", done: false, status: "Pending", notes: ""},
            {name: "Live / delivery", dueDate: p.deliveryDate || "", done: false, status: "Pending", notes: ""}
        ];
    }

    modal({
        wide: true,
        title: "Milestones — " + p.code,
        sub: "Build a timeline so the project stays on schedule",
        body: '<div id="msWrap"></div><button class="btn btn-ghost btn-sm" id="msAdd" style="margin-top:10px">' + icon("plus") + ' Add milestone</button>',
        foot: '<button class="btn btn-ghost" id="mCancel">Cancel</button><button class="btn btn-primary" id="msSave">Save milestones</button>',
        mount(ov) {
            const wrap = ov.querySelector("#msWrap");

            function redraw() {
                wrap.innerHTML = list.map((m, i) => '<div class="ms-row" style="align-items:center">' +
                    '<span class="ms-dot ' + (m.done ? "done" : "") + '">' + (m.done ? "✓" : (i + 1)) + '</span>' +
                    '<div style="flex:1;display:grid;grid-template-columns:2fr 1fr 1fr 0.4fr;gap:8px;align-items:end">' +
                    '<label class="f" style="margin:0"><span>Milestone</span><input class="i" data-m="name" data-i="' + i + '" value="' + esc(m.name) + '"></label>' +
                    '<label class="f" style="margin:0"><span>Due date</span><input class="i" type="date" data-m="dueDate" data-i="' + i + '" value="' + esc(String(m.dueDate || "").slice(0, 10)) + '"></label>' +
                    '<label class="f" style="margin:0"><span>Status</span><select class="i" data-m="status" data-i="' + i + '">' +
                    ["Pending", "In Progress", "Done", "Blocked"].map((x) => '<option ' + (m.status === x ? "selected" : "") + '>' + x + '</option>').join("") +
                    '</select></label>' +
                    '<div style="display:flex;gap:5px;padding-bottom:2px">' +
                    '<button class="mini-btn ' + (m.done ? "" : "") + '" data-mdone="' + i + '" title="Toggle done" style="' + (m.done ? "background:#eef7e8;color:#3d7a24;border-color:#cfe6c2" : "") + '">' + icon("check") + '</button>' +
                    '<button class="mini-btn danger" data-mdel="' + i + '">' + icon("del") + '</button>' +
                    '</div>' +
                    '</div></div>').join("");
                wrap.querySelectorAll("[data-m]").forEach((el) => {
                    el.onchange = () => {
                        const row = list[Number(el.dataset.i)];
                        row[el.dataset.m] = el.value;
                        if (el.dataset.m === "status") {
                            row.done = (el.value === "Done");
                            redraw();
                        }
                    };
                    el.oninput = () => {
                        list[Number(el.dataset.i)][el.dataset.m] = el.value;
                    };
                });
                wrap.querySelectorAll("[data-mdone]").forEach((b) => {
                    b.onclick = () => {
                        const i = Number(b.dataset.mdone);
                        list[i].done = !list[i].done;
                        list[i].status = list[i].done ? "Done" : "Pending";
                        redraw();
                    };
                });
                wrap.querySelectorAll("[data-mdel]").forEach((b) => {
                    b.onclick = () => {
                        list.splice(Number(b.dataset.mdel), 1);
                        redraw();
                    };
                });
            }

            ov.querySelector("#msAdd").onclick = () => {
                list.push({name: "", dueDate: "", done: false, status: "Pending", notes: ""});
                redraw();
            };
            ov.querySelector("#msSave").onclick = async function () {
                this.disabled = true;
                this.textContent = "Saving...";
                try {
                    await API.saveMilestones(pid, list.filter((m) => m.name.trim()));
                    closeModal();
                    PTAB = "milestones";
                    await refresh(true);
                    toast("Milestones saved");
                } catch (e) {
                    this.disabled = false;
                    this.textContent = "Retry";
                    toast(e.message, "err");
                }
            };
            redraw();
        }
    });
}

function openUploadDoc(preset) {
    if (!isCoord()) return toast("Only a Coordinator can upload documents", "err");
    modal({
        title: "Upload document",
        sub: "Contract, KYC, invoice, proposal or delivery proof",
        body: '<div class="form-grid">' +
            '<label class="f full"><span>Customer *</span><select class="i" id="uCust">' +
            DB.customers.map((c) => '<option value="' + c.id + '" ' + (preset && String(preset.customerId) === String(c.id) ? "selected" : "") + '>' + esc(c.company) + '</option>').join("") +
            '</select></label>' +
            '<label class="f full"><span>Project (optional)</span><select class="i" id="uProj"><option value="">— no project —</option>' +
            DB.projects.map((p) => '<option value="' + p.id + '" ' + (preset && String(preset.projectId) === String(p.id) ? "selected" : "") + '>' + esc(p.code) + ' — ' + esc(p.name) + '</option>').join("") +
            '</select></label>' +
            '<label class="f"><span>Category</span><select class="i" id="uCat">' + DOC_CATS.map((x) => '<option>' + x + '</option>').join("") + '</select></label>' +
            '<label class="f"><span>Title</span><input class="i" id="uTitle" placeholder="e.g. Signed agreement"></label>' +
            '</div>' +
            '<div class="drop" id="uDrop" style="margin-top:14px">' + icon("up") +
            '<div style="margin-top:6px">Drop a file here or click</div>' +
            '<div style="font-size:11px;margin-top:3px">PDF, image, Word, Excel, max ' + Math.round((CONFIG.MAX_UPLOAD_KB || 10240) / 1024) + ' MB</div></div>' +
            '<input type="file" id="uFile" style="display:none">' +
            '<div id="uStat" style="margin-top:10px;font-size:12.4px;color:var(--muted)"></div>',
        foot: '<button class="btn btn-ghost" id="mCancel">Cancel</button>' +
            '<button class="btn btn-primary" id="uGo" disabled>Upload</button>',
        mount(ov) {
            let file = null;
            const drop = ov.querySelector("#uDrop"), inp = ov.querySelector("#uFile"),
                stat = ov.querySelector("#uStat"), go = ov.querySelector("#uGo");
            drop.onclick = () => inp.click();
            drop.ondragover = (e) => {
                e.preventDefault();
                drop.style.borderColor = "#1a5cc8";
            };
            drop.ondragleave = () => {
                drop.style.borderColor = "";
            };
            drop.ondrop = (e) => {
                e.preventDefault();
                drop.style.borderColor = "";
                if (e.dataTransfer.files[0]) pick(e.dataTransfer.files[0]);
            };
            inp.onchange = () => {
                if (inp.files[0]) pick(inp.files[0]);
            };

            function pick(f) {
                if (f.size > (CONFIG.MAX_UPLOAD_KB || 10240) * 1024) return toast("File is larger than " + Math.round((CONFIG.MAX_UPLOAD_KB || 10240) / 1024) + " MB", "err");
                file = f;
                stat.innerHTML = '<b>' + esc(f.name) + '</b> · ' + Math.round(f.size / 1024) + ' KB';
                if (!ov.querySelector("#uTitle").value) ov.querySelector("#uTitle").value = f.name.replace(/\.[^.]+$/, "");
                go.disabled = false;
            }

            go.onclick = () => {
                if (!file) return toast("Choose a file", "err");
                this.disabled = true;
                this.textContent = "Uploading...";
                const rd = new FileReader();
                rd.onload = async () => {
                    try {
                        await API.uploadDoc({
                            customerId: Number(ov.querySelector("#uCust").value) || 0,
                            projectId: Number(ov.querySelector("#uProj").value) || 0,
                            category: ov.querySelector("#uCat").value,
                            title: ov.querySelector("#uTitle").value.trim() || file.name,
                            name: file.name, mime: file.type || "application/octet-stream", data: String(rd.result)
                        });
                        closeModal();
                        await refresh(true);
                        toast("Document uploaded");
                    } catch (e) {
                        go.disabled = false;
                        go.textContent = "Retry";
                        toast(e.message, "err");
                    }
                };
                rd.readAsDataURL(file);
            };
        }
    });
}

function openPreview(id) {
    const d = DB.documents.find((x) => String(x.id) === String(id));
    if (!d) return;
    const img = d.isImage || /png|jpg|jpeg|gif|webp/i.test(d.name || "");
    const pdf = d.isPdf || /pdf/i.test(d.name || "");
    modal({
        wide: true,
        title: d.title,
        sub: d.name + " · " + d.sizeKb + " KB · " + d.category,
        body: '<div class="preview">' +
            (img ? '<img src="' + esc(d.url) + '" alt="' + esc(d.name) + '">' :
                pdf ? '<iframe src="' + esc(d.url) + '"></iframe>' :
                    '<div style="color:#c3cee5;text-align:center;padding:40px">' + icon("doc") +
                    '<div style="margin-top:10px">This file type cannot be previewed</div>' +
                    '<div style="font-size:12px;margin-top:4px;opacity:.7">Download it to view</div></div>') +
            '</div>' +
            '<div style="display:flex;gap:9px;margin-top:14px;justify-content:flex-end">' +
            '<a class="btn btn-primary btn-sm" href="' + esc(d.url) + '" target="_blank" rel="noopener">' + icon("eye") + ' Open in new tab</a>' +
            '<a class="btn btn-ghost btn-sm" href="' + esc(d.url) + '" download>' + icon("down") + ' Download</a>' +
            '</div>',
        foot: '<button class="btn btn-ghost" id="mCancel">Close</button>'
    });
}

function openFollowup(id, presetCustomer) {
    const f = id ? DB.followups.find((x) => String(x.id) === String(id)) : null;
    modal({
        title: f ? "Edit follow-up" : "Log Follow-up",
        sub: "What was discussed and what comes next",
        body: '<div class="form-grid">' +
            '<label class="f full"><span>Customer *</span><select class="i" id="fCust">' +
            DB.customers.map((c) => '<option value="' + c.id + '" ' + (String(presetCustomer) === String(c.id) ? "selected" : "") + '>' + esc(c.company) + '</option>').join("") +
            '</select></label>' +
            '<label class="f"><span>Type</span><select class="i" id="fKind">' + FU_KINDS.map((k) => '<option>' + k + '</option>').join("") + '</select></label>' +
            '<label class="f"><span>Project (optional)</span><select class="i" id="fProj"><option value="">— none —</option>' +
            DB.projects.map((p) => '<option value="' + p.id + '">' + esc(p.code) + ' — ' + esc(p.name) + '</option>').join("") +
            '</select></label>' +
            '<label class="f full"><span>Summary *</span><input class="i" id="fSum" placeholder="e.g. Discussed the pending payment"></label>' +
            '<label class="f full"><span>Detail</span><textarea class="i" id="fDet"></textarea></label>' +
            '<label class="f"><span>Next follow-up date</span><input class="i" type="date" id="fNext"></label>' +
            '<label class="f"><span>What to do next</span><input class="i" id="fNN" placeholder="e.g. Send the invoice"></label>' +
            '</div>',
        foot: '<button class="btn btn-ghost" id="mCancel">Cancel</button><button class="btn btn-primary" id="fSave">Save follow-up</button>',
        mount(ov) {
            ov.querySelector("#fSave").onclick = async function () {
                const data = {
                    customerId: Number(ov.querySelector("#fCust").value),
                    projectId: Number(ov.querySelector("#fProj").value) || 0,
                    kind: ov.querySelector("#fKind").value,
                    summary: ov.querySelector("#fSum").value.trim(),
                    detail: ov.querySelector("#fDet").value,
                    nextDate: ov.querySelector("#fNext").value,
                    nextNote: ov.querySelector("#fNN").value
                };
                if (!data.summary) return toast("Write a summary", "err");
                this.disabled = true;
                this.textContent = "Saving...";
                try {
                    await API.saveFollowup(data);
                    closeModal();
                    await refresh(true);
                    toast("Follow-up logged");
                } catch (e) {
                    this.disabled = false;
                    this.textContent = "Retry";
                    toast(e.message, "err");
                }
            };
        }
    });
}

function openTask(pid, cid) {
    modal({
        title: "New task",
        body: '<div class="form-grid">' +
            '<label class="f full"><span>What needs to be done *</span><input class="i" id="tT" placeholder="e.g. Send payment reminder"></label>' +
            '<label class="f"><span>Project (optional)</span><select class="i" id="tP"><option value="">— none —</option>' +
            DB.projects.map((p) => '<option value="' + p.id + '" ' + (String(pid) === String(p.id) ? "selected" : "") + '>' + esc(p.code) + ' — ' + esc(p.name) + '</option>').join("") + '</select></label>' +
            '<label class="f"><span>Priority</span><select class="i" id="tPr">' + ["Low", "Medium", "High", "Urgent"].map((x) => '<option ' + (x === "Medium" ? "selected" : "") + '>' + x + '</option>').join("") + '</select></label>' +
            '<label class="f"><span>Due date</span><input class="i" type="date" id="tD" value="' + todayISO() + '"></label>' +
            '<label class="f"><span>Assign to</span><select class="i" id="tA">' + DB.users.map((u) => '<option>' + esc(u.name) + '</option>').join("") + '</select></label>' +
            '</div>',
        foot: '<button class="btn btn-ghost" id="mCancel">Cancel</button><button class="btn btn-primary" id="tSave">Add task</button>',
        mount(ov) {
            ov.querySelector("#tSave").onclick = async function () {
                const data = {
                    title: ov.querySelector("#tT").value.trim(),
                    projectId: Number(ov.querySelector("#tP").value) || 0,
                    customerId: cid || 0,
                    priority: ov.querySelector("#tPr").value,
                    dueDate: ov.querySelector("#tD").value,
                    assignedTo: ov.querySelector("#tA").value
                };
                if (!data.title) return toast("Write a title", "err");
                this.disabled = true;
                this.textContent = "Saving...";
                try {
                    await API.saveTask(data);
                    closeModal();
                    await refresh(true);
                    toast("Task added");
                } catch (e) {
                    this.disabled = false;
                    this.textContent = "Retry";
                    toast(e.message, "err");
                }
            };
        }
    });
}

function openSales(id) {
    const s = id ? DB.sales.find((x) => String(x.id) === String(id)) : null;
    const d = s || {name: "", phone: "", email: "", target: 0, status: "Active"};
    modal({
        title: s ? "Edit sales person" : "Add Salesperson",
        sub: "Salespeople do not have a login — this is a record only",
        body: '<div class="form-grid">' +
            '<label class="f full"><span>Name *</span><input class="i" id="sN" value="' + esc(d.name) + '"></label>' +
            '<label class="f"><span>Phone</span><input class="i" id="sP" value="' + esc(d.phone) + '"></label>' +
            '<label class="f"><span>Email</span><input class="i" id="sE" value="' + esc(d.email) + '"></label>' +
            '<label class="f"><span>Monthly target (₹)</span><input class="i" type="number" id="sT" value="' + (d.target || 0) + '"></label>' +
            '<label class="f"><span>Status</span><select class="i" id="sS">' +
            ["Active", "Inactive"].map((x) => '<option ' + (d.status === x ? "selected" : "") + '>' + x + '</option>').join("") + '</select></label>' +
            '</div>',
        foot: '<button class="btn btn-ghost" id="mCancel">Cancel</button><button class="btn btn-primary" id="sSave">' + (s ? "Save" : "Add") + '</button>',
        mount(ov) {
            ov.querySelector("#sSave").onclick = async function () {
                const data = {
                    id: s ? s.id : 0,
                    name: ov.querySelector("#sN").value.trim(),
                    phone: ov.querySelector("#sP").value,
                    email: ov.querySelector("#sE").value,
                    target: ov.querySelector("#sT").value,
                    status: ov.querySelector("#sS").value
                };
                if (!data.name) return toast("Write a name", "err");
                this.disabled = true;
                this.textContent = "Saving...";
                try {
                    await API.saveSales(data);
                    closeModal();
                    await refresh(true);
                    toast("Salesperson saved");
                } catch (e) {
                    this.disabled = false;
                    this.textContent = "Retry";
                    toast(e.message, "err");
                }
            };
        }
    });
}

function openUser(id) {
    if (ME.role !== "Admin") return toast("Only an Admin can manage users", "err");
    const u = id ? DB.users.find((x) => String(x.id) === String(id)) : null;
    const d = u || {
        name: "", email: "", phone: "", role: "Coordinator", designation: "Project Coordinator",
        city: "", status: "Active", joined: todayISO()
    };
    modal({
        title: u ? "Edit user" : "Add user",
        sub: "Give the new user a temporary password — they will change it on first login",
        body: '<div class="form-grid">' +
            '<label class="f full"><span>Full name *</span><input class="i" id="uN" value="' + esc(d.name) + '"></label>' +
            '<label class="f"><span>Email (login ID) *</span><input class="i" id="uE" type="email" value="' + esc(d.email) + '"></label>' +
            '<label class="f"><span>Phone</span><input class="i" id="uP" value="' + esc(d.phone) + '"></label>' +
            '<label class="f"><span>Role</span><select class="i" id="uR">' +
            ["Coordinator", "Admin"].map((x) => '<option ' + (d.role === x ? "selected" : "") + '>' + x + '</option>').join("") +
            '</select><div class="hint">Coordinator manages everything · Admin can manage users</div></label>' +
            '<label class="f"><span>Designation</span><input class="i" id="uDg" value="' + esc(d.designation) + '"></label>' +
            '<label class="f"><span>City</span><input class="i" id="uC" value="' + esc(d.city) + '"></label>' +
            '<label class="f"><span>Status</span><select class="i" id="uS">' +
            ["Active", "Inactive"].map((x) => '<option ' + (d.status === x ? "selected" : "") + '>' + x + '</option>').join("") + '</select></label>' +
            '<label class="f"><span>Temporary password</span><input class="i" id="uPw" placeholder="' + (u ? "Leave blank to keep the current password" : "Min 8 chars, 1 capital, 1 number") + '"></label>' +
            '</div>',
        foot: '<button class="btn btn-ghost" id="mCancel">Cancel</button><button class="btn btn-primary" id="uSave">' + (u ? "Save" : "Add user") + '</button>',
        mount(ov) {
            ov.querySelector("#uSave").onclick = async function () {
                const data = {
                    id: u ? u.id : 0, name: ov.querySelector("#uN").value.trim(), email: ov.querySelector("#uE").value,
                    phone: ov.querySelector("#uP").value, role: ov.querySelector("#uR").value,
                    designation: ov.querySelector("#uDg").value, city: ov.querySelector("#uC").value,
                    status: ov.querySelector("#uS").value, password: ov.querySelector("#uPw").value
                };
                if (!data.name) return toast("Name is required", "err");
                this.disabled = true;
                this.textContent = "Saving...";
                try {
                    const r = await API.saveUser(data);
                    closeModal();
                    await refresh(true);
                    toast(r.tempPassword ? ("User created. Temporary password: " + r.tempPassword) : "User saved");
                } catch (e) {
                    this.disabled = false;
                    this.textContent = "Retry";
                    toast(e.message, "err");
                }
            };
        }
    });
}

function openImport() {
    modal({
        wide: true,
        title: "Bulk import customers",
        sub: "Paste from Excel, or upload a CSV",
        body: '<div class="notice">' + icon("import") +
            '<div>Format (with header):<br><code style="font-size:11.5px">company,contact,phone,email,city,state,gstin,source</code><br>' +
            'One customer per line. The first line is treated as the header.</div></div>' +
            '<label class="f"><span>Default salesperson (if missing in a row)</span><select class="i" id="iSp"><option value="">— none —</option>' +
            DB.sales.map((s) => '<option>' + esc(s.name) + '</option>').join("") + '</select></label>' +
            '<label class="f" style="margin-top:12px"><span>Paste your data</span>' +
            '<textarea class="i" id="iData" style="min-height:170px;font-family:Consolas,monospace;font-size:12.2px" placeholder="company,contact,phone,email,city,state,gstin,source\nAcme Pvt Ltd,Rajesh,9876543210,raj@acme.in,Mumbai,Maharashtra,27AAAAA0000A1Z5,Referral"></textarea></label>' +
            '<div class="drop" id="iDrop" style="margin-top:12px">' + icon("up") + '<div style="margin-top:6px">or drop a CSV file</div></div>' +
            '<input type="file" id="iFile" accept=".csv,.txt" style="display:none">',
        foot: '<button class="btn btn-ghost" id="mCancel">Cancel</button><button class="btn btn-primary" id="iGo">Import</button>',
        mount(ov) {
            const drop = ov.querySelector("#iDrop"), inp = ov.querySelector("#iFile");
            drop.onclick = () => inp.click();
            drop.ondragover = (e) => e.preventDefault();
            drop.ondrop = (e) => {
                e.preventDefault();
                if (e.dataTransfer.files[0]) read(e.dataTransfer.files[0]);
            };
            inp.onchange = () => {
                if (inp.files[0]) read(inp.files[0]);
            };

            function read(f) {
                const rd = new FileReader();
                rd.onload = () => {
                    ov.querySelector("#iData").value = String(rd.result);
                    toast("File loaded");
                };
                rd.readAsText(f);
            }

            ov.querySelector("#iGo").onclick = async function () {
                const raw = ov.querySelector("#iData").value.trim();
                if (!raw) return toast("Paste your data", "err");
                const lines = raw.split(/\r?\n/).filter((l) => l.trim());
                if (lines.length < 2) return toast("At least one data row is required", "err");
                const head = splitCSV(lines[0]).map((h) => h.trim().toLowerCase().replace(/[^a-z]/g, ""));
                const rows = [];
                for (let i = 1; i < lines.length; i++) {
                    const cols = splitCSV(lines[i]);
                    const r = {};
                    head.forEach((h, j) => {
                        const v = (cols[j] || "").trim();
                        if (h === "company") r.company = v;
                        else if (["contact", "contactname", "name"].includes(h)) r.contactName = v;
                        else if (["phone", "mobile"].includes(h)) r.phone = v;
                        else if (h === "email") r.email = v;
                        else if (h === "city") r.city = v;
                        else if (h === "state") r.state = v;
                        else if (["gstin", "gst"].includes(h)) r.gstin = v;
                        else if (h === "source") r.source = v;
                        else if (h === "industry") r.industry = v;
                    });
                    if (r.company) rows.push(r);
                }
                if (!rows.length) return toast("No valid rows found", "err");
                this.disabled = true;
                this.textContent = "Importing...";
                try {
                    const r = await API.bulkImport(rows, ov.querySelector("#iSp").value);
                    closeModal();
                    await refresh(true);
                    toast(r.made + " customers imported" + (r.skipped ? " · " + r.skipped + " skipped" : ""));
                } catch (e) {
                    this.disabled = false;
                    this.textContent = "Retry";
                    toast(e.message, "err");
                }
            };
        }
    });
}

function splitCSV(line) {
    const out = [];
    let cur = "", q = false;
    for (let i = 0; i < line.length; i++) {
        const ch = line[i];
        if (ch === '"') {
            if (q && line[i + 1] === '"') {
                cur += '"';
                i++;
            } else q = !q;
        } else if (ch === "," && !q) {
            out.push(cur);
            cur = "";
        } else cur += ch;
    }
    out.push(cur);
    return out;
}

function doExport(kind) {
    const url = CONFIG.API_URL + "?action=export&kind=" + encodeURIComponent(kind) + "&token=" + encodeURIComponent(API.token || "");
    window.open(url, "_blank");
    toast("Export started — your file will download");
}

/* ---------------- Event Bindings & Actions ---------------- */
function bindContent(skippedLinks) {
    const c = $("#content");
    if (!c || c.dataset.bound) return;
    c.dataset.bound = "1";

    c.addEventListener("click", (e) => {
        const ct = e.target.closest("[data-ctab]");
        if (ct) {
            CTAB = ct.dataset.ctab;
            renderCustomer(FOCUS ? FOCUS.id : null);
            return;
        }
        const pt = e.target.closest("[data-ptab]");
        if (pt) {
            PTAB = pt.dataset.ptab;
            renderProject(FOCUS ? FOCUS.id : null);
            return;
        }

        const go = e.target.closest("[data-go]");
        if (go) {
            VIEW = go.dataset.go;
            FOCUS = null;
            renderShell();
            render();
            return;
        }

        const pay = e.target.closest("[data-pay]");
        if (pay) {
            openPayment(pay.dataset.pay, pay.dataset.term || null);
            return;
        }
        const prev = e.target.closest("[data-preview]");
        if (prev) {
            openPreview(prev.dataset.preview);
            return;
        }
        const dc = e.target.closest("[data-deldoc]");
        if (dc) {
            askDelete("document", dc.dataset.deldoc);
            return;
        }
        const dfu = e.target.closest("[data-delfu]");
        if (dfu) {
            askDelete("followup", dfu.dataset.delfu);
            return;
        }
        const dp = e.target.closest("[data-delpay]");
        if (dp) {
            askDelete("payment", dp.dataset.delpay);
            return;
        }
        const dt = e.target.closest("[data-delterm]");
        if (dt) {
            askDelete("term", dt.dataset.delterm);
            return;
        }
        const dk = e.target.closest("[data-deltask]");
        if (dk) {
            askDelete("task", dk.dataset.deltask);
            return;
        }
        const ec = e.target.closest("[data-editcust]");
        if (ec) {
            openCustomer(ec.dataset.editcust);
            return;
        }
        const ep = e.target.closest("[data-editproj]");
        if (ep) {
            openProject(ep.dataset.editproj);
            return;
        }
        const es = e.target.closest("[data-editsales]");
        if (es) {
            openSales(es.dataset.editsales);
            return;
        }
        const ds = e.target.closest("[data-delsales]");
        if (ds) {
            askDelete("sales", ds.dataset.delsales);
            return;
        }
        const eu = e.target.closest("[data-edituser]");
        if (eu) {
            openUser(eu.dataset.edituser);
            return;
        }
        const du = e.target.closest("[data-deluser]");
        if (du) {
            askDelete("user", du.dataset.deluser);
            return;
        }

        const nt = e.target.closest("[data-notif]");
        if (nt) {
            API.notifRead(Number(nt.dataset.notif)).catch(() => {
            });
            const n = DB.notifications.find((x) => String(x.id) === String(nt.dataset.notif));
            if (n && !n.read) {
                n.read = 1;
                DB.unread = Math.max(0, (DB.unread || 0) - 1);
            }
            if (nt.dataset.nproj) {
                FOCUS = {type: "project", id: nt.dataset.nproj};
                PTAB = "overview";
                render();
                return;
            }
            if (nt.dataset.ncust) {
                FOCUS = {type: "customer", id: nt.dataset.ncust};
                CTAB = "overview";
                render();
                return;
            }
            render();
            return;
        }

        const map = {
            "#addCust": () => openCustomer(null),
            "#expCust": () => doExport("customers"),
            "#impCust": () => openImport(),
            "#addProj": () => openProject(null),
            "#expPay": () => doExport("payments"),
            "#expSched": () => doExport("schedule"),
            "#expSales": () => doExport("sales"),
            "#payBtn3": () => pickProjectForPayment(),
            "#addTask": () => openTask(null, null),
            "#fuAdd": () => openFollowup(null, null),
            "#addSales": () => openSales(null),
            "#addUser": () => openUser(null),
            "#upDoc": () => openUploadDoc(FOCUS ? {customerId: FOCUS.id} : null),
            "#upDoc2": () => openUploadDoc(null),
            "#fuBtn2": () => openFollowup(null, FOCUS ? FOCUS.id : null),
            "#msBtn2": () => openMilestones(FOCUS.id),
            "#schedBtn2": () => openSchedule(FOCUS.id),
            "#payBtn": () => openPayment(FOCUS.id, null),
            "#payBtn2": () => openPayment(FOCUS.id, null),
            "#genRec": () => openRecurring(FOCUS.id),
            "#markAll": async () => {
                try {
                    await API.notifRead(0);
                    await refresh(true);
                    toast("All marked as read");
                } catch (err) {
                    toast(err.message, "err");
                }
            },
            "#stSave": async function () {
                this.disabled = true;
                this.textContent = "Saving...";
                try {
                    const curSel = $("#stCur");
                    if (curSel) localStorage.setItem("pjt_cur", curSel.value);
                    CUR = curSel ? curSel.value : CUR;
                    await API.saveSettings({
                        paymentAlertDays: $("#stPay").value,
                        amcAlertDays: $("#stAmc").value,
                        followupAlertDays: $("#stFu").value,
                        companyName: $("#stComp").value
                    });
                    await refresh(true);
                    toast("Settings saved");
                } catch (err) {
                    this.disabled = false;
                    this.textContent = "Save settings";
                    toast(err.message, "err");
                }
            },
            "#logout2": async () => {
                try {
                    await API.logout();
                } catch (err) {
                }
                if (pollId) clearInterval(pollId);
                Session.clear();
                ME = null;
                renderLogin();
            },
            "#pwSave": async function () {
                const o = $("#pwOld").value, n = $("#pwNew").value, n2 = $("#pwNew2").value;
                const prob = pwProblem(n);
                if (prob) return toast(prob, "err");
                if (n !== n2) return toast("The two passwords do not match", "err");
                this.disabled = true;
                this.textContent = "Saving...";
                try {
                    await API.changePassword(o, n);
                    $("#pwOld").value = "";
                    $("#pwNew").value = "";
                    $("#pwNew2").value = "";
                    toast("Password changed");
                } catch (err) {
                    toast(err.message, "err");
                } finally {
                    this.disabled = false;
                    this.innerHTML = icon("key") + " Update password";
                }
            }
        };
        for (const sel in map) {
            if (e.target.closest(sel)) {
                map[sel].call(e.target.closest(sel), e);
                return;
            }
        }

        if (skippedLinks) return;

        const cu = e.target.closest("[data-customer]");
        if (cu && cu.dataset.customer) {
            FOCUS = {type: "customer", id: cu.dataset.customer};
            CTAB = "overview";
            render();
            return;
        }
        const pr = e.target.closest("[data-project]");
        if (pr && pr.dataset.project) {
            FOCUS = {type: "project", id: pr.dataset.project};
            PTAB = "overview";
            render();
            return;
        }
    });

    c.addEventListener("change", async (e) => {
        const tg = e.target.dataset.toggletask;
        if (tg) {
            try {
                await API.toggleTask(Number(tg));
                const t = DB.tasks.find((x) => String(x.id) === String(tg));
                if (t) t.done = Number(t.done) ? 0 : 1;
                render();
            } catch (err) {
                toast(err.message, "err");
            }
            return;
        }
        const id = e.target.id;
        if (id === "cSales") {
            CF.sales = e.target.value;
            render();
        }
        if (id === "cCity") {
            CF.city = e.target.value;
            render();
        }
        if (id === "cPay") {
            CF.pay = e.target.value;
            render();
        }
        if (id === "cStatus") {
            CF.status = e.target.value;
            render();
        }
        if (id === "pHealth") {
            PF.health = e.target.value;
            render();
        }
        if (id === "pStatus") {
            PF.status = e.target.value;
            render();
        }
        if (id === "payMode") {
            PAYF.mode = e.target.value;
            render();
        }
        if (id === "dCat") {
            DF.cat = e.target.value;
            render();
        }
    });

    c.addEventListener("input", (e) => {
        const id = e.target.id, v = e.target.value;
        if (id === "cSearch") {
            CF.q = v;
            refocus("cSearch", v);
        } else if (id === "pSearch") {
            PF.q = v;
            refocus("pSearch", v);
        } else if (id === "paySearch") {
            PAYF.q = v;
            refocus("paySearch", v);
        } else if (id === "dSearch") {
            DF.q = v;
            refocus("dSearch", v);
        }
    });

    const clr = {
        "#cClear": () => {
            CF = {q: "", sales: "", city: "", pay: "", status: ""};
            render();
        },
        "#pClear": () => {
            PF = {q: "", health: "", status: ""};
            render();
        },
        "#payClear": () => {
            PAYF = {q: "", mode: ""};
            render();
        },
        "#dClear": () => {
            DF = {q: "", cat: ""};
            render();
        }
    };
    for (const sel in clr) {
        const el = $(sel);
        if (el) el.onclick = clr[sel];
    }
}

function refocus(id, v) {
    render();
    const b = document.getElementById(id);
    if (b) {
        b.focus();
        b.setSelectionRange(v.length, v.length);
    }
}

function askDelete(kind, id) {
    const cfg = {
        document: {t: "Remove document?", m: "The file will be permanently deleted.", f: () => API.delDoc(Number(id))},
        followup: {t: "Remove follow-up?", m: "This entry will be removed.", f: () => API.delFollowup(Number(id))},
        payment: {
            t: "Remove payment entry?",
            m: "The receipt will be removed and the installment balance will go back up.",
            f: () => API.delPayment(Number(id))
        },
        term: {
            t: "Remove installment?",
            m: "This installment will be removed from the schedule.",
            f: () => API.delTerm(Number(id))
        },
        task: {t: "Remove task?", m: "It will be removed from your list.", f: () => API.delTask(Number(id))},
        sales: {
            t: "Remove salesperson?",
            m: "Only the record is removed — past customers are unaffected.",
            f: () => API.delSales(Number(id))
        },
        user: {t: "Remove user?", m: "This login will be permanently removed.", f: () => API.delUser(Number(id))},
        customer: {
            t: "Delete customer?",
            m: "The customer and ALL their projects, payment schedules, receipts, documents and follow-ups will be permanently deleted.",
            f: () => API.delCustomer(Number(id), true).then((r) => {
                FOCUS = null;
                DASH = null;
                return r;
            })
        },
        project: {
            t: "Delete project?",
            m: "The project with its milestones, payment schedule, receipts and documents will be permanently deleted.",
            f: () => API.delProject(Number(id)).then((r) => {
                FOCUS = null;
                DASH = null;
                return r;
            })
        }
    }[kind];
    if (!cfg) return;
    confirmBox(cfg.t, "<p>" + esc(cfg.m) + "</p>", "Remove", async () => {
        await cfg.f();
        await refresh(true);
        toast("Removed");
    });
}

function pickProjectForPayment() {
    if (!DB.projects.length) return toast("Create a project first", "err");
    modal({
        title: "Payment for which project?",
        body: '<label class="f"><span>Project</span><select class="i" id="pkP">' +
            DB.projects.map((p) => '<option value="' + p.id + '">' + esc(p.code) + ' — ' + esc(p.name) + ' (' + money(p.money.due) + ' due)</option>').join("") +
            '</select></label>',
        foot: '<button class="btn btn-ghost" id="mCancel">Cancel</button><button class="btn btn-green" id="pkGo">Continue</button>',
        mount(ov) {
            ov.querySelector("#pkGo").onclick = () => {
                const id = ov.querySelector("#pkP").value;
                closeModal();
                openPayment(id, null);
            };
        }
    });
}

function killSearch() {
    const s = $(".sres");
    if (s) s.remove();
}

async function globalSearch(q) {
    killSearch();
    if (!q || q.trim().length < 2) return;
    const gsw = $("#gsw");
    if (!gsw) return;
    const box = document.createElement("div");
    box.className = "sres";
    box.innerHTML = '<div class="sres-head">Searching...</div>';
    gsw.appendChild(box);
    try {
        const r = await API.search(q.trim());
        const res = r.results || [];
        if (!$(".sres")) return;
        if (!res.length) {
            box.innerHTML = '<div class="sres-head">Nothing found</div>';
            return;
        }
        const groups = {customer: "Customers", project: "Projects", sales: "Sales team"};
        let html = "", last = "";
        res.forEach((x) => {
            if (x.type !== last) {
                html += '<div class="sres-head">' + (groups[x.type] || x.type) + '</div>';
                last = x.type;
            }
            html += '<div class="sres-item" data-st="' + x.type + '" data-sid="' + esc(String(x.id)) + '">' +
                '<div style="flex:1;min-width:0"><div class="tt">' + esc(x.title) + '</div>' +
                '<div class="ss">' + esc(x.sub || "") + '</div></div>' +
                (x.badge ? '<span class="badge ' + (HEALTH[x.badge] ? HEALTH[x.badge].cls.replace("h-", "b-") : "b-grey") + '">' + esc(x.badge) + '</span>' : "") +
                '</div>';
        });
        box.innerHTML = html;
        box.querySelectorAll(".sres-item").forEach((el) => {
            el.onclick = () => {
                killSearch();
                const g = $("#gsearch");
                if (g) g.value = "";
                if (el.dataset.st === "customer") {
                    FOCUS = {type: "customer", id: el.dataset.sid};
                    CTAB = "overview";
                } else if (el.dataset.st === "project") {
                    FOCUS = {type: "project", id: el.dataset.sid};
                    PTAB = "overview";
                } else {
                    VIEW = "sales";
                    FOCUS = null;
                }
                renderShell();
                render();
            };
        });
    } catch (e) {
        box.innerHTML = '<div class="sres-head">' + esc(e.message) + '</div>';
    }
}

document.addEventListener("click", (e) => {
    if (!e.target.closest("#gsw")) killSearch();
});
document.addEventListener("keydown", (e) => {
    if ((e.metaKey || e.ctrlKey) && e.key.toLowerCase() === "k") {
        e.preventDefault();
        const g = $("#gsearch");
        if (g) g.focus();
    }
    if (e.key === "Escape") {
        closeModal();
        killSearch();
    }
});
