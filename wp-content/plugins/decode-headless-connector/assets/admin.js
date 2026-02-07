/**
 * Admin JS - Decode Headless Connector.
 */
(function () {
  "use strict";

  // Raccourcis
  function qs(sel) {
    return document.querySelector(sel);
  }

  function msg(selector, text, color) {
    var el = qs(selector);
    if (el) {
      el.textContent = text;
      el.style.color = color;
    }
  }

  function ajaxPost(params, onSuccess, onError) {
    var body = new URLSearchParams();
    for (var key in params) {
      if (params.hasOwnProperty(key)) {
        body.append(key, params[key]);
      }
    }

    fetch(DHC.ajax_url, {
      method: "POST",
      credentials: "same-origin",
      body: body,
    })
      .then(function (response) {
        return response.json();
      })
      .then(function (res) {
        if (res.success) {
          onSuccess(res.data);
        } else if (onError) {
          onError(res.data);
        }
      })
      .catch(function () {
        if (onError) {
          onError({ message: "Erreur reseau" });
        }
      });
  }

  // Delegation d'evenements
  document.addEventListener("click", function (e) {
    var target = e.target;

    // Connexion
    if (target.id === "dhc-login-btn") {
      e.preventDefault();
      msg("#dhc-login-msg", "Chargement...", "blue");

      ajaxPost(
        {
          action: "dhc_login",
          nonce: DHC.nonce,
          base_url: qs("#dhc-base-url").value,
          login: qs("#dhc-login").value,
          password: qs("#dhc-password").value,
          secret_key: qs("#dhc-secret").value,
        },
        function (data) {
          msg("#dhc-login-msg", data.message, "green");
          setTimeout(function () {
            location.reload();
          }, 500);
        },
        function (data) {
          msg("#dhc-login-msg", data.message || "Erreur", "red");
        },
      );
      return;
    }

    // Deconnexion
    if (target.id === "dhc-logout") {
      ajaxPost({ action: "dhc_logout", nonce: DHC.nonce }, function () {
        location.reload();
      });
      return;
    }

    // Cache - sauvegarder
    if (target.id === "dhc-save-cache") {
      e.preventDefault();
      ajaxPost(
        {
          action: "dhc_save_cache",
          nonce: DHC.nonce,
          enabled: qs("#dhc-cache-enabled").checked,
          ttl: qs("#dhc-cache-ttl").value,
        },
        function (data) {
          msg("#dhc-cache-msg", data.message, "green");
        },
      );
      return;
    }

    // Cache - vider
    if (target.id === "dhc-flush-cache") {
      ajaxPost(
        { action: "dhc_flush_cache", nonce: DHC.nonce },
        function (data) {
          msg("#dhc-cache-msg", data.message, "green");
        },
      );
      return;
    }

    // Rafraichir contenus
    if (target.id === "dhc-refresh") {
      loadContent();
      return;
    }

    // Edition - ouvrir
    if (target.classList.contains("dhc-edit")) {
      var id = target.getAttribute("data-id");
      var row = target.closest("tr");
      var cells = row ? row.querySelectorAll("td") : [];
      var rowTitle = cells.length > 1 ? cells[1].textContent : "";

      var old = qs("#dhc-editor");
      if (old) old.remove();

      var html =
        '<div id="dhc-editor" style="background:#fff;border:1px solid #ccc;padding:10px;margin-top:10px">';
      html += "<h3>Editer #" + id + "</h3>";
      html += '<input type="hidden" id="edit-id" value="' + id + '">';
      html +=
        '<p>Titre: <input type="text" id="edit-title" class="widefat" value="' +
        target.getAttribute("data-title").replace(/"/g, "&quot;") +
        '"></p>';
      html +=
        '<p>Contenu: <textarea id="edit-content" class="widefat" rows="5">' +
        target
          .getAttribute("data-content")
          .replace(/</g, "&lt;")
          .replace(/>/g, "&gt;") +
        "</textarea></p>";
      html +=
        '<button id="save-edit" class="button button-primary">Sauvegarder</button> ';
      html += '<button id="close-edit" class="button">Annuler</button>';
      html += "</div>";

      var area = qs("#dhc-content-area");
      area.insertAdjacentHTML("beforebegin", html);

      ajaxPost(
        { action: "dhc_get_item", nonce: DHC.nonce, id: id },
        function (data) {
          qs("#edit-title").value = data.item.title || rowTitle;
          qs("#edit-content").value = data.item.content || "";
        },
      );
      return;
    }

    // Edition - fermer
    if (target.id === "close-edit") {
      var editor = qs("#dhc-editor");
      if (editor) editor.remove();
      return;
    }

    // Edition - sauvegarder
    if (target.id === "save-edit") {
      target.textContent = "Enregistrement...";
      ajaxPost(
        {
          action: "dhc_update_content",
          nonce: DHC.nonce,
          id: qs("#edit-id").value,
          title: qs("#edit-title").value,
          content: qs("#edit-content").value,
        },
        function () {
          alert("Article mis à jour");
          var editor = qs("#dhc-editor");
          if (editor) editor.remove();
          loadContent();
        },
        function () {
          alert("Erreur");
          target.textContent = "Sauvegarder";
        },
      );
      return;
    }
  });

  // Contenus
  function loadContent() {
    var area = qs("#dhc-content-area");
    ajaxPost(
      { action: "dhc_fetch_content", nonce: DHC.nonce },
      function (data) {
        if (area) area.innerHTML = data.html;
      },
      function (data) {
        if (area) area.innerHTML = "Erreur: " + (data.message || "Inconnue");
      },
    );
  }

  // Charger les contenus si connecte
  if (qs(".dhc-label.ok")) {
    loadContent();
  }
})();
