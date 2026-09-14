"use strict";

/**
 * Menyertakan token CSRF CodeIgniter pada setiap request jQuery AJAX yang
 * mengubah data (POST/PUT/PATCH/DELETE) dan memperbarui token dari header
 * respons agar submit berikutnya pada halaman yang sama tidak memakai token
 * basi (Config\Security::$regenerate = true).
 *
 * Sumber token: <meta name="{csrf_header()}" content="{csrf_hash()}"> dan
 * <meta name="eult-csrf-field" content="{csrf_token()}"> pada layout.
 */
(function ($) {
    if (!$ || !$.ajaxPrefilter) {
        return;
    }

    var metaToken = function () {
        return $('meta[name="eult-csrf-header"]');
    };

    var headerName = function () {
        return metaToken().attr('data-header') || 'X-CSRF-TOKEN';
    };

    var fieldName = function () {
        return metaToken().attr('data-field') || '';
    };

    var currentToken = function () {
        return metaToken().attr('content') || '';
    };

    var setToken = function (token) {
        if (token) {
            metaToken().attr('content', token);
            $('input[type="hidden"][name="' + fieldName() + '"]').val(token);
        }
    };

    $.ajaxPrefilter(function (options, originalOptions, jqXHR) {
        var method = (options.type || options.method || 'GET').toUpperCase();
        if (method === 'GET' || method === 'HEAD' || options.crossDomain) {
            return;
        }

        var token = currentToken();
        if (!token) {
            return;
        }

        jqXHR.setRequestHeader(headerName(), token);

        jqXHR.always(function () {
            var baru = jqXHR.getResponseHeader(headerName());
            if (baru) {
                setToken(baru);
            }
        });
    });

    // Form yang dirender ulang via AJAX (modal/inline) membawa hidden input
    // csrf_field() dari server; sinkronkan meta agar token terbaru dipakai.
    $(document).on('DOMNodeInserted', 'form', function () {
        var input = $(this).find('input[type="hidden"][name="' + fieldName() + '"]').first();
        if (input.length && input.val()) {
            metaToken().attr('content', input.val());
        }
    });
})(window.jQuery);
