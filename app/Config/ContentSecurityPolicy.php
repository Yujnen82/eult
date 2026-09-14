<?php

namespace Config;

use CodeIgniter\Config\BaseConfig;

/**
 * Stores the default settings for the ContentSecurityPolicy, if you
 * choose to use it. The values here will be read in and set as defaults
 * for the site. If needed, they can be overridden on a page-by-page basis.
 *
 * Suggested reference for explanations:
 *
 * @see https://www.html5rocks.com/en/tutorials/security/content-security-policy/
 */
class ContentSecurityPolicy extends BaseConfig
{
    // -------------------------------------------------------------------------
    // Broadbrush CSP management
    // -------------------------------------------------------------------------

    /**
     * Default CSP report context
     */
    public bool $reportOnly = false;

    /**
     * Specifies a URL where a browser will send reports
     * when a content security policy is violated.
     */
    public ?string $reportURI = null;

    /**
     * Specifies a reporting endpoint to which violation reports ought to be sent.
     */
    public ?string $reportTo = null;

    /**
     * Instructs user agents to rewrite URL schemes, changing
     * HTTP to HTTPS. This directive is for websites with
     * large numbers of old URLs that need to be rewritten.
     */
    public bool $upgradeInsecureRequests = false;

    // -------------------------------------------------------------------------
    // CSP DIRECTIVES SETTINGS
    // NOTE: once you set a policy to 'none', it cannot be further restricted
    // -------------------------------------------------------------------------

    /**
     * Will default to `'self'` if not overridden
     *
     * @var list<string>|string|null
     */
    public $defaultSrc;

    /**
     * Lists allowed scripts' URLs.
     *
     * Opsi B (nonce first-party): seluruh `<script>` inline milik
     * aplikasi memakai placeholder `{csp-script-nonce}` (dikunci oleh
     * `CspFirstPartyNoncePreservationTest`), sehingga tetap diizinkan
     * meski header memuat `'nonce-…'` (mis. saat Debug Toolbar aktif).
     * `unsafe-inline` dipertahankan sebagai fallback untuk respons
     * tanpa placeholder. Seluruh `<script src="...">` first-party
     * adalah `base_url()`-prefixed (same-origin, tercover `self`).
     *
     * @var list<string>|string
     */
    public $scriptSrc = ['self', 'unsafe-inline'];

    /**
     * Specifies valid sources for JavaScript <script> elements.
     *
     * Directive TERPISAH dari `script-src` (tidak ada fallback otomatis
     * base ke elem) — disamakan agar `<script>` inline ber-nonce maupun
     * `src=` same-origin tetap diizinkan.
     *
     * @var list<string>|string
     */
    public array|string $scriptSrcElem = ['self', 'unsafe-inline'];

    /**
     * Specifies valid sources for JavaScript inline event
     * handlers and JavaScript URLs.
     *
     * Audit `login.php`/`detail_user.php`: TIDAK ada atribut
     * event-handler inline (`onclick=`, `onerror=`, dll) — seluruh
     * binding event dilakukan via jQuery `.on()` di dalam blok
     * `<script>` (tercover `scriptSrc`/`scriptSrcElem` di atas).
     * Dipertahankan default `self` (bukan `unsafe-inline`) karena tidak
     * ada penggunaan yang membutuhkannya — directive ini murni tidak
     * relevan untuk halaman ini, mempertahankan `self` tidak
     * memblokir apa pun yang benar-benar dipakai.
     *
     * @var list<string>|string
     */
    public array|string $scriptSrcAttr = 'self';

    /**
     * Lists allowed stylesheets' URLs.
     *
     * Opsi B: seluruh blok `<style>` inline memakai placeholder
     * `{csp-style-nonce}` (dikunci `CspFirstPartyNoncePreservationTest`);
     * `unsafe-inline` sebagai fallback. Satu `<link>` eksternal
     * (`https://fonts.googleapis.com`, Google Fonts CSS) adalah
     * satu-satunya domain CSS eksternal halaman publik; sisanya
     * `base_url()`-prefixed (tercover `self`).
     *
     * @var list<string>|string
     */
    public $styleSrc = ['self', 'unsafe-inline', 'https://fonts.googleapis.com'];

    /**
     * Specifies valid sources for stylesheets <link> elements.
     *
     * Directive HTTP header TERPISAH dari `styleSrc` (sama seperti
     * `scriptSrcElem` di atas) — disamakan agar `<link rel="stylesheet"
     * href="https://fonts.googleapis.com/...">` (elemen `<link>`,
     * relevan langsung untuk directive -elem ini) tidak diblokir.
     *
     * @var list<string>|string
     */
    public array|string $styleSrcElem = ['self', 'unsafe-inline', 'https://fonts.googleapis.com'];

    /**
     * Specifies valid sources for inline style attributes
     * (`style="…"` pada elemen HTML).
     *
     * Koreksi: elemen `<style>` diatur `style-src-elem`, BUKAN directive
     * ini. `unsafe-inline` dipertahankan karena atribut `style="…"`
     * masih dipakai luas di view (mis. `login.php`), dan CI4 TIDAK
     * menambahkan nonce ke directive ini (nonce style hanya ke
     * `style-src`/`style-src-elem`), sehingga `unsafe-inline` di sini
     * tetap dihormati browser.
     *
     * @var list<string>|string
     */
    public array|string $styleSrcAttr = ['self', 'unsafe-inline'];

    /**
     * Defines the origins from which images can be loaded.
     *
     * Audit: satu `background-image: url("data:image/svg+xml,...")`
     * (ikon search SVG inline pada Select2 dropdown, `login.php:951`)
     * di dalam blok `<style>` — WAJIB `data:` di imageSrc, jika tidak
     * ikon tersebut diblokir CSP (regresi visual, bukan error fatal).
     * Seluruh `<img src="...">` lain adalah `base_url()`-prefixed
     * (logo, favicon — tercover `self`).
     *
     * @var list<string>|string
     */
    public $imageSrc = ['self', 'data:'];

    /**
     * Restricts the URLs that can appear in a page's `<base>` element.
     *
     * Will default to self if not overridden
     *
     * @var list<string>|string|null
     */
    public $baseURI;

    /**
     * Lists the URLs for workers and embedded frame contents
     *
     * @var list<string>|string
     */
    public $childSrc = 'self';

    /**
     * Limits the origins that you can connect to (via XHR,
     * WebSockets, and EventSource).
     *
     * @var list<string>|string
     */
    public $connectSrc = 'self';

    /**
     * Specifies the origins that can serve web fonts.
     *
     * Diverifikasi via fetch langsung terhadap CSS
     * `https://fonts.googleapis.com/css?family=Poppins...` (task 21.1):
     * response benar-benar mereferensikan file font (`.ttf`, bukan
     * `.woff2`) dari domain `https://fonts.gstatic.com` — DUA domain
     * berbeda (googleapis untuk CSS, gstatic untuk file font itu
     * sendiri) WAJIB diwhitelist terpisah. Tanpa ini, directive
     * `font-src` yang sebelumnya `null` (tidak muncul di header) akan
     * fallback ke `default-src` (efektif `self` karena CI4 default-kan
     * `defaultSrc` kosong menjadi `'self'`), yang akan memblokir
     * pemuatan file font tersebut meski CSS-nya sendiri sudah
     * diizinkan via `styleSrcElem`. Opsi B aset admin: `data:`
     * ditambahkan karena icon font `fcicons` FullCalendar di-embed via
     * data: URI di `fullcalendar.bundle.css` first-party (tanpanya,
     * font tersebut diblokir `font-src`).
     *
     * @var list<string>|string
     */
    public $fontSrc = ['self', 'https://fonts.gstatic.com', 'data:'];

    /**
     * Lists valid endpoints for submission from `<form>` tags.
     *
     * @var list<string>|string
     */
    public $formAction = 'self';

    /**
     * Specifies the sources that can embed the current page.
     * This directive applies to `<frame>`, `<iframe>`, `<embed>`,
     * and `<applet>` tags. This directive can't be used in
     * `<meta>` tags and applies only to non-HTML resources.
     *
     * @var list<string>|string|null
     */
    public $frameAncestors;

    /**
     * The frame-src directive restricts the URLs which may
     * be loaded into nested browsing contexts.
     *
     * @var list<string>|string|null
     */
    public $frameSrc;

    /**
     * Restricts the origins allowed to deliver video and audio.
     *
     * @var list<string>|string|null
     */
    public $mediaSrc;

    /**
     * Allows control over Flash and other plugins.
     *
     * @var list<string>|string
     */
    public $objectSrc = 'self';

    /**
     * @var list<string>|string|null
     */
    public $manifestSrc;

    /**
     * @var list<string>|string
     */
    public array|string $workerSrc = [];

    /**
     * Limits the kinds of plugins a page may invoke.
     *
     * @var list<string>|string|null
     */
    public $pluginTypes;

    /**
     * List of actions allowed.
     *
     * @var list<string>|string|null
     */
    public $sandbox;

    /**
     * Nonce placeholder for style tags.
     */
    public string $styleNonceTag = '{csp-style-nonce}';

    /**
     * Nonce placeholder for script tags.
     */
    public string $scriptNonceTag = '{csp-script-nonce}';

    /**
     * Replace nonce tag automatically?
     */
    public bool $autoNonce = true;
}
