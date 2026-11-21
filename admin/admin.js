/* Administration behaviours: menu, tabs, repeaters, media picker, uploads, confirmations. */
(function () {
  "use strict";
  var d = document, $ = function (s, r) { return (r || d).querySelector(s); }, $$ = function (s, r) { return Array.prototype.slice.call((r || d).querySelectorAll(s)); };
  var csrf = ($("meta[name=csrf]") || {}).content || "";

  /* menu */
  d.addEventListener("click", function (e) { if (e.target.closest("[data-menu]")) $("#side").classList.toggle("open"); });

  /* confirmations */
  d.addEventListener("submit", function (e) { var f = e.target, m = f.getAttribute("data-confirm"); if (m && !confirm(m)) e.preventDefault(); });

  /* language tabs */
  $$(".tabs").forEach(function (t) {
    t.addEventListener("click", function (e) {
      var b = e.target.closest("[data-tab]"); if (!b) return;
      var card = t.parentNode;
      $$("[data-tab]", t).forEach(function (x) { x.classList.toggle("on", x === b); });
      $$("[data-pane]", card).forEach(function (p) { p.classList.toggle("on", p.getAttribute("data-pane") === b.getAttribute("data-tab")); });
    });
  });

  /* unsaved changes */
  $$("form[data-dirty]").forEach(function (f) {
    var dirty = false;
    f.addEventListener("input", function () { dirty = true; f.classList.add("dirty"); });
    f.addEventListener("change", function () { dirty = true; f.classList.add("dirty"); });
    f.addEventListener("submit", function () { dirty = false; });
    window.addEventListener("beforeunload", function (e) { if (dirty) { e.preventDefault(); e.returnValue = ""; } });
    f.markDirty = function () { dirty = true; f.classList.add("dirty"); };
  });
  function markDirty(el) { var f = el.closest("form[data-dirty]"); if (f && f.markDirty) f.markDirty(); }

  /* repeaters */
  function refresh(rep) {
    var items = $$(":scope > .rep-i", rep);
    items.forEach(function (it, i) {
      var b = $$(":scope > .rep-h [data-rep]", it);
      b.forEach(function (x) {
        var a = x.getAttribute("data-rep");
        x.disabled = (a === "up" && i === 0) || (a === "down" && i === items.length - 1) || (a === "del" && items.length === 1);
      });
    });
  }
  $$(".rep").forEach(refresh);
  d.addEventListener("click", function (e) {
    var add = e.target.closest("[data-rep-add]");
    if (add) {
      var wrap = add.parentNode, rep = $(":scope > .rep", wrap), tpl = $(":scope > template", wrap), ph = rep.getAttribute("data-ph");
      var html = tpl.innerHTML.split(ph).join(String(Date.now()));
      rep.insertAdjacentHTML("beforeend", html);
      $$(".rep", rep.lastElementChild).forEach(refresh);
      refresh(rep); markDirty(rep);
      var first = $("input,textarea", rep.lastElementChild); if (first) first.focus();
      return;
    }
    var b = e.target.closest("[data-rep]");
    if (!b) return;
    var it = b.closest(".rep-i"), rp = it.parentNode, a = b.getAttribute("data-rep");
    if (a === "del") { if (!confirm("Supprimer cet élément ?")) return; it.remove(); }
    else if (a === "up" && it.previousElementSibling) rp.insertBefore(it, it.previousElementSibling);
    else if (a === "down" && it.nextElementSibling) rp.insertBefore(it.nextElementSibling, it);
    refresh(rp); markDirty(rp);
  });
  d.addEventListener("input", function (e) {
    var it = e.target.closest(".rep-i"); if (!it) return;
    var t = $(":scope > .rep-b > .f input[name$='[t]'], :scope > .rep-b > .f input[name$='[k]'], :scope > .rep-b > .f input[name$='[q]']", it);
    if (t === e.target) $(":scope > .rep-h .rep-t", it).textContent = t.value;
  });

  /* markdown toolbar */
  d.addEventListener("click", function (e) {
    var b = e.target.closest("[data-md]"); if (!b) return;
    var ta = $("textarea.md", b.closest(".f")), s = ta.selectionStart, en = ta.selectionEnd, sel = ta.value.slice(s, en), k = b.getAttribute("data-md"), ins;
    if (k === "h2") ins = "\n\n## " + (sel || "Intertitre") + "\n\n";
    else if (k === "b") ins = "**" + (sel || "texte en gras") + "**";
    else if (k === "ul") ins = "\n\n- " + (sel ? sel.split("\n").join("\n- ") : "Premier point\n- Deuxième point") + "\n\n";
    else { var u = prompt("Adresse du lien (ex. reservation.html ou https://…)", "reservation.html"); if (!u) return; ins = "[" + (sel || "texte du lien") + "](" + u + ")"; }
    ta.setRangeText(ins, s, en, "end"); ta.focus(); markDirty(ta);
  });

  /* uploads */
  function upload(file, extra) {
    var fd = new FormData(); fd.append("file", file); fd.append("_csrf", csrf);
    Object.keys(extra || {}).forEach(function (k) { fd.append(k, extra[k]); });
    return fetch("?r=upload", { method: "POST", body: fd, credentials: "same-origin" }).then(function (r) {
      return r.json().catch(function () { return { ok: false, error: "Réponse du serveur illisible (fichier trop lourd ?)." }; });
    });
  }
  function uploadAll(files, extra, msgEl) {
    files = Array.prototype.slice.call(files);
    var done = [], errors = [], i = 0;
    function next() {
      if (i >= files.length) { if (msgEl) msgEl.textContent = errors.length ? errors.join(" ") : ""; return Promise.resolve(done); }
      var f = files[i++];
      if (msgEl) msgEl.textContent = "Envoi de " + f.name + " (" + i + "/" + files.length + ")…";
      return upload(f, extra).then(function (r) { if (r.ok) done.push(r); else errors.push(f.name + " : " + (r.error || "erreur") + "."); }).catch(function () { errors.push(f.name + " : envoi impossible."); }).then(next);
    }
    return next();
  }

  /* media picker */
  var modal = $("#picker"), grid = modal && $("[data-grid]", modal), target = null, multi = false;
  function thumb(p, url) {
    var b = d.createElement("button"); b.type = "button"; b.setAttribute("data-p", p); b.title = p.split("/").pop();
    var im = d.createElement("img"); im.src = url; im.loading = "lazy"; im.alt = ""; b.appendChild(im); return b;
  }
  function loadGrid() {
    grid.innerHTML = "";
    return fetch("?r=media_json", { credentials: "same-origin" }).then(function (r) { return r.json(); }).then(function (j) {
      j.items.forEach(function (it) { grid.appendChild(thumb(it.p, it.u)); });
    });
  }
  function openPicker(t, m) { target = t; multi = m; modal.hidden = false; loadGrid(); }
  function closePicker() { modal.hidden = true; target = null; }
  function apply(p, url) {
    if (!target) return;
    if (multi) {
      var name = target.getAttribute("data-name"), span = d.createElement("span");
      span.className = "imgs-i";
      span.innerHTML = '<img alt=""><input type="hidden"><button type="button" data-imgs-del title="Retirer">×</button>';
      span.firstChild.src = url; span.children[1].name = name; span.children[1].value = p;
      target.insertBefore(span, $(".imgs-add", target));
    } else {
      $("img", target).src = url; $("input[type=hidden]", target).value = p; target.classList.remove("empty");
      markDirty(target);
      closePicker();
      return;
    }
    markDirty(target);
  }
  if (modal) {
    modal.addEventListener("click", function (e) {
      if (e.target === modal || e.target.closest("[data-close]")) return closePicker();
      var b = e.target.closest("[data-p]"); if (b) apply(b.getAttribute("data-p"), $("img", b).src);
    });
    d.addEventListener("keydown", function (e) { if (e.key === "Escape" && !modal.hidden) closePicker(); });
    $("[data-upload]", modal).addEventListener("change", function (e) {
      var msg = $("[data-up-msg]", modal);
      uploadAll(e.target.files, {}, msg).then(function (res) {
        e.target.value = "";
        loadGrid().then(function () { if (res.length === 1 && !multi) apply(res[0].path, res[0].url); else res.forEach(function (r) { if (multi) apply(r.path, r.url); }); });
      });
    });
  }
  d.addEventListener("click", function (e) {
    var p = e.target.closest("[data-pick]"); if (p) return openPicker(p.closest(".imgw"), false);
    var pm = e.target.closest("[data-pick-multi]"); if (pm) return openPicker(pm.closest(".imgs"), true);
    var c = e.target.closest("[data-clear]");
    if (c) { var w = c.closest(".imgw"); $("img", w).removeAttribute("src"); $("input[type=hidden]", w).value = ""; w.classList.add("empty"); markDirty(w); return; }
    var x = e.target.closest("[data-imgs-del]"); if (x) { var s = x.closest(".imgs-i"); markDirty(s); s.remove(); }
  });

  /* gallery: add photos directly */
  var drop = $("[data-photo-drop]");
  if (drop) {
    var go = function (files) {
      uploadAll(files, { as: "photo", cat: $("[data-photo-cat]", drop).value }, $("[data-up-msg]", drop)).then(function (r) { if (r.length) location.reload(); });
    };
    $("[data-photo-files]", drop).addEventListener("change", function (e) { go(e.target.files); });
    ["dragenter", "dragover"].forEach(function (t) { drop.addEventListener(t, function (e) { e.preventDefault(); drop.classList.add("over"); }); });
    ["dragleave", "drop"].forEach(function (t) { drop.addEventListener(t, function (e) { e.preventDefault(); drop.classList.remove("over"); }); });
    drop.addEventListener("drop", function (e) { if (e.dataTransfer.files.length) go(e.dataTransfer.files); });
  }
  var mf = $("[data-media-files]");
  if (mf) mf.addEventListener("change", function (e) { uploadAll(e.target.files, {}, $("[data-up-msg]")).then(function (r) { if (r.length) location.reload(); }); });
})();
