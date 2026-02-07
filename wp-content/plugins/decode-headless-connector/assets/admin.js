/**
 * Admin JS - Decode Headless Connector.
 */
(() => {
  "use strict";

  const qs = (sel) => document.querySelector(sel);

  const msg = (selector, text, color) => {
    const el = qs(selector);
    if (el) {
      el.textContent = text;
      el.style.color = color;
    }
  };

  const ajaxPost = (params, onSuccess, onError) => {
    const body = new URLSearchParams();
    for (const [key, value] of Object.entries(params)) {
      body.append(key, value);
    }

    fetch(DHC.ajax_url, {
      method: "POST",
      credentials: "same-origin",
      body,
    })
      .then((response) => response.json())
      .then((res) => {
        if (res.success) {
          onSuccess(res.data);
        } else if (onError) {
          onError(res.data);
        }
      })
      .catch(() => {
        if (onError) {
          onError({ message: "Erreur reseau" });
        }
      });
  };

  const loadContent = () => {
    const area = qs("#dhc-content-area");
    ajaxPost(
      { action: "dhc_fetch_content", nonce: DHC.nonce },
      (data) => {
        if (area) area.innerHTML = data.html;
      },
      (data) => {
        if (area) area.innerHTML = `Erreur: ${data.message || "Inconnue"}`;
      },
    );
  };

  // Delegation d'evenements
  document.addEventListener("click", (e) => {
    const { target } = e;

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
        (data) => {
          msg("#dhc-login-msg", data.message, "green");
          setTimeout(() => location.reload(), 500);
        },
        (data) => {
          msg("#dhc-login-msg", data.message || "Erreur", "red");
        },
      );
      return;
    }

    // Deconnexion
    if (target.id === "dhc-logout") {
      ajaxPost({ action: "dhc_logout", nonce: DHC.nonce }, () => {
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
        (data) => {
          msg("#dhc-cache-msg", data.message, "green");
        },
      );
      return;
    }

    // Cache - vider
    if (target.id === "dhc-flush-cache") {
      ajaxPost({ action: "dhc_flush_cache", nonce: DHC.nonce }, (data) => {
        msg("#dhc-cache-msg", data.message, "green");
      });
      return;
    }

    // Rafraichir contenus
    if (target.id === "dhc-refresh") {
      loadContent();
      return;
    }

    // Edition - ouvrir
    if (target.classList.contains("dhc-edit")) {
      const id = target.getAttribute("data-id");
      const row = target.closest("tr");
      const cells = row ? row.querySelectorAll("td") : [];
      const rowTitle = cells.length > 1 ? cells[1].textContent : "";
      const dataTitle = (target.getAttribute("data-title") || "").replace(
        /"/g,
        "&quot;",
      );
      const dataContent = (target.getAttribute("data-content") || "")
        .replace(/</g, "&lt;")
        .replace(/>/g, "&gt;");

      const old = qs("#dhc-editor");
      if (old) old.remove();

      const html = `
        <div id="dhc-editor" style="background:#fff;border:1px solid #ccc;padding:10px;margin-top:10px">
          <h3>Editer #${id}</h3>
          <input type="hidden" id="edit-id" value="${id}">
          <p>Titre: <input type="text" id="edit-title" class="widefat" value="${dataTitle}"></p>
          <p>Contenu: <textarea id="edit-content" class="widefat" rows="5">${dataContent}</textarea></p>
          <button id="save-edit" class="button button-primary">Sauvegarder</button>
          <button id="close-edit" class="button">Annuler</button>
        </div>`;

      const area = qs("#dhc-content-area");
      area.insertAdjacentHTML("beforebegin", html);

      ajaxPost({ action: "dhc_get_item", nonce: DHC.nonce, id }, (data) => {
        qs("#edit-title").value = data.item.title || rowTitle;
        qs("#edit-content").value = data.item.content || "";
      });
      return;
    }

    // Edition - fermer
    if (target.id === "close-edit") {
      const editor = qs("#dhc-editor");
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
        () => {
          alert("Article mis à jour");
          const editor = qs("#dhc-editor");
          if (editor) editor.remove();
          loadContent();
        },
        () => {
          alert("Erreur");
          target.textContent = "Sauvegarder";
        },
      );
      return;
    }
  });

  // Charger les contenus si connecte
  if (qs(".dhc-label.ok")) {
    loadContent();
  }
})();
