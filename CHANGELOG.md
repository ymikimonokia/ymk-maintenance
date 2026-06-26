# Changelog

## [1.0.1] — 2026-06-26

### Added
- Card en dashboards Child, OO y Extra via `ymk_overview_cards` hook
- Tab "Mantenimiento" en dashboards via `ymk_dashboard_sections` + `ymk_dashboard_render_tab`
- Toggle activable desde cualquier dashboard via `ymk_toggle_module_allowed`

## [1.0.0] — 2026-06-26

### Added
- Modo mantenimiento con HTTP 503 y contenido desde CPT `article`
- Redirección a URL externa alternativa
- Exclusión automática de bots, admins y `wp-login.php`
- Settings page: activar/desactivar, slug artículo, URL redirección
- Aviso visual en admin cuando el modo mantenimiento está activo
- License panel integrado en ymk-licenses hub
- Updater via ymk-github-updater cuando disponible, fallback standalone
