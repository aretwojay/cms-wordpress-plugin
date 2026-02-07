/**
 * Admin JS - Decode Headless Connector.
 */
(function ($) {
  "use strict";

  function msg(selector, text, color) {
    $(selector).text(text).css("color", color);
  }

  function ajaxPost(data, onSuccess, onError) {
    $.ajax({
      url: DHC.ajax_url,
      type: "POST",
      data: data,
      dataType: "json",
      success: function (res) {
        if (res.success) {
          onSuccess(res.data);
        } else {
          if (onError) {
            onError(res.data);
          }
        }
      },
      error: function () {
        if (onError) {
          onError({ message: "Erreur reseau" });
        }
      },
    });
  }

  // Connexion - bouton click (pas submit)
  $(document).on("click", "#dhc-login-btn", function (e) {
    e.preventDefault();
    msg("#dhc-login-msg", "Chargement...", "blue");

    ajaxPost(
      {
        action: "dhc_login",
        nonce: DHC.nonce,
        base_url: $("#dhc-base-url").val(),
        login: $("#dhc-login").val(),
        password: $("#dhc-password").val(),
        secret_key: $("#dhc-secret").val(),
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
  });

  // Deconnexion
  $(document).on("click", "#dhc-logout", function () {
    ajaxPost({ action: "dhc_logout", nonce: DHC.nonce }, function () {
      location.reload();
    });
  });

  // Cache - sauvegarder
  $(document).on("click", "#dhc-save-cache", function (e) {
    e.preventDefault();
    ajaxPost(
      {
        action: "dhc_save_cache",
        nonce: DHC.nonce,
        enabled: $("#dhc-cache-enabled").is(":checked"),
        ttl: $("#dhc-cache-ttl").val(),
      },
      function (data) {
        msg("#dhc-cache-msg", data.message, "green");
      },
    );
  });

  // Cache - vider
  $(document).on("click", "#dhc-flush-cache", function () {
    ajaxPost({ action: "dhc_flush_cache", nonce: DHC.nonce }, function (data) {
      msg("#dhc-cache-msg", data.message, "green");
    });
  });

  // Contenus
  function loadContent() {
    $("#dhc-content-area").html("Chargement...");
    ajaxPost(
      { action: "dhc_fetch_content", nonce: DHC.nonce },
      function (data) {
        $("#dhc-content-area").html(data.html);
      },
      function (data) {
        $("#dhc-content-area").html("Erreur: " + (data.message || "Inconnue"));
      },
    );
  }

  if ($(".dhc-label.ok").length) {
    loadContent();
  }

  $(document).on("click", "#dhc-refresh", function () {
    loadContent();
  });

  // Edition
  $(document).on("click", ".dhc-edit", function () {
    var id = $(this).data("id");
    var html =
      '<div id="dhc-editor" style="background:#fff;border:1px solid #ccc;padding:10px;margin-top:10px">';
    html += "<h3>Editer #" + id + "</h3>";
    html += '<input type="hidden" id="edit-id" value="' + id + '">';
    html += '<p>Titre: <input type="text" id="edit-title" class="widefat"></p>';
    html +=
      '<p>Contenu: <textarea id="edit-content" class="widefat" rows="5"></textarea></p>';
    html +=
      '<button id="save-edit" class="button button-primary">Sauvegarder</button> ';
    html += '<button id="close-edit" class="button">Annuler</button>';
    html += "</div>";

    $("#dhc-editor").remove();
    $("#dhc-content-area").before(html);

    ajaxPost(
      { action: "dhc_get_item", nonce: DHC.nonce, id: id },
      function (data) {
        $("#edit-title").val(data.item.title || "");
        $("#edit-content").val(data.item.content || "");
      },
    );
  });

  $(document).on("click", "#close-edit", function () {
    $("#dhc-editor").remove();
  });

  $(document).on("click", "#save-edit", function () {
    var btn = $(this);
    btn.text("Enregistrement...");
    ajaxPost(
      {
        action: "dhc_update_content",
        nonce: DHC.nonce,
        id: $("#edit-id").val(),
        title: $("#edit-title").val(),
        content: $("#edit-content").val(),
      },
      function () {
        alert("OK");
        $("#dhc-editor").remove();
        loadContent();
      },
      function () {
        alert("Erreur");
        btn.text("Sauvegarder");
      },
    );
  });
})(jQuery);
