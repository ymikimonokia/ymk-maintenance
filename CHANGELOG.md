# Changelog

## [1.2.0] — 2026-06-26

### Added
- Estadísticas de bloqueos por grupo: visitantes, bots bloqueados, bots excluidos (SEO), roles por tipo, REST y XML-RPC
- Desglose de roles que pasaron (ej. cuántos shop_manager accedieron)
- Fechas de primer y último bloqueo registrado
- Totales de bloqueados y pasaron
- Botón reiniciar contadores con confirmación
- `includes/stats.php` — módulo de contadores con `get`, `increment` y `reset`

## [1.1.0] — 2026-06-26

### Added
- Roles excluidos configurables via checkboxes — `shop_manager` excluido por defecto (compatibilidad OrtoGest ERP)
- Bots excluidos configurables via checkboxes — lista ampliada con SemrushBot, AhrefsBot, MJ12bot
- Bloqueo opcional de REST API para usuarios no autenticados
- Bloqueo opcional de XML-RPC
- Controles disponibles en los tres dashboards (Child/OO/Extra) y en YMKs Rules

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
