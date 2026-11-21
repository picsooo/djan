/* Sends booking requests, contact messages and reviews to the site's admin. */
(function () {
  var api = window.TWAPI, d = document;
  function post(data) {
    return fetch(api, { method: "POST", headers: { "Content-Type": "application/json" }, body: JSON.stringify(data), keepalive: true, credentials: "same-origin" });
  }
  window.TWSTORE = function (kind, data, msg, sel) {
    if (!api) return;
    data.action = kind; data.lang = window.TWLANG || "fr";
    try {
      post(data).then(function (r) {
        if (r.ok && msg && sel) { var el = d.querySelector(sel); if (el) { el.textContent = msg; el.classList.add("show"); } }
      }).catch(function () {});
    } catch (e) {}
  };
  var f = d.getElementById("revf");
  if (f) f.addEventListener("submit", function (e) {
    e.preventDefault();
    var err = d.getElementById("revErr"), ok = d.getElementById("revOk"), b = f.querySelector("button[type=submit]");
    err.classList.remove("show");
    var r = f.querySelector("input[name=rating]:checked");
    var data = { action: "review", lang: window.TWLANG || "fr", name: f.elements.name.value.trim(), origin: f.elements.origin.value.trim(),
      circuit: f.elements.circuit.value, rating: r ? r.value : 5, text: f.elements.text.value.trim(), website: f.elements.website.value, t0: f.elements.t0.value };
    if (!data.name || data.text.length < 10) { err.classList.add("show"); return; }
    b.disabled = true;
    post(data).then(function (res) { if (!res.ok) throw 0; ok.classList.add("show"); f.reset(); b.disabled = false; })
      .catch(function () { err.classList.add("show"); b.disabled = false; });
  });
})();
